<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogPost;
use BlogCms\Models\BlogPostBasedOn;
use Illuminate\Http\Request;

class BlogPostBasedOnController extends Controller
{
    public function index(BlogPost $post)
    {
        return response()->json($post->basedOn()->orderBy('sort_order')->get());
    }

    public function store(Request $request, BlogPost $post)
    {
        $validated = $this->validateSource($request);

        $validated['sort_order'] = $validated['sort_order'] ?? $post->basedOn()->count();
        $source = $post->basedOn()->create($validated);

        return response()->json($source, 201);
    }

    public function update(Request $request, BlogPost $post, BlogPostBasedOn $basedOn)
    {
        $validated = $this->validateSource($request);

        $basedOn->update($validated);

        return response()->json($basedOn);
    }

    public function destroy(BlogPost $post, BlogPostBasedOn $basedOn)
    {
        $basedOn->delete();

        return response()->json(['message' => 'Source deleted']);
    }

    public function reorder(Request $request, BlogPost $post)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:blog_post_based_on,id',
        ]);

        foreach ($validated['ids'] as $order => $id) {
            BlogPostBasedOn::where('id', $id)->update(['sort_order' => $order]);
        }

        return response()->json(['message' => 'Reordered']);
    }

    protected function validateSource(Request $request): array
    {
        return $request->validate([
            'url' => 'required|url|max:2048',
            'sort_order' => 'nullable|integer',
        ]);
    }
}
