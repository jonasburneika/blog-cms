@extends('blog::layouts.admin')

@section('page-title', 'Translations')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="hover:text-warm-700 dark:hover:text-warm-300 transition-colors">Dashboard</a>
    <span class="mx-1.5">/</span>
    <span class="text-warm-700 dark:text-warm-300">Translations</span>
@endsection

@section('content')
<div x-data="translationManager()" class="space-y-6">

    {{-- Top Bar --}}
    <div class="bg-white dark:bg-neutral-800 rounded-2xl border border-sand-200 dark:border-neutral-700 p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">

            {{-- Locale Selector --}}
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-warm-700 dark:text-warm-300 whitespace-nowrap">Locale:</label>
                <select x-model="selectedLocale" @change="navigateToSelection()"
                        class="rounded-lg border-sand-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-warm-900 dark:text-warm-100 text-sm focus:ring-earth-500 focus:border-earth-500 px-3 py-2">
                    @foreach($locales as $loc)
                        <option value="{{ $loc }}" {{ $locale === $loc ? 'selected' : '' }}>{{ strtoupper($loc) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Group Selector --}}
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-warm-700 dark:text-warm-300 whitespace-nowrap">Group:</label>
                <select x-model="selectedGroup" @change="navigateToSelection()"
                        class="rounded-lg border-sand-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-warm-900 dark:text-warm-100 text-sm focus:ring-earth-500 focus:border-earth-500 px-3 py-2">
                    @foreach($groups as $grp)
                        <option value="{{ $grp }}" {{ $group === $grp ? 'selected' : '' }}>{{ $grp }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1"></div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap items-center gap-2">
                {{-- Add Locale Toggle --}}
                <button @click="showAddLocale = !showAddLocale" type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg border border-sand-300 dark:border-neutral-600 text-warm-700 dark:text-warm-300 hover:bg-sand-50 dark:hover:bg-neutral-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582"/></svg>
                    Add Locale
                </button>

                {{-- Import from Files --}}
                <form method="POST" action="{{ route('admin.translations.import') }}" class="inline">
                    @csrf
                    <input type="hidden" name="locale" value="{{ $locale }}">
                    <input type="hidden" name="group" value="{{ $group }}">
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg border border-sand-300 dark:border-neutral-600 text-warm-700 dark:text-warm-300 hover:bg-sand-50 dark:hover:bg-neutral-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Import from Files
                    </button>
                </form>

                {{-- Add Translation --}}
                <button @click="showAddTranslation = true" type="button"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg bg-earth-500 text-white hover:bg-earth-600 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Add Translation
                </button>
            </div>
        </div>

        {{-- Add Locale inline form --}}
        <div x-show="showAddLocale" x-collapse class="mt-4 pt-4 border-t border-sand-200 dark:border-neutral-700">
            <form method="POST" action="{{ route('admin.translations.add-locale') }}" class="flex items-end gap-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-warm-700 dark:text-warm-300 mb-1">Locale Code</label>
                    <input type="text" name="locale" placeholder="e.g. lt, de, fr" required pattern="^[a-z]{2}(_[A-Z]{2})?$"
                           class="rounded-lg border-sand-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-warm-900 dark:text-warm-100 text-sm focus:ring-earth-500 focus:border-earth-500 px-3 py-2 w-32">
                </div>
                <button type="submit" class="px-4 py-2 text-sm font-medium rounded-lg bg-earth-500 text-white hover:bg-earth-600 transition-colors">
                    Add
                </button>
                <button type="button" @click="showAddLocale = false" class="px-4 py-2 text-sm font-medium rounded-lg border border-sand-300 dark:border-neutral-600 text-warm-700 dark:text-warm-300 hover:bg-sand-50 dark:hover:bg-neutral-700 transition-colors">
                    Cancel
                </button>
            </form>
        </div>
    </div>

    {{-- Search & Stats --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-sand-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" x-model="searchQuery" placeholder="Filter keys..."
                   class="pl-10 pr-4 py-2 rounded-lg border-sand-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-warm-900 dark:text-warm-100 text-sm focus:ring-earth-500 focus:border-earth-500 w-full sm:w-72">
        </div>
        <p class="text-sm text-sand-500 dark:text-sand-400">
            Showing <span x-text="filteredTranslations.length" class="font-semibold text-warm-700 dark:text-warm-300"></span> translations,
            <span x-text="overriddenCount" class="font-semibold text-amber-600 dark:text-amber-400"></span> overridden
        </p>
    </div>

    {{-- Translations Table --}}
    <div class="bg-white dark:bg-neutral-800 rounded-2xl border border-sand-200 dark:border-neutral-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-sand-200 dark:border-neutral-700 bg-sand-50 dark:bg-neutral-800/50">
                        <th class="text-left px-4 py-3 font-semibold text-warm-700 dark:text-warm-300 w-1/4">Key</th>
                        <th class="text-left px-4 py-3 font-semibold text-warm-700 dark:text-warm-300 w-1/4">File Value</th>
                        <th class="text-left px-4 py-3 font-semibold text-warm-700 dark:text-warm-300 w-1/3">Admin Override</th>
                        <th class="text-center px-4 py-3 font-semibold text-warm-700 dark:text-warm-300 w-20">Status</th>
                        <th class="text-center px-4 py-3 font-semibold text-warm-700 dark:text-warm-300 w-24">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(t, idx) in filteredTranslations" :key="t.key">
                        <tr class="border-b border-sand-100 dark:border-neutral-700/50 hover:bg-sand-50/50 dark:hover:bg-neutral-700/30 transition-colors">
                            {{-- Key --}}
                            <td class="px-4 py-3">
                                <code class="text-xs font-mono bg-sand-100 dark:bg-neutral-700 text-warm-800 dark:text-warm-200 px-2 py-1 rounded" x-text="t.key"></code>
                            </td>
                            {{-- File Value --}}
                            <td class="px-4 py-3">
                                <span class="text-sand-500 dark:text-sand-400 text-sm" x-text="t.file_value || '—'"></span>
                            </td>
                            {{-- Admin Override Input --}}
                            <td class="px-4 py-3">
                                <input type="text" x-model="t.override_value"
                                       :placeholder="t.file_value || 'Enter translation...'"
                                       class="w-full rounded-lg border-sand-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-warm-900 dark:text-warm-100 text-sm focus:ring-earth-500 focus:border-earth-500 px-3 py-1.5"
                                       :class="t.is_dirty ? 'ring-2 ring-amber-400/50 border-amber-400' : ''"
                                       @input="t.is_dirty = (t.override_value !== (t.db_value || ''))">
                            </td>
                            {{-- Status --}}
                            <td class="px-4 py-3 text-center">
                                <span x-show="t.is_overridden" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300">
                                    Override
                                </span>
                                <span x-show="!t.is_overridden" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-sand-100 dark:bg-neutral-700 text-sand-600 dark:text-sand-400">
                                    File
                                </span>
                            </td>
                            {{-- Actions --}}
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    {{-- Save --}}
                                    <button @click="saveTranslation(t)" :disabled="t.is_saving"
                                            class="p-1.5 rounded-lg text-earth-600 dark:text-earth-400 hover:bg-earth-50 dark:hover:bg-earth-900/30 transition-colors disabled:opacity-50"
                                            title="Save override">
                                        <svg x-show="!t.is_saving" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        <svg x-show="t.is_saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    </button>
                                    {{-- Reset (delete override) --}}
                                    <button x-show="t.is_overridden" @click="resetTranslation(t)" :disabled="t.is_saving"
                                            class="p-1.5 rounded-lg text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors disabled:opacity-50"
                                            title="Remove override (use file value)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-if="filteredTranslations.length === 0">
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-sand-500 dark:text-sand-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-sand-300 dark:text-neutral-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3"/></svg>
                                <p class="text-sm font-medium">No translations found</p>
                                <p class="text-xs mt-1">Try importing from files or adding a new translation.</p>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add Translation Modal --}}
    <div x-show="showAddTranslation" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showAddTranslation = false" x-cloak>
        <div x-show="showAddTranslation" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             class="w-full max-w-lg bg-white dark:bg-neutral-800 rounded-2xl border border-sand-200 dark:border-neutral-700 shadow-xl">
            <div class="px-6 py-4 border-b border-sand-200 dark:border-neutral-700">
                <h3 class="text-lg font-bold text-warm-900 dark:text-warm-100">Add Translation</h3>
                <p class="text-sm text-sand-500 dark:text-sand-400 mt-0.5">Add a new translation key for <strong x-text="selectedLocale.toUpperCase()"></strong> / <strong x-text="selectedGroup"></strong></p>
            </div>
            <form @submit.prevent="addTranslation()" class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-warm-700 dark:text-warm-300 mb-1">Key</label>
                    <input type="text" x-model="newKey" required placeholder="e.g. hero.subtitle"
                           class="w-full rounded-lg border-sand-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-warm-900 dark:text-warm-100 text-sm focus:ring-earth-500 focus:border-earth-500 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-warm-700 dark:text-warm-300 mb-1">Value</label>
                    <textarea x-model="newValue" rows="3" placeholder="The translated text..."
                              class="w-full rounded-lg border-sand-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-warm-900 dark:text-warm-100 text-sm focus:ring-earth-500 focus:border-earth-500 px-3 py-2"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showAddTranslation = false; newKey = ''; newValue = '';"
                            class="px-4 py-2 text-sm font-medium rounded-lg border border-sand-300 dark:border-neutral-600 text-warm-700 dark:text-warm-300 hover:bg-sand-50 dark:hover:bg-neutral-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" :disabled="!newKey"
                            class="px-4 py-2 text-sm font-medium rounded-lg bg-earth-500 text-white hover:bg-earth-600 transition-colors shadow-sm disabled:opacity-50">
                        Save Translation
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Toast Notification --}}
    <div x-show="toast.show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-6 right-6 z-50 max-w-sm" x-cloak>
        <div class="rounded-xl border px-4 py-3 shadow-lg text-sm font-medium"
             :class="toast.type === 'success' ? 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800 text-green-700 dark:text-green-300' : 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800 text-red-700 dark:text-red-300'">
            <span x-text="toast.message"></span>
        </div>
    </div>
