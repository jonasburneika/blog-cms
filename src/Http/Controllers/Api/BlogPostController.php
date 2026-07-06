<?php

namespace BlogCms\Http\Controllers\Api;

use BlogCms\Http\Controllers\Api\Concerns\HandlesApiLocale;
use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogCategory;
use BlogCms\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlogPostController extends Controller
{
    use HandlesApiLocale;

    public function index(Request $request)
    {
        $locale = $this->apiLocale($request);

        $posts = BlogPost::with(['category:id,title,slug', 'faqs', 'howToSteps'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->category_id, fn ($q, $id) => $q->where('blog_category_id', $id))
            ->when($request->category, function ($q, $category) use ($locale) {
                $q->whereHas('category', fn ($cq) => $cq->where("slug->{$locale}", $category)->orWhere("title->{$locale}", $category));
            })
            // Only posts that actually have a translation in the requested locale.
            ->when($request->locale, fn ($q, $l) => $q->whereNotNull("title->{$l}"))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        $posts->getCollection()->transform(function ($post) use ($locale) {
            return array_merge($this->presentInLocale($post, $locale), [
                'cover_desktop_url' => $post->getFirstMediaUrl('cover_desktop'),
                'cover_mobile_url' => $post->getFirstMediaUrl('cover_mobile'),
            ]);
        });

        return response()->json($posts);
    }

    public function show(Request $request, BlogPost $post)
    {
        $locale = $this->apiLocale($request);
        $post->load(['category:id,title,slug', 'faqs', 'howToSteps', 'media']);

        return response()->json([
            'data' => array_merge($this->presentInLocale($post, $locale), [
                'cover_desktop_url' => $post->getFirstMediaUrl('cover_desktop'),
                'cover_mobile_url' => $post->getFirstMediaUrl('cover_mobile'),
            ]),
            'url' => $this->presentInLocale($post, $locale)['url'],
        ]);
    }

    public function store(Request $request)
    {
        $locale = $this->apiLocale($request);
        $request->merge(['locale' => $locale]);
        $this->resolveCategory($request, $locale);

        // Scalar translatable fields are written under the requested locale.
        app()->setLocale($locale);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'blog_category_id' => 'required|exists:blog_categories,id',
            'status' => 'required|in:0,1,2',
            'locale' => 'nullable|string|max:10',
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'howto_title' => 'nullable|string|max:255',
            'howto_description' => 'nullable|string',
            'faq_title' => 'nullable|string|max:255',
            'toc_enabled' => 'nullable|boolean',
            'faqs' => 'nullable|array',
            'faqs.*.question' => 'required_with:faqs|string|max:500',
            'faqs.*.answer' => 'required_with:faqs|string',
            'faqs.*.sort_order' => 'nullable|integer',
            'howto_steps' => 'nullable|array',
            'howto_steps.*.title' => 'required_with:howto_steps|string|max:500',
            'howto_steps.*.content' => 'nullable|string',
            'howto_steps.*.image' => 'nullable|string|max:500',
            'howto_steps.*.tip' => 'nullable|string',
            'howto_steps.*.sort_order' => 'nullable|integer',
            'citations' => 'nullable|array',
            'citations.*.name' => 'required_with:citations|string|max:255',
            'citations.*.url' => 'required_with:citations|url|max:2048',
            'citations.*.type' => 'nullable|string|max:100',
            'citations.*.sort_order' => 'nullable|integer',
            'topics' => 'nullable|array',
            'topics.*.name' => 'required_with:topics|string|max:255',
            'topics.*.same_as' => 'nullable|url|max:2048',
            'topics.*.type' => 'nullable|string|max:100',
            'topics.*.sort_order' => 'nullable|integer',
            'based_on' => 'nullable|array',
            'based_on.*' => 'required_with:based_on|url|max:2048',
        ]);

        $post = DB::transaction(function () use ($validated) {
            $postData = collect($validated)->except(['faqs', 'howto_steps', 'citations', 'topics', 'based_on'])->toArray();
            $postData['user_id'] = config('blog-cms.api.default_author_id');

            $post = BlogPost::create($postData);

            if (! empty($validated['faqs'])) {
                foreach ($validated['faqs'] as $faq) {
                    $post->faqs()->create($faq);
                }
            }

            if (! empty($validated['howto_steps'])) {
                foreach ($validated['howto_steps'] as $step) {
                    $post->howToSteps()->create($step);
                }
            }

            if (! empty($validated['citations'])) {
                foreach ($validated['citations'] as $citation) {
                    $post->citations()->create($citation);
                }
            }

            if (! empty($validated['topics'])) {
                foreach ($validated['topics'] as $topic) {
                    $post->topics()->create($topic);
                }
            }

            if (! empty($validated['based_on'])) {
                foreach ($validated['based_on'] as $url) {
                    $post->basedOn()->create(['url' => $url]);
                }
            }

            return $post;
        });

        $post->load(['category:id,title,slug', 'faqs', 'howToSteps', 'citations', 'topics', 'basedOn']);

        $presented = $this->presentInLocale($post, $locale);

        return response()->json([
            'data' => $presented,
            'url' => $presented['url'],
        ], 201);
    }

    public function update(Request $request, BlogPost $post)
    {
        $locale = $this->apiLocale($request);
        $this->resolveCategory($request, $locale);

        // Merge only the requested locale's translations, preserving the others.
        app()->setLocale($locale);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'blog_category_id' => 'sometimes|required|exists:blog_categories,id',
            'status' => 'sometimes|required|in:0,1,2',
            'locale' => 'nullable|string|max:10',
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'howto_title' => 'nullable|string|max:255',
            'howto_description' => 'nullable|string',
            'faq_title' => 'nullable|string|max:255',
            'toc_enabled' => 'nullable|boolean',
            'faqs' => 'nullable|array',
            'faqs.*.question' => 'required_with:faqs|string|max:500',
            'faqs.*.answer' => 'required_with:faqs|string',
            'faqs.*.sort_order' => 'nullable|integer',
            'howto_steps' => 'nullable|array',
            'howto_steps.*.title' => 'required_with:howto_steps|string|max:500',
            'howto_steps.*.content' => 'nullable|string',
            'howto_steps.*.image' => 'nullable|string|max:500',
            'howto_steps.*.tip' => 'nullable|string',
            'howto_steps.*.sort_order' => 'nullable|integer',
        ]);

        DB::transaction(function () use ($validated, $post) {
            $postData = collect($validated)->except(['faqs', 'howto_steps'])->toArray();
            $post->update($postData);

            if (array_key_exists('faqs', $validated)) {
                $post->faqs()->delete();
                foreach ($validated['faqs'] ?? [] as $faq) {
                    $post->faqs()->create($faq);
                }
            }

            if (array_key_exists('howto_steps', $validated)) {
                $post->howToSteps()->delete();
                foreach ($validated['howto_steps'] ?? [] as $step) {
                    $post->howToSteps()->create($step);
                }
            }
        });

        $post->load(['category:id,title,slug', 'faqs', 'howToSteps']);

        $presented = $this->presentInLocale($post, $locale);

        return response()->json([
            'data' => $presented,
            'url' => $presented['url'],
        ]);
    }

    public function destroy(BlogPost $post)
    {
        $post->delete();

        return response()->json(['message' => 'Post deleted.']);
    }

    /**
     * If "category" is sent (title or slug), resolve it to blog_category_id in
     * the given locale (falling back to the default locale).
     */
    private function resolveCategory(Request $request, string $locale): void
    {
        if ($request->filled('category') && ! $request->filled('blog_category_id')) {
            if ($category = $this->findCategoryByRef($request->category, $locale)) {
                $request->merge(['blog_category_id' => $category->id]);
            }
        }
    }
}
