<?php

namespace BlogCms\Events;

use BlogCms\Models\GuestBlogRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** A guest submitted their full article and a draft post was created. */
class GuestPostSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public GuestBlogRequest $request,
        public int $postId,
    ) {}
}
