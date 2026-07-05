@extends('blog::layouts.admin')

@section('page-title', $category->title)

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">Dashboard</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <a href="{{ route('admin.categories.index') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">Categories</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <span class="text-stone-500 dark:text-stone-400">{{ $category->title }}</span>
@endsection

@section('content')
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-stone-800 dark:text-stone-200">{{ $category->title }}</h1>
            <p class="text-sm text-stone-500 dark:text-stone-400 mt-1 font-mono">/{{ $category->slug }}</p>
        </div>
        <a href="{{ route('admin.categories.edit', $category) }}"
           class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Edit Category
        </a>
    </div>

    {{-- Category Details --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Info Card --}}
        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
            <h2 class="text-sm font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider mb-4">Details</h2>
            <dl class="space-y-3">
                <div>
                    <dt class="text-xs text-stone-400 dark:text-stone-500">Title</dt>
                    <dd class="text-sm font-medium text-stone-800 dark:text-stone-200">{{ $category->title }}</dd>
                </div>
                @if($category->menu_title)
                    <div>
                        <dt class="text-xs text-stone-400 dark:text-stone-500">Menu Title</dt>
                        <dd class="text-sm text-stone-700 dark:text-stone-300">{{ $category->menu_title }}</dd>
                    </div>
                @endif
                @if($category->meta_title)
                    <div>
                        <dt class="text-xs text-stone-400 dark:text-stone-500">Meta Title</dt>
                        <dd class="text-sm text-stone-700 dark:text-stone-300">{{ $category->meta_title }}</dd>
                    </div>
                @endif
                @if($category->description)
                    <div>
                        <dt class="text-xs text-stone-400 dark:text-stone-500">Description</dt>
                        <dd class="text-sm text-stone-700 dark:text-stone-300">{{ $category->description }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs text-stone-400 dark:text-stone-500">Status</dt>
                    <dd class="mt-0.5">
                        @if($category->status === 'active')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Active</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-600 dark:bg-stone-700 dark:text-stone-400">Inactive</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-stone-400 dark:text-stone-500">Posts</dt>
                    <dd class="text-sm font-medium text-stone-800 dark:text-stone-200">{{ $category->posts_count }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-stone-400 dark:text-stone-500">Sort Order</dt>
                    <dd class="text-sm text-stone-700 dark:text-stone-300">{{ $category->sort_order }}</dd>
                </div>
            </dl>
        </div>

        {{-- Cover Images --}}
        <div class="lg:col-span-2 bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
            <h2 class="text-sm font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider mb-4">Cover Images</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-stone-400 dark:text-stone-500 mb-2">Desktop</p>
                    @if($category->getFirstMediaUrl('cover_desktop'))
                        <img src="{{ $category->getFirstMediaUrl('cover_desktop', 'preview') }}" alt="Desktop cover" class="w-full h-40 object-cover rounded-lg border border-warm-200 dark:border-neutral-700">
                    @else
                        <div class="w-full h-40 rounded-lg border-2 border-dashed border-warm-300 dark:border-neutral-600 flex items-center justify-center">
                            <span class="text-xs text-stone-400 dark:text-stone-500">No image</span>
                        </div>
                    @endif
                </div>
                <div>
                    <p class="text-xs text-stone-400 dark:text-stone-500 mb-2">Mobile</p>
                    @if($category->getFirstMediaUrl('cover_mobile'))
                        <img src="{{ $category->getFirstMediaUrl('cover_mobile', 'preview') }}" alt="Mobile cover" class="w-full h-40 object-cover rounded-lg border border-warm-200 dark:border-neutral-700">
                    @else
                        <div class="w-full h-40 rounded-lg border-2 border-dashed border-warm-300 dark:border-neutral-600 flex items-center justify-center">
                            <span class="text-xs text-stone-400 dark:text-stone-500">No image</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Analytics --}}
    <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6 mb-6">
        <h2 class="text-lg font-semibold text-stone-800 dark:text-stone-200 mb-4">Analytics (Last 30 Days)</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- GA4 --}}
            <div>
                <h4 class="text-sm font-semibold text-stone-600 dark:text-stone-400 mb-3">
                    <span class="inline-block w-2 h-2 rounded-full bg-green-500 mr-2"></span>
                    Google Analytics
                </h4>
                @if($ga4Data['error'])
                    <p class="text-xs text-amber-600 dark:text-amber-400">{{ $ga4Data['error'] }}</p>
                @else
                    <div class="flex gap-6 mb-4">
                        <div>
                            <p class="text-2xl font-bold text-stone-800 dark:text-stone-200">{{ number_format($ga4Data['total']) }}</p>
                            <p class="text-xs text-stone-500 dark:text-stone-400">Page Views</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-stone-800 dark:text-stone-200">{{ number_format($ga4Data['users'] ?? 0) }}</p>
                            <p class="text-xs text-stone-500 dark:text-stone-400">Users</p>
                        </div>
                    </div>
                    @if(count($ga4Data['daily']) > 0)
                        <div class="flex items-end gap-px h-16">
                            @php $maxViews = max(array_column($ga4Data['daily'], 'views')) ?: 1; @endphp
                            @foreach($ga4Data['daily'] as $day)
                                <div class="flex-1 bg-amber-500/60 hover:bg-amber-500 transition-colors rounded-t-sm"
                                     style="height: {{ ($day['views'] / $maxViews) * 100 }}%"
                                     title="{{ $day['date'] }}: {{ $day['views'] }} views"></div>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>

            {{-- GSC --}}
            <div>
                <h4 class="text-sm font-semibold text-stone-600 dark:text-stone-400 mb-3">
                    <span class="inline-block w-2 h-2 rounded-full bg-blue-500 mr-2"></span>
                    Search Console
                </h4>
                @if($gscData['error'])
                    <p class="text-xs text-amber-600 dark:text-amber-400">{{ $gscData['error'] }}</p>
                @else
                    <div class="flex gap-6 mb-4">
                        <div>
                            <p class="text-2xl font-bold text-stone-800 dark:text-stone-200">{{ number_format($gscData['totals']['clicks']) }}</p>
                            <p class="text-xs text-stone-500 dark:text-stone-400">Clicks</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-stone-800 dark:text-stone-200">{{ number_format($gscData['totals']['impressions']) }}</p>
                            <p class="text-xs text-stone-500 dark:text-stone-400">Impressions</p>
                        </div>
                    </div>
                    @if(count($gscData['queries']) > 0)
                        <div class="space-y-1.5 max-h-48 overflow-y-auto">
                            @foreach($gscData['queries'] as $q)
                                <div class="flex items-center justify-between text-xs py-1 border-b border-warm-100 dark:border-neutral-700">
                                    <span class="text-stone-700 dark:text-stone-300 truncate mr-4">{{ $q['query'] }}</span>
                                    <span class="text-stone-500 dark:text-stone-400 flex-shrink-0">
                                        {{ $q['clicks'] }} clicks · pos {{ $q['position'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-stone-400 dark:text-stone-500">No search queries found for this page.</p>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Blog Posts --}}
    <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-warm-200 dark:border-neutral-700">
            <h2 class="text-lg font-semibold text-stone-800 dark:text-stone-200">Blog Posts ({{ $category->posts_count }})</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-stone-500 dark:text-stone-400 bg-warm-50 dark:bg-neutral-850 border-b border-warm-200 dark:border-neutral-700">
                        <th class="px-6 py-3 font-medium">Title</th>
                        <th class="px-6 py-3 font-medium">Slug</th>
                        <th class="px-6 py-3 font-medium text-center">Status</th>
                        <th class="px-6 py-3 font-medium text-center">Views</th>
                        <th class="px-6 py-3 font-medium text-center">Featured</th>
                        <th class="px-6 py-3 font-medium">Published</th>
                        <th class="px-6 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-warm-100 dark:divide-neutral-700">
                    @forelse($posts as $post)
                        <tr class="hover:bg-warm-50 dark:hover:bg-neutral-750 transition {{ $loop->even ? 'bg-warm-50/50 dark:bg-neutral-800/50' : '' }}">
                            <td class="px-6 py-4">
                                <p class="font-medium text-stone-800 dark:text-stone-200">{{ $post->title }}</p>
                                @if($post->meta_title)
                                    <p class="text-xs text-stone-400 dark:text-stone-500 mt-0.5 truncate max-w-xs" title="{{ $post->meta_title }}">{{ $post->meta_title }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-stone-500 dark:text-stone-400 font-mono text-xs">
                                {{ $post->slug }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($post->status === \BlogCms\Models\BlogPost::STATUS_PUBLISHED)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Published</span>
                                @elseif($post->status === \BlogCms\Models\BlogPost::STATUS_DRAFT)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">Draft</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-600 dark:bg-stone-700 dark:text-stone-400">Archived</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center text-stone-600 dark:text-stone-300">
                                {{ number_format($post->views_count ?? 0) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($post->is_featured)
                                    <svg class="w-5 h-5 text-amber-500 mx-auto" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                @else
                                    <span class="text-stone-300 dark:text-stone-600">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-stone-500 dark:text-stone-400 text-xs">
                                {{ $post->published_at ? $post->published_at->format('Y-m-d') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($post->status === \BlogCms\Models\BlogPost::STATUS_PUBLISHED)
                                        <a href="/{{ $category->slug }}/{{ $post->slug }}" target="_blank"
                                           class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition"
                                           title="View on site">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.posts.edit', $post) }}"
                                       class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/40 transition">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-stone-300 dark:text-stone-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-stone-500 dark:text-stone-400 font-medium">No posts in this category</p>
                                    <a href="{{ route('admin.posts.create') }}"
                                       class="mt-4 inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition">
                                        Create Post
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
                {{ $posts->links() }}
            </div>
        @endif
    </div>
@endsection
