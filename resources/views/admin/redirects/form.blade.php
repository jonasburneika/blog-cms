@php
    $isEdit = isset($redirect);
    $sourceVal = $isEdit ? $redirect->source_type.':'.$redirect->source_id : '';
    $targetVal = $isEdit ? $redirect->target_type.':'.$redirect->target_id : '';
    $statusVal = old('status_code', $isEdit ? $redirect->status_code : \BlogCms\Models\BlogRedirect::STATUS_PERMANENT);
@endphp

<div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-warm-200 dark:border-neutral-700 p-6">
    <form method="POST" action="{{ $action }}">
        @csrf
        @if($isEdit) @method('PUT') @endif

        @include('blog::admin.partials.page-select', [
            'name' => 'source',
            'label' => 'Redirect from (source page)',
            'selected' => $sourceVal,
            'placeholder' => '— Select the page to redirect —',
            'help' => 'Visitors who open this page will be sent to the target below.',
            'pages' => $pages,
        ])

        @include('blog::admin.partials.page-select', [
            'name' => 'target',
            'label' => 'Redirect to (target page)',
            'selected' => $targetVal,
            'placeholder' => '— Select the destination —',
            'help' => 'Where the visitor lands. The target’s current URL is resolved automatically, so it keeps working even if the target is later renamed.',
            'pages' => $pages,
        ])

        {{-- Redirect type --}}
        <div class="mb-6">
            <label for="status_code" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1.5">Redirect type</label>
            <select name="status_code" id="status_code"
                    class="w-full px-4 py-2.5 bg-warm-50 dark:bg-neutral-900 border border-warm-300 dark:border-neutral-600 rounded-lg text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                @foreach(\BlogCms\Models\BlogRedirect::STATUSES as $code => $label)
                    <option value="{{ $code }}" {{ (int) $statusVal === $code ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">301 = permanent move (passes SEO value to the target). 302 = temporary.</p>
            @error('status_code')
                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-warm-200 dark:border-neutral-700">
            <a href="{{ route('admin.redirects.index') }}"
               class="px-4 py-2.5 bg-stone-200 dark:bg-neutral-700 text-stone-700 dark:text-stone-300 font-medium rounded-lg hover:bg-stone-300 dark:hover:bg-neutral-600 transition">
                Cancel
            </a>
            <button type="submit"
                    class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800">
                {{ $isEdit ? 'Update Redirect' : 'Save Redirect' }}
            </button>
        </div>
    </form>
</div>
