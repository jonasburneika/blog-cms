<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogPost;
use BlogCms\Models\BlogPostHook;
use Illuminate\Http\Request;

class BlogPostHookController extends Controller
{
    public function index(BlogPost $post)
    {
        $hooks = $post->hooks()->with('template')->orderBy('position')->orderBy('sort_order')->get();

        return response()->json($hooks);
    }

    public function store(Request $request, BlogPost $post)
    {
        $validated = $request->validate([
            'hook_template_id' => 'nullable|exists:hook_templates,id',
            'name' => 'required|string|max:255',
            'position' => 'required|integer|min:0',
            'html_content' => 'nullable|string',
            'config' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? $post->hooks()->where('position', $validated['position'])->count();

        $hook = $post->hooks()->create($validated);
        $hook->load('template');

        return response()->json($hook, 201);
    }

    public function update(Request $request, BlogPost $post, BlogPostHook $hook)
    {
        $validated = $request->validate([
            'hook_template_id' => 'nullable|exists:hook_templates,id',
            'name' => 'required|string|max:255',
            'position' => 'required|integer|min:0',
            'html_content' => 'nullable|string',
            'config' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $hook->update($validated);
        $hook->load('template');

        return response()->json($hook);
    }

    public function destroy(BlogPost $post, BlogPostHook $hook)
    {
        $hook->delete();

        return response()->json(['message' => 'Hook removed']);
    }

    public function reorder(Request $request, BlogPost $post)
    {
        $validated = $request->validate([
            'hooks' => 'required|array',
            'hooks.*.id' => 'required|integer|exists:blog_post_hooks,id',
            'hooks.*.position' => 'required|integer|min:0',
            'hooks.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['hooks'] as $hookData) {
            BlogPostHook::where('id', $hookData['id'])->update([
                'position' => $hookData['position'],
                'sort_order' => $hookData['sort_order'],
            ]);
        }

        return response()->json(['message' => 'Hooks reordered']);
    }

    // Preview rendered HTML for a hook
    public function preview(BlogPost $post, BlogPostHook $hook)
    {
        return response()->json([
            'html' => $hook->getRenderedHtml(),
        ]);
    }
}
