<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogCategory;
use BlogCms\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlogPostController extends Controller
{
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

    public function create()
    {
        $categories = BlogCategory::orderBy('title')->get();

        return view('blog::admin.posts.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'blog_category_id' => 'required|exists:blog_categories,id',
            'status' => 'required|in:0,1,2',
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
            'cover_desktop' => 'nullable|image|max:5120',
            'cover_mobile' => 'nullable|image|max:5120',
            'is_featured' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'howto_title' => 'nullable|string|max:255',
            'howto_description' => 'nullable|string',
            'faq_title' => 'nullable|string|max:255',
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

        DB::transaction(function () use ($validated, $request) {
            $postData = collect($validated)->except(['faqs', 'howto_steps', 'cover_desktop', 'cover_mobile'])->toArray();
            $postData['user_id'] = $request->user()->id;

            $post = BlogPost::create($postData);

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

    public function edit(BlogPost $post)
    {
        $categories = BlogCategory::orderBy('title')->get();

        return view('blog::admin.posts.edit', compact('post', 'categories'));
    }

    public function update(Request $request, BlogPost $post)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'blog_category_id' => 'required|exists:blog_categories,id',
            'status' => 'required|in:0,1,2',
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
            'cover_desktop' => 'nullable|image|max:5120',
            'cover_mobile' => 'nullable|image|max:5120',
            'is_featured' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'howto_title' => 'nullable|string|max:255',
            'howto_description' => 'nullable|string',
            'faq_title' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $post, $request) {
            $postData = collect($validated)->except(['cover_desktop', 'cover_mobile'])->toArray();
            $post->update($postData);

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
}
