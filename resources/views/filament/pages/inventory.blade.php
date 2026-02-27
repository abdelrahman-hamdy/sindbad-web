<x-filament-panels::page>
    <div wire:init="loadProducts" class="space-y-4">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3">
            {{-- Search --}}
            <div class="flex-1 min-w-48">
                <x-filament::input.wrapper>
                    <x-slot name="prefix">
                        <x-filament::icon
                            icon="heroicon-m-magnifying-glass"
                            class="h-5 w-5 text-gray-400"
                        />
                    </x-slot>
                    <x-filament::input
                        wire:model.live.debounce.600ms="search"
                        type="search"
                        placeholder="{{ __('Search products...') }}"
                    />
                </x-filament::input.wrapper>
            </div>

            {{-- Refresh --}}
            <x-filament::button
                wire:click="refresh"
                wire:loading.attr="disabled"
                color="gray"
                icon="heroicon-m-arrow-path"
            >
                <span wire:loading.remove wire:target="refresh">{{ __('Refresh') }}</span>
                <span wire:loading wire:target="refresh">{{ __('Refreshing…') }}</span>
            </x-filament::button>

            {{-- Total badge --}}
            @if (! $isLoading)
                <x-filament::badge color="gray">
                    {{ number_format($total) }} {{ __('products') }}
                </x-filament::badge>
            @endif
        </div>

        {{-- Loading skeleton --}}
        @if ($isLoading)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden animate-pulse">
                <div class="p-4 space-y-3">
                    @foreach(range(1, 10) as $_)
                        <div class="flex items-center gap-4">
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-12"></div>
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded flex-1"></div>
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-32"></div>
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-20"></div>
                        </div>
                    @endforeach
                </div>
            </div>

        {{-- Empty state --}}
        @elseif (count($products) === 0)
            <x-filament::section>
                <div class="py-12 text-center">
                    <x-filament::icon
                        icon="heroicon-o-cube"
                        class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-4"
                    />
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        @if ($search)
                            {{ __('No products match') }} <strong>"{{ $search }}"</strong>
                        @else
                            {{ __('No products found in Odoo.') }}
                        @endif
                    </p>
                </div>
            </x-filament::section>

        {{-- Products table --}}
        @else
            <x-filament::section :padding="false">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800/60">
                            <tr class="text-left">
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 w-16">#</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Product Name') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Category') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-right">{{ __('Price (OMR)') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700/60">
                            @foreach ($products as $product)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                                    <td class="px-4 py-3 text-gray-400 dark:text-gray-500 font-mono text-xs">
                                        {{ $product['id'] }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-900 dark:text-white font-medium max-w-lg">
                                        <span class="line-clamp-2 leading-snug">{{ $product['name'] }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @php $cat = is_array($product['categ_id']) ? $product['categ_id'][1] : null; @endphp
                                        @if ($cat)
                                            @php
                                                // Strip the "All / Saleable / " prefix for cleaner display
                                                $catLabel = preg_replace('/^All\s*\/\s*Saleable\s*\/?\s*/i', '', $cat);
                                                $catLabel = $catLabel ?: $cat;
                                            @endphp
                                            <x-filament::badge color="gray" size="sm">
                                                {{ $catLabel }}
                                            </x-filament::badge>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-gray-700 dark:text-gray-300 tabular-nums">
                                        {{ number_format((float) ($product['list_price'] ?? 0), 3) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>

            {{-- Pagination --}}
            @if ($totalPages > 1)
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Showing') }} {{ number_format(($page - 1) * $perPage + 1) }}–{{ number_format(min($page * $perPage, $total)) }}
                        {{ __('of') }} {{ number_format($total) }}
                    </p>
                    <div class="flex items-center gap-2">
                        <x-filament::button
                            wire:click="prevPage"
                            :disabled="$page <= 1"
                            color="gray"
                            size="sm"
                            icon="heroicon-m-chevron-left"
                            icon-position="before"
                        >
                            {{ __('Previous') }}
                        </x-filament::button>

                        <span class="text-sm text-gray-600 dark:text-gray-400 px-2">
                            {{ __('Page') }} {{ $page }} / {{ $totalPages }}
                        </span>

                        <x-filament::button
                            wire:click="nextPage"
                            :disabled="$page >= $totalPages"
                            color="gray"
                            size="sm"
                            icon="heroicon-m-chevron-right"
                            icon-position="after"
                        >
                            {{ __('Next') }}
                        </x-filament::button>
                    </div>
                </div>
            @endif
        @endif

        {{-- Cache note --}}
        @if (! $isLoading)
            <p class="text-xs text-gray-400 dark:text-gray-600">
                {{ __('Data cached for 30 minutes. Use Refresh to fetch the latest from Odoo.') }}
            </p>
        @endif

    </div>
</x-filament-panels::page>
