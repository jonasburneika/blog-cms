<?php

namespace BlogCms\Http\Controllers\Api;

use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogPost;
use BlogCms\Models\BlogPostHowToStep;
use Illuminate\Http\Request;

class BlogPostHowToStepController extends Controller
{
    public function index(BlogPost $post)
    {
        return response()->json(['data' => $post->howToSteps]);
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

        $step = $post->howToSteps()->create($validated);

        return response()->json(['data' => $step], 201);
    }

    public function show(BlogPost $post, BlogPostHowToStep $howtoStep)
    {
        return response()->json(['data' => $howtoStep]);
    }

    public function update(Request $request, BlogPost $post, BlogPostHowToStep $howtoStep)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:500',
            'content' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'tip' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $howtoStep->update($validated);

        return response()->json(['data' => $howtoStep]);
    }

    public function destroy(BlogPost $post, BlogPostHowToStep $howtoStep)
    {
        $howtoStep->delete();

        return response()->json(['message' => 'HowTo step deleted.']);
    }
}
