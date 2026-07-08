@php
    $isEdit = isset($author);
    $bioVal = old('bio', $isEdit ? $author->getTranslation('bio', $locale, false) : '');
    $links = old('social_links', $isEdit ? $author->socialLinks() : []);
    if (empty($links)) { $links = [['label' => '', 'url' => '']]; }
    $userId = old('user_id', $isEdit ? $author->user_id : '');
    $knowsAboutVal = old('knows_about', $isEdit ? implode("\n", $author->knowsAbout()) : '');
    $creds = old('credentials', $isEdit ? $author->credentials() : []);
    if (empty($creds)) { $creds = [['name' => '', 'category' => '']]; }
@endphp

<div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
        @csrf
        @if($isEdit) @method('PUT') @endif
        <input type="hidden" name="locale" value="{{ $locale }}">

        {{-- User --}}
        <div class="mb-5">
            <label for="user_id" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">User</label>
            <select name="user_id" id="user_id"
                    class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                <option value="">— Select a user —</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ (string) $userId === (string) $u->id ? 'selected' : '' }}>{{ $u->name }} — {{ $u->email }}</option>
                @endforeach
            </select>
            @error('user_id')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
            {{-- Display name --}}
            <div>
                <label for="display_name" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Display name (byline)</label>
                <input type="text" name="display_name" id="display_name" value="{{ old('display_name', $isEdit ? $author->display_name : '') }}"
                       class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                       placeholder="Falls back to the user's name">
            </div>
            {{-- Role --}}
            <div>
                <label for="role" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Role / title</label>
                <input type="text" name="role" id="role" value="{{ old('role', $isEdit ? $author->role : '') }}"
                       class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                       placeholder="e.g. Soil Scientist">
            </div>
        </div>

        {{-- Avatar --}}
        <div class="mb-5">
            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Avatar</label>
            <div class="flex items-center gap-4">
                @if($isEdit && $author->avatarUrl('thumb'))
                    <img src="{{ $author->avatarUrl('thumb') }}" class="w-16 h-16 rounded-full object-cover border border-warm-200 dark:border-neutral-700">
                @endif
                <input type="file" name="avatar" accept="image/*"
                       class="text-sm text-stone-600 dark:text-stone-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 dark:file:bg-amber-900/20 dark:file:text-amber-400">
            </div>
            @error('avatar')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        {{-- Bio (translatable) --}}
        @include('blog::admin.partials.locale-switcher')
        <div class="mb-5">
            <label for="bio" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Bio</label>
            <textarea name="bio" id="bio" rows="5" x-data="{ words: 0 }"
                      x-init="words = $el.value.trim().split(/\s+/).filter(Boolean).length"
                      @input="words = $el.value.trim().split(/\s+/).filter(Boolean).length"
                      class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition resize-y"
                      placeholder="A descriptive, professional bio — specific background, years of experience, and passion for the topic.">{{ $bioVal }}</textarea>
            <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Aim for a specific ~150-word bio. Feeds schema.org <code>description</code> on the author's Person node. <span x-text="words + ' words'"></span></p>
        </div>

        {{-- Verification links (schema.org sameAs) --}}
        <div class="mb-5" x-data="{ links: @js(array_values($links)) }">
            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Profiles &amp; verification links (sameAs)</label>
            <p class="mb-2 text-xs text-stone-400 dark:text-stone-500">Anything that proves a real, credible human: personal site, GitHub, a hosted CV/certificate PDF, Medium, a professional Reddit or niche-forum profile — not only mainstream social. Exposed as schema.org <code>sameAs</code>.</p>
            <template x-for="(link, i) in links" :key="i">
                <div class="flex items-center gap-2 mb-2">
                    <input type="text" x-model="link.label" :name="'social_links['+i+'][label]'" placeholder="Label (e.g. LinkedIn)"
                           class="w-1/3 px-3 py-2 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-sm text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <input type="url" x-model="link.url" :name="'social_links['+i+'][url]'" placeholder="https://…"
                           class="flex-1 px-3 py-2 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-sm text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <button type="button" @click="links.splice(i, 1)" class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg" title="Remove">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>
            <button type="button" @click="links.push({label:'',url:''})"
                    class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-stone-600 dark:text-stone-300 bg-stone-100 dark:bg-neutral-700 rounded-lg hover:bg-stone-200 dark:hover:bg-neutral-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Add link
            </button>
        </div>

        {{-- Areas of expertise (schema.org knowsAbout) --}}
        <div class="mb-5">
            <label for="knows_about" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Areas of expertise (knowsAbout)</label>
            <textarea name="knows_about" id="knows_about" rows="4"
                      class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition resize-y"
                      placeholder="One topic per line, e.g.&#10;Living Soil Systems&#10;Compost Tea Brewing&#10;Permaculture Design">{{ $knowsAboutVal }}</textarea>
            <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">One topic per line. Exposed as schema.org <code>knowsAbout</code>.</p>
        </div>

        {{-- Credentials (schema.org hasCredential) --}}
        <div class="mb-5" x-data="{ creds: @js(array_values($creds)) }">
            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Credentials (hasCredential)</label>
            <template x-for="(c, i) in creds" :key="i">
                <div class="flex items-center gap-2 mb-2">
                    <input type="text" x-model="c.name" :name="'credentials['+i+'][name]'" placeholder="Name (e.g. Permaculture Design Certification)"
                           class="flex-1 px-3 py-2 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-sm text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <input type="text" x-model="c.category" :name="'credentials['+i+'][category]'" placeholder="Category (e.g. Certification)"
                           class="w-1/3 px-3 py-2 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-sm text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <button type="button" @click="creds.splice(i, 1)" class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg" title="Remove">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>
            <button type="button" @click="creds.push({name:'',category:''})"
                    class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-stone-600 dark:text-stone-300 bg-stone-100 dark:bg-neutral-700 rounded-lg hover:bg-stone-200 dark:hover:bg-neutral-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Add credential
            </button>
            <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Degrees, certifications or licenses. Exposed as schema.org <code>hasCredential</code> (EducationalOccupationalCredential).</p>
        </div>

        {{-- Indexable --}}
        <div class="mb-6">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="hidden" name="is_indexable" value="0">
                <input type="checkbox" name="is_indexable" value="1"
                       {{ old('is_indexable', $isEdit ? ($author->is_indexable ? '1' : '0') : '0') == '1' ? 'checked' : '' }}
                       class="rounded border-warm-300 dark:border-neutral-600 text-amber-600 focus:ring-amber-500">
                <span class="text-sm font-medium text-stone-700 dark:text-stone-300">Author page is indexable</span>
            </label>
            <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Leave off for thin profiles. When on, the author URL is exposed in the Article schema.</p>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-warm-200 dark:border-neutral-700">
            <a href="{{ route('admin.authors.index') }}" class="px-4 py-2.5 bg-stone-200 dark:bg-neutral-700 text-stone-700 dark:text-stone-300 font-medium rounded-lg hover:bg-stone-300 dark:hover:bg-neutral-600 transition">Cancel</a>
            <button type="submit" class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
                {{ $isEdit ? 'Update Author' : 'Save Author' }}
            </button>
        </div>
    </form>
</div>
