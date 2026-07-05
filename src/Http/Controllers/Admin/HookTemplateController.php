<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\HookTemplate;
use Illuminate\Http\Request;

class HookTemplateController extends Controller
{
    public function index(Request $request)
    {
        $templates = HookTemplate::query()
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->active_only, fn($q) => $q->active())
            ->orderBy('name')
            ->get();

        if ($request->wantsJson()) {
            return response()->json($templates);
        }

        return view('blog::admin.hook-templates.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:related_posts,ad,newsletter,cta,custom_html,divider',
            'description' => 'nullable|string',
            'html_content' => 'required|string',
            'css_class' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $template = HookTemplate::create($validated);

        return response()->json($template, 201);
    }

    public function show(HookTemplate $hookTemplate)
    {
        return response()->json($hookTemplate);
    }

    public function update(Request $request, HookTemplate $hookTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:related_posts,ad,newsletter,cta,custom_html,divider',
            'description' => 'nullable|string',
            'html_content' => 'required|string',
            'css_class' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $hookTemplate->update($validated);

        return response()->json($hookTemplate);
    }

    public function destroy(HookTemplate $hookTemplate)
    {
        $hookTemplate->delete();

        return response()->json(['message' => 'Template deleted']);
    }

    // Return available hook types for dropdown
    public function types()
    {
        return response()->json([
            ['value' => 'related_posts', 'label' => 'Related Posts', 'icon' => 'newspaper'],
            ['value' => 'ad', 'label' => 'Advertisement', 'icon' => 'megaphone'],
            ['value' => 'newsletter', 'label' => 'Newsletter Signup', 'icon' => 'envelope'],
            ['value' => 'cta', 'label' => 'Call to Action', 'icon' => 'cursor-arrow-rays'],
            ['value' => 'custom_html', 'label' => 'Custom HTML', 'icon' => 'code-bracket'],
            ['value' => 'divider', 'label' => 'Divider / Spacer', 'icon' => 'minus'],
        ]);
    }
}
