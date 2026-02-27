<x-filament::section :heading="__('Payment Summary')">
    <x-slot name="afterHeader">
        <x-filament::icon
            icon="heroicon-o-banknotes"
            class="h-5 w-5 text-gray-400"
        />
    </x-slot>

    <div class="flex flex-col gap-4">

        {{-- Three financial boxes --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

            <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('Total Invoiced') }}</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($totalInvoiced, 3) }}
                    <span class="text-sm font-normal text-gray-400">OMR</span>
                </p>
            </div>

            <div class="rounded-xl bg-emerald-50 dark:bg-emerald-950/50 p-4">
                <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">{{ __('Collected') }}</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700 dark:text-emerald-300">
                    {{ number_format($totalCollected, 3) }}
                    <span class="text-sm font-normal text-emerald-500">OMR</span>
                </p>
            </div>

            <div class="rounded-xl bg-amber-50 dark:bg-amber-950/50 p-4">
                <p class="text-xs font-medium text-amber-600 dark:text-amber-400 uppercase tracking-wide">{{ __('Remaining') }}</p>
                <p class="mt-2 text-2xl font-bold text-amber-700 dark:text-amber-300">
                    {{ number_format($totalRemaining, 3) }}
                    <span class="text-sm font-normal text-amber-500">OMR</span>
                </p>
            </div>

        </div>

        {{-- Collection Rate — full width row --}}
        <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Collection Rate') }}</span>
                    <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ $collectionRate }}%</span>
                </div>
                <div class="flex gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 dark:bg-emerald-900 px-3 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        {{ $paidCount }} {{ __('Paid') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 dark:bg-amber-900 px-3 py-1 text-xs font-semibold text-amber-700 dark:text-amber-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        {{ $partialCount }} {{ __('Partial') }}
                    </span>
                </div>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div
                    class="bg-emerald-500 h-3 rounded-full transition-all duration-500"
                    style="width: {{ min($collectionRate, 100) }}%"
                ></div>
            </div>
        </div>

    </div>
</x-filament::section>
