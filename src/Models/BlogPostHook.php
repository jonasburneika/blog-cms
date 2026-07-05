<?php

namespace BlogCms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPostHook extends Model
{
    protected $fillable = [
        'blog_post_id',
        'hook_template_id',
        'name',
        'position',
        'html_content',
        'config',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'config' => 'array',
        ];
    }

    public function blogPost(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class);
    }

    public function hookTemplate(): BelongsTo
    {
        return $this->belongsTo(HookTemplate::class);
    }

    public function getRenderedHtml(): string
    {
        if (! empty($this->html_content)) {
            return $this->html_content;
        }

        if ($this->hookTemplate) {
            return $this->hookTemplate->html_content;
        }

        return '';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
