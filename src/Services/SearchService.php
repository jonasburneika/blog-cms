<?php

namespace BlogCms\Services;

use BlogCms\Models\BlogPostTopic;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Site-wide content search. A post is a hit when the term appears in the post
 * itself (title/excerpt/content/meta, in the active locale) OR in any of its
 * related content — FAQs, how-to steps, topics ("tags"/"about"), citations, or
 * "based on" sources. Categories and tags (post topics) are searched too.
 *
 * Translatable fields (post/category) are matched against the given locale's
 * JSON value; child rows are plain columns.
 */
class SearchService
{
    /** Everything in one call: paginated posts + matching categories + tags. */
    public function search(string $term, ?string $locale = null, ?int $perPage = null): array
    {
        return [
            'term' => $term,
            'posts' => $this->posts($term, $locale, $perPage),
            'categories' => $this->categories($term, $locale),
            'tags' => $this->tags($term),
        ];
    }

    /** Readable posts matching the term in their own or any related content. */
    public function posts(string $term, ?string $locale = null, ?int $perPage = null): LengthAwarePaginator
    {
        $locale = $locale ?: app()->getLocale();
        $perPage = $perPage ?: (int) config('blog-cms.search.per_page', 12);
        $like = $this->like($term);
        $postClass = config('blog-cms.models.post');

        return $postClass::query()
            ->readable()
            ->with(['category', 'author'])
            ->where(function (Builder $q) use ($like, $locale) {
                $q->where("title->{$locale}", 'like', $like)
                    ->orWhere("excerpt->{$locale}", 'like', $like)
                    ->orWhere("content->{$locale}", 'like', $like)
                    ->orWhere("meta_title->{$locale}", 'like', $like)
                    ->orWhere("meta_description->{$locale}", 'like', $like)
                    ->orWhereHas('faqs', fn (Builder $f) => $f
                        ->where('question', 'like', $like)->orWhere('answer', 'like', $like))
                    ->orWhereHas('howToSteps', fn (Builder $h) => $h
                        ->where('title', 'like', $like)->orWhere('content', 'like', $like)->orWhere('tip', 'like', $like))
                    ->orWhereHas('topics', fn (Builder $t) => $t->where('name', 'like', $like))
                    ->orWhereHas('citations', fn (Builder $c) => $c->where('name', 'like', $like))
                    ->orWhereHas('basedOn', fn (Builder $b) => $b->where('url', 'like', $like));
            })
            ->orderByDesc('published_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Active categories whose translatable text matches the term. */
    public function categories(string $term, ?string $locale = null): Collection
    {
        $locale = $locale ?: app()->getLocale();
        $like = $this->like($term);
        $categoryClass = config('blog-cms.models.category');

        return $categoryClass::query()
            ->where('status', 'active')
            ->where(function (Builder $q) use ($like, $locale) {
                $q->where("title->{$locale}", 'like', $like)
                    ->orWhere("menu_title->{$locale}", 'like', $like)
                    ->orWhere("description->{$locale}", 'like', $like)
                    ->orWhere("meta_title->{$locale}", 'like', $like);
            })
            ->orderBy('sort_order')
            ->limit(20)
            ->get();
    }

    /** Tags (post topics) matching the term, ranked by how many posts use them. */
    public function tags(string $term, int $limit = 20): Collection
    {
        return $this->topicQuery()
            ->where('name', 'like', $this->like($term))
            ->limit($limit)
            ->get();
    }

    /** The most-used tags (post topics) across readable posts. */
    public function topTags(?int $limit = null): Collection
    {
        $limit = $limit ?: (int) config('blog-cms.tags.top_count', 5);

        return $this->topicQuery()->limit($limit)->get();
    }

    /** Base query: topic names grouped + counted over readable posts only. */
    protected function topicQuery(): Builder
    {
        return BlogPostTopic::query()
            ->select('name', DB::raw('COUNT(DISTINCT blog_post_id) as posts_count'))
            ->whereHas('blogPost', fn (Builder $p) => $p->readable())
            ->groupBy('name')
            ->orderByDesc('posts_count');
    }

    /** Build a LIKE pattern, escaping the term's own %/_ wildcards. */
    protected function like(string $term): string
    {
        $escaped = addcslashes(trim($term), '%_\\');

        return "%{$escaped}%";
    }
}
