@extends('blog::layouts.admin')

@section('page-title', 'Hook Templates')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition">Dashboard</a>
    <span class="text-stone-400 dark:text-stone-500 mx-2">/</span>
    <span class="text-stone-500 dark:text-stone-400">Hook Templates</span>
@endsection

@section('content')
    <div x-data="{
        templates: [],
        types: [],
        loading: false,
        saving: false,
        message: '',
        error: '',
        showModal: false,
        editingTemplate: null,
        activeFilter: 'all',
        form: {
            name: '',
            type: 'custom_html',
            description: '',
            html_content: '',
            css_class: '',
            is_active: true
        },

        async loadTemplates() {
            this.loading = true;
            try {
                const [templatesRes, typesRes] = await Promise.all([
                    fetch('{{ \BlogCms\Support\AdminPath::url() }}/hook-templates', { headers: { 'Accept': 'application/json' } }),
                    fetch('{{ \BlogCms\Support\AdminPath::url() }}/hook-templates/types', { headers: { 'Accept': 'application/json' } })
                ]);
                this.templates = await templatesRes.json();
                this.types = await typesRes.json();
            } catch (e) {
                this.error = 'Failed to load templates';
            } finally {
                this.loading = false;
            }
        },

        get filteredTemplates() {
            if (this.activeFilter === 'all') return this.templates;
            return this.templates.filter(t => t.type === this.activeFilter);
        },

        openCreate() {
            this.editingTemplate = null;
            this.form = { name: '', type: 'custom_html', description: '', html_content: '', css_class: '', is_active: true };
            this.showModal = true;
        },

        openEdit(template) {
            this.editingTemplate = template;
            this.form = {
                name: template.name,
                type: template.type || 'custom_html',
                description: template.description || '',
                html_content: template.html_content || '',
                css_class: template.css_class || '',
                is_active: template.is_active
            };
            this.showModal = true;
        },

        async saveTemplate() {
            if (!this.form.name) { this.error = 'Template name is required'; return; }
            this.saving = true;
            this.message = '';
            this.error = '';
            try {
                const isNew = !this.editingTemplate;
                const url = isNew
                    ? '{{ \BlogCms\Support\AdminPath::url() }}/hook-templates'
                    : `{{ \BlogCms\Support\AdminPath::url() }}/hook-templates/${this.editingTemplate.id}`;
                const res = await fetch(url, {
                    method: isNew ? 'POST' : 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]').content
                    },
                    body: JSON.stringify(this.form)
                });
                if (!res.ok) { const err = await res.json(); throw new Error(err.message || 'Failed'); }
                this.showModal = false;
                await this.loadTemplates();
                this.message = isNew ? 'Template created' : 'Template updated';
                setTimeout(() => this.message = '', 3000);
            } catch (e) {
                this.error = e.message || 'Failed to save template';
                setTimeout(() => this.error = '', 3000);
            } finally {
                this.saving = false;
            }
        },

        async duplicateTemplate(template) {
            try {
                const res = await fetch(`{{ \BlogCms\Support\AdminPath::url() }}/hook-templates/${template.id}/duplicate`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]').content
                    }
                });
                if (!res.ok) throw new Error('Failed');
                await this.loadTemplates();
                this.message = 'Template duplicated';
                setTimeout(() => this.message = '', 3000);
            } catch (e) {
                this.error = 'Failed to duplicate template';
                setTimeout(() => this.error = '', 3000);
            }
        },

        async deleteTemplate(template) {
            if (!confirm('Delete this template? Hooks using it will keep their current content but lose the template link.')) return;
            try {
                await fetch(`{{ \BlogCms\Support\AdminPath::url() }}/hook-templates/${template.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]').content
                    }
                });
                await this.loadTemplates();
                this.message = 'Template deleted';
                setTimeout(() => this.message = '', 3000);
            } catch (e) {
                this.error = 'Failed to delete template';
                setTimeout(() => this.error = '', 3000);
            }
        },

        typeBadgeClass(type) {
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

        typeLabel(type) {
            const map = {
                'related_posts': 'Related Posts',
                'ad': 'Ad',
                'newsletter': 'Newsletter',
                'cta': 'CTA',
                'custom_html': 'Custom HTML',
                'divider': 'Divider'
            };
            return map[type] || type;
        }
    }" x-init="loadTemplates()">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <h1 class="text-2xl font-bold text-stone-800 dark:text-stone-200">Hook Templates</h1>
            <button type="button" @click="openCreate()"
                    class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Create Template
            </button>
        </div>

        {{-- Success/Error messages --}}
        <div x-show="message" x-transition class="mb-4 px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-700 dark:text-green-400" x-text="message"></div>
        <div x-show="error" x-transition class="mb-4 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-400" x-text="error"></div>

        {{-- Type filter tabs --}}
        <div class="mb-6 border-b border-warm-200 dark:border-neutral-700">
            <nav class="flex gap-1 -mb-px flex-wrap">
                <button type="button" @click="activeFilter = 'all'"
                        :class="activeFilter === 'all'
                            ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                            : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 rounded-t-lg transition">
                    All
                </button>
                <button type="button" @click="activeFilter = 'related_posts'"
                        :class="activeFilter === 'related_posts'
                            ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                            : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 rounded-t-lg transition">
                    Related Posts
                </button>
                <button type="button" @click="activeFilter = 'ad'"
                        :class="activeFilter === 'ad'
                            ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                            : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 rounded-t-lg transition">
                    Ad
                </button>
                <button type="button" @click="activeFilter = 'newsletter'"
                        :class="activeFilter === 'newsletter'
                            ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                            : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 rounded-t-lg transition">
                    Newsletter
                </button>
                <button type="button" @click="activeFilter = 'cta'"
                        :class="activeFilter === 'cta'
                            ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                            : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 rounded-t-lg transition">
                    CTA
                </button>
                <button type="button" @click="activeFilter = 'custom_html'"
                        :class="activeFilter === 'custom_html'
                            ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                            : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 rounded-t-lg transition">
                    Custom HTML
                </button>
                <button type="button" @click="activeFilter = 'divider'"
                        :class="activeFilter === 'divider'
                            ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
                            : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 hover:border-stone-300 dark:hover:border-neutral-600'"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 rounded-t-lg transition">
                    Divider
                </button>
            </nav>
        </div>

        {{-- Loading state --}}
        <div x-show="loading" class="text-center py-16">
            <svg class="w-8 h-8 mx-auto text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="text-stone-500 dark:text-stone-400 mt-2">Loading templates...</p>
        </div>

        {{-- Empty state --}}
        <div x-show="!loading && filteredTemplates.length === 0" class="text-center py-16">
            <svg class="w-16 h-16 mx-auto text-stone-300 dark:text-stone-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
            </svg>
            <h3 class="text-lg font-medium text-stone-600 dark:text-stone-400 mb-1">No templates found</h3>
            <p class="text-stone-500 dark:text-stone-400 mb-4">Create your first hook template to get started.</p>
            <button type="button" @click="openCreate()"
                    class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Create Template
            </button>
        </div>

        {{-- Templates grid --}}
        <div x-show="!loading && filteredTemplates.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <template x-for="template in filteredTemplates" :key="template.id">
                <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 overflow-hidden flex flex-col">
                    {{-- Card header --}}
                    <div class="p-5 flex-1">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-semibold text-stone-800 dark:text-stone-200 truncate" x-text="template.name"></h3>
                                <div class="flex items-center gap-2 mt-1.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                          :class="typeBadgeClass(template.type)" x-text="typeLabel(template.type)"></span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                          :class="template.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-stone-100 text-stone-500 dark:bg-stone-800 dark:text-stone-500'"
                                          x-text="template.is_active ? 'Active' : 'Inactive'"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Description --}}
                        <p x-show="template.description" class="text-xs text-stone-500 dark:text-stone-400 mb-3 line-clamp-2" x-text="template.description"></p>

                        {{-- HTML preview --}}
                        <div x-show="template.html_content" class="mb-3">
                            <p class="text-xs font-medium text-stone-500 dark:text-stone-400 mb-1">HTML Preview</p>
                            <div class="bg-warm-50 dark:bg-neutral-900 border border-warm-200 dark:border-neutral-600 rounded-lg p-3 max-h-24 overflow-hidden relative">
                                <pre class="text-xs text-stone-600 dark:text-stone-400 font-mono whitespace-pre-wrap break-all" x-text="template.html_content?.substring(0, 200) + (template.html_content?.length > 200 ? '...' : '')"></pre>
                                <div class="absolute bottom-0 left-0 right-0 h-6 bg-gradient-to-t from-warm-50 dark:from-neutral-900 to-transparent"></div>
                            </div>
                        </div>

                        {{-- Live preview --}}
                        <div x-show="template.html_content" class="mb-2">
                            <p class="text-xs font-medium text-stone-500 dark:text-stone-400 mb-1">Rendered</p>
                            <div class="border border-warm-200 dark:border-neutral-600 rounded-lg p-3 max-h-28 overflow-hidden relative bg-white dark:bg-neutral-900">
                                <div x-html="template.html_content" class="text-sm"></div>
                                <div class="absolute bottom-0 left-0 right-0 h-6 bg-gradient-to-t from-white dark:from-neutral-900 to-transparent"></div>
                            </div>
                        </div>

                        {{-- CSS class --}}
                        <div x-show="template.css_class" class="text-xs text-stone-400 dark:text-stone-500">
                            Class: <code class="bg-stone-100 dark:bg-neutral-700 px-1.5 py-0.5 rounded" x-text="template.css_class"></code>
                        </div>
                    </div>

                    {{-- Card actions --}}
                    <div class="border-t border-warm-200 dark:border-neutral-700 px-5 py-3 flex items-center justify-end gap-2 bg-warm-50/50 dark:bg-neutral-900/50">
                        <button type="button" @click="openEdit(template)"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/40 transition">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit
                        </button>
                        <button type="button" @click="duplicateTemplate(template)"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            Duplicate
                        </button>
                        <button type="button" @click="deleteTemplate(template)"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete
                        </button>
                    </div>
                </div>
            </template>
        </div>

        {{-- Create/Edit Modal --}}
        <div x-show="showModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
                <div class="relative bg-white dark:bg-neutral-800 rounded-xl shadow-xl border border-warm-200 dark:border-neutral-700 max-w-3xl w-full p-6 z-10 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-stone-800 dark:text-stone-200" x-text="editingTemplate ? 'Edit Template' : 'Create Template'"></h3>
                        <button type="button" @click="showModal = false" class="p-1.5 rounded-lg text-stone-400 hover:text-stone-600 dark:hover:text-stone-300 hover:bg-stone-100 dark:hover:bg-neutral-700 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div class="lg:col-span-2">
                            {{-- Name --}}
                            <div class="mb-5">
                                <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Name <span class="text-red-500">*</span></label>
                                <input type="text" x-model="form.name"
                                       class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                                       placeholder="e.g. Newsletter Signup Box, Ad Banner">
                            </div>
                        </div>

                        {{-- Type --}}
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Type</label>
                            <select x-model="form.type"
                                    class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                                <option value="related_posts">Related Posts</option>
                                <option value="ad">Ad</option>
                                <option value="newsletter">Newsletter</option>
                                <option value="cta">CTA</option>
                                <option value="custom_html">Custom HTML</option>
                                <option value="divider">Divider</option>
                            </select>
                        </div>

                        {{-- CSS Class --}}
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">CSS Class</label>
                            <input type="text" x-model="form.css_class"
                                   class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                                   placeholder="e.g. hook-newsletter, hook-cta-primary">
                        </div>

                        {{-- Description --}}
                        <div class="lg:col-span-2 mb-5">
                            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Description</label>
                            <textarea x-model="form.description" rows="2"
                                      class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition resize-y"
                                      placeholder="Brief description of what this template does..."></textarea>
                        </div>

                        {{-- HTML Content --}}
                        <div class="lg:col-span-2 mb-5">
                            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">HTML Content</label>
                            <textarea x-model="form.html_content" rows="12"
                                      class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition resize-y font-mono text-sm"
                                      placeholder="<div class='hook-content'>&#10;  Your HTML content here...&#10;</div>"></textarea>
                        </div>

                        {{-- Live Preview --}}
                        <div class="lg:col-span-2 mb-5">
                            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Live Preview</label>
                            <div class="border border-warm-200 dark:border-neutral-600 rounded-lg p-4 bg-white dark:bg-neutral-900 min-h-[80px] max-h-[200px] overflow-auto">
                                <div x-show="form.html_content" x-html="form.html_content" class="text-sm"></div>
                                <p x-show="!form.html_content" class="text-sm text-stone-400 dark:text-stone-500 italic">Start typing HTML content to see a live preview...</p>
                            </div>
                        </div>

                        {{-- Active toggle --}}
                        <div class="lg:col-span-2 mb-2">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="form.is_active" class="sr-only peer">
                                <div class="w-11 h-6 bg-stone-200 dark:bg-neutral-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-neutral-600 peer-checked:bg-amber-600"></div>
                                <span class="ml-3 text-sm font-medium text-stone-700 dark:text-stone-300">Active</span>
                            </label>
                        </div>
                    </div>

                    {{-- Modal actions --}}
                    <div class="flex items-center justify-end gap-3 pt-5 border-t border-warm-200 dark:border-neutral-700 mt-4">
                        <button type="button" @click="showModal = false"
                                class="px-4 py-2 bg-stone-200 dark:bg-neutral-700 text-stone-700 dark:text-stone-300 font-medium rounded-lg hover:bg-stone-300 dark:hover:bg-neutral-600 transition">
                            Cancel
                        </button>
                        <button type="button" @click="saveTemplate()" :disabled="saving"
                                class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg shadow-sm transition disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
                            <span x-show="!saving" x-text="editingTemplate ? 'Update Template' : 'Create Template'"></span>
                            <span x-show="saving">Saving...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
