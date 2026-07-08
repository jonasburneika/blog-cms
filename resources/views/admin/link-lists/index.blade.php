@extends('blog::layouts.admin')

@section('page-title', 'Link Lists')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">Dashboard</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <span class="text-stone-500 dark:text-stone-400">Link Lists</span>
@endsection

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-stone-800 dark:text-stone-200">Link Lists</h1>
        <a href="{{ route('admin.link-lists.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
            New list
        </a>
    </div>

    <p class="text-sm text-stone-500 dark:text-stone-400 mb-4">Named lists of links your theme renders by key, e.g. <code>&lt;x-blog::link-list key="footer" /&gt;</code>.</p>

    <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-stone-500 dark:text-stone-400 border-b border-warm-200 dark:border-neutral-700">
                    <th class="px-6 py-3 font-medium">Name</th>
                    <th class="px-6 py-3 font-medium">Key</th>
                    <th class="px-6 py-3 font-medium">Links</th>
                    <th class="px-6 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-warm-100 dark:divide-neutral-700">
                @forelse($lists as $list)
                    <tr class="hover:bg-warm-50 dark:hover:bg-neutral-750 transition">
                        <td class="px-6 py-3 font-medium text-stone-800 dark:text-stone-200">
                            <a href="{{ route('admin.link-lists.edit', $list) }}" class="hover:text-amber-600 dark:hover:text-amber-400">{{ $list->name }}</a>
                        </td>
                        <td class="px-6 py-3"><code class="text-xs text-stone-500 dark:text-stone-400">{{ $list->key }}</code></td>
                        <td class="px-6 py-3 text-stone-500 dark:text-stone-400">{{ $list->links_count }}</td>
                        <td class="px-6 py-3 text-right">
                            <a href="{{ route('admin.link-lists.edit', $list) }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-stone-400 dark:text-stone-500">No link lists yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
