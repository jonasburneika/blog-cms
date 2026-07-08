@extends('blog::layouts.admin')

@php($isEdit = (bool) $link)

@section('page-title', $isEdit ? 'Edit Link' : 'Add Link')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 transition">Dashboard</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <a href="{{ route('admin.link-lists.index') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 transition">Link Lists</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <a href="{{ route('admin.link-lists.edit', $list) }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 transition">{{ $list->name }}</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <span class="text-stone-500 dark:text-stone-400">{{ $isEdit ? 'Edit link' : 'Add link' }}</span>
@endsection

@section('content')
    <div class="max-w-xl mx-auto">
        <h1 class="text-2xl font-bold text-stone-800 dark:text-stone-200 mb-6">{{ $isEdit ? 'Edit Link' : 'Add Link' }}</h1>

        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
            <form method="POST" action="{{ $isEdit ? route('admin.link-lists.links.update', [$list, $link]) : route('admin.link-lists.links.store', $list) }}">
                @csrf
                @if($isEdit) @method('PUT') @endif
                <input type="hidden" name="locale" value="{{ $locale }}">

                @include('blog::admin.partials.locale-switcher')

                {{-- Anchor text (translatable) --}}
                <div class="mb-5">
                    <label for="label" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Anchor text</label>
                    <input type="text" name="label" id="label" required
                           value="{{ old('label', $isEdit ? $link->getTranslation('label', $locale, false) : '') }}"
                           class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                           placeholder="e.g. About us">
                    @error('label')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                {{-- Internal page target --}}
                @include('blog::admin.partials.page-select', [
                    'name' => 'page',
                    'label' => 'Link to a page',
                    'selected' => $isEdit ? $link->pageValue() : '',
                    'placeholder' => '— None (use an external URL below) —',
                    'help' => 'Pick a post or category, OR leave this blank and enter an external URL.',
                ])

                {{-- External URL --}}
                <div class="mb-5">
                    <label for="url" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">External URL</label>
                    <input type="url" name="url" id="url" value="{{ old('url', $isEdit ? $link->url : '') }}"
                           class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                           placeholder="https://…">
                    <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Used only when no page is selected above.</p>
                    @error('url')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                    {{-- Target --}}
                    <div>
                        <label for="target" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Opens in (target)</label>
                        <select name="target" id="target"
                                class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                            @foreach(['_self' => 'Same tab (_self)', '_blank' => 'New tab (_blank)', '_parent' => '_parent', '_top' => '_top'] as $val => $lbl)
                                <option value="{{ $val }}" {{ old('target', $isEdit ? $link->target : '_self') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Sort order --}}
                    <div>
                        <label for="sort_order" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Sort order</label>
                        <input type="number" name="sort_order" id="sort_order" min="0" value="{{ old('sort_order', $isEdit ? $link->sort_order : 0) }}"
                               class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                    </div>
                </div>

                {{-- rel --}}
                <div class="mb-5">
                    <label for="rel" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">rel <span class="text-stone-400">(optional)</span></label>
                    <input type="text" name="rel" id="rel" value="{{ old('rel', $isEdit ? $link->rel : '') }}"
                           class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                           placeholder="e.g. nofollow sponsored">
                    <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Leave blank; external links opened in a new tab get <code>noopener noreferrer</code> automatically.</p>
                </div>

                {{-- Active --}}
                <label class="flex items-center gap-3 mb-6 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $isEdit ? ($link->is_active ? '1' : '0') : '1') == '1' ? 'checked' : '' }}
                           class="rounded border-warm-300 dark:border-neutral-600 text-amber-600 focus:ring-amber-500">
                    <span class="text-sm font-medium text-stone-700 dark:text-stone-300">Active</span>
                </label>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-warm-200 dark:border-neutral-700">
                    <a href="{{ route('admin.link-lists.edit', $list) }}" class="px-4 py-2.5 bg-stone-200 dark:bg-neutral-700 text-stone-700 dark:text-stone-300 font-medium rounded-lg hover:bg-stone-300 dark:hover:bg-neutral-600 transition">Cancel</a>
                    <button type="submit" class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg shadow-sm transition">{{ $isEdit ? 'Update link' : 'Add link' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
