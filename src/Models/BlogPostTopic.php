<?php

namespace BlogCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A topical entity the post is "about" — rendered into the schema.org
 * Article "about" array as a Thing { name, sameAs }.
 */
class BlogPostTopic extends Model
{
    protected $fillable = [
        'blog_post_id',
        'name',
        'same_as',
        'type',
        'sort_order',
    ];

    public function blogPost(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class);
    }
}
