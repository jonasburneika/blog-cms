<?php

namespace BlogCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPostFaq extends Model
{
    protected $fillable = [
        'blog_post_id',
        'question',
        'answer',
        'sort_order',
    ];

    public function blogPost(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class);
    }
}
