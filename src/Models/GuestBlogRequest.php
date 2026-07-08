<?php

namespace BlogCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class GuestBlogRequest extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'proposed_topic',
        'category_preference',
        'message',
        'sample_url',
        'title',
        'content',
        'excerpt',
        'locale',
        'status',
        'admin_notes',
        'submit_token',
        'submitted_at',
        'created_post_id',
        'created_user_id',
        'reviewed_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(config('blog-cms.models.user'), 'reviewed_by');
    }

    /** The draft post created from the submitted article (audit / idempotency). */
    public function createdPost(): BelongsTo
    {
        return $this->belongsTo(config('blog-cms.models.post'), 'created_post_id');
    }

    /** The guest-author user created/resolved on submission. */
    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(config('blog-cms.models.user'), 'created_user_id');
    }

    // ─── State ───────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /** Approved and still awaiting the guest's article submission. */
    public function awaitingSubmission(): bool
    {
        return $this->isApproved() && $this->submitted_at === null;
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function hasDraft(): bool
    {
        return $this->created_post_id !== null;
    }

    // ─── Submission token (flow B) ───────────────────────────────

    /** Ensure a unique submit token exists; returns it. */
    public function ensureSubmitToken(): string
    {
        if (! $this->submit_token) {
            $this->submit_token = Str::random(48);
            $this->save();
        }

        return $this->submit_token;
    }

    /** Public URL where the guest submits their article. */
    public function submitUrl(): ?string
    {
        if (! $this->submit_token) {
            return null;
        }

        $prefix = trim((string) config('blog-cms.guest_blogging.route_prefix', 'guest-blog'), '/');

        return url($prefix.'/submit/'.$this->submit_token);
    }

    public function coverUrl(string $conversion = ''): ?string
    {
        return $this->getFirstMediaUrl('cover', $conversion) ?: null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
    }
}
