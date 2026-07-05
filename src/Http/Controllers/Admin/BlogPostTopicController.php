<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogPost;
use BlogCms\Models\BlogPostTopic;
use Illuminate\Http\Request;

class BlogPostTopicController extends Controller
{
    public function index(BlogPost $post)
    {
        return response()->json($post->topics()->orderBy('sort_order')->get());
    }

    public function store(Request $request, BlogPost $post)
    {
        $validated = $this->validateTopic($request);

        $validated['sort_order'] = $validated['sort_order'] ?? $post->topics()->count();
        $topic = $post->topics()->create($validated);

        return response()->json($topic, 201);
    }

    public function update(Request $request, BlogPost $post, BlogPostTopic $topic)
    {
        $validated = $this->validateTopic($request);

        $topic->update($validated);

        return response()->json($topic);
    }

    public function destroy(BlogPost $post, BlogPostTopic $topic)
    {
        $topic->delete();

        return response()->json(['message' => 'Topic deleted']);
    }

    public function reorder(Request $request, BlogPost $post)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:blog_post_topics,id',
        ]);

        foreach ($validated['ids'] as $order => $id) {
            BlogPostTopic::where('id', $id)->update(['sort_order' => $order]);
        }

        return response()->json(['message' => 'Reordered']);
    }

    protected function validateTopic(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'same_as' => 'nullable|url|max:2048',
            'type' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
        ]);
    }
}
