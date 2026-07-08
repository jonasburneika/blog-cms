<?php

namespace BlogCms\Http\Controllers;

use BlogCms\Http\Requests\GuestArticleRequest;
use BlogCms\Http\Requests\GuestPitchRequest;
use BlogCms\Models\GuestBlogRequest;
use BlogCms\Services\GuestBlogService;
use BlogCms\Support\LocaleManager;
use Illuminate\Http\Request;

/**
 * Public, guest-facing endpoints for two-step guest blogging. No auth: a guest
 * pitches, an editor approves (emailing a tokenized link), then the guest
 * submits the full article here, which creates a draft post. A honeypot field
 * (`website`, must stay empty) + route throttling guard against bots/spam.
 */
class GuestBlogController extends Controller
{
    public function __construct(protected GuestBlogService $service) {}

    /** Step 1: the pitch form. */
    public function pitchForm()
    {
        return view('blog::guest-blog.pitch');
    }

    /** Step 1: store the pitch (status: pending). */
    public function submitPitch(GuestPitchRequest $request)
    {
        if ($this->isBot($request)) {
            return view('blog::guest-blog.thanks', ['stage' => 'pitch']); // silently drop
        }

        GuestBlogRequest::create(array_merge(
            $request->safe()->only([
                'name', 'email', 'phone', 'proposed_topic', 'category_preference', 'message', 'sample_url',
            ]),
            ['status' => 'pending'],
        ));

        return view('blog::guest-blog.thanks', ['stage' => 'pitch']);
    }

    /** Step 2: the article form, reached via the emailed token. */
    public function articleForm(string $token)
    {
        $guest = $this->resolveByToken($token);

        if ($guest->isSubmitted()) {
            return view('blog::guest-blog.thanks', ['stage' => 'already']);
        }

        return view('blog::guest-blog.submit', ['guest' => $guest, 'token' => $token]);
    }

    /** Step 2: store the article → create the draft post (idempotent). */
    public function submitArticle(GuestArticleRequest $request, string $token)
    {
        $guest = $this->resolveByToken($token);

        if ($this->isBot($request)) {
            return view('blog::guest-blog.thanks', ['stage' => 'article']);
        }

        if (! $guest->isSubmitted()) {
            $guest->forceFill([
                'title' => (string) $request->input('title'),
                'content' => (string) $request->input('content'),
                'excerpt' => $request->input('excerpt'),
                'locale' => LocaleManager::default(),
            ])->save();

            if ($request->hasFile('cover')) {
                $guest->clearMediaCollection('cover');
                $guest->addMediaFromRequest('cover')->toMediaCollection('cover');
            }

            $this->service->createDraftFromSubmission($guest);
        }

        return view('blog::guest-blog.thanks', ['stage' => 'article']);
    }

    /** Find an approved, still-open request by token, or 404. */
    protected function resolveByToken(string $token): GuestBlogRequest
    {
        return GuestBlogRequest::where('submit_token', $token)
            ->where('status', 'approved')
            ->firstOrFail();
    }

    /** Honeypot: real users leave the hidden `website` field empty. */
    protected function isBot(Request $request): bool
    {
        return filled($request->input('website'));
    }
}
