@extends('blog::layouts.admin')

@section('page-title', 'Redirects')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">Dashboard</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <span class="text-stone-500 dark:text-stone-400">Redirects</span>
@endsection

@section('content')
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-stone-800 dark:text-stone-200">Redirects</h1>
            <p class="text-sm text-stone-500 dark:text-stone-400 mt-1">Send an old page to another post or category with a 301 or 302.</p>
        </div>
        <a href="{{ route('admin.redirects.create') }}"
           class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Redirect
        </a>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-stone-500 dark:text-stone-400 bg-warm-50 dark:bg-neutral-850 border-b border-warm-200 dark:border-neutral-700">
                        <th class="px-6 py-3 font-medium">From (source)</th>
                        <th class="px-6 py-3 font-medium">To (target)</th>
                        <th class="px-6 py-3 font-medium text-center">Type</th>
                        <th class="px-6 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-warm-100 dark:divide-neutral-700">
                    @forelse($redirects as $redirect)
                        <tr class="hover:bg-warm-50 dark:hover:bg-neutral-750 transition {{ $loop->even ? 'bg-warm-50/50 dark:bg-neutral-800/50' : '' }}">
                            <td class="px-6 py-4">
                                <span class="font-medium text-stone-800 dark:text-stone-200">{{ $redirect->sourceLabel() }}</span>
                                @if($redirect->source_path)
                                    <span class="block text-xs text-stone-400 dark:text-stone-500 font-mono mt-0.5">/{{ $redirect->source_path }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 font-medium text-stone-800 dark:text-stone-200">
                                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                    {{ $redirect->targetLabel() }}
                                </span>
                                @if($url = $redirect->targetUrl())
                                    <span class="block text-xs text-stone-400 dark:text-stone-500 font-mono mt-0.5 truncate max-w-xs">{{ $url }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($redirect->status_code === \BlogCms\Models\BlogRedirect::STATUS_PERMANENT)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">301</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">302</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.redirects.edit', $redirect) }}"
                                       class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/40 transition">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.redirects.destroy', $redirect) }}"
                                          onsubmit="return confirm('Delete this redirect?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-stone-300 dark:text-stone-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                    <p class="text-stone-500 dark:text-stone-400 font-medium">No redirects yet</p>
                                    <p class="text-stone-400 dark:text-stone-500 text-sm mt-1">Redirect a post or category to another page.</p>
                                    <a href="{{ route('admin.redirects.create') }}"
                                       class="mt-4 inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition">
                                        Add Redirect
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($redirects->hasPages())
            <div class="px-6 py-4 border-t border-warm-200 dark:border-neutral-700">
                {{ $redirects->links() }}
            </div>
        @endif
    </div>
@endsection
