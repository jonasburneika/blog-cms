{{--
    Reusable page picker (categories + posts), grouped with <optgroup>.
    Params:
      $name        form field name
      $label       label text
      $selected    currently-selected "type:id" value (string, may be '')
      $placeholder first blank option label
      $help        optional helper text
      $pages       optional pre-resolved options; defaults to PageUrl::options()
--}}
@php
    $pages = $pages ?? \BlogCms\Support\PageUrl::options();
    $fieldId = $id ?? $name;
    $current = old($name, $selected ?? '');
@endphp

<div class="mb-5">
    <label for="{{ $fieldId }}" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $fieldId }}"
            class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
        <option value="">{{ $placeholder ?? '— Select a page —' }}</option>
        @foreach($pages as $group => $items)
            <optgroup label="{{ $group }}">
                @foreach($items as $opt)
                    <option value="{{ $opt['value'] }}" {{ (string) $current === $opt['value'] ? 'selected' : '' }}>
                        {{ $opt['label'] }}
                    </option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
    @isset($help)
        <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">{{ $help }}</p>
    @endisset
    @error($name)
        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
