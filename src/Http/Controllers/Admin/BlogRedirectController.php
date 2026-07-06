<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogRedirect;
use BlogCms\Support\PageUrl;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BlogRedirectController extends Controller
{
    public function index()
    {
        $redirects = BlogRedirect::orderByDesc('id')->paginate(20);

        return view('blog::admin.redirects.index', compact('redirects'));
    }

    public function create()
    {
        return view('blog::admin.redirects.create', ['pages' => PageUrl::options()]);
    }

    public function store(Request $request)
    {
        BlogRedirect::create($this->validatedData($request));

        return redirect()->route('admin.redirects.index')
            ->with('success', 'Redirect created successfully.');
    }

    public function edit(BlogRedirect $redirect)
    {
        return view('blog::admin.redirects.edit', [
            'redirect' => $redirect,
            'pages' => PageUrl::options(),
        ]);
    }

    public function update(Request $request, BlogRedirect $redirect)
    {
        $redirect->update($this->validatedData($request, $redirect));

        return redirect()->route('admin.redirects.index')
            ->with('success', 'Redirect updated successfully.');
    }

    public function destroy(BlogRedirect $redirect)
    {
        $redirect->delete();

        return redirect()->route('admin.redirects.index')
            ->with('success', 'Redirect deleted successfully.');
    }

    /**
     * Validate the "type:id" source/target pickers into concrete columns and
     * guard against self-redirects and duplicate sources.
     */
    protected function validatedData(Request $request, ?BlogRedirect $redirect = null): array
    {
        $request->validate([
            'source' => 'required|string',
            'target' => 'required|string',
            'status_code' => ['required', Rule::in(array_keys(BlogRedirect::STATUSES))],
        ]);

        $source = PageUrl::parse($request->input('source'));
        $target = PageUrl::parse($request->input('target'));

        if (! $source || ! PageUrl::model($source['type'], $source['id'])) {
            throw ValidationException::withMessages(['source' => 'Choose a valid source page.']);
        }

        if (! $target || ! PageUrl::model($target['type'], $target['id'])) {
            throw ValidationException::withMessages(['target' => 'Choose a valid target page.']);
        }

        if ($source['type'] === $target['type'] && $source['id'] === $target['id']) {
            throw ValidationException::withMessages(['target' => 'A page cannot redirect to itself.']);
        }

        $duplicate = BlogRedirect::where('source_type', $source['type'])
            ->where('source_id', $source['id'])
            ->when($redirect, fn ($q) => $q->whereKeyNot($redirect->getKey()))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['source' => 'This page already has a redirect.']);
        }

        return [
            'source_type' => $source['type'],
            'source_id' => $source['id'],
            'target_type' => $target['type'],
            'target_id' => $target['id'],
            'status_code' => (int) $request->input('status_code'),
        ];
    }
}
