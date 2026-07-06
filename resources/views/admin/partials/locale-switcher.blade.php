{{--
    Language switcher for content forms. Editing is one language at a time:
    clicking a language reloads the form showing that locale's values. The
    active $locale is submitted as a hidden field so the save targets it.
    Requires: $locale (current), $blogLocales + $blogDefaultLocale (shared).
--}}
@if(count($blogLocales ?? []) > 1)
    <div class="mb-6 flex flex-wrap items-center gap-2 p-3 bg-warm-50 dark:bg-neutral-900 border border-warm-200 dark:border-neutral-700 rounded-lg">
        <span class="text-xs font-semibold text-stone-400 dark:text-stone-500 uppercase tracking-wide mr-1">Editing language:</span>
        @foreach($blogLocales as $loc)
            <a href="{{ request()->fullUrlWithQuery(['locale' => $loc]) }}"
               class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-lg border transition
                      {{ $loc === $locale
                          ? 'bg-amber-600 text-white border-amber-600'
                          : 'bg-white dark:bg-neutral-800 text-stone-600 dark:text-stone-300 border-warm-300 dark:border-neutral-600 hover:bg-warm-100 dark:hover:bg-neutral-700' }}">
                {{ strtoupper($loc) }}
                @if($loc === ($blogDefaultLocale ?? null))
                    <span class="text-[10px] opacity-70">default</span>
                @endif
            </a>
        @endforeach
        <span class="w-full sm:w-auto sm:ml-2 text-xs text-stone-400 dark:text-stone-500">Save before switching — unsaved changes in the current language are lost.</span>
    </div>
@endif
