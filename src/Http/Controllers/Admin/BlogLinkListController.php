<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogLinkList;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogLinkListController extends Controller
{
    public function index()
    {
        $lists = BlogLinkList::withCount('links')->ordered()->get();

        return view('blog::admin.link-lists.index', compact('lists'));
    }

    public function create()
    {
        return view('blog::admin.link-lists.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        BlogLinkList::create([
            'name' => $validated['name'],
            'key' => $this->uniqueKey($validated['key'] ?? $validated['name']),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()->route('admin.link-lists.index')->with('success', 'Link list created.');
    }

    public function edit(BlogLinkList $linkList)
    {
        $linkList->load('links');

        return view('blog::admin.link-lists.edit', ['list' => $linkList]);
    }

    public function update(Request $request, BlogLinkList $linkList)
    {
        $validated = $this->validateData($request, $linkList);

        $linkList->update([
            'name' => $validated['name'],
            'key' => $this->uniqueKey($validated['key'] ?? $validated['name'], $linkList),
            'sort_order' => $validated['sort_order'] ?? $linkList->sort_order,
        ]);

        return redirect()->route('admin.link-lists.edit', $linkList)->with('success', 'Link list updated.');
    }

    public function destroy(BlogLinkList $linkList)
    {
        $linkList->delete(); // cascades to its links

        return redirect()->route('admin.link-lists.index')->with('success', 'Link list deleted.');
    }

    protected function validateData(Request $request, ?BlogLinkList $list = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'key' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', Rule::unique('blog_link_lists', 'key')->ignore($list?->id)],
            'sort_order' => 'nullable|integer',
        ], [
            'key.regex' => 'The key may only contain lowercase letters, numbers and hyphens.',
        ]);
    }

    protected function uniqueKey(string $base, ?BlogLinkList $ignore = null): string
    {
        $slug = Str::slug($base) ?: 'list';
        $candidate = $slug;
        $i = 1;
        while (BlogLinkList::where('key', $candidate)->when($ignore, fn ($q) => $q->whereKeyNot($ignore->id))->exists()) {
            $candidate = $slug.'-'.(++$i);
        }

        return $candidate;
    }
}
