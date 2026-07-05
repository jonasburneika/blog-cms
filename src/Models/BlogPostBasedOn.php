<?php

namespace BlogCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A source URL the post is based on — rendered into the schema.org Article
 * "isBasedOn" array as a plain URL string.
 */
class BlogPostBasedOn extends Model
{
    protected $table = 'blog_post_based_on';

    protected $fillable = [
        'blog_post_id',
        'url',
        'sort_order',
    ];

    public function blogPost(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class);
    }
}
