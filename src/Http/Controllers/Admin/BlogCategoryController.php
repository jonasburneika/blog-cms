<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Admin\Concerns\EditsTranslations;
use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogCategory;
use BlogCms\Services\AnalyticsService;
use BlogCms\Support\PageUrl;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlogCategoryController extends Controller
{
    use EditsTranslations;

    public function index(Request $request)
    {
        $categories = BlogCategory::withCount('posts')
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->orderBy('sort_order')
            ->paginate(15)
            ->withQueryString();

        return view('blog::admin.categories.index', compact('categories'));
    }

    public function show(BlogCategory $category, AnalyticsService $analytics)
    {
        $category->loadCount('posts');
        $posts = $category->posts()
            ->with('author')
            ->latest()
            ->paginate(15);

        $ga4Data = $analytics->getPageViews('/' . $category->slug);
        $gscData = $analytics->getSearchQueries('/' . $category->slug);

        return view('blog::admin.categories.show', compact('category', 'posts', 'ga4Data', 'gscData'));
    }

    public function create(Request $request)
    {
        return view('blog::admin.categories.create', [
            'locale' => $this->editingLocale($request),
            'parentOptions' => BlogCategory::parentOptionsFor(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', Rule::exists('blog_categories', 'id')],
            'title' => 'required|string|max:255',
            'menu_title' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'og_title' => 'nullable|string|max:255',
            'twitter_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string',
            'sort_order' => 'nullable|integer',
            'locale' => 'nullable|string',
            'canonical' => 'nullable|string',
            'cover_desktop' => 'nullable|image|max:5120',
            'cover_mobile' => 'nullable|image|max:5120',
        ]);

        $locale = $this->editingLocale($request);

        $category = new BlogCategory(array_merge(
            [
                'parent_id' => $validated['parent_id'] ?? null,
                'status' => $validated['status'],
                'sort_order' => $validated['sort_order'] ?? 0,
            ],
            $this->canonicalColumns($request),
        ));
        $this->fillTranslations($category, $request, $locale);
        $category->save();

        if ($request->hasFile('cover_desktop')) {
            $category->addMediaFromRequest('cover_desktop')->toMediaCollection('cover_desktop');
        }
        if ($request->hasFile('cover_mobile')) {
            $category->addMediaFromRequest('cover_mobile')->toMediaCollection('cover_mobile');
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Request $request, BlogCategory $category)
    {
        return view('blog::admin.categories.edit', [
            'category' => $category,
            'locale' => $this->editingLocale($request),
            'parentOptions' => BlogCategory::parentOptionsFor($category),
        ]);
    }

    public function update(Request $request, BlogCategory $category)
    {
        $validated = $request->validate([
            'parent_id' => [
                'nullable',
                Rule::exists('blog_categories', 'id'),
                Rule::notIn($category->subtreeIds()), // no self/descendant → no cycles
            ],
            'title' => 'required|string|max:255',
            'menu_title' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'og_title' => 'nullable|string|max:255',
            'twitter_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string',
            'sort_order' => 'nullable|integer',
            'locale' => 'nullable|string',
            'canonical' => 'nullable|string',
            'cover_desktop' => 'nullable|image|max:5120',
            'cover_mobile' => 'nullable|image|max:5120',
        ], [
            'parent_id.not_in' => 'A category cannot be nested under itself or one of its own subcategories.',
        ]);

        $locale = $this->editingLocale($request);

        $category->fill(array_merge(
            [
                'parent_id' => $validated['parent_id'] ?? null,
                'status' => $validated['status'],
                'sort_order' => $validated['sort_order'] ?? $category->sort_order,
            ],
            $this->canonicalColumns($request),
        ));
        $this->fillTranslations($category, $request, $locale);
        $category->save();

        if ($request->hasFile('cover_desktop')) {
            $category->addMediaFromRequest('cover_desktop')->toMediaCollection('cover_desktop');
        }
        if ($request->hasFile('cover_mobile')) {
            $category->addMediaFromRequest('cover_mobile')->toMediaCollection('cover_mobile');
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(BlogCategory $category)
    {
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    /** Resolve the canonical "type:id" picker value into columns (null = self). */
    protected function canonicalColumns(Request $request): array
    {
        $canonical = PageUrl::parse($request->input('canonical'));

        return [
            'canonical_type' => $canonical['type'] ?? null,
            'canonical_id' => $canonical['id'] ?? null,
        ];
    }
}
