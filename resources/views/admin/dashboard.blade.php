@extends('blog::layouts.admin')

@section('page-title', 'Dashboard')

@section('breadcrumbs')
    <span class="text-stone-500 dark:text-stone-400">Dashboard</span>
@endsection

@section('content')
    {{-- Welcome Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-stone-800 dark:text-stone-200">
            Welcome back{{ isset($user) ? ', ' . $user->name : '' }}!
        </h1>
        <p class="mt-1 text-stone-500 dark:text-stone-400">Here's what's happening with your blog today.</p>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        {{-- Total Posts --}}
        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6 transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-stone-500 dark:text-stone-400">Total Posts</p>
                    <p class="mt-2 text-3xl font-bold text-stone-800 dark:text-stone-200">{{ $totalPosts ?? 0 }}</p>
                </div>
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Published Posts --}}
        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6 transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-stone-500 dark:text-stone-400">Published</p>
                    <p class="mt-2 text-3xl font-bold text-stone-800 dark:text-stone-200">{{ $publishedPosts ?? 0 }}</p>
                </div>
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Categories --}}
        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6 transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-stone-500 dark:text-stone-400">Categories</p>
                    <p class="mt-2 text-3xl font-bold text-stone-800 dark:text-stone-200">{{ $totalCategories ?? 0 }}</p>
                </div>
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Pending Requests --}}
        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6 transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-stone-500 dark:text-stone-400">Pending Requests</p>
                    <p class="mt-2 text-3xl font-bold text-stone-800 dark:text-stone-200">{{ $pendingRequests ?? 0 }}</p>
                </div>
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-stone-100 dark:bg-stone-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-stone-600 dark:text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
        {{-- Recent Posts --}}
        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700">
            <div class="px-6 py-4 border-b border-warm-200 dark:border-neutral-700 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-stone-800 dark:text-stone-200">Recent Posts</h2>
                <a href="{{ route('admin.posts.index') }}" class="text-sm text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-stone-500 dark:text-stone-400 border-b border-warm-100 dark:border-neutral-700">
                            <th class="px-6 py-3 font-medium">Title</th>
                            <th class="px-6 py-3 font-medium">Category</th>
                            <th class="px-6 py-3 font-medium">Status</th>
                            <th class="px-6 py-3 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-warm-100 dark:divide-neutral-700">
                        @forelse($recentPosts ?? [] as $post)
                            <tr class="hover:bg-warm-50 dark:hover:bg-neutral-750 transition">
                                <td class="px-6 py-3 text-stone-800 dark:text-stone-200 font-medium">
                                    <a href="{{ route('admin.posts.edit', $post) }}" class="hover:text-amber-600 dark:hover:text-amber-400 transition">
                                        {{ Str::limit($post->title, 40) }}
                                    </a>
                                </td>
                                <td class="px-6 py-3 text-stone-500 dark:text-stone-400">
                                    {{ $post->category->name ?? '-' }}
                                </td>
                                <td class="px-6 py-3">
                                    @if($post->status === \BlogCms\Models\BlogPost::STATUS_PUBLISHED)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Published</span>
                                    @elseif($post->status === \BlogCms\Models\BlogPost::STATUS_DRAFT)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-800 dark:bg-stone-700 dark:text-stone-300">Draft</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">Archived</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-stone-500 dark:text-stone-400">
                                    {{ $post->created_at->format('M d, Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-stone-400 dark:text-stone-500">No posts yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent Guest Requests --}}
        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700">
            <div class="px-6 py-4 border-b border-warm-200 dark:border-neutral-700 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-stone-800 dark:text-stone-200">Recent Guest Requests</h2>
                <a href="{{ route('admin.guest-requests.index') }}" class="text-sm text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-stone-500 dark:text-stone-400 border-b border-warm-100 dark:border-neutral-700">
                            <th class="px-6 py-3 font-medium">Name</th>
                            <th class="px-6 py-3 font-medium">Topic</th>
                            <th class="px-6 py-3 font-medium">Status</th>
                            <th class="px-6 py-3 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-warm-100 dark:divide-neutral-700">
                        @forelse($recentGuestRequests ?? [] as $request)
                            <tr class="hover:bg-warm-50 dark:hover:bg-neutral-750 transition">
                                <td class="px-6 py-3 text-stone-800 dark:text-stone-200 font-medium">
                                    <a href="{{ route('admin.guest-requests.show', $request) }}" class="hover:text-amber-600 dark:hover:text-amber-400 transition">
                                        {{ $request->name }}
                                    </a>
                                </td>
                                <td class="px-6 py-3 text-stone-500 dark:text-stone-400">
                                    {{ Str::limit($request->proposed_topic, 30) }}
                                </td>
                                <td class="px-6 py-3">
                                    @if($request->status === 'pending')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">Pending</span>
                                    @elseif($request->status === 'approved')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Approved</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Rejected</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-stone-500 dark:text-stone-400">
                                    {{ $request->created_at->format('M d, Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-stone-400 dark:text-stone-500">No guest requests yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
