@props([
    'status' => 'unknown',
])

@php
    $normalized = strtolower(trim($status));

    $styles = match($normalized) {
        'active', 'published', 'approved', 'completed', 'success' =>
            'bg-green-100 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-500/20',
        'pending', 'draft', 'review', 'processing' =>
            'bg-yellow-100 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400 dark:ring-yellow-500/20',
        'inactive', 'rejected', 'archived', 'cancelled', 'failed', 'deleted' =>
            'bg-red-100 text-red-700 ring-red-600/20 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-500/20',
        default =>
            'bg-sand-100 text-sand-700 ring-sand-600/20 dark:bg-sand-900/30 dark:text-sand-400 dark:ring-sand-500/20',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset $styles"]) }}>
    {{ ucfirst($normalized) }}
</span>
