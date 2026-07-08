@extends('blog::layouts.admin')

@php($isEdit = (bool) $block)

@section('page-title', $isEdit ? 'Edit Content Block' : 'New Content Block')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 transition">Dashboard</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <a href="{{ route('admin.content-blocks.index') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 transition">Content Blocks</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <span class="text-stone-500 dark:text-stone-400">{{ $isEdit ? 'Edit' : 'New' }}</span>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-stone-800 dark:text-stone-200">{{ $isEdit ? 'Edit Content Block' : 'New Content Block' }}</h1>
            @if($isEdit)
                <form method="POST" action="{{ route('admin.content-blocks.destroy', $block) }}" onsubmit="return confirm('Delete this content block?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">Delete</button>
                </form>
            @endif
        </div>

        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
            <form method="POST" action="{{ $isEdit ? route('admin.content-blocks.update', $block) : route('admin.content-blocks.store') }}">
                @csrf
                @if($isEdit) @method('PUT') @endif
                <input type="hidden" name="locale" value="{{ $locale }}">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                    <div>
                        <label for="name" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Name</label>
                        <input type="text" name="name" id="name" required value="{{ old('name', $isEdit ? $block->name : '') }}"
                               class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                               placeholder="e.g. Homepage — before featured">
                        @error('name')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="key" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Key</label>
                        <input type="text" name="key" id="key" value="{{ old('key', $isEdit ? $block->key : '') }}"
                               list="content-block-keys"
                               class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                               placeholder="homepage_before_featured">
                        <datalist id="content-block-keys">
                            <option value="homepage_before_featured">
                            <option value="homepage_after_featured">
                        </datalist>
                        <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">How the theme fetches it. Lowercase, hyphens/underscores.</p>
                        @error('key')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                </div>

                @include('blog::admin.partials.locale-switcher')

                {{-- Heading (translatable, optional) --}}
                <div class="mb-5">
                    <label for="title" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Heading <span class="text-stone-400">(optional)</span></label>
                    <input type="text" name="title" id="title" value="{{ old('title', $isEdit ? $block->getTranslation('title', $locale, false) : '') }}"
                           class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                </div>

                {{-- Content (translatable WYSIWYG) --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Content</label>
                    @include('blog::admin.partials.wysiwyg', [
                        'name' => 'content',
                        'value' => old('content', $isEdit ? $block->getTranslation('content', $locale, false) : ''),
                        'height' => 350,
                    ])
                    @error('content')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                {{-- Active + sort --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6 items-center">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $isEdit ? ($block->is_active ? '1' : '0') : '1') == '1' ? 'checked' : '' }}
                               class="rounded border-warm-300 dark:border-neutral-600 text-amber-600 focus:ring-amber-500">
                        <span class="text-sm font-medium text-stone-700 dark:text-stone-300">Active</span>
                    </label>
                    <div>
                        <label for="sort_order" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Sort order</label>
                        <input type="number" name="sort_order" id="sort_order" min="0" value="{{ old('sort_order', $isEdit ? $block->sort_order : 0) }}"
                               class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-warm-200 dark:border-neutral-700">
                    <a href="{{ route('admin.content-blocks.index') }}" class="px-4 py-2.5 bg-stone-200 dark:bg-neutral-700 text-stone-700 dark:text-stone-300 font-medium rounded-lg hover:bg-stone-300 dark:hover:bg-neutral-600 transition">Cancel</a>
                    <button type="submit" class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg shadow-sm transition">{{ $isEdit ? 'Update block' : 'Create block' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
