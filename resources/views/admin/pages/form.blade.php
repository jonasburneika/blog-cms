@extends('blog::layouts.admin')

@php($isEdit = (bool) $page)
@php($tv = fn ($f) => old($f, $isEdit ? $page->getTranslation($f, $locale, false) : ''))

@section('page-title', $isEdit ? 'Edit Page' : 'New Page')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 transition">Dashboard</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <a href="{{ route('admin.pages.index') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 transition">Pages</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <span class="text-stone-500 dark:text-stone-400">{{ $isEdit ? 'Edit' : 'New' }}</span>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-stone-800 dark:text-stone-200">{{ $isEdit ? 'Edit Page' : 'New Page' }}</h1>
            @if($isEdit)
                <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" onsubmit="return confirm('Delete this page?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">Delete</button>
                </form>
            @endif
        </div>

        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
            <form method="POST" action="{{ $isEdit ? route('admin.pages.update', $page) : route('admin.pages.store') }}">
                @csrf
                @if($isEdit) @method('PUT') @endif
                <input type="hidden" name="locale" value="{{ $locale }}">

                @include('blog::admin.partials.locale-switcher')

                {{-- Title --}}
                <div class="mb-5">
                    <label for="title" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Title</label>
                    <input type="text" name="title" id="title" required value="{{ $tv('title') }}"
                           class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                           placeholder="e.g. Privacy Policy">
                    @error('title')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                {{-- Slug --}}
                <div class="mb-5">
                    <label for="slug" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Slug <span class="text-stone-400">(optional)</span></label>
                    <input type="text" name="slug" id="slug" value="{{ $tv('slug') }}"
                           class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                           placeholder="Auto-generated from the title if blank">
                    @error('slug')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                {{-- Content (WYSIWYG) --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Content</label>
                    @include('blog::admin.partials.wysiwyg', ['name' => 'content', 'value' => $tv('content'), 'height' => 450])
                    @error('content')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                {{-- Meta title/description --}}
                <div class="grid grid-cols-1 gap-4 mb-5">
                    <div>
                        <label for="meta_title" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Meta title <span class="text-stone-400">(optional)</span></label>
                        <input type="text" name="meta_title" id="meta_title" value="{{ $tv('meta_title') }}"
                               class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                    </div>
                    <div>
                        <label for="meta_description" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Meta description <span class="text-stone-400">(optional)</span></label>
                        <textarea name="meta_description" id="meta_description" rows="2"
                                  class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition resize-y">{{ $tv('meta_description') }}</textarea>
                    </div>
                </div>

                {{-- Status / indexable / sort --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 items-end">
                    <div>
                        <label for="status" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Status</label>
                        <select name="status" id="status"
                                class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                            <option value="draft" {{ old('status', $isEdit ? $page->status : 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status', $isEdit ? $page->status : 'draft') === 'published' ? 'selected' : '' }}>Published</option>
                        </select>
                    </div>
                    <div>
                        <label for="sort_order" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Sort order</label>
                        <input type="number" name="sort_order" id="sort_order" min="0" value="{{ old('sort_order', $isEdit ? $page->sort_order : 0) }}"
                               class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                    </div>
                    <label class="flex items-center gap-3 cursor-pointer pb-2.5">
                        <input type="hidden" name="is_indexable" value="0">
                        <input type="checkbox" name="is_indexable" value="1" {{ old('is_indexable', $isEdit ? ($page->is_indexable ? '1' : '0') : '1') == '1' ? 'checked' : '' }}
                               class="rounded border-warm-300 dark:border-neutral-600 text-amber-600 focus:ring-amber-500">
                        <span class="text-sm font-medium text-stone-700 dark:text-stone-300">Indexable</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-warm-200 dark:border-neutral-700">
                    <a href="{{ route('admin.pages.index') }}" class="px-4 py-2.5 bg-stone-200 dark:bg-neutral-700 text-stone-700 dark:text-stone-300 font-medium rounded-lg hover:bg-stone-300 dark:hover:bg-neutral-600 transition">Cancel</a>
                    <button type="submit" class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg shadow-sm transition">{{ $isEdit ? 'Update page' : 'Create page' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
