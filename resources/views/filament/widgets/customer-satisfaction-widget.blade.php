<x-filament::section :heading="__('Customer Satisfaction')">
    <x-slot name="afterHeader">
        <x-filament::icon
            icon="heroicon-o-star"
            class="h-5 w-5 text-amber-400"
        />
    </x-slot>

    <div class="flex flex-col gap-4">

        {{-- Overall average + per-category --}}
        <div class="flex items-center gap-4">
            <div class="text-center">
                <span class="text-3xl font-extrabold text-amber-500">{{ $overallAvg }}</span>
                <div class="flex justify-center mt-0.5">
                    @for($s = 1; $s <= 5; $s++)
                        <svg class="h-3.5 w-3.5 {{ $s <= round($overallAvg) ? 'text-amber-400' : 'text-gray-200 dark:text-gray-700' }}"
                             fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor
                </div>
                <p class="text-xs text-gray-400 mt-0.5">{{ __('Overall') }}</p>
            </div>
            <div class="h-10 border-l border-gray-100 dark:border-gray-800"></div>
            <div class="flex gap-4 text-center">
                <div>
                    <p class="text-base font-bold text-gray-800 dark:text-gray-200">{{ $ratingStats['avg_product'] }}</p>
                    <p class="text-xs text-gray-500">{{ __('Product') }}</p>
                </div>
                <div>
                    <p class="text-base font-bold text-gray-800 dark:text-gray-200">{{ $ratingStats['avg_service'] }}</p>
                    <p class="text-xs text-gray-500">{{ __('Service') }}</p>
                </div>
            </div>
            <div class="ml-auto text-right">
                <p class="text-xs text-gray-400">{{ __('Based on') }}</p>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $ratingStats['total_ratings'] }} {{ __('ratings') }}</p>
            </div>
        </div>

        {{-- Star distribution bars --}}
        @php $maxCount = max(array_values($breakdown) ?: [1]); @endphp
        <div class="flex flex-col gap-1.5">
            @foreach(array_reverse([1, 2, 3, 4, 5], true) as $star => $ignored)
                @php
                    $count = $breakdown[$star] ?? 0;
                    $pct   = $maxCount > 0 ? round($count / $maxCount * 100) : 0;
                    $barColor = match(true) {
                        $star >= 4 => 'bg-emerald-500',
                        $star === 3 => 'bg-amber-400',
                        default    => 'bg-red-400',
                    };
                @endphp
                <div class="flex items-center gap-2">
                    <span class="w-4 text-xs font-semibold text-gray-600 dark:text-gray-400 text-right shrink-0">{{ $star }}</span>
                    <svg class="h-3 w-3 text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-2">
                        <div class="{{ $barColor }} h-2 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                    </div>
                    <span class="w-6 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 shrink-0">{{ $count }}</span>
                </div>
            @endforeach
        </div>

    </div>
</x-filament::section>
