<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogPost;
use BlogCms\Models\BlogPostFaq;
use Illuminate\Http\Request;

class BlogPostFaqController extends Controller
{
    public function index(BlogPost $post)
    {
        return response()->json($post->faqs()->orderBy('sort_order')->get());
    }

    public function store(Request $request, BlogPost $post)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? $post->faqs()->count();
        $faq = $post->faqs()->create($validated);

        return response()->json($faq, 201);
    }

    public function update(Request $request, BlogPost $post, BlogPostFaq $faq)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'sort_order' => 'nullable|integer',
        ]);

        $faq->update($validated);

        return response()->json($faq);
    }

    public function destroy(BlogPost $post, BlogPostFaq $faq)
    {
        $faq->delete();

        return response()->json(['message' => 'FAQ deleted']);
    }

    public function reorder(Request $request, BlogPost $post)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:blog_post_faqs,id',
        ]);

        foreach ($validated['ids'] as $order => $id) {
            BlogPostFaq::where('id', $id)->update(['sort_order' => $order]);
        }

        return response()->json(['message' => 'Reordered']);
    }
}
