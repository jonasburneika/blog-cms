<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogPost;
use BlogCms\Models\BlogPostCitation;
use Illuminate\Http\Request;

class BlogPostCitationController extends Controller
{
    public function index(BlogPost $post)
    {
        return response()->json($post->citations()->orderBy('sort_order')->get());
    }

    public function store(Request $request, BlogPost $post)
    {
        $validated = $this->validateCitation($request);

        $validated['sort_order'] = $validated['sort_order'] ?? $post->citations()->count();
        $citation = $post->citations()->create($validated);

        return response()->json($citation, 201);
    }

    public function update(Request $request, BlogPost $post, BlogPostCitation $citation)
    {
        $validated = $this->validateCitation($request);

        $citation->update($validated);

        return response()->json($citation);
    }

    public function destroy(BlogPost $post, BlogPostCitation $citation)
    {
        $citation->delete();

        return response()->json(['message' => 'Citation deleted']);
    }

    public function reorder(Request $request, BlogPost $post)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:blog_post_citations,id',
        ]);

        foreach ($validated['ids'] as $order => $id) {
            BlogPostCitation::where('id', $id)->update(['sort_order' => $order]);
        }

        return response()->json(['message' => 'Reordered']);
    }

    protected function validateCitation(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:2048',
            'type' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
        ]);
    }
}
