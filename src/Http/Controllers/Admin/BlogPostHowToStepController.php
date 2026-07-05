<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogPost;
use BlogCms\Models\BlogPostHowToStep;
use Illuminate\Http\Request;

class BlogPostHowToStepController extends Controller
{
    public function index(BlogPost $post)
    {
        return response()->json($post->howToSteps()->orderBy('sort_order')->get());
    }

    public function store(Request $request, BlogPost $post)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'content' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'tip' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? $post->howToSteps()->count();
        $step = $post->howToSteps()->create($validated);

        return response()->json($step, 201);
    }

    public function update(Request $request, BlogPost $post, BlogPostHowToStep $step)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'content' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'tip' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $step->update($validated);

        return response()->json($step);
    }

    public function destroy(BlogPost $post, BlogPostHowToStep $step)
    {
        $step->delete();

        return response()->json(['message' => 'HowTo step deleted']);
    }

    public function reorder(Request $request, BlogPost $post)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:blog_post_how_to_steps,id',
        ]);

        foreach ($validated['ids'] as $order => $id) {
            BlogPostHowToStep::where('id', $id)->update(['sort_order' => $order]);
        }

        return response()->json(['message' => 'Reordered']);
    }
}
