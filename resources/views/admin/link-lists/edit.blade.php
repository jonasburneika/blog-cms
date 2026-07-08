@extends('blog::layouts.admin')

@section('page-title', 'Edit Link List')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 transition">Dashboard</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <a href="{{ route('admin.link-lists.index') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 transition">Link Lists</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <span class="text-stone-500 dark:text-stone-400">{{ $list->name }}</span>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">
        {{-- List settings --}}
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-stone-800 dark:text-stone-200">{{ $list->name }}</h1>
            <form method="POST" action="{{ route('admin.link-lists.destroy', $list) }}" onsubmit="return confirm('Delete this list and all its links?')">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">Delete list</button>
            </form>
        </div>

        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
            <form method="POST" action="{{ route('admin.link-lists.update', $list) }}">
                @csrf @method('PUT')
                @include('blog::admin.link-lists.partials.fields', ['list' => $list])
                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg shadow-sm transition">Save list</button>
                </div>
            </form>
        </div>

        {{-- Links --}}
        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700">
            <div class="px-6 py-4 border-b border-warm-200 dark:border-neutral-700 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-stone-800 dark:text-stone-200">Links</h2>
                <a href="{{ route('admin.link-lists.links.create', $list) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition">Add link</a>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-stone-500 dark:text-stone-400 border-b border-warm-100 dark:border-neutral-700">
                        <th class="px-6 py-3 font-medium">Anchor</th>
                        <th class="px-6 py-3 font-medium">Target</th>
                        <th class="px-6 py-3 font-medium">Opens</th>
                        <th class="px-6 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-warm-100 dark:divide-neutral-700">
                    @forelse($list->links as $link)
                        <tr class="hover:bg-warm-50 dark:hover:bg-neutral-750 transition {{ $link->is_active ? '' : 'opacity-50' }}">
                            <td class="px-6 py-3 font-medium text-stone-800 dark:text-stone-200">{{ $link->label() ?: '—' }}</td>
                            <td class="px-6 py-3 text-stone-500 dark:text-stone-400 truncate max-w-xs">
                                @if($link->isInternal())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-stone-100 dark:bg-neutral-700">{{ $link->link_type }}</span>
                                    <span class="text-xs">{{ \Illuminate\Support\Str::limit($link->href(), 40) }}</span>
                                @else
                                    <span class="text-xs">{{ \Illuminate\Support\Str::limit($link->url, 45) }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-stone-500 dark:text-stone-400"><code class="text-xs">{{ $link->target }}</code></td>
                            <td class="px-6 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.link-lists.links.edit', [$list, $link]) }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400">Edit</a>
                                <form method="POST" action="{{ route('admin.link-lists.links.destroy', [$list, $link]) }}" class="inline ml-3" onsubmit="return confirm('Delete this link?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-700 dark:text-red-400">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-stone-400 dark:text-stone-500">No links yet. Add the first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
