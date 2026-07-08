<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Admin\Concerns\EditsTranslations;
use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogPageController extends Controller
{
    use EditsTranslations;

    public function index()
    {
        $pages = BlogPage::ordered()->get();

        return view('blog::admin.pages.index', compact('pages'));
    }

    public function create(Request $request)
    {
        return view('blog::admin.pages.form', [
            'page' => null,
            'locale' => $this->editingLocale($request),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);
        $locale = $this->editingLocale($request);

        $page = new BlogPage([
            'status' => $validated['status'],
            'is_indexable' => $request->boolean('is_indexable'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);
        $this->fillTranslations($page, $request, $locale);
        $this->applySlug($page, $request, $locale);
        $page->save();

        return redirect()->route('admin.pages.index')->with('success', 'Page created.');
    }

    public function edit(Request $request, BlogPage $page)
    {
        return view('blog::admin.pages.form', [
            'page' => $page,
            'locale' => $this->editingLocale($request),
        ]);
    }

    public function update(Request $request, BlogPage $page)
    {
        $validated = $this->validateData($request);
        $locale = $this->editingLocale($request);

        $page->fill([
            'status' => $validated['status'],
            'is_indexable' => $request->boolean('is_indexable'),
            'sort_order' => $validated['sort_order'] ?? $page->sort_order,
        ]);
        $this->fillTranslations($page, $request, $locale);
        $this->applySlug($page, $request, $locale);
        $page->save();

        return redirect()->route('admin.pages.index')->with('success', 'Page updated.');
    }

    /** Apply an explicit slug for this locale (auto-generated from title if blank). */
    protected function applySlug(BlogPage $page, Request $request, string $locale): void
    {
        if ($request->filled('slug')) {
            $page->setTranslation('slug', $locale, Str::slug($request->input('slug')));
        }
    }

    public function destroy(BlogPage $page)
    {
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Page deleted.');
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published',
            'is_indexable' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'locale' => 'nullable|string',
        ]);
    }
}
