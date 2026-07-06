@extends('blog::layouts.admin')

@section('page-title', 'Authors')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">Dashboard</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <span class="text-stone-500 dark:text-stone-400">Authors</span>
@endsection

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-stone-800 dark:text-stone-200">Authors</h1>
            <p class="text-sm text-stone-500 dark:text-stone-400 mt-1">Bio, avatar and social links shown on posts and in Article schema.</p>
        </div>
        <a href="{{ route('admin.authors.create') }}"
           class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Author
        </a>
    </div>

    <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-stone-500 dark:text-stone-400 bg-warm-50 dark:bg-neutral-850 border-b border-warm-200 dark:border-neutral-700">
                        <th class="px-6 py-3 font-medium">Author</th>
                        <th class="px-6 py-3 font-medium">Role</th>
                        <th class="px-6 py-3 font-medium text-center">Posts</th>
                        <th class="px-6 py-3 font-medium text-center">Indexable</th>
                        <th class="px-6 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-warm-100 dark:divide-neutral-700">
                    @forelse($profiles as $profile)
                        <tr class="hover:bg-warm-50 dark:hover:bg-neutral-750 transition {{ $loop->even ? 'bg-warm-50/50 dark:bg-neutral-800/50' : '' }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($profile->avatarUrl('thumb'))
                                        <img src="{{ $profile->avatarUrl('thumb') }}" class="w-9 h-9 rounded-full object-cover">
                                    @else
                                        <div class="w-9 h-9 rounded-full bg-earth-400 dark:bg-earth-600 flex items-center justify-center text-white text-sm font-semibold">{{ substr($profile->name(), 0, 1) }}</div>
                                    @endif
                                    <div>
                                        <span class="font-medium text-stone-800 dark:text-stone-200">{{ $profile->name() }}</span>
                                        <span class="block text-xs text-stone-400 dark:text-stone-500 font-mono">/{{ $profile->pagePath() }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-stone-600 dark:text-stone-300">{{ $profile->role ?: '—' }}</td>
                            <td class="px-6 py-4 text-center text-stone-600 dark:text-stone-300">{{ $profile->posts_count }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($profile->is_indexable)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Yes</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-600 dark:bg-stone-700 dark:text-stone-400">noindex</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.authors.edit', $profile) }}"
                                       class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/40 transition">Edit</a>
                                    <form method="POST" action="{{ route('admin.authors.destroy', $profile) }}" onsubmit="return confirm('Delete this author profile?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <p class="text-stone-500 dark:text-stone-400 font-medium">No author profiles yet</p>
                                <a href="{{ route('admin.authors.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition">Add Author</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($profiles->hasPages())
            <div class="px-6 py-4 border-t border-warm-200 dark:border-neutral-700">{{ $profiles->links() }}</div>
        @endif
    </div>
@endsection
