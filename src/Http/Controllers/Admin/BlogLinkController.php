<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Admin\Concerns\EditsTranslations;
use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogLink;
use BlogCms\Models\BlogLinkList;
use BlogCms\Support\PageUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BlogLinkController extends Controller
{
    use EditsTranslations;

    public function create(Request $request, BlogLinkList $linkList)
    {
        return view('blog::admin.link-lists.link-form', [
            'list' => $linkList,
            'link' => null,
            'locale' => $this->editingLocale($request),
        ]);
    }

    public function store(Request $request, BlogLinkList $linkList)
    {
        $this->validateData($request);
        $locale = $this->editingLocale($request);

        $link = new BlogLink($this->targetColumns($request) + [
            'blog_link_list_id' => $linkList->id,
            'target' => $this->target($request),
            'rel' => $request->input('rel') ?: null,
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active'),
        ]);
        $this->fillTranslations($link, $request, $locale);
        $link->save();

        return redirect()->route('admin.link-lists.edit', $linkList)->with('success', 'Link added.');
    }

    public function edit(Request $request, BlogLinkList $linkList, BlogLink $link)
    {
        abort_unless($link->blog_link_list_id === $linkList->id, 404);

        return view('blog::admin.link-lists.link-form', [
            'list' => $linkList,
            'link' => $link,
            'locale' => $this->editingLocale($request),
        ]);
    }

    public function update(Request $request, BlogLinkList $linkList, BlogLink $link)
    {
        abort_unless($link->blog_link_list_id === $linkList->id, 404);

        $this->validateData($request);
        $locale = $this->editingLocale($request);

        $link->fill($this->targetColumns($request) + [
            'target' => $this->target($request),
            'rel' => $request->input('rel') ?: null,
            'sort_order' => (int) $request->input('sort_order', $link->sort_order),
            'is_active' => $request->boolean('is_active'),
        ]);
        $this->fillTranslations($link, $request, $locale);
        $link->save();

        return redirect()->route('admin.link-lists.edit', $linkList)->with('success', 'Link updated.');
    }

    public function destroy(BlogLinkList $linkList, BlogLink $link)
    {
        abort_unless($link->blog_link_list_id === $linkList->id, 404);

        $link->delete();

        return redirect()->route('admin.link-lists.edit', $linkList)->with('success', 'Link deleted.');
    }

    protected function validateData(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'label' => 'required|string|max:255',
            'page' => 'nullable|string',
            'url' => 'nullable|url|max:2048',
            'target' => ['nullable', Rule::in(BlogLink::TARGETS)],
            'rel' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'locale' => 'nullable|string',
        ]);

        // Need a destination: an internal page OR an external URL.
        $validator->after(function ($validator) use ($request) {
            if (! PageUrl::parse($request->input('page')) && ! $request->filled('url')) {
                $validator->errors()->add('url', 'Choose a page to link to, or enter an external URL.');
            }
        });

        $validator->validate();
    }

    /** Internal page picker wins over the external URL. */
    protected function targetColumns(Request $request): array
    {
        $page = PageUrl::parse($request->input('page'));

        return [
            'link_type' => $page['type'] ?? null,
            'link_id' => $page['id'] ?? null,
            'url' => $page ? null : ($request->input('url') ?: null),
        ];
    }

    protected function target(Request $request): string
    {
        $target = (string) $request->input('target', '_self');

        return in_array($target, BlogLink::TARGETS, true) ? $target : '_self';
    }
}
