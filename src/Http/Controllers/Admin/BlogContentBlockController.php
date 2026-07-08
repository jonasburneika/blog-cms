<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Admin\Concerns\EditsTranslations;
use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogContentBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogContentBlockController extends Controller
{
    use EditsTranslations;

    public function index()
    {
        $blocks = BlogContentBlock::ordered()->get();

        return view('blog::admin.content-blocks.index', compact('blocks'));
    }

    public function create(Request $request)
    {
        return view('blog::admin.content-blocks.form', [
            'block' => null,
            'locale' => $this->editingLocale($request),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);
        $locale = $this->editingLocale($request);

        $block = new BlogContentBlock([
            'name' => $validated['name'],
            'key' => $this->uniqueKey($validated['key'] ?? $validated['name']),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);
        $this->fillTranslations($block, $request, $locale);
        $block->save();

        return redirect()->route('admin.content-blocks.index')->with('success', 'Content block created.');
    }

    public function edit(Request $request, BlogContentBlock $contentBlock)
    {
        return view('blog::admin.content-blocks.form', [
            'block' => $contentBlock,
            'locale' => $this->editingLocale($request),
        ]);
    }

    public function update(Request $request, BlogContentBlock $contentBlock)
    {
        $validated = $this->validateData($request, $contentBlock);
        $locale = $this->editingLocale($request);

        $contentBlock->fill([
            'name' => $validated['name'],
            'key' => $this->uniqueKey($validated['key'] ?? $validated['name'], $contentBlock),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? $contentBlock->sort_order,
        ]);
        $this->fillTranslations($contentBlock, $request, $locale);
        $contentBlock->save();

        return redirect()->route('admin.content-blocks.index')->with('success', 'Content block updated.');
    }

    public function destroy(BlogContentBlock $contentBlock)
    {
        $contentBlock->delete();

        return redirect()->route('admin.content-blocks.index')->with('success', 'Content block deleted.');
    }

    protected function validateData(Request $request, ?BlogContentBlock $block = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'key' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9_-]+$/', Rule::unique('blog_content_blocks', 'key')->ignore($block?->id)],
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'locale' => 'nullable|string',
        ], [
            'key.regex' => 'The key may only contain lowercase letters, numbers, hyphens and underscores.',
        ]);
    }

    protected function uniqueKey(string $base, ?BlogContentBlock $ignore = null): string
    {
        $slug = Str::slug($base, '_') ?: 'block';
        $candidate = $slug;
        $i = 1;
        while (BlogContentBlock::where('key', $candidate)->when($ignore, fn ($q) => $q->whereKeyNot($ignore->id))->exists()) {
            $candidate = $slug.'_'.(++$i);
        }

        return $candidate;
    }
}
