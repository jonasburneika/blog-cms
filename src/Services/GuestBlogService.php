<?php

namespace BlogCms\Services;

use BlogCms\Events\GuestPostApproved;
use BlogCms\Events\GuestPostRejected;
use BlogCms\Events\GuestPostSubmitted;
use BlogCms\Mail\GuestPitchApprovedMail;
use BlogCms\Mail\GuestPitchRejectedMail;
use BlogCms\Models\BlogAuthorProfile;
use BlogCms\Models\BlogCategory;
use BlogCms\Models\GuestBlogRequest;
use BlogCms\Support\HtmlSanitizer;
use BlogCms\Support\LocaleManager;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * End-to-end guest blogging (flow B). approve() mints a submit token + notifies;
 * createDraftFromSubmission() turns the guest's article into a DRAFT post
 * attributed to a resolved/created guest author. Both are idempotent.
 */
class GuestBlogService
{
    /** Approve a pitch → mint a submit token, notify the guest. No-op if already approved. */
    public function approve(GuestBlogRequest $request, ?int $reviewerId = null): void
    {
        if ($request->isApproved()) {
            return;
        }

        $request->forceFill([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $reviewerId,
        ])->save();

        $request->ensureSubmitToken();

        GuestPostApproved::dispatch($request);
        $this->notify($request, fn () => new GuestPitchApprovedMail($request));
    }

    /** Reject a pitch → notify. Creates nothing. No-op if already rejected. */
    public function reject(GuestBlogRequest $request, ?int $reviewerId = null, ?string $notes = null): void
    {
        if ($request->isRejected()) {
            return;
        }

        $request->forceFill([
            'status' => 'rejected',
            'admin_notes' => $notes,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewerId,
        ])->save();

        GuestPostRejected::dispatch($request);
        $this->notify($request, fn () => new GuestPitchRejectedMail($request));
    }

    /**
     * Create a DRAFT post from the guest's submitted article, attributed to a
     * guest author. Idempotent: returns the existing post if one was created.
     */
    public function createDraftFromSubmission(GuestBlogRequest $request): ?Model
    {
        if ($request->created_post_id) {
            return $request->createdPost; // already created — no duplicate
        }

        $postClass = config('blog-cms.models.post');
        $locale = $request->locale ?: LocaleManager::default();

        return DB::transaction(function () use ($request, $postClass, $locale) {
            $authorId = $this->resolveGuestAuthor($request);

            $post = new $postClass([
                'blog_category_id' => $this->resolveCategoryId($request),
                'status' => (int) config('blog-cms.guest_blogging.default_status', $postClass::STATUS_DRAFT),
                'locale' => $locale,
            ]);
            $post->user_id = $authorId;
            $post->setTranslation('title', $locale, (string) $request->title);
            $post->setTranslation('content', $locale, HtmlSanitizer::clean($request->content));
            if ($request->excerpt) {
                $post->setTranslation('excerpt', $locale, (string) $request->excerpt);
            }
            $post->save(); // observers run: TOC, content-image ingest, srcset

            // Move the guest's uploaded cover onto the post.
            if ($request->hasMedia('cover')) {
                $request->getFirstMedia('cover')->copy($post, 'cover_desktop');
            }

            $request->forceFill([
                'created_post_id' => $post->getKey(),
                'created_user_id' => $authorId,
                'submitted_at' => now(),
            ])->save();

            GuestPostSubmitted::dispatch($request, (int) $post->getKey());

            return $post;
        });
    }

    /**
     * Resolve or create a locked guest-author user; returns its id.
     *
     * SECURITY: the pitch email is attacker-controlled and unverified, so we only
     * ever reuse an existing account that is ITSELF a guest author (a profile
     * flagged is_guest). A match on any other account — e.g. a staff/admin whose
     * email was guessed — is refused; the draft is attributed to the neutral
     * default author instead. This prevents authorship spoofing / borrowing a
     * credentialed author's identity and E-E-A-T.
     */
    protected function resolveGuestAuthor(GuestBlogRequest $request): ?int
    {
        $userModel = config('blog-cms.models.user');
        $user = $userModel::where('email', $request->email)->first();

        if ($user) {
            $profile = BlogAuthorProfile::where('user_id', $user->getKey())->first();
            if ($profile && $profile->is_guest) {
                return (int) $user->getKey(); // a returning guest — safe to reuse
            }

            Log::warning('[blog-cms] guest pitch email matches a non-guest account; attributing to the default author', [
                'email' => $request->email,
            ]);

            return (int) config('blog-cms.api.default_author_id', 1);
        }

        if (! config('blog-cms.guest_blogging.auto_create_author', true)) {
            return (int) config('blog-cms.api.default_author_id', 1);
        }

        try {
            $user = new $userModel();
            $user->forceFill([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make(Str::random(40)), // unguessable — effectively no login
            ])->save();
        } catch (\Throwable $e) {
            Log::warning('[blog-cms] guest author user creation failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return (int) config('blog-cms.api.default_author_id', 1);
        }

        $this->createGuestProfile($user, $request);

        return (int) $user->getKey();
    }

    /** Create the (non-indexable) guest BlogAuthorProfile, flagged is_guest. */
    protected function createGuestProfile(Model $user, GuestBlogRequest $request): void
    {
        if (BlogAuthorProfile::where('user_id', $user->getKey())->exists()) {
            return;
        }

        BlogAuthorProfile::create([
            'user_id' => $user->getKey(),
            'slug' => $this->uniqueAuthorSlug($request->name ?: 'guest-author'),
            'display_name' => $request->name,
            'role' => 'Guest Contributor',
            'is_indexable' => false, // thin profile stays noindex until fleshed out
            'is_guest' => true,      // only these are ever reused by the guest flow
        ]);
    }

    /** Map the pitch's category preference to a category id, with fallbacks. */
    protected function resolveCategoryId(GuestBlogRequest $request): ?int
    {
        $preference = trim((string) $request->category_preference);
        if ($preference !== '') {
            $locale = LocaleManager::default();
            $category = BlogCategory::query()
                ->where("title->{$locale}", $preference)
                ->orWhere("slug->{$locale}", Str::slug($preference))
                ->first();
            if ($category) {
                return (int) $category->id;
            }
        }

        if ($id = config('blog-cms.guest_blogging.default_category_id')) {
            return (int) $id;
        }

        return optional(BlogCategory::orderBy('sort_order')->first())->id;
    }

    protected function uniqueAuthorSlug(string $base): string
    {
        $slug = Str::slug($base) ?: 'guest-author';
        $candidate = $slug;
        $i = 1;
        while (BlogAuthorProfile::where('slug', $candidate)->exists()) {
            $candidate = $slug.'-'.(++$i);
        }

        return $candidate;
    }

    /** Send a config-gated mailable to the guest; failures are logged, never thrown. */
    protected function notify(GuestBlogRequest $request, Closure $mailable): void
    {
        if (! config('blog-cms.guest_blogging.notify', true)) {
            return;
        }
        if (! filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mail::to($request->email)->send($mailable());
        } catch (\Throwable $e) {
            Log::warning('[blog-cms] guest notification failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
