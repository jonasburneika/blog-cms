@props([])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-sand-200 dark:border-neutral-700 overflow-hidden']) }}>
    <div class="overflow-x-auto admin-scrollbar">
        <table class="min-w-full divide-y divide-sand-200 dark:divide-neutral-700">
            @isset($head)
                <thead class="bg-sand-50 dark:bg-neutral-800/60">
                    <tr>
                        {{ $head }}
                    </tr>
                </thead>
            @endisset

            <tbody class="divide-y divide-sand-100 dark:divide-neutral-700/60">
                {{ $slot }}
            </tbody>

            @isset($foot)
                <tfoot class="bg-sand-50 dark:bg-neutral-800/60 border-t border-sand-200 dark:border-neutral-700">
                    {{ $foot }}
                </tfoot>
            @endisset
        </table>
    </div>
</div>
