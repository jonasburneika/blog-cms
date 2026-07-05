<?php

namespace BlogCms\Http\Controllers\Api;

use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogPost;
use BlogCms\Models\BlogPostFaq;
use Illuminate\Http\Request;

class BlogPostFaqController extends Controller
{
    public function index(BlogPost $post)
    {
        return response()->json(['data' => $post->faqs]);
    }

    public function store(Request $request, BlogPost $post)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'sort_order' => 'nullable|integer',
        ]);

        $faq = $post->faqs()->create($validated);

        return response()->json(['data' => $faq], 201);
    }

    public function show(BlogPost $post, BlogPostFaq $faq)
    {
        return response()->json(['data' => $faq]);
    }

    public function update(Request $request, BlogPost $post, BlogPostFaq $faq)
    {
        $validated = $request->validate([
            'question' => 'sometimes|required|string|max:500',
            'answer' => 'sometimes|required|string',
            'sort_order' => 'nullable|integer',
        ]);

        $faq->update($validated);

        return response()->json(['data' => $faq]);
    }

    public function destroy(BlogPost $post, BlogPostFaq $faq)
    {
        $faq->delete();

        return response()->json(['message' => 'FAQ deleted.']);
    }
}
