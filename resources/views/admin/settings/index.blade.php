@extends('blog::layouts.admin')

@section('page-title', 'Settings')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">Dashboard</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <span class="text-stone-500 dark:text-stone-400">Settings</span>
@endsection

@section('content')
    <div x-data="{
        activeTab: new URLSearchParams(window.location.search).get('tab') || 'general',
        testingGsc: false,
        testingGa4: false,
        gscResult: null,
        ga4Result: null,

        switchTab(tab) {
            this.activeTab = tab;
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
        },

        async testGsc() {
            this.testingGsc = true;
            this.gscResult = null;
            try {
                const res = await fetch('/admin/settings/test-gsc', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                const data = await res.json();
                this.gscResult = { success: res.ok, message: data.message || (res.ok ? 'Connection successful!' : 'Connection failed.') };
            } catch (e) {
                this.gscResult = { success: false, message: 'Network error. Please try again.' };
            } finally {
                this.testingGsc = false;
            }
        },

        async testGa4() {
            this.testingGa4 = true;
            this.ga4Result = null;
            try {
                const res = await fetch('/admin/settings/test-ga4', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                const data = await res.json();
                this.ga4Result = { success: res.ok, message: data.message || (res.ok ? 'Connection successful!' : 'Connection failed.') };
            } catch (e) {
                this.ga4Result = { success: false, message: 'Network error. Please try again.' };
            } finally {
                this.testingGa4 = false;
            }
        }
    }">

        {{-- Tab Navigation --}}
        <div class="mb-6 border-b border-warm-200 dark:border-neutral-700">
            <nav class="flex gap-1 -mb-px">
                <button type="button" @click="switchTab('general')"
                        :class="activeTab === 'general'
                            ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                            : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                        class="px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition">
                    General
                </button>
                <button type="button" @click="switchTab('languages')"
                        :class="activeTab === 'languages'
                            ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                            : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                        class="inline-flex items-center gap-2 px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                    </svg>
                    Languages
                </button>
                <button type="button" @click="switchTab('gsc')"
                        :class="activeTab === 'gsc'
                            ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                            : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                        class="inline-flex items-center gap-2 px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Google Search Console
                </button>
                <button type="button" @click="switchTab('ga4')"
                        :class="activeTab === 'ga4'
                            ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                            : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                        class="inline-flex items-center gap-2 px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Google Analytics 4
                </button>
                <button type="button" @click="switchTab('api')"
                        :class="activeTab === 'api'
                            ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                            : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                        class="inline-flex items-center gap-2 px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                    </svg>
                    API
                </button>
                <button type="button" @click="switchTab('shortpixel')"
                        :class="activeTab === 'shortpixel'
                            ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                            : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                        class="inline-flex items-center gap-2 px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Image Optimization
                </button>
            </nav>
        </div>

        {{-- ============================================================ --}}
        {{-- General Tab --}}
        {{-- ============================================================ --}}
        <div x-show="activeTab === 'general'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
                <h2 class="text-lg font-semibold text-warm-900 dark:text-warm-100 mb-6">General Settings</h2>

                <form method="POST" action="{{ route('admin.settings.update.general') }}">
                    @csrf
                    @method('PUT')

                    {{-- Site Name --}}
                    <div class="mb-6">
                        <label for="site_name" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Site Name</label>
                        <input type="text" name="site_name" id="site_name"
                               value="{{ old('site_name', $settings['general']['site_name'] ?? '') }}"
                               class="w-full bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg px-4 py-2.5 text-sm text-warm-900 dark:text-warm-100 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                        @error('site_name')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Site Description --}}
                    <div class="mb-6">
                        <label for="site_description" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Site Description</label>
                        <textarea name="site_description" id="site_description" rows="3"
                                  class="w-full bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg px-4 py-2.5 text-sm text-warm-900 dark:text-warm-100 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">{{ old('site_description', $settings['general']['site_description'] ?? '') }}</textarea>
                        @error('site_description')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Homepage Meta Title --}}
                    <div class="mb-5" x-data="{ charCount: '{{ old('homepage_meta_title', $settings['general']['homepage_meta_title'] ?? '') }}'.length }">
                        <label for="homepage_meta_title" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Homepage Meta Title</label>
                        <input type="text" name="homepage_meta_title" id="homepage_meta_title"
                               value="{{ old('homepage_meta_title', $settings['general']['homepage_meta_title'] ?? '') }}"
                               @input="charCount = $event.target.value.length"
                               class="w-full bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg px-4 py-2.5 text-sm text-warm-900 dark:text-warm-100 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                               placeholder="Alaus Darykla — Istorijos. Amatas. Paveldas.">
                        <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">
                            <span x-text="charCount" :class="charCount > 60 ? 'text-red-500' : ''"></span>/60 characters
                        </p>
                        @error('homepage_meta_title')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Homepage Meta Description --}}
                    <div class="mb-6" x-data="{ charCount: '{{ old('homepage_meta_description', $settings['general']['homepage_meta_description'] ?? '') }}'.length }">
                        <label for="homepage_meta_description" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Homepage Meta Description</label>
                        <textarea name="homepage_meta_description" id="homepage_meta_description" rows="3"
                                  @input="charCount = $event.target.value.length"
                                  class="w-full bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg px-4 py-2.5 text-sm text-warm-900 dark:text-warm-100 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition resize-y"
                                  placeholder="Asmeninis tinklaraštis apie lietuvišką alaus kultūrą...">{{ old('homepage_meta_description', $settings['general']['homepage_meta_description'] ?? '') }}</textarea>
                        <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">
                            <span x-text="charCount" :class="charCount > 160 ? 'text-red-500' : ''"></span>/160 characters
                        </p>
                        @error('homepage_meta_description')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Blog Posts Per Page --}}
                    <div class="mb-6">
                        <label for="blog_posts_per_page" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Blog Posts Per Page</label>
                        <input type="number" name="blog_posts_per_page" id="blog_posts_per_page" min="1" max="100"
                               value="{{ old('blog_posts_per_page', $settings['general']['blog_posts_per_page'] ?? 10) }}"
                               class="w-full max-w-[200px] bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg px-4 py-2.5 text-sm text-warm-900 dark:text-warm-100 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                        @error('blog_posts_per_page')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Save --}}
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- Languages Tab --}}
        {{-- ============================================================ --}}
        <div x-show="activeTab === 'languages'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
                <h2 class="text-lg font-semibold text-warm-900 dark:text-warm-100 mb-1">Languages</h2>
                <p class="text-sm text-stone-500 dark:text-stone-400 mb-6">Choose which languages your posts and categories are available in, and which one is the default (served without a URL prefix).</p>

                @error('locales')<p class="mb-4 text-sm text-red-500">{{ $message }}</p>@enderror
                @error('locales.*')<p class="mb-4 text-sm text-red-500">Language codes must look like <code>en</code> or <code>en_US</code>.</p>@enderror

                <form method="POST" action="{{ route('admin.settings.update.languages') }}"
                      x-data="{
                          locales: @js(array_values($settings['languages']['locales'])),
                          def: @js($settings['languages']['default_locale']),
                          adding: '',
                          common: { en:'English', lt:'Lietuvių', lv:'Latviešu', et:'Eesti', ru:'Русский', de:'Deutsch', fr:'Français', es:'Español', pl:'Polski', it:'Italiano', uk:'Українська', fi:'Suomi', sv:'Svenska', nb:'Norsk' },
                          add() {
                              let code = (this.adding || '').trim();
                              if (!/^[a-z]{2}(_[A-Z]{2})?$/.test(code)) { alert('Use a code like en or en_US'); return; }
                              if (!this.locales.includes(code)) this.locales.push(code);
                              this.adding = '';
                          },
                          remove(code) {
                              if (this.locales.length <= 1) return;
                              this.locales = this.locales.filter(l => l !== code);
                              if (this.def === code) this.def = this.locales[0];
                          },
                          label(code) { return this.common[code] || code; }
                      }">
                    @csrf
                    @method('PUT')

                    <div class="space-y-2 mb-6">
                        <template x-for="code in locales" :key="code">
                            <div class="flex items-center justify-between gap-3 px-4 py-3 bg-warm-50 dark:bg-neutral-900 border border-warm-200 dark:border-neutral-700 rounded-lg">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="radio" name="default_locale" :value="code" x-model="def"
                                           class="text-amber-600 focus:ring-amber-500">
                                    <input type="hidden" name="locales[]" :value="code">
                                    <span class="text-sm font-medium text-warm-900 dark:text-warm-100" x-text="label(code)"></span>
                                    <code class="text-xs text-stone-400 dark:text-stone-500" x-text="code"></code>
                                    <span x-show="def === code" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">Default</span>
                                </label>
                                <button type="button" @click="remove(code)" :disabled="locales.length <= 1"
                                        class="text-xs font-medium text-red-600 dark:text-red-400 hover:underline disabled:opacity-40 disabled:cursor-not-allowed">
                                    Remove
                                </button>
                            </div>
                        </template>
                    </div>

                    {{-- Add a language --}}
                    <div class="flex flex-wrap items-center gap-2 mb-6">
                        <select x-model="adding"
                                class="bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg px-3 py-2 text-sm text-warm-900 dark:text-warm-100 focus:outline-none focus:ring-2 focus:ring-amber-500">
                            <option value="">— pick a common language —</option>
                            <template x-for="(name, code) in common" :key="code">
                                <option :value="code" x-text="name + ' (' + code + ')'"></option>
                            </template>
                        </select>
                        <span class="text-xs text-stone-400 dark:text-stone-500">or</span>
                        <input type="text" x-model="adding" placeholder="custom code, e.g. lt"
                               class="bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg px-3 py-2 text-sm text-warm-900 dark:text-warm-100 focus:outline-none focus:ring-2 focus:ring-amber-500 w-40">
                        <button type="button" @click="add()"
                                class="inline-flex items-center gap-1 px-4 py-2 bg-stone-200 dark:bg-neutral-700 text-stone-700 dark:text-stone-300 text-sm font-medium rounded-lg hover:bg-stone-300 dark:hover:bg-neutral-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Add
                        </button>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Save Languages
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- Google Search Console Tab --}}
        {{-- ============================================================ --}}
        <div x-show="activeTab === 'gsc'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Main Form (2/3) --}}
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">

                        {{-- Connection Status Banner --}}
                        @if(($settings['gsc']['gsc_enabled'] ?? false) && !empty($settings['gsc']['gsc_credentials_json'] ?? ''))
                            <div class="mb-6 flex items-center gap-3 px-4 py-3 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-sm font-medium text-green-700 dark:text-green-300">Connected to Google Search Console</span>
                            </div>
                        @else
                            <div class="mb-6 flex items-center gap-3 px-4 py-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                <span class="text-sm font-medium text-amber-700 dark:text-amber-300">Not connected — configure credentials below</span>
                            </div>
                        @endif

                        <h2 class="text-lg font-semibold text-warm-900 dark:text-warm-100 mb-6">Google Search Console</h2>

                        <form method="POST" action="{{ route('admin.settings.update.gsc') }}">
                            @csrf
                            @method('PUT')

                            {{-- Enable GSC Integration --}}
                            <div class="mb-6">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="gsc_enabled" value="0">
                                    <input type="checkbox" name="gsc_enabled" value="1" {{ old('gsc_enabled', $settings['gsc']['gsc_enabled'] ?? false) ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-stone-200 dark:bg-neutral-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-neutral-600 peer-checked:bg-amber-600"></div>
                                    <span class="ml-3 text-sm font-medium text-stone-700 dark:text-stone-300">Enable GSC Integration</span>
                                </label>
                            </div>

                            {{-- Property URL --}}
                            <div class="mb-6">
                                <label for="gsc_property_url" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Property URL</label>
                                <input type="text" name="gsc_property_url" id="gsc_property_url"
                                       value="{{ old('gsc_property_url', $settings['gsc']['gsc_property_url'] ?? '') }}"
                                       placeholder="https://www.example.com/"
                                       class="w-full bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg px-4 py-2.5 text-sm text-warm-900 dark:text-warm-100 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                                <p class="mt-1.5 text-xs text-stone-400 dark:text-stone-500">The property URL as it appears in Google Search Console (e.g., https://www.example.com/ or sc-domain:example.com)</p>
                                @error('gsc_property_url')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Service Account Credentials JSON --}}
                            <div class="mb-6">
                                <label for="gsc_credentials_json" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Service Account Credentials (JSON)</label>
                                <textarea name="gsc_credentials_json" id="gsc_credentials_json" rows="8"
                                          placeholder="{{ ($settings['gsc']['gsc_credentials_json'] ?? '') === '********' ? 'Credentials are stored securely. Paste new credentials to replace.' : '{\n  \"type\": \"service_account\",\n  ...\n}' }}"
                                          class="w-full bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg px-4 py-2.5 text-sm text-warm-900 dark:text-warm-100 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition font-mono">{{ ($settings['gsc']['gsc_credentials_json'] ?? '') !== '********' ? old('gsc_credentials_json', $settings['gsc']['gsc_credentials_json'] ?? '') : '' }}</textarea>
                                <div class="mt-2 text-xs text-stone-400 dark:text-stone-500 space-y-1">
                                    <p class="font-medium text-stone-500 dark:text-stone-400">How to set up:</p>
                                    <ol class="list-decimal list-inside space-y-0.5 ml-1">
                                        <li>Go to <a href="https://console.cloud.google.com/" target="_blank" class="text-amber-600 dark:text-amber-400 hover:underline">Google Cloud Console</a></li>
                                        <li>Create a Service Account</li>
                                        <li>Download the JSON key file</li>
                                        <li>Paste the contents here</li>
                                        <li>Add the service account email as a user in GSC</li>
                                    </ol>
                                </div>
                                @error('gsc_credentials_json')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-3 justify-between">
                                {{-- Test Connection --}}
                                <button type="button" @click="testGsc()"
                                        :disabled="testingGsc"
                                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-stone-200 dark:bg-neutral-700 hover:bg-stone-300 dark:hover:bg-neutral-600 text-stone-700 dark:text-stone-300 text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-amber-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                    {{-- Spinner --}}
                                    <svg x-show="testingGsc" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg x-show="!testingGsc" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span x-text="testingGsc ? 'Testing...' : 'Test Connection'"></span>
                                </button>

                                {{-- Save --}}
                                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    Save Settings
                                </button>
                            </div>

                            {{-- Test Result Message --}}
                            <template x-if="gscResult">
                                <div class="mt-4 px-4 py-3 rounded-xl text-sm"
                                     :class="gscResult.success ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300'">
                                    <span x-text="gscResult.message"></span>
                                </div>
                            </template>
                        </form>
                    </div>
                </div>

                {{-- Info Panel (1/3) --}}
                <div class="lg:col-span-1">
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300">What GSC Integration Provides</h3>
                        </div>
                        <ul class="space-y-2 text-sm text-blue-700 dark:text-blue-300">
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Track which queries bring visitors to your blog posts
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Monitor click-through rates and average positions
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Identify content optimization opportunities
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                See indexing status of your pages
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- Google Analytics 4 Tab --}}
        {{-- ============================================================ --}}
        <div x-show="activeTab === 'ga4'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Main Form (2/3) --}}
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">

                        {{-- Connection Status Banner --}}
                        @if(($settings['ga4']['ga4_enabled'] ?? false) && !empty($settings['ga4']['ga4_credentials_json'] ?? ''))
                            <div class="mb-6 flex items-center gap-3 px-4 py-3 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-sm font-medium text-green-700 dark:text-green-300">Connected to Google Analytics 4</span>
                            </div>
                        @else
                            <div class="mb-6 flex items-center gap-3 px-4 py-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                <span class="text-sm font-medium text-amber-700 dark:text-amber-300">Not connected — configure credentials below</span>
                            </div>
                        @endif

                        <h2 class="text-lg font-semibold text-warm-900 dark:text-warm-100 mb-6">Google Analytics 4</h2>

                        <form method="POST" action="{{ route('admin.settings.update.ga4') }}">
                            @csrf
                            @method('PUT')

                            {{-- Enable GA4 Integration --}}
                            <div class="mb-6">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="ga4_enabled" value="0">
                                    <input type="checkbox" name="ga4_enabled" value="1" {{ old('ga4_enabled', $settings['ga4']['ga4_enabled'] ?? false) ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-stone-200 dark:bg-neutral-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-neutral-600 peer-checked:bg-amber-600"></div>
                                    <span class="ml-3 text-sm font-medium text-stone-700 dark:text-stone-300">Enable GA4 Integration</span>
                                </label>
                            </div>

                            {{-- Measurement ID --}}
                            <div class="mb-6">
                                <label for="ga4_measurement_id" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Measurement ID</label>
                                <input type="text" name="ga4_measurement_id" id="ga4_measurement_id"
                                       value="{{ old('ga4_measurement_id', $settings['ga4']['ga4_measurement_id'] ?? '') }}"
                                       placeholder="G-XXXXXXXXXX"
                                       class="w-full max-w-md bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg px-4 py-2.5 text-sm text-warm-900 dark:text-warm-100 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                                <p class="mt-1.5 text-xs text-stone-400 dark:text-stone-500">Found in GA4 Admin > Data Streams > Web stream details</p>
                                @error('ga4_measurement_id')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Property ID --}}
                            <div class="mb-6">
                                <label for="ga4_property_id" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Property ID</label>
                                <input type="text" name="ga4_property_id" id="ga4_property_id"
                                       value="{{ old('ga4_property_id', $settings['ga4']['ga4_property_id'] ?? '') }}"
                                       placeholder="123456789"
                                       class="w-full max-w-md bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg px-4 py-2.5 text-sm text-warm-900 dark:text-warm-100 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                                <p class="mt-1.5 text-xs text-stone-400 dark:text-stone-500">Numeric property ID for API access (Admin > Property Settings)</p>
                                @error('ga4_property_id')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Service Account Credentials JSON --}}
                            <div class="mb-6">
                                <label for="ga4_credentials_json" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Service Account Credentials (JSON)</label>
                                <textarea name="ga4_credentials_json" id="ga4_credentials_json" rows="8"
                                          placeholder="{{ ($settings['ga4']['ga4_credentials_json'] ?? '') === '********' ? 'Credentials are stored securely. Paste new credentials to replace.' : '{\n  \"type\": \"service_account\",\n  ...\n}' }}"
                                          class="w-full bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg px-4 py-2.5 text-sm text-warm-900 dark:text-warm-100 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition font-mono">{{ ($settings['ga4']['ga4_credentials_json'] ?? '') !== '********' ? old('ga4_credentials_json', $settings['ga4']['ga4_credentials_json'] ?? '') : '' }}</textarea>
                                <div class="mt-2 text-xs text-stone-400 dark:text-stone-500 space-y-1">
                                    <p class="font-medium text-stone-500 dark:text-stone-400">How to set up:</p>
                                    <ol class="list-decimal list-inside space-y-0.5 ml-1">
                                        <li>Go to <a href="https://console.cloud.google.com/" target="_blank" class="text-amber-600 dark:text-amber-400 hover:underline">Google Cloud Console</a></li>
                                        <li>Create a Service Account</li>
                                        <li>Download the JSON key file</li>
                                        <li>Paste the contents here</li>
                                        <li>Grant the service account access to your GA4 property</li>
                                    </ol>
                                </div>
                                @error('ga4_credentials_json')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Tracking Code --}}
                            <div class="mb-6">
                                <label for="ga4_tracking_code" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Tracking Code</label>
                                <textarea name="ga4_tracking_code" id="ga4_tracking_code" rows="6"
                                          placeholder="<!-- Google tag (gtag.js) -->&#10;<script async src=&quot;https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX&quot;></script>&#10;<script>&#10;  window.dataLayer = window.dataLayer || [];&#10;  function gtag(){dataLayer.push(arguments);}&#10;  gtag('js', new Date());&#10;  gtag('config', 'G-XXXXXXXXXX');&#10;</script>"
                                          class="w-full bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg px-4 py-2.5 text-sm text-warm-900 dark:text-warm-100 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition font-mono">{{ old('ga4_tracking_code', $settings['ga4']['ga4_tracking_code'] ?? '') }}</textarea>
                                <p class="mt-1.5 text-xs text-stone-400 dark:text-stone-500">Paste your GA4 tracking script. This will be injected into the site header.</p>
                                @error('ga4_tracking_code')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-3 justify-between">
                                {{-- Test Connection --}}
                                <button type="button" @click="testGa4()"
                                        :disabled="testingGa4"
                                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-stone-200 dark:bg-neutral-700 hover:bg-stone-300 dark:hover:bg-neutral-600 text-stone-700 dark:text-stone-300 text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-amber-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                    {{-- Spinner --}}
                                    <svg x-show="testingGa4" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg x-show="!testingGa4" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span x-text="testingGa4 ? 'Testing...' : 'Test Connection'"></span>
                                </button>

                                {{-- Save --}}
                                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    Save Settings
                                </button>
                            </div>

                            {{-- Test Result Message --}}
                            <template x-if="ga4Result">
                                <div class="mt-4 px-4 py-3 rounded-xl text-sm"
                                     :class="ga4Result.success ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300'">
                                    <span x-text="ga4Result.message"></span>
                                </div>
                            </template>
                        </form>
                    </div>
                </div>

                {{-- Info Panel (1/3) --}}
                <div class="lg:col-span-1">
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300">What GA4 Integration Provides</h3>
                        </div>
                        <ul class="space-y-2 text-sm text-blue-700 dark:text-blue-300">
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Page views and user engagement per blog post
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Traffic sources and user demographics
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Content performance metrics
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Real-time visitor data
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- API Tab --}}
        {{-- ============================================================ --}}
        <div x-show="activeTab === 'api'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Main Panel (2/3) --}}
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">

                        @if($settings['api']['api_key'])
                            <div class="mb-6 flex items-center gap-3 px-4 py-3 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-sm font-medium text-green-700 dark:text-green-300">API key is active</span>
                            </div>
                        @else
                            <div class="mb-6 flex items-center gap-3 px-4 py-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                <span class="text-sm font-medium text-amber-700 dark:text-amber-300">No API key — generate one below</span>
                            </div>
                        @endif

                        <h2 class="text-lg font-semibold text-warm-900 dark:text-warm-100 mb-6">API Access</h2>

                        {{-- Newly generated key flash --}}
                        @if(session('new_api_key'))
                            <div class="mb-6 px-4 py-4 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                                <p class="text-sm font-medium text-blue-800 dark:text-blue-300 mb-2">Your new API key (copy it now — it won't be shown again):</p>
                                <div class="flex items-center gap-2">
                                    <code class="flex-1 px-3 py-2 bg-white dark:bg-neutral-900 border border-blue-300 dark:border-blue-700 rounded-lg text-sm font-mono text-blue-900 dark:text-blue-200 select-all break-all">{{ session('new_api_key') }}</code>
                                    <button type="button" onclick="navigator.clipboard.writeText('{{ session('new_api_key') }}')" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition">
                                        Copy
                                    </button>
                                </div>
                            </div>
                        @endif

                        <div class="space-y-4">
                            {{-- Generate / Regenerate --}}
                            <form method="POST" action="{{ route('admin.settings.generate-api-key') }}" onsubmit="return confirm('{{ $settings['api']['api_key'] ? 'This will invalidate the current API key. Continue?' : 'Generate a new API key?' }}')">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/></svg>
                                    {{ $settings['api']['api_key'] ? 'Regenerate API Key' : 'Generate API Key' }}
                                </button>
                            </form>

                            {{-- Revoke --}}
                            @if($settings['api']['api_key'])
                                <form method="POST" action="{{ route('admin.settings.revoke-api-key') }}" onsubmit="return confirm('Revoke API key? All API access will stop immediately.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        Revoke API Key
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Info Panel (1/3) --}}
                <div class="lg:col-span-1">
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300">API Usage</h3>
                        </div>
                        <div class="space-y-3 text-sm text-blue-700 dark:text-blue-300">
                            <p>Use the API key as a Bearer token in the <code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-900/40 rounded text-xs">Authorization</code> header:</p>
                            <code class="block px-3 py-2 bg-white dark:bg-neutral-900 border border-blue-200 dark:border-blue-800 rounded-lg text-xs font-mono break-all">Authorization: Bearer ak_xxx...</code>
                            <p class="font-medium text-blue-800 dark:text-blue-200 mt-4">Available endpoints:</p>
                            <ul class="space-y-1.5 text-xs">
                                <li><code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-900/40 rounded">GET/POST</code> /api/posts</li>
                                <li><code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-900/40 rounded">GET/PUT/DELETE</code> /api/posts/{id}</li>
                                <li><code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-900/40 rounded">CRUD</code> /api/posts/{id}/faqs</li>
                                <li><code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-900/40 rounded">CRUD</code> /api/posts/{id}/howto-steps</li>
                                <li><code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-900/40 rounded">POST</code> /api/posts/{id}/cover</li>
                                <li><code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-900/40 rounded">POST</code> /api/media/upload</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- Image Optimization (ShortPixel) Tab --}}
        {{-- ============================================================ --}}
        <div x-show="activeTab === 'shortpixel'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Main Panel (2/3) --}}
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
                        <h2 class="text-lg font-semibold text-warm-900 dark:text-warm-100 mb-6">ShortPixel Image Optimization</h2>

                        <form method="POST" action="{{ route('admin.settings.update.shortpixel') }}">
                            @csrf
                            @method('PUT')

                            {{-- Enabled --}}
                            <label class="flex items-center gap-3 mb-5 cursor-pointer">
                                <input type="checkbox" name="shortpixel_enabled" value="1"
                                       {{ old('shortpixel_enabled', $settings['shortpixel']['shortpixel_enabled']) ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-warm-300 dark:border-neutral-600 text-amber-600 focus:ring-amber-500">
                                <span class="text-sm font-medium text-stone-700 dark:text-stone-300">Enable ShortPixel optimization on save</span>
                            </label>

                            {{-- API Key --}}
                            <div class="mb-5">
                                <label for="shortpixel_api_key" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">API Key</label>
                                <input type="password" name="shortpixel_api_key" id="shortpixel_api_key" autocomplete="off"
                                       value="{{ old('shortpixel_api_key', $settings['shortpixel']['shortpixel_api_key']) }}"
                                       placeholder="Your ShortPixel API key"
                                       class="w-full bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg px-4 py-2.5 text-sm text-warm-900 dark:text-warm-100 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                                <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Stored encrypted. Leave the masked value untouched to keep the current key. Get a key at <span class="font-mono">shortpixel.com</span>.</p>
                                @error('shortpixel_api_key')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </div>

                            {{-- Compression --}}
                            <div class="mb-5">
                                <label for="shortpixel_compression" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Compression</label>
                                <select name="shortpixel_compression" id="shortpixel_compression"
                                        class="w-full bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg px-4 py-2.5 text-sm text-warm-900 dark:text-warm-100 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                                    @foreach(['lossy' => 'Lossy (smallest)', 'glossy' => 'Glossy (balanced)', 'lossless' => 'Lossless (highest quality)'] as $value => $label)
                                        <option value="{{ $value }}" {{ old('shortpixel_compression', $settings['shortpixel']['shortpixel_compression']) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Modern output formats --}}
                            @php($enabledFormats = old('image_formats', $settings['shortpixel']['image_formats']))
                            <div class="mb-5">
                                <span class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Modern formats</span>
                                <div class="flex flex-wrap gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="image_formats[]" value="webp"
                                               {{ in_array('webp', (array) $enabledFormats, true) ? 'checked' : '' }}
                                               class="w-4 h-4 rounded border-warm-300 dark:border-neutral-600 text-amber-600 focus:ring-amber-500">
                                        <span class="text-sm text-stone-700 dark:text-stone-300">WebP</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="image_formats[]" value="avif"
                                               {{ in_array('avif', (array) $enabledFormats, true) ? 'checked' : '' }}
                                               class="w-4 h-4 rounded border-warm-300 dark:border-neutral-600 text-amber-600 focus:ring-amber-500">
                                        <span class="text-sm text-stone-700 dark:text-stone-300">AVIF</span>
                                    </label>
                                </div>
                                <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Served via <code>&lt;picture&gt;</code> with the original as fallback. <strong>AVIF</strong> requires an imagick/GD build with AVIF support.</p>
                            </div>

                            {{-- Keep EXIF --}}
                            <label class="flex items-center gap-3 mb-6 cursor-pointer">
                                <input type="checkbox" name="shortpixel_keep_exif" value="1"
                                       {{ old('shortpixel_keep_exif', $settings['shortpixel']['shortpixel_keep_exif']) ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-warm-300 dark:border-neutral-600 text-amber-600 focus:ring-amber-500">
                                <span class="text-sm font-medium text-stone-700 dark:text-stone-300">Keep EXIF metadata</span>
                            </label>

                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
                                Save Image Optimization Settings
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Info Panel (1/3) --}}
                <div class="lg:col-span-1">
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300">How it works</h3>
                        </div>
                        <div class="space-y-3 text-sm text-blue-700 dark:text-blue-300">
                            <p>When enabled, saving a post queues each content image for optimization via the ShortPixel API, then writes the optimized image back in place.</p>
                            <p class="font-medium text-blue-800 dark:text-blue-200">Requirements:</p>
                            <ul class="space-y-1.5 text-xs list-disc list-inside">
                                <li>A running queue worker (<code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-900/40 rounded">php artisan queue:work</code>).</li>
                                <li>Content images must be publicly reachable by ShortPixel.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection