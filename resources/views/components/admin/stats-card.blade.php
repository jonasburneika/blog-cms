@props([
    'title' => '',
    'value' => '0',
    'icon' => '',
    'trend' => 'neutral',
    'trendValue' => '',
    'color' => 'warm',
])

@php
    $colorMap = [
        'warm' => [
            'bg'   => 'bg-warm-100 dark:bg-warm-900/30',
            'icon' => 'text-warm-600 dark:text-warm-400',
            'ring' => 'ring-warm-200 dark:ring-warm-800',
        ],
        'earth' => [
            'bg'   => 'bg-earth-100 dark:bg-earth-900/30',
            'icon' => 'text-earth-600 dark:text-earth-400',
            'ring' => 'ring-earth-200 dark:ring-earth-800',
        ],
        'sand' => [
            'bg'   => 'bg-sand-100 dark:bg-sand-900/30',
            'icon' => 'text-sand-600 dark:text-sand-400',
            'ring' => 'ring-sand-200 dark:ring-sand-800',
        ],
    ];
    $c = $colorMap[$color] ?? $colorMap['warm'];

    $trendColors = [
        'up'      => 'text-green-600 dark:text-green-400',
        'down'    => 'text-red-600 dark:text-red-400',
        'neutral' => 'text-sand-500 dark:text-sand-400',
    ];
    $trendColor = $trendColors[$trend] ?? $trendColors['neutral'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-sand-200 dark:border-neutral-700 p-5 transition-shadow hover:shadow-md']) }}>
    <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-sand-500 dark:text-sand-400 truncate">{{ $title }}</p>
            <p class="mt-2 text-2xl font-bold text-warm-900 dark:text-warm-100">{{ $value }}</p>

            @if($trendValue)
                <div class="mt-2 flex items-center gap-1 {{ $trendColor }}">
                    @if($trend === 'up')
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    @elseif($trend === 'down')
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                    @endif
                    <span class="text-xs font-medium">{{ $trendValue }}</span>
                </div>
            @endif
        </div>

        @if($icon)
            <div class="flex-shrink-0 w-11 h-11 rounded-xl {{ $c['bg'] }} ring-1 {{ $c['ring'] }} flex items-center justify-center {{ $c['icon'] }}">
                {!! $icon !!}
            </div>
        @endif
    </div>
</div>
