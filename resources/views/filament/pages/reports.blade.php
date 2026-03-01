<x-filament-panels::page>
@once
    @push('styles')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    @endpush
@endonce
@php
    $medals = ['🥇', '🥈', '🥉'];
    $starSvg = '<svg class="inline h-4 w-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
    $starSvgSm = '<svg class="inline h-3.5 w-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
    $starSvgGray = '<svg class="inline h-3.5 w-3.5 text-gray-200 dark:text-gray-700" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
    $overallAvg = round(($ratingStats['avg_product'] + $ratingStats['avg_service']) / 2, 1);
@endphp

{{-- ═══════════════════════════════════════════════════════════
     A. PERIOD FILTER BAR
═══════════════════════════════════════════════════════════ --}}
<div class="overflow-hidden rounded-xl shadow-sm bg-gradient-to-br from-[#E0F5F8] to-[#C2EBF3] dark:from-[#0d2d32] dark:to-[#091e23] mb-6">
    <div class="p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-base font-bold text-[#006A7A] dark:text-cyan-300">{{ __('Filter Period') }}</h2>
            <div class="flex flex-wrap gap-2">
                @foreach([
                    'today' => __('Today'),
                    '7'     => __('7 Days'),
                    '30'    => __('30 Days'),
                    '90'    => __('90 Days'),
                    'all'   => __('All Time'),
                ] as $value => $label)
                    <button
                        wire:click="$set('period', '{{ $value }}')"
                        class="px-3 py-1.5 text-sm font-semibold rounded-lg transition-all duration-150 focus:outline-none
                            {{ $period === $value
                                ? 'bg-[#008BA0] text-white shadow-sm dark:bg-cyan-600'
                                : 'bg-[rgba(0,139,160,0.15)] text-[#006A7A] dark:bg-cyan-400/10 dark:text-cyan-300' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     B. KPI HERO ROW
═══════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">

    {{-- Total Requests --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 flex flex-col items-center text-center">
        <div class="w-9 h-9 rounded-lg bg-cyan-100 dark:bg-cyan-900/30 flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-[#008BA0]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </div>
        <span class="text-4xl font-extrabold text-[#008BA0] dark:text-cyan-400">{{ $filteredStats['total'] }}</span>
        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Total Requests') }}</span>
    </div>

    {{-- Completed --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 flex flex-col items-center text-center">
        <div class="w-9 h-9 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <span class="text-4xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ $filteredStats['completed'] }}</span>
        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Completed') }}</span>
        <span class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold mt-0.5">{{ $filteredStats['completion_rate'] }}%</span>
    </div>

    {{-- Cancelled --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 flex flex-col items-center text-center">
        <div class="w-9 h-9 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <span class="text-4xl font-extrabold text-red-500 dark:text-red-400">{{ $filteredStats['canceled'] }}</span>
        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Cancelled') }}</span>
    </div>

    {{-- Active --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 flex flex-col items-center text-center">
        <div class="w-9 h-9 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </div>
        <span class="text-4xl font-extrabold text-blue-600 dark:text-blue-400">{{ $filteredStats['active'] }}</span>
        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Active') }}</span>
    </div>

    {{-- Avg Rating --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 flex flex-col items-center text-center">
        <div class="w-9 h-9 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
        </div>
        <span class="text-4xl font-extrabold text-amber-500 dark:text-amber-400">{{ $ratingStats['avg_service'] }}</span>
        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Avg Rating') }}</span>
    </div>

    {{-- Avg Completion Time --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 flex flex-col items-center text-center">
        <div class="w-9 h-9 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <span class="text-4xl font-extrabold text-indigo-600 dark:text-indigo-400">{{ $avgCompletion }}</span>
        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Avg Hours') }}</span>
    </div>

</div>

{{-- ═══════════════════════════════════════════════════════════
     C. TRENDS CHART + SERVICE TYPE BREAKDOWN
═══════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    {{-- Trends Chart --}}
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#008BA0]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Request Volume Trend') }}</h3>
                </div>
                <div class="flex gap-1">
                    @foreach(['7' => '7D', '30' => '30D', '90' => '90D'] as $tv => $tl)
                        <button
                            wire:click="$set('trendPeriod', '{{ $tv }}')"
                            class="px-2.5 py-1 text-xs font-semibold rounded-md transition-all
                                {{ $trendPeriod === $tv
                                    ? 'bg-[#008BA0] text-white shadow-sm dark:bg-cyan-600'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                        >
                            {{ $tl }}
                        </button>
                    @endforeach
                </div>
            </div>
            {{-- Chart canvas — wire:ignore keeps Alpine/Chart.js in control, $wire.$watch re-renders on data change --}}
            <div class="p-4"
                x-data="{
                    chart: null,
                    init() {
                        if (typeof Chart === 'undefined') return;
                        this.build(@json($trendData));
                        $wire.$watch('trendData', (data) => this.build(data));
                    },
                    build(data) {
                        if (this.chart) this.chart.destroy();
                        const isDark = document.documentElement.classList.contains('dark');
                        const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';
                        const tickColor = isDark ? '#9ca3af' : '#6b7280';
                        this.chart = new Chart(this.$refs.canvas, {
                            type: 'line',
                            data: {
                                labels: data.labels,
                                datasets: [
                                    { label: '{{ __('Service') }}',      data: data.service,      borderColor: '#0EA5E9', backgroundColor: 'rgba(14,165,233,0.12)',  tension: 0.4, fill: true, pointRadius: 3 },
                                    { label: '{{ __('Installation') }}', data: data.installation, borderColor: '#8B5CF6', backgroundColor: 'rgba(139,92,246,0.10)', tension: 0.4, fill: true, pointRadius: 3 },
                                ]
                            },
                            options: {
                                responsive: true,
                                interaction: { mode: 'index', intersect: false },
                                plugins: { legend: { position: 'top', labels: { color: tickColor, font: { size: 12 } } } },
                                scales: {
                                    x: { ticks: { color: tickColor, maxTicksLimit: 10 }, grid: { color: gridColor } },
                                    y: { beginAtZero: true, ticks: { stepSize: 1, color: tickColor }, grid: { color: gridColor } }
                                }
                            }
                        });
                    }
                }"
                wire:ignore
            >
                <canvas x-ref="canvas" height="130"></canvas>
            </div>
        </div>
    </div>

    {{-- Service Type Breakdown --}}
    <div class="lg:col-span-1">
        <x-filament::section :heading="__('Request Type Split')" icon="heroicon-o-squares-2x2">
            @php
                $typeTotal = $typeBreakdown['service'] + $typeBreakdown['installation'];
                $servicePct = $typeTotal > 0 ? round($typeBreakdown['service'] / $typeTotal * 100) : 0;
                $installPct = $typeTotal > 0 ? round($typeBreakdown['installation'] / $typeTotal * 100) : 0;
            @endphp

            <div class="space-y-4">
                {{-- Service bar --}}
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-semibold text-sky-600 dark:text-sky-400">{{ __('Service') }}</span>
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ $typeBreakdown['service'] }} <span class="text-xs text-gray-400 font-normal">({{ $servicePct }}%)</span></span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-3">
                        <div class="bg-sky-500 h-3 rounded-full transition-all duration-500" style="width: {{ $servicePct }}%"></div>
                    </div>
                </div>

                {{-- Installation bar --}}
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-semibold text-violet-600 dark:text-violet-400">{{ __('Installation') }}</span>
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ $typeBreakdown['installation'] }} <span class="text-xs text-gray-400 font-normal">({{ $installPct }}%)</span></span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-3">
                        <div class="bg-violet-500 h-3 rounded-full transition-all duration-500" style="width: {{ $installPct }}%"></div>
                    </div>
                </div>

                @if (!empty($typeBreakdown['by_service_type']))
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-sky-600 dark:text-sky-400 mb-2">{{ __('Service Sub-types') }}</p>
                        @php $subMax = max(array_values($typeBreakdown['by_service_type']) ?: [1]); @endphp
                        @foreach ($typeBreakdown['by_service_type'] as $sType => $sCount)
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-20 truncate text-xs text-gray-600 dark:text-gray-400 shrink-0">{{ ucfirst($sType) }}</span>
                                <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-2">
                                    <div class="bg-sky-400 h-2 rounded-full" style="width: {{ $subMax > 0 ? round($sCount / $subMax * 100) : 0 }}%"></div>
                                </div>
                                <span class="w-6 text-right text-xs font-bold text-sky-600 dark:text-sky-400 shrink-0">{{ $sCount }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>

</div>

{{-- ═══════════════════════════════════════════════════════════
     D. TECHNICIAN PERFORMANCE
═══════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <x-filament::section :heading="__('Technician Performance')" icon="heroicon-o-user-group">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Top Performers --}}
            <div>
                <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide mb-3">
                    {{ __('Top Performers') }}
                </p>
                @php $topMax = !empty($leaderboard) ? max(array_column($leaderboard, 'completed_count') ?: [1]) : 1; @endphp
                @forelse($leaderboard as $i => $tech)
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-6 text-center text-sm shrink-0">
                            {{ $i < 3 ? $medals[$i] : ($i + 1) }}
                        </span>
                        <span class="w-24 truncate text-xs text-gray-700 dark:text-gray-300 shrink-0" title="{{ $tech['name'] }}">
                            {{ $tech['name'] }}
                        </span>
                        <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-2.5">
                            <div
                                class="bg-emerald-500 h-2.5 rounded-full transition-all duration-500"
                                style="width: {{ $topMax > 0 ? round(($tech['completed_count'] ?? 0) / $topMax * 100) : 0 }}%"
                            ></div>
                        </div>
                        <span class="w-6 text-right text-xs font-bold text-emerald-600 dark:text-emerald-400 shrink-0">
                            {{ $tech['completed_count'] ?? 0 }}
                        </span>
                        @if (!empty($tech['avg_rating']))
                            <span class="text-xs text-amber-500 font-medium shrink-0">⭐ {{ $tech['avg_rating'] }}</span>
                        @else
                            <span class="text-xs text-gray-300 dark:text-gray-600 shrink-0">⭐ —</span>
                        @endif
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
                @php $botMax = !empty($bottomPerformers) ? max(array_column($bottomPerformers, 'completed_count') ?: [1]) : 1; @endphp
                @forelse($bottomPerformers as $i => $tech)
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-6 text-center text-xs font-medium text-gray-500 shrink-0">{{ $i + 1 }}</span>
                        <span class="w-24 truncate text-xs text-gray-700 dark:text-gray-300 shrink-0" title="{{ $tech['name'] }}">
                            {{ $tech['name'] }}
                        </span>
                        <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-2.5">
                            <div
                                class="bg-amber-400 h-2.5 rounded-full transition-all duration-500"
                                style="width: {{ $botMax > 0 ? round(($tech['completed_count'] ?? 0) / $botMax * 100) : 0 }}%"
                            ></div>
                        </div>
                        <span class="w-6 text-right text-xs font-bold text-amber-600 dark:text-amber-400 shrink-0">
                            {{ $tech['completed_count'] ?? 0 }}
                        </span>
                        @if (!empty($tech['avg_rating']))
                            <span class="text-xs text-amber-500 font-medium shrink-0">⭐ {{ $tech['avg_rating'] }}</span>
                        @else
                            <span class="text-xs text-gray-300 dark:text-gray-600 shrink-0">⭐ —</span>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-gray-400">{{ __('No data yet') }}</p>
                @endforelse
            </div>

        </div>
    </x-filament::section>
</div>

{{-- ═══════════════════════════════════════════════════════════
     E. CUSTOMER SATISFACTION
═══════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <x-filament::section :heading="__('Customer Satisfaction')" icon="heroicon-o-star">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Left: Overall Score --}}
            <div class="flex flex-col items-center justify-center py-4">
                <span class="text-5xl font-extrabold text-amber-500">{{ $overallAvg }}</span>
                <div class="flex mt-2">
                    @for ($s = 1; $s <= 5; $s++)
                        @if ($s <= round($overallAvg))
                            {!! $starSvg !!}
                        @else
                            {!! $starSvgGray !!}
                        @endif
                    @endfor
                </div>
                <p class="text-xs text-gray-400 mt-1">{{ __('Based on') }} {{ $ratingStats['total_ratings'] }} {{ __('ratings') }}</p>
                <div class="flex gap-6 mt-4">
                    <div class="text-center">
                        <p class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $ratingStats['avg_product'] }}</p>
                        <p class="text-xs text-gray-500">{{ __('Product') }}</p>
                    </div>
                    <div class="h-10 border-l border-gray-200 dark:border-gray-700"></div>
                    <div class="text-center">
                        <p class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $ratingStats['avg_service'] }}</p>
                        <p class="text-xs text-gray-500">{{ __('Service') }}</p>
                    </div>
                </div>
            </div>

            {{-- Right: Star Distribution Bars --}}
            <div class="flex flex-col justify-center space-y-2">
                @php
                    $ratingTotal = array_sum($ratingBreakdown) ?: 1;
                    $maxBreak = max(array_values($ratingBreakdown) ?: [1]);
                @endphp
                @foreach(array_reverse([1, 2, 3, 4, 5], true) as $star => $ignored)
                    @php
                        $count = $ratingBreakdown[$star] ?? 0;
                        $pct   = $maxBreak > 0 ? round($count / $maxBreak * 100) : 0;
                        $pctOfTotal = round($count / $ratingTotal * 100);
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
                        <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-2.5">
                            <div class="{{ $barColor }} h-2.5 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="w-8 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 shrink-0">{{ $count }}</span>
                        <span class="w-8 text-right text-xs text-gray-400 shrink-0">{{ $pctOfTotal }}%</span>
                    </div>
                @endforeach
            </div>

        </div>
    </x-filament::section>
</div>

{{-- ═══════════════════════════════════════════════════════════
     F. TOP CUSTOMERS
═══════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <x-filament::section :heading="__('Top Customers by Spending')" icon="heroicon-o-trophy">
        @if (count($topCustomers) === 0)
            <div class="text-center py-8">
                <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <p class="text-sm text-gray-400">{{ __('No customer data yet.') }}</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($topCustomers as $i => $customer)
                    @php
                        $spent     = $customer['total_spent'];
                        $paid      = $customer['total_paid'];
                        $remaining = max(0, $spent - $paid);
                        $paidPct   = $spent > 0 ? round($paid / $spent * 100) : 0;
                    @endphp
                    <div class="relative bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 overflow-hidden">
                        {{-- Rank badge --}}
                        <div class="absolute top-3 end-3 text-lg">
                            {{ $i < 3 ? $medals[$i] : '#' . ($i + 1) }}
                        </div>

                        {{-- Customer info --}}
                        <div class="flex items-center gap-3 mb-3 pr-8">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center shrink-0">
                                <span class="text-sm font-bold text-white">{{ strtoupper(mb_substr($customer['name'], 0, 1)) }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $customer['name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $customer['phone'] }}</p>
                            </div>
                        </div>

                        {{-- Order count --}}
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-gray-500">{{ __('Orders') }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-[rgba(0,139,160,0.12)] text-[#006A7A] dark:bg-cyan-900/30 dark:text-cyan-300">
                                {{ $customer['order_count'] }}
                            </span>
                        </div>

                        {{-- Spend bar --}}
                        <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2 mb-2 overflow-hidden flex">
                            <div class="bg-emerald-500 h-2 rounded-l-full transition-all" style="width: {{ $paidPct }}%"></div>
                            <div class="bg-red-400 h-2 rounded-r-full transition-all" style="width: {{ 100 - $paidPct }}%"></div>
                        </div>

                        {{-- Amounts --}}
                        <div class="flex justify-between text-xs">
                            <span class="text-emerald-600 dark:text-emerald-400 font-medium">{{ number_format($paid, 3) }} {{ __('OMR paid') }}</span>
                            <span class="text-red-500 dark:text-red-400 font-medium">{{ number_format($remaining, 3) }} {{ __('rem.') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</div>

{{-- ═══════════════════════════════════════════════════════════
     G. DAILY ACTIVITY
═══════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <x-filament::section :heading="__('Daily Activity')" icon="heroicon-o-calendar-days">
        <x-slot name="afterHeader">
            <input
                type="date"
                wire:model.live="dailyDate"
                class="rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm px-3 py-1.5 focus:ring-2 focus:ring-[#008BA0] focus:border-[#008BA0]"
            >
        </x-slot>

        @if (count($dailyActivity) === 0)
            <div class="text-center py-8">
                <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-sm text-gray-400">{{ __('No completed requests on this date.') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                            <th class="pb-2 font-semibold">{{ __('Invoice') }}</th>
                            <th class="pb-2 font-semibold">{{ __('Type') }}</th>
                            <th class="pb-2 font-semibold">{{ __('Customer') }}</th>
                            <th class="pb-2 font-semibold">{{ __('Technician') }}</th>
                            <th class="pb-2 font-semibold">{{ __('Rating') }}</th>
                            <th class="pb-2 font-semibold">{{ __('Completed At') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($dailyActivity as $row)
                            <tr class="odd:bg-white even:bg-gray-50/50 dark:odd:bg-gray-800 dark:even:bg-gray-700/20 hover:bg-[rgba(0,139,160,0.04)] dark:hover:bg-cyan-900/10 transition-colors">
                                <td class="py-2.5 px-1 font-medium text-gray-900 dark:text-white">{{ $row['invoice_number'] ?? '#'.$row['id'] }}</td>
                                <td class="py-2.5 px-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        {{ $row['type'] === 'service'
                                            ? 'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300'
                                            : 'bg-violet-100 text-violet-800 dark:bg-violet-900/30 dark:text-violet-300' }}">
                                        {{ ucfirst($row['type']) }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-1 text-gray-700 dark:text-gray-300">{{ $row['customer'] }}</td>
                                <td class="py-2.5 px-1 text-gray-700 dark:text-gray-300">{{ $row['technician'] }}</td>
                                <td class="py-2.5 px-1">
                                    @if ($row['rating'])
                                        <div class="flex items-center gap-1">
                                            @for ($s = 1; $s <= 5; $s++)
                                                <svg class="h-3.5 w-3.5 {{ $s <= round($row['rating']) ? 'text-amber-400' : 'text-gray-200 dark:text-gray-700' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @endfor
                                            <span class="text-xs text-gray-500 ms-0.5">{{ $row['rating'] }}</span>
                                        </div>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">—</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-1 text-gray-500 dark:text-gray-400 text-xs">{{ $row['completed_at'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</div>

{{-- ═══════════════════════════════════════════════════════════
     H. LATEST RATINGS
═══════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <x-filament::section :heading="__('Latest Ratings')" icon="heroicon-o-chat-bubble-left-right">
        @if (count($latestRatings) === 0)
            <div class="text-center py-8">
                <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
                <p class="text-sm text-gray-400">{{ __('No ratings yet.') }}</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($latestRatings as $rating)
                    @php
                        $initial = strtoupper(mb_substr($rating['customer'], 0, 1));
                        $gradients = ['from-sky-400 to-blue-600', 'from-violet-400 to-purple-600', 'from-emerald-400 to-teal-600', 'from-amber-400 to-orange-600', 'from-rose-400 to-pink-600'];
                        $gradient = $gradients[abs(crc32($rating['customer'])) % count($gradients)];
                    @endphp
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">

                        {{-- Header: avatar + name + invoice --}}
                        <div class="flex items-start gap-3 mb-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br {{ $gradient }} flex items-center justify-center shrink-0">
                                <span class="text-sm font-bold text-white">{{ $initial }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $rating['customer'] }}</p>
                                <p class="text-xs text-gray-400">{{ $rating['invoice_number'] }}</p>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium shrink-0
                                {{ ($rating['type'] ?? '') === 'service'
                                    ? 'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300'
                                    : 'bg-violet-100 text-violet-800 dark:bg-violet-900/30 dark:text-violet-300' }}">
                                {{ ucfirst($rating['type'] ?? '-') }}
                            </span>
                        </div>

                        {{-- Ratings row --}}
                        <div class="flex items-center gap-4 mb-2">
                            @if ($rating['product_rating'])
                                <div class="flex items-center gap-1">
                                    <span class="text-xs text-gray-500">{{ __('Product') }}</span>
                                    {!! $starSvgSm !!}
                                    <span class="text-xs font-bold text-amber-500">{{ $rating['product_rating'] }}</span>
                                </div>
                            @endif
                            @if ($rating['service_rating'])
                                <div class="flex items-center gap-1">
                                    <span class="text-xs text-gray-500">{{ __('Service') }}</span>
                                    {!! $starSvgSm !!}
                                    <span class="text-xs font-bold text-amber-500">{{ $rating['service_rating'] }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- How found us --}}
                        @if ($rating['how_found_us'])
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 mb-2">
                                {{ $rating['how_found_us'] }}
                            </span>
                        @endif

                        {{-- Notes --}}
                        @if ($rating['notes'])
                            <p class="text-xs text-gray-600 dark:text-gray-400 italic line-clamp-2">"{{ $rating['notes'] }}"</p>
                        @endif

                        {{-- Timestamp --}}
                        <p class="text-xs text-gray-400 mt-2">{{ $rating['created_at'] }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</div>

</x-filament-panels::page>
