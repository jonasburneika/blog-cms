<?php

namespace BlogCms\Http\Controllers\Api;

use BlogCms\Http\Controllers\Api\Concerns\HandlesApiLocale;
use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlogCategoryController extends Controller
{
    use HandlesApiLocale;

    public function index(Request $request)
    {
        $locale = $this->apiLocale($request);

        $categories = BlogCategory::orderBy('sort_order')->get()
            ->map(fn ($c) => $this->presentInLocale($c, $locale));

        return response()->json(['data' => $categories]);
    }

    public function store(Request $request)
    {
        $locale = $this->apiLocale($request);
        app()->setLocale($locale); // scalar fields land under the requested locale

        $validated = $request->validate([
            'parent_id' => ['nullable', Rule::exists('blog_categories', 'id')],
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'menu_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'sort_order' => 'nullable|integer',
        ]);

        $category = BlogCategory::create($validated);

        return response()->json(['data' => $this->presentInLocale($category, $locale)], 201);
    }

    public function update(Request $request, BlogCategory $category)
    {
        $locale = $this->apiLocale($request);
        app()->setLocale($locale); // merge only the requested locale

        $validated = $request->validate([
            'parent_id' => [
                'nullable',
                Rule::exists('blog_categories', 'id'),
                Rule::notIn($category->subtreeIds()), // no self/descendant → no cycles
            ],
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'menu_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'sort_order' => 'nullable|integer',
        ]);

        $category->update($validated);

        return response()->json(['data' => $this->presentInLocale($category, $locale)]);
    }
}
