@extends('blog::layouts.admin')

@section('page-title', 'Content Blocks')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 transition">Dashboard</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <span class="text-stone-500 dark:text-stone-400">Content Blocks</span>
@endsection

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-stone-800 dark:text-stone-200">Content Blocks</h1>
        <a href="{{ route('admin.content-blocks.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg shadow-sm transition">New block</a>
    </div>

    <p class="text-sm text-stone-500 dark:text-stone-400 mb-4">
        Rich-text blocks the theme renders by key, e.g. <code>&lt;x-blog::content-block key="homepage_before_featured" /&gt;</code>
        and <code>homepage_after_featured</code> around the homepage featured posts.
    </p>

    <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-stone-500 dark:text-stone-400 border-b border-warm-200 dark:border-neutral-700">
                    <th class="px-6 py-3 font-medium">Name</th>
                    <th class="px-6 py-3 font-medium">Key</th>
                    <th class="px-6 py-3 font-medium">Status</th>
                    <th class="px-6 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-warm-100 dark:divide-neutral-700">
                @forelse($blocks as $block)
                    <tr class="hover:bg-warm-50 dark:hover:bg-neutral-750 transition">
                        <td class="px-6 py-3 font-medium text-stone-800 dark:text-stone-200">
                            <a href="{{ route('admin.content-blocks.edit', $block) }}" class="hover:text-amber-600 dark:hover:text-amber-400">{{ $block->name }}</a>
                        </td>
                        <td class="px-6 py-3"><code class="text-xs text-stone-500 dark:text-stone-400">{{ $block->key }}</code></td>
                        <td class="px-6 py-3">
                            @if($block->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Active</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-700 dark:bg-neutral-700 dark:text-stone-300">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right">
                            <a href="{{ route('admin.content-blocks.edit', $block) }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-stone-400 dark:text-stone-500">No content blocks yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
