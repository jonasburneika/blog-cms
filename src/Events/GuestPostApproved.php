<?php

namespace BlogCms\Events;

use BlogCms\Models\GuestBlogRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** A guest pitch was approved; the guest can now submit the full article. */
class GuestPostApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(public GuestBlogRequest $request) {}
}
