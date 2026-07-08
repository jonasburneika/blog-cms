{{-- List name / key / sort order. Expects $list (nullable). --}}
<div class="mb-5">
    <label for="name" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Name</label>
    <input type="text" name="name" id="name" value="{{ old('name', $list->name ?? '') }}" required
           class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
           placeholder="e.g. Footer">
    @error('name')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div>
        <label for="key" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Key</label>
        <input type="text" name="key" id="key" value="{{ old('key', $list->key ?? '') }}"
               class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
               placeholder="footer">
        <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">How the theme fetches it. Lowercase, hyphens. Auto-derived from the name if blank.</p>
        @error('key')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="sort_order" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Sort order</label>
        <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $list->sort_order ?? 0) }}" min="0"
               class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
        @error('sort_order')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>
</div>
