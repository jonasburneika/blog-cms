<?php

namespace BlogCms\Http\Controllers\Api;

use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogCategory;
use Illuminate\Http\Request;

class BlogCategoryController extends Controller
{
    public function index()
    {
        $categories = BlogCategory::orderBy('sort_order')
            ->get(['id', 'title', 'slug', 'menu_title', 'description', 'meta_title', 'status', 'sort_order']);

        return response()->json(['data' => $categories]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_categories,slug',
            'menu_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'sort_order' => 'nullable|integer',
        ]);

        $category = BlogCategory::create($validated);

        return response()->json(['data' => $category], 201);
    }

    public function update(Request $request, BlogCategory $category)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_categories,slug,' . $category->id,
            'menu_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'sort_order' => 'nullable|integer',
        ]);

        $category->update($validated);

        return response()->json(['data' => $category]);
    }
}
