<?php

namespace BlogCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPostHowToStep extends Model
{
    protected $fillable = [
        'blog_post_id',
        'title',
        'content',
        'image',
        'tip',
        'sort_order',
    ];

    public function blogPost(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class);
    }
}
