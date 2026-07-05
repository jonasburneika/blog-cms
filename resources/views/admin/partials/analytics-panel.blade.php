@php
    $analytics = app(\BlogCms\Services\AnalyticsService::class);
    $ga4Data = $analytics->getPageViews($analyticsPath ?? '/');
    $gscData = $analytics->getSearchQueries($analyticsPath ?? '/');
@endphp

<div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6 mt-6">
    <h3 class="text-lg font-semibold text-stone-800 dark:text-stone-200 mb-4">Analytics (Last 30 Days)</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- GA4 Panel --}}
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

        {{-- GSC Panel --}}
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
