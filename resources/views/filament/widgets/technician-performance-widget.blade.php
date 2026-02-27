<x-filament::section :heading="__('Technician Performance')">
    <x-slot name="afterHeader">
        <x-filament::icon
            icon="heroicon-o-users"
            class="h-5 w-5 text-gray-400"
        />
    </x-slot>

    <div class="grid grid-cols-2 gap-5">

        {{-- Top Performers --}}
        <div>
            <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide mb-3">
                {{ __('Top Performers') }}
            </p>
            @php $topMax = $top->max('completed_count') ?: 1; @endphp
            @forelse($top as $tech)
                <div class="flex items-center gap-2 mb-2.5">
                    <span class="w-24 truncate text-xs text-gray-700 dark:text-gray-300 shrink-0" title="{{ $tech->name }}">
                        {{ $tech->name }}
                    </span>
                    <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-2">
                        <div
                            class="bg-emerald-500 h-2 rounded-full transition-all duration-500"
                            style="width: {{ round($tech->completed_count / $topMax * 100) }}%"
                        ></div>
                    </div>
                    <span class="w-6 text-right text-xs font-bold text-emerald-600 dark:text-emerald-400 shrink-0">
                        {{ $tech->completed_count }}
                    </span>
                </div>
            @empty
                <p class="text-xs text-gray-400">{{ __('No data yet') }}</p>
            @endforelse
        </div>

        {{-- Needs Attention --}}
        <div>
            <p class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wide mb-3">
                {{ __('Needs Attention') }}
            </p>
            @php $botMax = $bottom->max('completed_count') ?: 1; @endphp
            @forelse($bottom as $tech)
                <div class="flex items-center gap-2 mb-2.5">
                    <span class="w-24 truncate text-xs text-gray-700 dark:text-gray-300 shrink-0" title="{{ $tech->name }}">
                        {{ $tech->name }}
                    </span>
                    <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-2">
                        <div
                            class="bg-amber-400 h-2 rounded-full transition-all duration-500"
                            style="width: {{ round($tech->completed_count / $botMax * 100) }}%"
                        ></div>
                    </div>
                    <span class="w-6 text-right text-xs font-bold text-amber-600 dark:text-amber-400 shrink-0">
                        {{ $tech->completed_count }}
                    </span>
                </div>
            @empty
                <p class="text-xs text-gray-400">{{ __('No data yet') }}</p>
            @endforelse
        </div>

    </div>
</x-filament::section>
