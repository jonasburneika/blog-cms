@extends('blog::layouts.admin')

@section('page-title', 'Create Category')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">Dashboard</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <a href="{{ route('admin.categories.index') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">Categories</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <span class="text-stone-500 dark:text-stone-400">Create</span>
@endsection

@section('content')
    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold text-stone-800 dark:text-stone-200 mb-6">Create Category</h1>

        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
            <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">
                @csrf

                {{-- Title --}}
                <div class="mb-5">
                    <label for="title" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Title</label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required
                           class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                           placeholder="Category title (used as page heading)">
                    @error('title')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Menu Title --}}
                <div class="mb-5">
                    <label for="menu_title" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Menu Title</label>
                    <input type="text" name="menu_title" id="menu_title" value="{{ old('menu_title') }}"
                           class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                           placeholder="Short title for navigation menus">
                    <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Shorter version for menus/navigation. Falls back to Title if empty.</p>
                    @error('menu_title')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Meta Title --}}
                <div class="mb-5">
                    <label for="meta_title" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Meta Title</label>
                    <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title') }}"
                           class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                           placeholder="SEO title for browser tab and search results">
                    <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Falls back to Title if empty. Recommended: 50-60 characters.</p>
                    @error('meta_title')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="mb-5">
                    <label for="description" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Description</label>
                    <textarea name="description" id="description" rows="3"
                              class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition resize-y"
                              placeholder="Brief description of the category">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Cover Images --}}
                <div class="mb-5" x-data="{ desktopPreview: null, mobilePreview: null }">
                    <h3 class="text-sm font-semibold text-stone-700 dark:text-stone-300 mb-3">Cover Images</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Desktop Cover --}}
                        <div>
                            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Desktop Cover</label>
                            <div class="relative border-2 border-dashed border-warm-300 dark:border-neutral-600 rounded-lg p-4 text-center hover:border-amber-400 dark:hover:border-amber-600 transition cursor-pointer"
                                 @click="$refs.coverDesktop.click()">
                                <template x-if="desktopPreview">
                                    <img :src="desktopPreview" class="w-full h-32 object-cover rounded-lg mb-2">
                                </template>
                                <template x-if="!desktopPreview">
                                    <div class="py-4">
                                        <svg class="w-8 h-8 mx-auto text-stone-400 dark:text-stone-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <p class="text-xs text-stone-500 dark:text-stone-400">Click to upload desktop cover</p>
                                    </div>
                                </template>
                                <input type="file" name="cover_desktop" accept="image/*" class="hidden" x-ref="coverDesktop"
                                       @change="desktopPreview = URL.createObjectURL($event.target.files[0])">
                            </div>
                            @error('cover_desktop')
                                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Mobile Cover --}}
                        <div>
                            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Mobile Cover</label>
                            <div class="relative border-2 border-dashed border-warm-300 dark:border-neutral-600 rounded-lg p-4 text-center hover:border-amber-400 dark:hover:border-amber-600 transition cursor-pointer"
                                 @click="$refs.coverMobile.click()">
                                <template x-if="mobilePreview">
                                    <img :src="mobilePreview" class="w-full h-32 object-cover rounded-lg mb-2">
                                </template>
                                <template x-if="!mobilePreview">
                                    <div class="py-4">
                                        <svg class="w-8 h-8 mx-auto text-stone-400 dark:text-stone-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        <p class="text-xs text-stone-500 dark:text-stone-400">Click to upload mobile cover</p>
                                    </div>
                                </template>
                                <input type="file" name="cover_mobile" accept="image/*" class="hidden" x-ref="coverMobile"
                                       @change="mobilePreview = URL.createObjectURL($event.target.files[0])">
                            </div>
                            @error('cover_mobile')
                                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Status --}}
                <div class="mb-5">
                    <label for="status" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Status</label>
                    <select name="status" id="status"
                            class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Sort Order --}}
                <div class="mb-6">
                    <label for="sort_order" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Sort Order</label>
                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                           class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                           placeholder="0">
                    @error('sort_order')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-warm-200 dark:border-neutral-700">
                    <a href="{{ route('admin.categories.index') }}"
                       class="px-4 py-2.5 bg-stone-200 dark:bg-neutral-700 text-stone-700 dark:text-stone-300 font-medium rounded-lg hover:bg-stone-300 dark:hover:bg-neutral-600 transition">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
                        Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
