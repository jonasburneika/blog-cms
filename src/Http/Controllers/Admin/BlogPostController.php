<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Admin\Concerns\EditsTranslations;
use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogAuthorProfile;
use BlogCms\Models\BlogCategory;
use BlogCms\Models\BlogPost;
use BlogCms\Support\LocaleManager;
use BlogCms\Support\PageUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BlogPostController extends Controller
{
    use EditsTranslations;

    public function index(Request $request)
    {
        $posts = BlogPost::with(['category', 'author'])
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->when($request->category, function ($query, $categoryId) {
                $query->where('blog_category_id', $categoryId);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $categories = BlogCategory::orderBy('title')->get();

        return view('blog::admin.posts.index', compact('posts', 'categories'));
    }

    public function create(Request $request)
    {
        $categories = BlogCategory::orderBy('title')->get();

        return view('blog::admin.posts.create', [
            'categories' => $categories,
            'reviewers' => $this->reviewers(),
            'locale' => $this->editingLocale($request),
        ]);
    }

    /** Author profiles eligible to be an editorial reviewer (reviewedBy). */
    protected function reviewers()
    {
        return BlogAuthorProfile::with('user')->orderBy('slug')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'blog_category_id' => 'required|exists:blog_categories,id',
            'reviewed_by' => ['nullable', Rule::exists('blog_author_profiles', 'user_id')],
            'status' => 'required|in:0,1,2',
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
            'cover_desktop' => 'nullable|image|max:5120',
            'cover_mobile' => 'nullable|image|max:5120',
            'is_featured' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'og_title' => 'nullable|string|max:255',
            'twitter_title' => 'nullable|string|max:255',
            'howto_title' => 'nullable|string|max:255',
            'howto_description' => 'nullable|string',
            'faq_title' => 'nullable|string|max:255',
            'toc_enabled' => 'nullable|boolean',
            'locale' => 'nullable|string',
            'canonical' => 'nullable|string',
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

        $locale = $this->editingLocale($request);

        DB::transaction(function () use ($validated, $request, $locale) {
            $post = new BlogPost(array_merge([
                'blog_category_id' => $validated['blog_category_id'],
                'reviewed_by' => $validated['reviewed_by'] ?? null,
                'status' => $validated['status'],
                'is_featured' => $request->boolean('is_featured'),
                'published_at' => $validated['published_at'] ?? null,
                'toc_enabled' => $request->boolean('toc_enabled'),
                'locale' => $locale,
            ], $this->canonicalColumns($request)));
            $post->user_id = $request->user()->id;
            $this->fillTranslations($post, $request, $locale);
            $post->save();

            if ($request->hasFile('cover_desktop')) {
                $post->addMediaFromRequest('cover_desktop')->toMediaCollection('cover_desktop');
            }
            if ($request->hasFile('cover_mobile')) {
                $post->addMediaFromRequest('cover_mobile')->toMediaCollection('cover_mobile');
            }

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
        });

        return redirect()->route('admin.posts.index')
            ->with('success', 'Post created successfully.');
    }

    public function edit(Request $request, BlogPost $post)
    {
        $categories = BlogCategory::orderBy('title')->get();

        return view('blog::admin.posts.edit', [
            'post' => $post,
            'categories' => $categories,
            'reviewers' => $this->reviewers(),
            'locale' => $this->editingLocale($request),
        ]);
    }

    public function update(Request $request, BlogPost $post)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'blog_category_id' => 'required|exists:blog_categories,id',
            'reviewed_by' => ['nullable', Rule::exists('blog_author_profiles', 'user_id')],
            'status' => 'required|in:0,1,2',
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
            'cover_desktop' => 'nullable|image|max:5120',
            'cover_mobile' => 'nullable|image|max:5120',
            'is_featured' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'og_title' => 'nullable|string|max:255',
            'twitter_title' => 'nullable|string|max:255',
            'howto_title' => 'nullable|string|max:255',
            'howto_description' => 'nullable|string',
            'faq_title' => 'nullable|string|max:255',
            'toc_enabled' => 'nullable|boolean',
            'locale' => 'nullable|string',
            'canonical' => 'nullable|string',
        ]);

        $locale = $this->editingLocale($request);

        DB::transaction(function () use ($validated, $post, $request, $locale) {
            $post->fill(array_merge([
                'blog_category_id' => $validated['blog_category_id'],
                'reviewed_by' => $validated['reviewed_by'] ?? null,
                'status' => $validated['status'],
                'is_featured' => $request->boolean('is_featured'),
                'published_at' => $validated['published_at'] ?? $post->published_at,
                'toc_enabled' => $request->boolean('toc_enabled'),
            ], $this->canonicalColumns($request)));
            $this->fillTranslations($post, $request, $locale);
            $post->save();

            if ($request->hasFile('cover_desktop')) {
                $post->clearMediaCollection('cover_desktop');
                $post->addMediaFromRequest('cover_desktop')->toMediaCollection('cover_desktop');
            }
            if ($request->hasFile('cover_mobile')) {
                $post->clearMediaCollection('cover_mobile');
                $post->addMediaFromRequest('cover_mobile')->toMediaCollection('cover_mobile');
            }
        });

        return redirect()->route('admin.posts.index')
            ->with('success', 'Post updated successfully.');
    }

    public function destroy(BlogPost $post)
    {
        $post->delete();

        return redirect()->route('admin.posts.index')
            ->with('success', 'Post deleted successfully.');
    }

    /** Resolve the canonical "type:id" picker value into columns (null = self). */
    protected function canonicalColumns(Request $request): array
    {
        $canonical = PageUrl::parse($request->input('canonical'));

        return [
            'canonical_type' => $canonical['type'] ?? null,
            'canonical_id' => $canonical['id'] ?? null,
        ];
    }
}
