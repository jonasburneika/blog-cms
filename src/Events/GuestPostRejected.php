<?php

namespace BlogCms\Events;

use BlogCms\Models\GuestBlogRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** A guest pitch was rejected. Nothing is created. */
class GuestPostRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(public GuestBlogRequest $request) {}
}
