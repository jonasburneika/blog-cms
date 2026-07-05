@extends('blog::layouts.admin')

@section('page-title', 'Blog Posts')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">Dashboard</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <span class="text-stone-500 dark:text-stone-400">Blog Posts</span>
@endsection

@section('content')
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-stone-800 dark:text-stone-200">Blog Posts</h1>
        <a href="{{ route('admin.posts.create') }}"
           class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Post
        </a>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-4 mb-6">
        <form method="GET" action="{{ route('admin.posts.index') }}" class="flex flex-col sm:flex-row gap-4">
            {{-- Category Filter --}}
            <div class="sm:w-48">
                <select name="category" onchange="this.form.submit()"
                        class="w-full px-3 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                    <option value="">All Categories</option>
                    @foreach($categories ?? [] as $category)
                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Status Filter --}}
            <div class="sm:w-40">
                <select name="status" onchange="this.form.submit()"
                        class="w-full px-3 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                    <option value="">All Statuses</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Draft</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Published</option>
                    <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>Archived</option>
                </select>
            </div>

            {{-- Search --}}
            <div class="flex-1">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-stone-400 dark:text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search posts..."
                           class="w-full pl-10 pr-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                </div>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-stone-500 dark:text-stone-400 bg-warm-50 dark:bg-neutral-850 border-b border-warm-200 dark:border-neutral-700">
                        <th class="px-6 py-3 font-medium">Title</th>
                        <th class="px-6 py-3 font-medium">Category</th>
                        <th class="px-6 py-3 font-medium">Author</th>
                        <th class="px-6 py-3 font-medium text-center">Status</th>
                        <th class="px-6 py-3 font-medium text-center">Featured</th>
                        <th class="px-6 py-3 font-medium">Published</th>
                        <th class="px-6 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-warm-100 dark:divide-neutral-700">
                    @forelse($posts as $post)
                        <tr class="hover:bg-warm-50 dark:hover:bg-neutral-750 transition {{ $loop->even ? 'bg-warm-50/50 dark:bg-neutral-800/50' : '' }}">
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.posts.edit', $post) }}" class="font-medium text-stone-800 dark:text-stone-200 hover:text-amber-600 dark:hover:text-amber-400 transition">
                                    {{ Str::limit($post->title, 50) }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                @if($post->category)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">
                                        {{ $post->category->title }}
                                    </span>
                                @else
                                    <span class="text-stone-400 dark:text-stone-500">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-stone-600 dark:text-stone-400">
                                {{ $post->user->name ?? 'Unknown' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($post->status === \BlogCms\Models\BlogPost::STATUS_PUBLISHED)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Published</span>
                                @elseif($post->status === \BlogCms\Models\BlogPost::STATUS_DRAFT)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-600 dark:bg-stone-700 dark:text-stone-400">Draft</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">Archived</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($post->is_featured)
                                    <svg class="w-5 h-5 text-amber-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-stone-300 dark:text-stone-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-stone-500 dark:text-stone-400">
                                {{ $post->published_at ? $post->published_at->format('M d, Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.posts.edit', $post) }}"
                                       class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/40 transition">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.posts.destroy', $post) }}"
                                          onsubmit="return confirm('Are you sure you want to delete this post?')">
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
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-stone-300 dark:text-stone-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                    </svg>
                                    <p class="text-stone-500 dark:text-stone-400 font-medium">No posts found</p>
                                    <p class="text-stone-400 dark:text-stone-500 text-sm mt-1">Create your first blog post to get started.</p>
                                    <a href="{{ route('admin.posts.create') }}"
                                       class="mt-4 inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition">
                                        New Post
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($posts->hasPages())
            <div class="px-6 py-4 border-t border-warm-200 dark:border-neutral-700">
                {{ $posts->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
