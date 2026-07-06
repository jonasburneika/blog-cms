@extends('blog::layouts.admin')

@section('page-title', 'Create Post')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">Dashboard</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <a href="{{ route('admin.posts.index') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">Blog Posts</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <span class="text-stone-500 dark:text-stone-400">Create</span>
@endsection

@section('content')
    <div x-data="{
        activeTab: 'main',
        faqs: [],
        howtoSteps: [],
        addHowToStep() {
            this.howtoSteps.push({ title: '', content: '', image: '', tip: '', sort_order: this.howtoSteps.length });
        },
        removeHowToStep(index) {
            this.howtoSteps.splice(index, 1);
            this.howtoSteps.forEach((s, i) => s.sort_order = i);
        },
        addFaq() {
            this.faqs.push({ question: '', answer: '', sort_order: this.faqs.length });
        },
        removeFaq(index) {
            this.faqs.splice(index, 1);
            this.faqs.forEach((f, i) => f.sort_order = i);
        }
    }">
        <h1 class="text-2xl font-bold text-stone-800 dark:text-stone-200 mb-6">Create Post</h1>

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

        <form method="POST" action="{{ route('admin.posts.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="locale" value="{{ $locale }}">

            @include('blog::admin.partials.locale-switcher')

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
                </nav>
            </div>

            {{-- Main Info Tab --}}
            <div x-show="activeTab === 'main'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {{-- Title --}}
                        <div class="lg:col-span-2">
                            <label for="title" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Title</label>
                            <input type="text" name="title" id="title" value="{{ old('title') }}" required
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
                                    <option value="{{ $category->id }}" {{ old('blog_category_id') == $category->id ? 'selected' : '' }}>
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
                                <option value="0" {{ (int) old('status') === 0 ? 'selected' : '' }}>Draft</option>
                                <option value="1" {{ (int) old('status') === 1 ? 'selected' : '' }}>Published</option>
                                <option value="2" {{ (int) old('status') === 2 ? 'selected' : '' }}>Archived</option>
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
                                      placeholder="Brief summary of the post">{{ old('excerpt') }}</textarea>
                            @error('excerpt')
                                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Cover Images --}}
                        <div class="lg:col-span-2" x-data="{ desktopPreview: null, mobilePreview: null }">
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
                            <input type="datetime-local" name="published_at" id="published_at" value="{{ old('published_at') }}"
                                   class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                            @error('published_at')
                                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Is Featured --}}
                        <div class="flex items-center pt-7">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="is_featured" value="0">
                                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
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
                        <input type="hidden" name="content" x-ref="contentHidden" value="{{ old('content') }}">
                    </div>
                    @error('content')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- SEO Tab --}}
            <div x-show="activeTab === 'seo'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
                    {{-- Meta Title --}}
                    <div class="mb-5">
                        <label for="meta_title" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Meta Title</label>
                        <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title') }}"
                               class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                               placeholder="SEO title (leave empty to use post title)">
                        @error('meta_title')
                            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Meta Description --}}
                    <div class="mb-5">
                        <label for="meta_description" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Meta Description</label>
                        <textarea name="meta_description" id="meta_description" rows="3"
                                  class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition resize-y"
                                  placeholder="Brief description for search engines (150-160 characters recommended)">{{ old('meta_description') }}</textarea>
                        @error('meta_description')
                            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Canonical --}}
                    @include('blog::admin.partials.page-select', [
                        'name' => 'canonical',
                        'label' => 'Canonical Page',
                        'selected' => '',
                        'placeholder' => '— Self (this page is its own canonical) —',
                        'help' => 'Point search engines to another post/category as the canonical version. Leave as “Self” unless this page duplicates another.',
                    ])

                    {{-- Slug Preview --}}
                    <div>
                        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Slug Preview</label>
                        <div class="px-4 py-2.5 bg-stone-100 dark:bg-neutral-900 border border-stone-200 dark:border-neutral-700 rounded-lg text-sm text-stone-500 dark:text-stone-400">
                            <span class="text-stone-400 dark:text-stone-500">/blog/</span><span class="text-stone-600 dark:text-stone-300 italic">auto-generated-from-title</span>
                        </div>
                        <p class="mt-1.5 text-xs text-stone-400 dark:text-stone-500">The slug will be automatically generated from the post title.</p>
                    </div>
                </div>
            </div>

            {{-- FAQ Tab --}}
            <div x-show="activeTab === 'faq'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
                    {{-- FAQ Title --}}
                    <div class="mb-6 pb-6 border-b border-warm-200 dark:border-neutral-700">
                        <label for="faq_title" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">FAQ Section Title</label>
                        <input type="text" name="faq_title" id="faq_title" value="{{ old('faq_title') }}"
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
                            <p class="text-sm text-stone-500 dark:text-stone-400 mt-1">Add FAQ items for this article. They will be saved when you submit the form.</p>
                        </div>
                        <button type="button" @click="addFaq()"
                                class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add FAQ
                        </button>
                    </div>

                    {{-- Empty state --}}
                    <div x-show="faqs.length === 0" class="text-center py-12">
                        <svg class="w-12 h-12 mx-auto text-stone-300 dark:text-stone-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-stone-500 dark:text-stone-400">No FAQ items yet. Click "Add FAQ" to get started.</p>
                    </div>

                    {{-- FAQ items --}}
                    <div class="space-y-4">
                        <template x-for="(faq, index) in faqs" :key="index">
                            <div class="border border-warm-200 dark:border-neutral-600 rounded-lg p-4 bg-warm-50/50 dark:bg-neutral-900/50">
                                <div class="flex items-start justify-between mb-3">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-bold" x-text="index + 1"></span>
                                    <button type="button" @click="removeFaq(index)"
                                            class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Question</label>
                                    <input type="text" x-model="faq.question" :name="'faqs[' + index + '][question]'"
                                           class="w-full px-4 py-2.5 bg-white dark:bg-neutral-800 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                                           placeholder="Enter the question...">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Answer</label>
                                    <textarea x-model="faq.answer" :name="'faqs[' + index + '][answer]'" rows="3"
                                              class="w-full px-4 py-2.5 bg-white dark:bg-neutral-800 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition resize-y"
                                              placeholder="Enter the answer..."></textarea>
                                </div>
                                <input type="hidden" :name="'faqs[' + index + '][sort_order]'" :value="index">
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
                        <input type="text" name="howto_title" id="howto_title" value="{{ old('howto_title') }}"
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
                                  placeholder="Brief description of what this how-to guide covers">{{ old('howto_description') }}</textarea>
                        <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Used as the Schema.org HowTo "description" property.</p>
                        @error('howto_description')
                            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-stone-800 dark:text-stone-200">HowTo Steps</h3>
                            <p class="text-sm text-stone-500 dark:text-stone-400 mt-1">Add step-by-step instructions for this article. Useful for Schema.org HowTo markup.</p>
                        </div>
                        <button type="button" @click="addHowToStep()"
                                class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Step
                        </button>
                    </div>

                    {{-- Empty state --}}
                    <div x-show="howtoSteps.length === 0" class="text-center py-12">
                        <svg class="w-12 h-12 mx-auto text-stone-300 dark:text-stone-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        <p class="text-stone-500 dark:text-stone-400">No steps yet. Click "Add Step" to create a how-to guide.</p>
                    </div>

                    {{-- Step items --}}
                    <div class="space-y-4">
                        <template x-for="(step, index) in howtoSteps" :key="index">
                            <div class="border border-warm-200 dark:border-neutral-600 rounded-lg p-4 bg-warm-50/50 dark:bg-neutral-900/50">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-sm font-bold" x-text="index + 1"></span>
                                        <span class="text-sm font-medium text-stone-600 dark:text-stone-400">Step</span>
                                    </div>
                                    <button type="button" @click="removeHowToStep(index)"
                                            class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>

                                {{-- Step Title --}}
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Step Title</label>
                                    <input type="text" x-model="step.title" :name="'howto_steps[' + index + '][title]'"
                                           class="w-full px-4 py-2.5 bg-white dark:bg-neutral-800 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                                           placeholder="e.g. Preheat the oven to 180°C">
                                </div>

                                {{-- Step Content --}}
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Instructions</label>
                                    <textarea x-model="step.content" :name="'howto_steps[' + index + '][content]'" rows="3"
                                              class="w-full px-4 py-2.5 bg-white dark:bg-neutral-800 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition resize-y"
                                              placeholder="Detailed instructions for this step..."></textarea>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    {{-- Image URL --}}
                                    <div>
                                        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Image URL</label>
                                        <input type="text" x-model="step.image" :name="'howto_steps[' + index + '][image]'"
                                               class="w-full px-4 py-2.5 bg-white dark:bg-neutral-800 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition text-sm"
                                               placeholder="https://... (optional)">
                                    </div>

                                    {{-- Tip --}}
                                    <div>
                                        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Tip / Note</label>
                                        <input type="text" x-model="step.tip" :name="'howto_steps[' + index + '][tip]'"
                                               class="w-full px-4 py-2.5 bg-white dark:bg-neutral-800 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition text-sm"
                                               placeholder="Pro tip... (optional)">
                                    </div>
                                </div>

                                <input type="hidden" :name="'howto_steps[' + index + '][sort_order]'" :value="index">
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
                    Save Post
                </button>
            </div>
        </form>
    </div>
@endsection