</div>

@push('scripts')
<script>
function translationManager() {
    return {
        selectedLocale: '{{ $locale }}',
        selectedGroup: '{{ $group }}',
        searchQuery: '',
        showAddLocale: false,
        showAddTranslation: false,
        newKey: '',
        newValue: '',
        toast: { show: false, message: '', type: 'success' },
        translations: @json($translations).map(t => ({
            ...t,
            override_value: t.db_value || '',
            is_dirty: false,
            is_saving: false,
        })),

        get filteredTranslations() {
            if (!this.searchQuery) return this.translations;
            const q = this.searchQuery.toLowerCase();
            return this.translations.filter(t =>
                t.key.toLowerCase().includes(q) ||
                (t.file_value && t.file_value.toLowerCase().includes(q)) ||
                (t.db_value && t.db_value.toLowerCase().includes(q))
            );
        },

        get overriddenCount() {
            return this.translations.filter(t => t.is_overridden).length;
        },

        navigateToSelection() {
            window.location.href = `{{ route('admin.translations.index') }}?locale=${this.selectedLocale}&group=${this.selectedGroup}`;
        },

        async saveTranslation(t) {
            t.is_saving = true;
            try {
                const response = await fetch('{{ route('admin.translations.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        locale: this.selectedLocale,
                        group: this.selectedGroup,
                        key: t.key,
                        value: t.override_value || null,
                    }),
                });
                const data = await response.json();
                if (data.success) {
                    t.db_value = t.override_value;
                    t.is_overridden = !!(t.override_value);
                    t.is_dirty = false;
                    this.showToast('Translation saved.', 'success');
                }
            } catch (e) {
                this.showToast('Error saving translation.', 'error');
            } finally {
                t.is_saving = false;
            }
        },

        async resetTranslation(t) {
            if (!confirm('Remove this override? The file-based translation will be used instead.')) return;
            t.is_saving = true;
            try {
                const response = await fetch('{{ route('admin.translations.destroy') }}', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        locale: this.selectedLocale,
                        group: this.selectedGroup,
                        key: t.key,
                    }),
                });
                const data = await response.json();
                if (data.success) {
                    t.db_value = null;
                    t.override_value = '';
                    t.is_overridden = false;
                    t.is_dirty = false;
                    this.showToast('Override removed.', 'success');
                }
            } catch (e) {
                this.showToast('Error removing override.', 'error');
            } finally {
                t.is_saving = false;
            }
        },

        async addTranslation() {
            try {
                const response = await fetch('{{ route('admin.translations.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        locale: this.selectedLocale,
                        group: this.selectedGroup,
                        key: this.newKey,
                        value: this.newValue,
                    }),
                });
                const data = await response.json();
                if (data.success) {
                    this.translations.push({
                        key: this.newKey,
                        file_value: null,
                        db_value: this.newValue,
                        is_overridden: true,
                        override_value: this.newValue,
                        is_dirty: false,
                        is_saving: false,
                    });
                    this.translations.sort((a, b) => a.key.localeCompare(b.key));
                    this.newKey = '';
                    this.newValue = '';
                    this.showAddTranslation = false;
                    this.showToast('Translation added.', 'success');
                }
            } catch (e) {
                this.showToast('Error adding translation.', 'error');
            }
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 3000);
        },
    };
}
</script>
@endpush
@endsection
