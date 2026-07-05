@extends('blog::layouts.admin')

@section('page-title', 'Edit Post')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">Dashboard</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <a href="{{ route('admin.posts.index') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">Blog Posts</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <span class="text-stone-500 dark:text-stone-400">Edit</span>
@endsection

@section('content')
    <div x-data="postEditor"
         data-post-id="{{ $post->id }}"
>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <h1 class="text-2xl font-bold text-stone-800 dark:text-stone-200">Edit Post</h1>
            <button type="button" @click="showDeleteModal = true"
                    class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Delete Post
            </button>
        </div>

        {{-- Delete Confirmation Modal --}}
        <div x-show="showDeleteModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showDeleteModal = false"></div>
                <div class="relative bg-white dark:bg-neutral-800 rounded-xl shadow-xl border border-warm-200 dark:border-neutral-700 max-w-md w-full p-6 z-10">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-stone-800 dark:text-stone-200">Delete Post</h3>
                    </div>
                    <p class="text-stone-600 dark:text-stone-400 mb-6">Are you sure you want to delete "<strong>{{ $post->title }}</strong>"? This action cannot be undone.</p>
                    <div class="flex items-center justify-end gap-3">
                        <button type="button" @click="showDeleteModal = false"
                                class="px-4 py-2 bg-stone-200 dark:bg-neutral-700 text-stone-700 dark:text-stone-300 font-medium rounded-lg hover:bg-stone-300 dark:hover:bg-neutral-600 transition">
                            Cancel
                        </button>
                        <form method="POST" action="{{ route('admin.posts.destroy', $post) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Validation Errors Summary --}}
        @if($errors->any())
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-300">There were errors with your submission</h3>
                        <ul class="mt-2 text-sm text-red-700 dark:text-red-400 list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.posts.update', $post) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Tab Navigation --}}
            <div class="mb-6 border-b border-warm-200 dark:border-neutral-700">
                <nav class="flex gap-1 -mb-px">
                    <button type="button" @click="activeTab = 'main'"
                            :class="activeTab === 'main'
                                ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                                : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                            class="px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition">
                        Main Info
                    </button>
                    <button type="button" @click="activeTab = 'content'"
                            :class="activeTab === 'content'
                                ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                                : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                            class="px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition">
                        Content
                    </button>
                    <button type="button" @click="activeTab = 'seo'"
                            :class="activeTab === 'seo'
                                ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                                : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                            class="px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition">
                        SEO
                    </button>
                    <button type="button" @click="activeTab = 'faq'"
                            :class="activeTab === 'faq'
                                ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                                : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                            class="px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition">
                        FAQ
                    </button>
                    <button type="button" @click="activeTab = 'howto'"
                            :class="activeTab === 'howto'
                                ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                                : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                            class="px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition">
                        HowTo
                    </button>
                    <button type="button" @click="activeTab = 'hooks'"
                            :class="activeTab === 'hooks'
                                ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                                : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                            class="px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition">
                        Hooks
                    </button>
                    <button type="button" @click="activeTab = 'citations'"
                            :class="activeTab === 'citations'
                                ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                                : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                            class="px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition">
                        Citations
                    </button>
                    <button type="button" @click="activeTab = 'topics'"
                            :class="activeTab === 'topics'
                                ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                                : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                            class="px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition">
                        About / Topics
                    </button>
                    <button type="button" @click="activeTab = 'basedon'"
                            :class="activeTab === 'basedon'
                                ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                                : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                            class="px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition">
                        Based On
                    </button>
                </nav>
            </div>

            {{-- Main Info Tab --}}
            <div x-show="activeTab === 'main'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {{-- Title --}}
                        <div class="lg:col-span-2">
                            <label for="title" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Title</label>
                            <input type="text" name="title" id="title" value="{{ old('title', $post->title) }}" required
                                   class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                                   placeholder="Post title">
                            @error('title')
                                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Category --}}
                        <div>
                            <label for="blog_category_id" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Category</label>
                            <select name="blog_category_id" id="blog_category_id"
                                    class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                                <option value="">Select a category</option>
                                @foreach($categories ?? [] as $category)
                                    <option value="{{ $category->id }}" {{ old('blog_category_id', $post->blog_category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('blog_category_id')
                                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Status --}}
                        <div>
                            <label for="status" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Status</label>
                            <select name="status" id="status"
                                    class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                                <option value="0" {{ (int) old('status', $post->status) === 0 ? 'selected' : '' }}>Draft</option>
                                <option value="1" {{ (int) old('status', $post->status) === 1 ? 'selected' : '' }}>Published</option>
                                <option value="2" {{ (int) old('status', $post->status) === 2 ? 'selected' : '' }}>Archived</option>
                            </select>
                            @error('status')
                                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Excerpt --}}
                        <div class="lg:col-span-2">
                            <label for="excerpt" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Excerpt</label>
                            <textarea name="excerpt" id="excerpt" rows="3"
                                      class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition resize-y"
                                      placeholder="Brief summary of the post">{{ old('excerpt', $post->excerpt) }}</textarea>
                            @error('excerpt')
                                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Cover Images --}}
                        <div class="lg:col-span-2" x-data="{
                            desktopPreview: '{{ $post->getFirstMediaUrl('cover_desktop', 'preview') }}' || null,
                            mobilePreview: '{{ $post->getFirstMediaUrl('cover_mobile', 'preview') }}' || null
                        }">
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

                        {{-- Published At --}}
                        <div>
                            <label for="published_at" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Published At</label>
                            <input type="datetime-local" name="published_at" id="published_at"
                                   value="{{ old('published_at', $post->published_at ? $post->published_at->format('Y-m-d\TH:i') : '') }}"
                                   class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                            @error('published_at')
                                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Is Featured --}}
                        <div class="flex items-center pt-7">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="is_featured" value="0">
                                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $post->is_featured) ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-stone-200 dark:bg-neutral-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-neutral-600 peer-checked:bg-amber-600"></div>
                                <span class="ml-3 text-sm font-medium text-stone-700 dark:text-stone-300">Featured Post</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Content Tab --}}
            <div x-show="activeTab === 'content'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
                    <label for="content" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Content</label>
                    <div x-data="{
                        editor: null,
                        init() {
                            this.editor = Jodit.make(this.$refs.contentEditor, {
                                height: 500,
                                toolbarAdaptive: false,
                                showCharsCounter: false,
                                showWordsCounter: false,
                                showXPathInStatusbar: false,
                                askBeforePasteHTML: false,
                                askBeforePasteFromWord: false,
                                defaultActionOnPaste: 'insert_clear_html',
                                buttons: [
                                    'bold', 'italic', 'underline', 'strikethrough', '|',
                                    'ul', 'ol', '|',
                                    'paragraph', 'fontsize', '|',
                                    'link', 'image', 'video', 'table', '|',
                                    'align', '|',
                                    'hr', 'eraser', 'undo', 'redo', '|',
                                    'source'
                                ]
                            });
                            this.editor.value = this.$refs.contentHidden.value || '';
                            this.editor.events.on('change', () => {
                                this.$refs.contentHidden.value = this.editor.value;
                            });
                        }
                    }">
                        <textarea x-ref="contentEditor" style="display:none;"></textarea>
                        <input type="hidden" name="content" x-ref="contentHidden" value="{{ old('content', $post->content) }}">
                    </div>
                    @error('content')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- SEO Tab --}}
            <div x-show="activeTab === 'seo'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
                    <div class="mb-5">
                        <label for="meta_title" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Meta Title</label>
                        <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title', $post->meta_title) }}"
                               class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                               placeholder="SEO title (leave empty to use post title)">
                        @error('meta_title')
                            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <label for="meta_description" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Meta Description</label>
                        <textarea name="meta_description" id="meta_description" rows="3"
                                  class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition resize-y"
                                  placeholder="Brief description for search engines (150-160 characters recommended)">{{ old('meta_description', $post->meta_description) }}</textarea>
                        @error('meta_description')
                            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Slug Preview</label>
                        <div class="px-4 py-2.5 bg-stone-100 dark:bg-neutral-900 border border-stone-200 dark:border-neutral-700 rounded-lg text-sm text-stone-500 dark:text-stone-400">
                            <span class="text-stone-400 dark:text-stone-500">/blog/</span><span class="text-stone-600 dark:text-stone-300">{{ $post->slug }}</span>
                        </div>
                        <p class="mt-1.5 text-xs text-stone-400 dark:text-stone-500">The slug is generated from the post title and cannot be manually changed.</p>
                    </div>
                </div>
            </div>

            {{-- FAQ Tab --}}
            <div x-show="activeTab === 'faq'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
                    {{-- FAQ Title --}}
                    <div class="mb-6 pb-6 border-b border-warm-200 dark:border-neutral-700">
                        <label for="faq_title" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">FAQ Section Title</label>
                        <input type="text" name="faq_title" id="faq_title" value="{{ old('faq_title', $post->faq_title) }}"
                               class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                               placeholder="e.g. Frequently Asked Questions About Beer Brewing">
                        <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Heading displayed above FAQ section. Defaults to "Frequently Asked Questions" if empty.</p>
                        @error('faq_title')
                            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-stone-800 dark:text-stone-200">FAQ Items</h3>
                            <p class="text-sm text-stone-500 dark:text-stone-400 mt-1">Manage FAQ items via AJAX — changes save instantly.</p>
                        </div>
                        <button type="button" @click="addFaq()"
                                class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add FAQ
                        </button>
                    </div>

                    {{-- Success/Error messages --}}
                    <div x-show="faqMessage" x-transition class="mb-4 px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-700 dark:text-green-400" x-text="faqMessage"></div>
                    <div x-show="faqError" x-transition class="mb-4 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-400" x-text="faqError"></div>

                    {{-- Loading state --}}
                    <div x-show="faqLoading" class="text-center py-12">
                        <svg class="w-8 h-8 mx-auto text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="text-stone-500 dark:text-stone-400 mt-2">Loading FAQs...</p>
                    </div>

                    {{-- Empty state --}}
                    <div x-show="!faqLoading && faqs.length === 0" class="text-center py-12">
                        <svg class="w-12 h-12 mx-auto text-stone-300 dark:text-stone-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-stone-500 dark:text-stone-400">No FAQ items yet. Click "Add FAQ" to get started.</p>
                    </div>

                    {{-- FAQ items --}}
                    <div x-show="!faqLoading" class="space-y-4">
                        <template x-for="(faq, index) in faqs" :key="index">
                            <div class="border border-warm-200 dark:border-neutral-600 rounded-lg p-4 bg-warm-50/50 dark:bg-neutral-900/50">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-bold" x-text="index + 1"></span>
                                        <span x-show="faq.id" class="text-xs text-green-600 dark:text-green-400 font-medium">Saved</span>
                                        <span x-show="!faq.id" class="text-xs text-amber-600 dark:text-amber-400 font-medium">New — unsaved</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="saveFaq(index)" :disabled="faqSaving"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/40 transition disabled:opacity-50">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Save
                                        </button>
                                        <button type="button" @click="deleteFaq(index)"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Question</label>
                                    <input type="text" x-model="faq.question"
                                           class="w-full px-4 py-2.5 bg-white dark:bg-neutral-800 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                                           placeholder="Enter the question...">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Answer</label>
                                    <textarea x-model="faq.answer" rows="3"
                                              class="w-full px-4 py-2.5 bg-white dark:bg-neutral-800 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition resize-y"
                                              placeholder="Enter the answer..."></textarea>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- HowTo Steps Tab --}}
            <div x-show="activeTab === 'howto'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
                    {{-- HowTo Title for Schema.org --}}
                    <div class="mb-6 pb-6 border-b border-warm-200 dark:border-neutral-700">
                        <label for="howto_title" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">HowTo Title</label>
                        <input type="text" name="howto_title" id="howto_title" value="{{ old('howto_title', $post->howto_title) }}"
                               class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                               placeholder="e.g. How to Tie a Tie">
                        <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Used as the Schema.org HowTo "name" property. Leave empty to use the post title.</p>
                        @error('howto_title')
                            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- HowTo Description --}}
                    <div class="mb-6 pb-6 border-b border-warm-200 dark:border-neutral-700">
                        <label for="howto_description" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Short Description</label>
                        <textarea name="howto_description" id="howto_description" rows="2"
                                  class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition resize-y"
                                  placeholder="Brief description of what this how-to guide covers">{{ old('howto_description', $post->howto_description) }}</textarea>
                        <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Used as the Schema.org HowTo "description" property.</p>
                        @error('howto_description')
                            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-stone-800 dark:text-stone-200">HowTo Steps</h3>
                            <p class="text-sm text-stone-500 dark:text-stone-400 mt-1">Manage step-by-step instructions via AJAX — changes save instantly. Useful for Schema.org HowTo markup.</p>
                        </div>
                        <button type="button" @click="addHowToStep()"
                                class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Step
                        </button>
                    </div>

                    {{-- Success/Error messages --}}
                    <div x-show="howtoMessage" x-transition class="mb-4 px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-700 dark:text-green-400" x-text="howtoMessage"></div>
                    <div x-show="howtoError" x-transition class="mb-4 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-400" x-text="howtoError"></div>

                    {{-- Loading state --}}
                    <div x-show="howtoLoading" class="text-center py-12">
                        <svg class="w-8 h-8 mx-auto text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="text-stone-500 dark:text-stone-400 mt-2">Loading steps...</p>
                    </div>

                    {{-- Empty state --}}
                    <div x-show="!howtoLoading && howtoSteps.length === 0" class="text-center py-12">
                        <svg class="w-12 h-12 mx-auto text-stone-300 dark:text-stone-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        <p class="text-stone-500 dark:text-stone-400">No steps yet. Click "Add Step" to create a how-to guide.</p>
                    </div>

                    {{-- Step items --}}
                    <div x-show="!howtoLoading" class="space-y-4">
                        <template x-for="(step, index) in howtoSteps" :key="index">
                            <div class="border border-warm-200 dark:border-neutral-600 rounded-lg p-4 bg-warm-50/50 dark:bg-neutral-900/50">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-sm font-bold" x-text="index + 1"></span>
                                        <span x-show="step.id" class="text-xs text-green-600 dark:text-green-400 font-medium">Saved</span>
                                        <span x-show="!step.id" class="text-xs text-amber-600 dark:text-amber-400 font-medium">New — unsaved</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="saveHowToStep(index)" :disabled="howtoSaving"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/40 transition disabled:opacity-50">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Save
                                        </button>
                                        <button type="button" @click="deleteHowToStep(index)"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </div>

                                {{-- Step Title --}}
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Step Title</label>
                                    <input type="text" x-model="step.title"
                                           class="w-full px-4 py-2.5 bg-white dark:bg-neutral-800 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                                           placeholder="e.g. Preheat the oven to 180°C">
                                </div>

                                {{-- Step Content --}}
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Instructions</label>
                                    <textarea x-model="step.content" rows="3"
                                              class="w-full px-4 py-2.5 bg-white dark:bg-neutral-800 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition resize-y"
                                              placeholder="Detailed instructions for this step..."></textarea>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    {{-- Image URL --}}
                                    <div>
                                        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Image URL</label>
                                        <input type="text" x-model="step.image"
                                               class="w-full px-4 py-2.5 bg-white dark:bg-neutral-800 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition text-sm"
                                               placeholder="https://... (optional)">
                                    </div>

                                    {{-- Tip --}}
                                    <div>
                                        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Tip / Note</label>
                                        <input type="text" x-model="step.tip"
                                               class="w-full px-4 py-2.5 bg-white dark:bg-neutral-800 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition text-sm"
                                               placeholder="Pro tip... (optional)">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Hooks Tab --}}
            <div x-show="activeTab === 'hooks'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-stone-800 dark:text-stone-200">Content Hooks</h3>
                            <p class="text-sm text-stone-500 dark:text-stone-400 mt-1">Insert hooks between content sections — ads, CTAs, related posts, newsletters, and more.</p>
                        </div>
                        <button type="button" @click="openAddHook()"
                                class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Hook
                        </button>
                    </div>

                    {{-- Success/Error messages --}}
                    <div x-show="hookMessage" x-transition class="mb-4 px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-700 dark:text-green-400" x-text="hookMessage"></div>
                    <div x-show="hookError" x-transition class="mb-4 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-400" x-text="hookError"></div>

                    {{-- Loading state --}}
                    <div x-show="hookLoading" class="text-center py-12">
                        <svg class="w-8 h-8 mx-auto text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="text-stone-500 dark:text-stone-400 mt-2">Loading hooks...</p>
                    </div>

                    {{-- Hooks are inserted into the post content --}}

                    {{-- Hooks List --}}
                    <div x-show="!hookLoading">
                        <h4 class="text-sm font-semibold text-stone-700 dark:text-stone-300 mb-3">Placed Hooks</h4>

                        {{-- Empty state --}}
                        <div x-show="hooks.length === 0" class="text-center py-10">
                            <svg class="w-12 h-12 mx-auto text-stone-300 dark:text-stone-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            <p class="text-stone-500 dark:text-stone-400">No hooks placed yet. Use the section map above or click "Add Hook" to get started.</p>
                        </div>

                        {{-- Hooks grid --}}
                        <div x-show="hooks.length > 0" class="space-y-3">
                            <template x-for="hook in hooks" :key="hook.id">
                                <div class="border border-warm-200 dark:border-neutral-600 rounded-lg p-4 bg-warm-50/50 dark:bg-neutral-900/50">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1.5">
                                                <h5 class="text-sm font-semibold text-stone-800 dark:text-stone-200 truncate" x-text="hook.name"></h5>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                      :class="hookTypeBadgeClass(hook.type)" x-text="hook.type ? hook.type.replace('_', ' ') : 'custom'"></span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                      :class="hook.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-stone-100 text-stone-500 dark:bg-stone-800 dark:text-stone-500'"
                                                      x-text="hook.is_active ? 'Active' : 'Inactive'"></span>
                                            </div>
                                            <div class="flex items-center gap-3 text-xs text-stone-500 dark:text-stone-400">
                                                <span x-text="positionLabel(hook.position)"></span>
                                                <span x-show="hook.hook_template_id">
                                                    &middot; Template: <span x-text="(hookTemplates.find(t => t.id == hook.hook_template_id) || {}).name || 'Unknown'" class="font-medium"></span>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5 ml-3 flex-shrink-0">
                                            <button type="button" @click="previewHook(hook)"
                                                    class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition"
                                                    title="Preview">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </button>
                                            <button type="button" @click="openEditHook(hook)"
                                                    class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/40 transition"
                                                    title="Edit">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                            <button type="button" @click="deleteHook(hook)"
                                                    class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition"
                                                    title="Delete">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Add/Edit Hook Modal --}}
                <div x-show="showHookModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                    <div class="flex items-center justify-center min-h-screen px-4 py-8">
                        <div class="fixed inset-0 bg-black/50" @click="showHookModal = false"></div>
                        <div class="relative bg-white dark:bg-neutral-800 rounded-xl shadow-xl border border-warm-200 dark:border-neutral-700 max-w-2xl w-full p-6 z-10 max-h-[90vh] overflow-y-auto">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-semibold text-stone-800 dark:text-stone-200" x-text="editingHook ? 'Edit Hook' : 'Add Hook'"></h3>
                                <button type="button" @click="showHookModal = false" class="p-1.5 rounded-lg text-stone-400 hover:text-stone-600 dark:hover:text-stone-300 hover:bg-stone-100 dark:hover:bg-neutral-700 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            {{-- Template selector --}}
                            <div class="mb-5">
                                <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Template</label>
                                <select x-model="hookForm.hook_template_id" @change="onTemplateSelect()"
                                        class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                                    <option value="">-- Custom (no template) --</option>
                                    <template x-for="tpl in hookTemplates" :key="tpl.id">
                                        <option :value="tpl.id" x-text="tpl.name + ' (' + (tpl.type || 'custom') + ')'"></option>
                                    </template>
                                </select>
                                <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Select a template or create a custom hook.</p>
                            </div>

                            {{-- Name --}}
                            <div class="mb-5">
                                <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Name <span class="text-red-500">*</span></label>
                                <input type="text" x-model="hookForm.name"
                                       class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                                       placeholder="e.g. Newsletter Signup, Ad Block 1">
                            </div>

                            {{-- Position --}}
                            <div class="mb-5">
                                <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Position</label>
                                <input type="number" x-model.number="hookForm.position" min="0"
                                       class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                                       placeholder="0 = before content, 1 = after content">
                            </div>

                            {{-- HTML Content --}}
                            <div class="mb-5">
                                <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">HTML Content</label>
                                <textarea x-model="hookForm.html_content" rows="10"
                                          class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition resize-y font-mono text-sm"
                                          placeholder="Enter custom HTML content, or leave empty to use template default..."></textarea>
                                <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Leave empty to use the template's default HTML. Fill in to override.</p>
                            </div>

                            {{-- Active toggle --}}
                            <div class="mb-6">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="hookForm.is_active" class="sr-only peer">
                                    <div class="w-11 h-6 bg-stone-200 dark:bg-neutral-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-neutral-600 peer-checked:bg-amber-600"></div>
                                    <span class="ml-3 text-sm font-medium text-stone-700 dark:text-stone-300">Active</span>
                                </label>
                            </div>

                            {{-- Modal actions --}}
                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-warm-200 dark:border-neutral-700">
                                <button type="button" @click="showHookModal = false"
                                        class="px-4 py-2 bg-stone-200 dark:bg-neutral-700 text-stone-700 dark:text-stone-300 font-medium rounded-lg hover:bg-stone-300 dark:hover:bg-neutral-600 transition">
                                    Cancel
                                </button>
                                <button type="button" @click="saveHook()" :disabled="hookSaving"
                                        class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg shadow-sm transition disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
                                    <span x-show="!hookSaving" x-text="editingHook ? 'Update Hook' : 'Add Hook'"></span>
                                    <span x-show="hookSaving">Saving...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Preview Modal --}}
                <div x-show="showPreview" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                    <div class="flex items-center justify-center min-h-screen px-4 py-8">
                        <div class="fixed inset-0 bg-black/50" @click="showPreview = false"></div>
                        <div class="relative bg-white dark:bg-neutral-800 rounded-xl shadow-xl border border-warm-200 dark:border-neutral-700 max-w-3xl w-full p-6 z-10">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-stone-800 dark:text-stone-200">Hook Preview</h3>
                                <button type="button" @click="showPreview = false" class="p-1.5 rounded-lg text-stone-400 hover:text-stone-600 dark:hover:text-stone-300 hover:bg-stone-100 dark:hover:bg-neutral-700 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="border border-warm-200 dark:border-neutral-600 rounded-lg p-4 bg-warm-50 dark:bg-neutral-900 min-h-[200px] max-h-[60vh] overflow-auto">
                                <div x-html="previewHtml"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Citations Tab --}}
            <div x-show="activeTab === 'citations'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-stone-800 dark:text-stone-200">Citations</h3>
                            <p class="text-sm text-stone-500 dark:text-stone-400 mt-1">Manage citations via AJAX — changes save instantly. Used for Schema.org "citation" markup.</p>
                        </div>
                        <button type="button" @click="addCitation()"
                                class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Citation
                        </button>
                    </div>

                    {{-- Success/Error messages --}}
                    <div x-show="citationMessage" x-transition class="mb-4 px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-700 dark:text-green-400" x-text="citationMessage"></div>
                    <div x-show="citationError" x-transition class="mb-4 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-400" x-text="citationError"></div>

                    {{-- Loading state --}}
                    <div x-show="citationsLoading" class="text-center py-12">
                        <svg class="w-8 h-8 mx-auto text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="text-stone-500 dark:text-stone-400 mt-2">Loading citations...</p>
                    </div>

                    {{-- Empty state --}}
                    <div x-show="!citationsLoading && citations.length === 0" class="text-center py-12">
                        <svg class="w-12 h-12 mx-auto text-stone-300 dark:text-stone-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-stone-500 dark:text-stone-400">No citations yet. Click "Add Citation" to get started.</p>
                    </div>

                    {{-- Citation items --}}
                    <div x-show="!citationsLoading" class="space-y-4">
                        <template x-for="(citation, index) in citations" :key="index">
                            <div class="border border-warm-200 dark:border-neutral-600 rounded-lg p-4 bg-warm-50/50 dark:bg-neutral-900/50">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-bold" x-text="index + 1"></span>
                                        <span x-show="citation.id" class="text-xs text-green-600 dark:text-green-400 font-medium">Saved</span>
                                        <span x-show="!citation.id" class="text-xs text-amber-600 dark:text-amber-400 font-medium">New — unsaved</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="saveCitation(index)" :disabled="citationsSaving"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/40 transition disabled:opacity-50">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Save
                                        </button>
                                        <button type="button" @click="deleteCitation(index)"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Name</label>
                                        <input type="text" x-model="citation.name"
                                               class="w-full px-4 py-2.5 bg-white dark:bg-neutral-800 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                                               placeholder="e.g. USDA Soil Health Guide">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Type</label>
                                        <input type="text" x-model="citation.type"
                                               class="w-full px-4 py-2.5 bg-white dark:bg-neutral-800 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                                               placeholder="WebPage">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">URL</label>
                                    <input type="text" x-model="citation.url"
                                           class="w-full px-4 py-2.5 bg-white dark:bg-neutral-800 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                                           placeholder="https://...">
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- About / Topics Tab --}}
            <div x-show="activeTab === 'topics'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-stone-800 dark:text-stone-200">About / Topics</h3>
                            <p class="text-sm text-stone-500 dark:text-stone-400 mt-1">Manage topics via AJAX — changes save instantly. Used for Schema.org "about" markup.</p>
                        </div>
                        <button type="button" @click="addTopic()"
                                class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Topic
                        </button>
                    </div>

                    {{-- Success/Error messages --}}
                    <div x-show="topicMessage" x-transition class="mb-4 px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-700 dark:text-green-400" x-text="topicMessage"></div>
                    <div x-show="topicError" x-transition class="mb-4 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-400" x-text="topicError"></div>

                    {{-- Loading state --}}
                    <div x-show="topicsLoading" class="text-center py-12">
                        <svg class="w-8 h-8 mx-auto text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="text-stone-500 dark:text-stone-400 mt-2">Loading topics...</p>
                    </div>

                    {{-- Empty state --}}
                    <div x-show="!topicsLoading && topics.length === 0" class="text-center py-12">
                        <svg class="w-12 h-12 mx-auto text-stone-300 dark:text-stone-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-stone-500 dark:text-stone-400">No topics yet. Click "Add Topic" to get started.</p>
                    </div>

                    {{-- Topic items --}}
                    <div x-show="!topicsLoading" class="space-y-4">
                        <template x-for="(topic, index) in topics" :key="index">
                            <div class="border border-warm-200 dark:border-neutral-600 rounded-lg p-4 bg-warm-50/50 dark:bg-neutral-900/50">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-bold" x-text="index + 1"></span>
                                        <span x-show="topic.id" class="text-xs text-green-600 dark:text-green-400 font-medium">Saved</span>
                                        <span x-show="!topic.id" class="text-xs text-amber-600 dark:text-amber-400 font-medium">New — unsaved</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="saveTopic(index)" :disabled="topicsSaving"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/40 transition disabled:opacity-50">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Save
                                        </button>
                                        <button type="button" @click="deleteTopic(index)"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Name</label>
                                        <input type="text" x-model="topic.name"
                                               class="w-full px-4 py-2.5 bg-white dark:bg-neutral-800 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                                               placeholder="e.g. Soil Reclamation">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Type</label>
                                        <input type="text" x-model="topic.type"
                                               class="w-full px-4 py-2.5 bg-white dark:bg-neutral-800 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                                               placeholder="Thing">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Same As (URL)</label>
                                    <input type="text" x-model="topic.same_as"
                                           class="w-full px-4 py-2.5 bg-white dark:bg-neutral-800 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                                           placeholder="https://... (optional, e.g. Wikipedia/Wikidata link)">
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Based On / Sources Tab --}}
            <div x-show="activeTab === 'basedon'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-stone-800 dark:text-stone-200">Based On / Sources</h3>
                            <p class="text-sm text-stone-500 dark:text-stone-400 mt-1">Manage source URLs via AJAX — changes save instantly. Used for Schema.org "isBasedOn" markup.</p>
                        </div>
                        <button type="button" @click="addSource()"
                                class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Source
                        </button>
                    </div>

                    {{-- Success/Error messages --}}
                    <div x-show="basedOnMessage" x-transition class="mb-4 px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-700 dark:text-green-400" x-text="basedOnMessage"></div>
                    <div x-show="basedOnError" x-transition class="mb-4 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-400" x-text="basedOnError"></div>

                    {{-- Loading state --}}
                    <div x-show="basedOnLoading" class="text-center py-12">
                        <svg class="w-8 h-8 mx-auto text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="text-stone-500 dark:text-stone-400 mt-2">Loading sources...</p>
                    </div>

                    {{-- Empty state --}}
                    <div x-show="!basedOnLoading && basedOn.length === 0" class="text-center py-12">
                        <svg class="w-12 h-12 mx-auto text-stone-300 dark:text-stone-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-stone-500 dark:text-stone-400">No sources yet. Click "Add Source" to get started.</p>
                    </div>

                    {{-- Source items --}}
                    <div x-show="!basedOnLoading" class="space-y-4">
                        <template x-for="(source, index) in basedOn" :key="index">
                            <div class="border border-warm-200 dark:border-neutral-600 rounded-lg p-4 bg-warm-50/50 dark:bg-neutral-900/50">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-bold" x-text="index + 1"></span>
                                        <span x-show="source.id" class="text-xs text-green-600 dark:text-green-400 font-medium">Saved</span>
                                        <span x-show="!source.id" class="text-xs text-amber-600 dark:text-amber-400 font-medium">New — unsaved</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="saveSource(index)" :disabled="basedOnSaving"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/40 transition disabled:opacity-50">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Save
                                        </button>
                                        <button type="button" @click="deleteSource(index)"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">URL</label>
                                    <input type="text" x-model="source.url"
                                           class="w-full px-4 py-2.5 bg-white dark:bg-neutral-800 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                                           placeholder="https://...">
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('admin.posts.index') }}"
                   class="px-4 py-2.5 bg-stone-200 dark:bg-neutral-700 text-stone-700 dark:text-stone-300 font-medium rounded-lg hover:bg-stone-300 dark:hover:bg-neutral-600 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
                    Update Post
                </button>
            </div>
        </form>

        {{-- Analytics Panel --}}
        @php
            $analyticsPath = '/' . ($post->category?->slug ?? '') . '/' . $post->slug;
        @endphp
        @include('blog::admin.partials.analytics-panel', ['analyticsPath' => $analyticsPath])
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('postEditor', () => ({
        activeTab: 'main',
        showDeleteModal: false,
        postId: null,
        faqs: [],
        faqLoading: false,
        faqSaving: false,
        faqMessage: '',
        faqError: '',
        hooks: [],
        hookTemplates: [],
        hookTypes: [],
        hookLoading: false,
        hookSaving: false,
        hookMessage: '',
        hookError: '',
        showHookModal: false,
        editingHook: null,
        hookForm: {
            hook_template_id: '',
            name: '',
            position: 0,
            html_content: '',
            is_active: true,
            config: {}
        },
        previewHtml: '',
        showPreview: false,
        howtoSteps: [],
        howtoLoading: false,
        howtoSaving: false,
        howtoMessage: '',
        howtoError: '',
        citations: [],
        citationsLoading: false,
        citationsSaving: false,
        citationMessage: '',
        citationError: '',
        topics: [],
        topicsLoading: false,
        topicsSaving: false,
        topicMessage: '',
        topicError: '',
        basedOn: [],
        basedOnLoading: false,
        basedOnSaving: false,
        basedOnMessage: '',
        basedOnError: '',

        init() {
            this.postId = this.$el.dataset.postId;
            this.loadFaqs();
            this.loadHooks();
            this.loadHowToSteps();
            this.loadCitations();
            this.loadTopics();
            this.loadBasedOn();
        },

        async loadHooks() {
            this.hookLoading = true;
            try {
                const [hooksRes, templatesRes, typesRes] = await Promise.all([
                    fetch(`/admin/posts/${this.postId}/hooks`, { headers: { 'Accept': 'application/json' } }),
                    fetch('/admin/hook-templates?active_only=1', { headers: { 'Accept': 'application/json' } }),
                    fetch('/admin/hook-templates/types', { headers: { 'Accept': 'application/json' } })
                ]);
                this.hooks = await hooksRes.json();
                this.hookTemplates = await templatesRes.json();
                this.hookTypes = await typesRes.json();
            } catch (e) {
                this.hookError = 'Failed to load hooks';
            } finally {
                this.hookLoading = false;
            }
        },

        openAddHook(position = 0) {
            this.editingHook = null;
            this.hookForm = { hook_template_id: '', name: '', position: position, html_content: '', is_active: true, config: {} };
            this.showHookModal = true;
        },

        openEditHook(hook) {
            this.editingHook = hook;
            this.hookForm = {
                hook_template_id: hook.hook_template_id || '',
                name: hook.name,
                position: hook.position,
                html_content: hook.html_content || '',
                is_active: hook.is_active,
                config: hook.config || {}
            };
            this.showHookModal = true;
        },

        async saveHook() {
            if (!this.hookForm.name) { this.hookError = 'Hook name is required'; return; }
            this.hookSaving = true;
            this.hookMessage = '';
            this.hookError = '';
            try {
                const isNew = !this.editingHook;
                const url = isNew
                    ? `/admin/posts/${this.postId}/hooks`
                    : `/admin/posts/${this.postId}/hooks/${this.editingHook.id}`;
                const res = await fetch(url, {
                    method: isNew ? 'POST' : 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.hookForm)
                });
                if (!res.ok) { const err = await res.json(); throw new Error(err.message || 'Failed'); }
                this.showHookModal = false;
                await this.loadHooks();
                this.hookMessage = isNew ? 'Hook added' : 'Hook updated';
                setTimeout(() => this.hookMessage = '', 3000);
            } catch (e) {
                this.hookError = e.message || 'Failed to save hook';
                setTimeout(() => this.hookError = '', 3000);
            } finally {
                this.hookSaving = false;
            }
        },

        async deleteHook(hook) {
            if (!confirm('Delete this hook?')) return;
            try {
                await fetch(`/admin/posts/${this.postId}/hooks/${hook.id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                await this.loadHooks();
                this.hookMessage = 'Hook deleted';
                setTimeout(() => this.hookMessage = '', 3000);
            } catch (e) {
                this.hookError = 'Failed to delete hook';
            }
        },

        async previewHook(hook) {
            try {
                const res = await fetch(`/admin/posts/${this.postId}/hooks/${hook.id}/preview`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.previewHtml = data.html;
                this.showPreview = true;
            } catch (e) {
                this.hookError = 'Failed to load preview';
            }
        },

        onTemplateSelect() {
            if (this.hookForm.hook_template_id) {
                const tpl = this.hookTemplates.find(t => t.id == this.hookForm.hook_template_id);
                if (tpl) {
                    this.hookForm.name = this.hookForm.name || tpl.name;
                    this.hookForm.html_content = '';
                }
            }
        },

        hookTypeBadgeClass(type) {
            const map = {
                'related_posts': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                'ad': 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                'newsletter': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                'cta': 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                'custom_html': 'bg-stone-100 text-stone-700 dark:bg-stone-900/30 dark:text-stone-400',
                'divider': 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400'
            };
            return map[type] || 'bg-stone-100 text-stone-700 dark:bg-stone-900/30 dark:text-stone-400';
        },

        positionLabel(pos) {
            return pos === 0 ? 'Before content' : 'After content';
        },

        async loadFaqs() {
            this.faqLoading = true;
            try {
                const res = await fetch(`/admin/posts/${this.postId}/faqs`, {
                    headers: { 'Accept': 'application/json' }
                });
                this.faqs = await res.json();
            } catch (e) {
                this.faqError = 'Failed to load FAQs';
            } finally {
                this.faqLoading = false;
            }
        },

        async saveFaq(index) {
            const faq = this.faqs[index];
            if (!faq.question || !faq.answer) { this.faqError = 'Question and answer are required'; return; }
            this.faqSaving = true;
            this.faqMessage = '';
            this.faqError = '';
            try {
                const isNew = !faq.id;
                const url = isNew
                    ? `/admin/posts/${this.postId}/faqs`
                    : `/admin/posts/${this.postId}/faqs/${faq.id}`;
                const res = await fetch(url, {
                    method: isNew ? 'POST' : 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ question: faq.question, answer: faq.answer, sort_order: index })
                });
                if (!res.ok) throw new Error('Failed to save');
                const saved = await res.json();
                this.faqs[index] = saved;
                this.faqMessage = 'FAQ saved successfully';
                setTimeout(() => this.faqMessage = '', 3000);
            } catch (e) {
                this.faqError = 'Failed to save FAQ';
                setTimeout(() => this.faqError = '', 3000);
            } finally {
                this.faqSaving = false;
            }
        },

        async deleteFaq(index) {
            const faq = this.faqs[index];
            if (faq.id) {
                try {
                    await fetch(`/admin/posts/${this.postId}/faqs/${faq.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                } catch (e) {
                    this.faqError = 'Failed to delete FAQ';
                    return;
                }
            }
            this.faqs.splice(index, 1);
            this.faqMessage = 'FAQ deleted';
            setTimeout(() => this.faqMessage = '', 3000);
        },

        addFaq() {
            this.faqs.push({ id: null, question: '', answer: '', sort_order: this.faqs.length });
        },

        async loadHowToSteps() {
            this.howtoLoading = true;
            try {
                const res = await fetch(`/admin/posts/${this.postId}/howto-steps`, {
                    headers: { 'Accept': 'application/json' }
                });
                this.howtoSteps = await res.json();
            } catch (e) {
                this.howtoError = 'Failed to load steps';
            } finally {
                this.howtoLoading = false;
            }
        },

        async saveHowToStep(index) {
            const step = this.howtoSteps[index];
            if (!step.title) { this.howtoError = 'Step title is required'; return; }
            this.howtoSaving = true;
            this.howtoMessage = '';
            this.howtoError = '';
            try {
                const isNew = !step.id;
                const url = isNew
                    ? `/admin/posts/${this.postId}/howto-steps`
                    : `/admin/posts/${this.postId}/howto-steps/${step.id}`;
                const res = await fetch(url, {
                    method: isNew ? 'POST' : 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ title: step.title, content: step.content, image: step.image, tip: step.tip, sort_order: index })
                });
                if (!res.ok) throw new Error('Failed to save');
                const saved = await res.json();
                this.howtoSteps[index] = saved;
                this.howtoMessage = 'Step saved successfully';
                setTimeout(() => this.howtoMessage = '', 3000);
            } catch (e) {
                this.howtoError = 'Failed to save step';
                setTimeout(() => this.howtoError = '', 3000);
            } finally {
                this.howtoSaving = false;
            }
        },

        async deleteHowToStep(index) {
            const step = this.howtoSteps[index];
            if (step.id) {
                try {
                    await fetch(`/admin/posts/${this.postId}/howto-steps/${step.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                } catch (e) {
                    this.howtoError = 'Failed to delete step';
                    return;
                }
            }
            this.howtoSteps.splice(index, 1);
            this.howtoMessage = 'Step deleted';
            setTimeout(() => this.howtoMessage = '', 3000);
        },

        addHowToStep() {
            this.howtoSteps.push({ id: null, title: '', content: '', image: '', tip: '', sort_order: this.howtoSteps.length });
        },

        async loadCitations() {
            this.citationsLoading = true;
            try {
                const res = await fetch(`/admin/posts/${this.postId}/citations`, {
                    headers: { 'Accept': 'application/json' }
                });
                this.citations = await res.json();
            } catch (e) {
                this.citationError = 'Failed to load citations';
            } finally {
                this.citationsLoading = false;
            }
        },

        async saveCitation(index) {
            const citation = this.citations[index];
            if (!citation.name || !citation.url) { this.citationError = 'Name and URL are required'; return; }
            this.citationsSaving = true;
            this.citationMessage = '';
            this.citationError = '';
            try {
                const isNew = !citation.id;
                const url = isNew
                    ? `/admin/posts/${this.postId}/citations`
                    : `/admin/posts/${this.postId}/citations/${citation.id}`;
                const res = await fetch(url, {
                    method: isNew ? 'POST' : 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ name: citation.name, url: citation.url, type: citation.type, sort_order: index })
                });
                if (!res.ok) throw new Error('Failed to save');
                const saved = await res.json();
                this.citations[index] = saved;
                this.citationMessage = 'Citation saved successfully';
                setTimeout(() => this.citationMessage = '', 3000);
            } catch (e) {
                this.citationError = 'Failed to save citation';
                setTimeout(() => this.citationError = '', 3000);
            } finally {
                this.citationsSaving = false;
            }
        },

        async deleteCitation(index) {
            const citation = this.citations[index];
            if (citation.id) {
                try {
                    await fetch(`/admin/posts/${this.postId}/citations/${citation.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                } catch (e) {
                    this.citationError = 'Failed to delete citation';
                    return;
                }
            }
            this.citations.splice(index, 1);
            this.citationMessage = 'Citation deleted';
            setTimeout(() => this.citationMessage = '', 3000);
        },

        addCitation() {
            this.citations.push({ id: null, name: '', url: '', type: 'WebPage', sort_order: this.citations.length });
        },

        async loadTopics() {
            this.topicsLoading = true;
            try {
                const res = await fetch(`/admin/posts/${this.postId}/topics`, {
                    headers: { 'Accept': 'application/json' }
                });
                this.topics = await res.json();
            } catch (e) {
                this.topicError = 'Failed to load topics';
            } finally {
                this.topicsLoading = false;
            }
        },

        async saveTopic(index) {
            const topic = this.topics[index];
            if (!topic.name) { this.topicError = 'Name is required'; return; }
            this.topicsSaving = true;
            this.topicMessage = '';
            this.topicError = '';
            try {
                const isNew = !topic.id;
                const url = isNew
                    ? `/admin/posts/${this.postId}/topics`
                    : `/admin/posts/${this.postId}/topics/${topic.id}`;
                const res = await fetch(url, {
                    method: isNew ? 'POST' : 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ name: topic.name, same_as: topic.same_as, type: topic.type, sort_order: index })
                });
                if (!res.ok) throw new Error('Failed to save');
                const saved = await res.json();
                this.topics[index] = saved;
                this.topicMessage = 'Topic saved successfully';
                setTimeout(() => this.topicMessage = '', 3000);
            } catch (e) {
                this.topicError = 'Failed to save topic';
                setTimeout(() => this.topicError = '', 3000);
            } finally {
                this.topicsSaving = false;
            }
        },

        async deleteTopic(index) {
            const topic = this.topics[index];
            if (topic.id) {
                try {
                    await fetch(`/admin/posts/${this.postId}/topics/${topic.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                } catch (e) {
                    this.topicError = 'Failed to delete topic';
                    return;
                }
            }
            this.topics.splice(index, 1);
            this.topicMessage = 'Topic deleted';
            setTimeout(() => this.topicMessage = '', 3000);
        },

        addTopic() {
            this.topics.push({ id: null, name: '', same_as: '', type: 'Thing', sort_order: this.topics.length });
        },

        async loadBasedOn() {
            this.basedOnLoading = true;
            try {
                const res = await fetch(`/admin/posts/${this.postId}/based-on`, {
                    headers: { 'Accept': 'application/json' }
                });
                this.basedOn = await res.json();
            } catch (e) {
                this.basedOnError = 'Failed to load sources';
            } finally {
                this.basedOnLoading = false;
            }
        },

        async saveSource(index) {
            const source = this.basedOn[index];
            if (!source.url) { this.basedOnError = 'URL is required'; return; }
            this.basedOnSaving = true;
            this.basedOnMessage = '';
            this.basedOnError = '';
            try {
                const isNew = !source.id;
                const url = isNew
                    ? `/admin/posts/${this.postId}/based-on`
                    : `/admin/posts/${this.postId}/based-on/${source.id}`;
                const res = await fetch(url, {
                    method: isNew ? 'POST' : 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ url: source.url, sort_order: index })
                });
                if (!res.ok) throw new Error('Failed to save');
                const saved = await res.json();
                this.basedOn[index] = saved;
                this.basedOnMessage = 'Source saved successfully';
                setTimeout(() => this.basedOnMessage = '', 3000);
            } catch (e) {
                this.basedOnError = 'Failed to save source';
                setTimeout(() => this.basedOnError = '', 3000);
            } finally {
                this.basedOnSaving = false;
            }
        },

        async deleteSource(index) {
            const source = this.basedOn[index];
            if (source.id) {
                try {
                    await fetch(`/admin/posts/${this.postId}/based-on/${source.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                } catch (e) {
                    this.basedOnError = 'Failed to delete source';
                    return;
                }
            }
            this.basedOn.splice(index, 1);
            this.basedOnMessage = 'Source deleted';
            setTimeout(() => this.basedOnMessage = '', 3000);
        },

        addSource() {
            this.basedOn.push({ id: null, url: '', sort_order: this.basedOn.length });
        }
    }));
});
</script>
@endpush
