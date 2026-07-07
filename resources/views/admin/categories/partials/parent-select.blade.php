{{--
    Parent category picker. "Root" (empty value) makes the category top-level.
    Options are labelled with their ancestry path (e.g. "Guides › Beginner") so
    the hierarchy is readable in a flat <select>. The controller excludes the
    category itself and its descendants from $parentOptions to prevent cycles.

    Expects: $parentOptions (Collection<BlogCategory>), $selected (id|null), $locale
--}}
@php
    $byId = $parentOptions->keyBy('id');

    // Walk up the ancestor chain (kept within the loaded collection) to build a
    // breadcrumb-style label. Ancestors are never excluded, so lookups resolve.
    $pathLabel = function ($category) use ($byId, $locale) {
        $parts = [];
        $node = $category;
        $guard = 0;
        while ($node && $guard++ < 20) {
            $parts[] = $node->getTranslation('title', $locale, false) ?: ('#' . $node->id);
            $node = $node->parent_id ? $byId->get($node->parent_id) : null;
        }
        return implode(' › ', array_reverse($parts));
    };
@endphp

<div class="mb-5">
    <label for="parent_id" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Parent Category</label>
    <select name="parent_id" id="parent_id"
            class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
        <option value="">— Root (top level) —</option>
        @foreach ($parentOptions as $option)
            <option value="{{ $option->id }}" {{ (string) $selected === (string) $option->id ? 'selected' : '' }}>
                {{ $pathLabel($option) }}
            </option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Choose a parent to nest this category, or “Root” to keep it top-level.</p>
    @error('parent_id')
        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
