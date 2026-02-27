<x-filament-panels::page>
    @php
        $statusBadgeClass = fn(string $color) => match($color) {
            'success' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
            'info'    => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            'primary' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
            'danger'  => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            default   => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        };
    @endphp

    {{-- ─── Profile Header ─────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-6">
            {{-- Avatar --}}
            <div class="shrink-0 w-20 h-20 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center shadow-md">
                <span class="text-3xl font-bold text-white">
                    {{ strtoupper(mb_substr($customer->name, 0, 1)) }}
                </span>
            </div>
            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-3 mb-1">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white truncate">{{ $customer->name }}</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $customer->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                        {{ $customer->is_active ? __('Active') : __('Inactive') }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                        {{ __('Customer') }}
                    </span>
                </div>
                <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ $customer->phone }}
                    </span>
                    @if ($customer->odoo_id)
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            {{ __('Odoo ID') }}: {{ $customer->odoo_id }}
                        </span>
                    @endif
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ __('Joined') }} {{ $customer->created_at->format('M d, Y') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Stats Row ───────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        @php
            $statCards = [
                ['label' => __('Total Requests'),   'value' => $stats['total_requests'],                             'color' => 'text-gray-900 dark:text-white',   'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                ['label' => __('Completed'),         'value' => $stats['completed'],                                  'color' => 'text-green-600',                 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0'],
                ['label' => __('Avg Rating'),        'value' => $stats['avg_rating'] ?? 'N/A', 'color' => 'text-amber-500', 'star' => (bool) $stats['avg_rating'], 'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
                ['label' => __('Total Spent'),       'value' => number_format($stats['total_spent'], 3).' OMR',        'color' => 'text-blue-600',                  'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                ['label' => __('Outstanding'),       'value' => number_format($stats['outstanding'], 3).' OMR',        'color' => $stats['outstanding'] > 0 ? 'text-red-600' : 'text-gray-500 dark:text-gray-400', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0'],
            ];
        @endphp

        @foreach ($statCards as $card)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-start justify-between mb-2">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 leading-tight">{{ $card['label'] }}</p>
                    <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/>
                    </svg>
                </div>
                <p class="text-xl font-bold {{ $card['color'] }} flex items-center gap-1">
                    {{ $card['value'] }}
                    @if (!empty($card['star']))
                        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endif
                </p>
            </div>
        @endforeach
    </div>

    {{-- ─── Tab Navigation ─────────────────────────────────────────────── --}}
    @php
        $tabs = [
            'overview'      => ['label' => __('Overview'),      'badge' => null],
            'service'       => ['label' => __('Service'),        'badge' => $serviceTotal > 0 ? $serviceTotal : null],
            'installation'  => ['label' => __('Installations'),  'badge' => $installationTotal > 0 ? $installationTotal : null],
            'invoices'      => ['label' => __('Invoices'),       'badge' => count($manualOrders) > 0 ? count($manualOrders) : null],
            'orders'        => ['label' => __('Odoo Orders'),    'badge' => null],
            'ratings'       => ['label' => __('Ratings'),        'badge' => count($ratings) > 0 ? count($ratings) : null],
        ];
    @endphp

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        {{-- Tab Bar --}}
        <div class="flex border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
            @foreach ($tabs as $key => $tab)
                <button
                    wire:click="setTab('{{ $key }}')"
                    class="shrink-0 flex items-center gap-2 px-5 py-4 text-sm font-medium whitespace-nowrap transition border-b-2 -mb-px
                        {{ $activeTab === $key
                            ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600'
                        }}"
                >
                    {{ $tab['label'] }}
                    @if ($tab['badge'])
                        <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-xs font-semibold
                            {{ $activeTab === $key ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $tab['badge'] }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- ─── Tab: Overview ────────────────────────────────────────── --}}
        @if ($activeTab === 'overview')
            @php
                $totalReqs = $stats['total_requests'];
                $completedReqs = $stats['completed'];
                $completionRate = $totalReqs > 0 ? round($completedReqs / $totalReqs * 100) : 0;
                $paymentRate = $totalSpent > 0 ? round($totalPaid / $totalSpent * 100) : 0;
            @endphp
            <div class="p-6 space-y-6">

                {{-- ── Row 1: Profile + Request Breakdown ── --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                    {{-- Profile card --}}
                    <div class="lg:col-span-1 rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-950/40 dark:to-indigo-950/40 border border-blue-100 dark:border-blue-900/50 p-5 flex flex-col gap-4">
                        <p class="text-xs font-semibold text-blue-500 dark:text-blue-400 uppercase tracking-wider">{{ __('Account Info') }}</p>
                        <div class="space-y-3">
                            @foreach ([
                                ['icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',      'label' => __('Name'),         'value' => $customer->name,                              'mono' => false],
                                ['icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z', 'label' => __('Phone'),        'value' => $customer->phone,                             'mono' => true],
                                ['icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z', 'label' => __('Odoo ID'),      'value' => $customer->odoo_id ?? '—',                    'mono' => true],
                                ['icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',                                    'label' => __('Member Since'), 'value' => $customer->created_at->format('d M Y'),       'mono' => false],
                                ['icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0',                                                                                   'label' => __('Last Updated'), 'value' => $customer->updated_at->diffForHumans(),       'mono' => false],
                            ] as $row)
                                <div class="flex items-center gap-3">
                                    <div class="shrink-0 w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $row['icon'] }}"/>
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs text-blue-400 dark:text-blue-500">{{ $row['label'] }}</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate {{ $row['mono'] ? 'font-mono' : '' }}">{{ $row['value'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Request breakdown --}}
                    <div class="lg:col-span-2 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/50 p-5">
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Request Activity') }}</p>
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $totalReqs }} total</span>
                        </div>

                        {{-- Completion bar --}}
                        <div class="mb-5">
                            <div class="flex justify-between items-center mb-1.5">
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Completion Rate') }}</span>
                                <span class="text-xs font-semibold {{ $completionRate >= 70 ? 'text-emerald-600' : ($completionRate >= 40 ? 'text-amber-500' : 'text-red-500') }}">{{ $completionRate }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 {{ $completionRate >= 70 ? 'bg-emerald-500' : ($completionRate >= 40 ? 'bg-amber-400' : 'bg-red-400') }}"
                                     style="width: {{ $completionRate }}%"></div>
                            </div>
                        </div>

                        {{-- Breakdown grid --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @foreach ([
                                ['label' => __('Service'),       'value' => $serviceTotal,      'bg' => 'bg-sky-50 dark:bg-sky-950/30',     'text' => 'text-sky-700 dark:text-sky-300',     'sub' => 'text-sky-500 dark:text-sky-500'],
                                ['label' => __('Installation'),  'value' => $installationTotal, 'bg' => 'bg-violet-50 dark:bg-violet-950/30','text' => 'text-violet-700 dark:text-violet-300','sub' => 'text-violet-500 dark:text-violet-500'],
                                ['label' => __('Completed'),     'value' => $completedReqs,     'bg' => 'bg-emerald-50 dark:bg-emerald-950/30','text' => 'text-emerald-700 dark:text-emerald-300','sub' => 'text-emerald-500 dark:text-emerald-500'],
                                ['label' => __('Ratings Given'), 'value' => count($ratings),    'bg' => 'bg-amber-50 dark:bg-amber-950/30', 'text' => 'text-amber-700 dark:text-amber-300', 'sub' => 'text-amber-500 dark:text-amber-500'],
                            ] as $item)
                                <div class="rounded-xl {{ $item['bg'] }} p-3 text-center">
                                    <p class="text-2xl font-bold {{ $item['text'] }}">{{ $item['value'] }}</p>
                                    <p class="text-xs mt-0.5 {{ $item['sub'] }}">{{ $item['label'] }}</p>
                                </div>
                            @endforeach
                        </div>

                        {{-- Avg rating stars --}}
                        @if ($stats['avg_rating'])
                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center gap-3">
                                <div class="flex items-center gap-0.5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg class="w-4 h-4 {{ $i <= round($stats['avg_rating']) ? 'text-amber-400' : 'text-gray-200 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                </div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $stats['avg_rating'] }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('average rating across all requests') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ── Row 2: Financial Summary ── --}}
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/50 p-5">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">{{ __('Financial Summary') }}</p>

                    @if ($totalSpent > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-5">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalSpent, 3) }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ __('Total Invoiced (OMR)') }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($totalPaid, 3) }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ __('Total Paid (OMR)') }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold {{ $totalRemaining > 0 ? 'text-red-500 dark:text-red-400' : 'text-gray-400 dark:text-gray-500' }}">
                                    {{ number_format($totalRemaining, 3) }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ __('Outstanding (OMR)') }}</p>
                            </div>
                        </div>

                        {{-- Payment progress bar --}}
                        <div>
                            <div class="flex justify-between items-center mb-1.5">
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Payment Progress') }}</span>
                                <span class="text-xs font-semibold {{ $paymentRate >= 100 ? 'text-emerald-600' : ($paymentRate >= 50 ? 'text-amber-500' : 'text-red-500') }}">{{ $paymentRate }}% {{ __('Paid') }}</span>
                            </div>
                            <div class="h-3 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-700 {{ $paymentRate >= 100 ? 'bg-emerald-500' : ($paymentRate >= 50 ? 'bg-amber-400' : 'bg-red-400') }}"
                                     style="width: {{ min($paymentRate, 100) }}%"></div>
                            </div>
                            <div class="flex justify-between mt-1.5">
                                <span class="text-xs text-emerald-500">{{ number_format($totalPaid, 3) }} {{ __('Paid') }}</span>
                                @if ($totalRemaining > 0)
                                    <span class="text-xs text-red-400">{{ number_format($totalRemaining, 3) }} {{ __('Remaining') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ count($manualOrders) }} invoice{{ count($manualOrders) !== 1 ? 's' : '' }} total</span>
                            <button wire:click="setTab('invoices')" class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                {{ __('View all invoices') }} →
                            </button>
                        </div>
                    @else
                        <div class="py-8 text-center">
                            <svg class="w-10 h-10 mx-auto mb-2 text-gray-200 dark:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No invoices on record') }}</p>
                        </div>
                    @endif
                </div>

                {{-- ── Row 3: Recent Requests snippet ── --}}
                @if (count($serviceRequests) > 0 || count($installationRequests) > 0)
                    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/50 p-5">
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Recent Requests') }}</p>
                        </div>
                        <div class="space-y-2">
                            @foreach (array_slice(array_merge($serviceRequests, $installationRequests), 0, 4) as $req)
                                @php
                                    $dotColor = match($req['status_color']) {
                                        'success' => 'bg-emerald-500',
                                        'warning' => 'bg-amber-400',
                                        'info'    => 'bg-sky-400',
                                        'primary' => 'bg-indigo-500',
                                        'danger'  => 'bg-red-500',
                                        default   => 'bg-gray-400',
                                    };
                                    $badgeColor = match($req['status_color']) {
                                        'success' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
                                        'warning' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
                                        'info'    => 'bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300',
                                        'primary' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300',
                                        'danger'  => 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300',
                                        default   => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                    };
                                @endphp
                                <div class="flex items-center gap-3 py-2.5 px-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                    <span class="shrink-0 w-2 h-2 rounded-full {{ $dotColor }}"></span>
                                    <span class="font-mono text-xs text-gray-500 dark:text-gray-400 w-28 shrink-0">{{ $req['invoice_number'] ?? '#'.$req['id'] }}</span>
                                    <span class="flex-1 text-sm text-gray-700 dark:text-gray-200 truncate">
                                        {{ $req['type'] === 'service' ? ucfirst($req['service_type'] ?? __('Service')) : ($req['product_type'] ?? __('Installation')) }}
                                    </span>
                                    <span class="shrink-0 text-xs text-gray-400 dark:text-gray-500">{{ $req['scheduled_at'] ?? '—' }}</span>
                                    <span class="shrink-0 inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }}">
                                        {{ $req['status_label'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex gap-4">
                            <button wire:click="setTab('service')" class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                {{ __('All service requests') }} →
                            </button>
                            <button wire:click="setTab('installation')" class="text-xs font-medium text-violet-600 dark:text-violet-400 hover:underline">
                                {{ __('All installations') }} →
                            </button>
                        </div>
                    </div>
                @endif

            </div>

        {{-- ─── Tab: Service Requests ───────────────────────────────── --}}
        @elseif ($activeTab === 'service')
            @include('filament.partials.customer-requests-table', [
                'requests'     => $serviceRequests,
                'total'        => $serviceTotal,
                'currentPage'  => $servicePage,
                'perPage'      => $perPage,
                'prevAction'   => 'servicePrevPage',
                'nextAction'   => 'serviceNextPage',
                'emptyMessage' => __('No service requests yet'),
            ])

        {{-- ─── Tab: Installation Requests ─────────────────────────── --}}
        @elseif ($activeTab === 'installation')
            @include('filament.partials.customer-requests-table', [
                'requests'     => $installationRequests,
                'total'        => $installationTotal,
                'currentPage'  => $installationPage,
                'perPage'      => $perPage,
                'prevAction'   => 'installationPrevPage',
                'nextAction'   => 'installationNextPage',
                'emptyMessage' => __('No installation requests yet'),
            ])

        {{-- ─── Tab: Invoices ──────────────────────────────────────── --}}
        @elseif ($activeTab === 'invoices')
            <div class="p-4">
                {{-- Financial Summary Bar --}}
                @if (count($manualOrders) > 0)
                    <div class="grid grid-cols-3 gap-3 mb-4">
                        @foreach ([
                            ['label' => __('Total Invoiced'), 'value' => number_format($totalSpent, 3).' OMR',     'class' => 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300'],
                            ['label' => __('Total Paid'),     'value' => number_format($totalPaid, 3).' OMR',      'class' => 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300'],
                            ['label' => __('Outstanding'),    'value' => number_format($totalRemaining, 3).' OMR', 'class' => $totalRemaining > 0 ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300' : 'bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300'],
                        ] as $summary)
                            <div class="rounded-xl p-3 text-center {{ $summary['class'] }}">
                                <p class="text-xs font-medium opacity-75">{{ $summary['label'] }}</p>
                                <p class="text-lg font-bold mt-0.5">{{ $summary['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (count($manualOrders) === 0)
                    <div class="py-14 text-center text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-sm">{{ __('No invoices yet') }}</p>
                    </div>
                @else
                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr class="text-left">
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Invoice #') }}</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Template') }}</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Date') }}</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">{{ __('Total') }}</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">{{ __('Paid') }}</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">{{ __('Remaining') }}</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($manualOrders as $order)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                        <td class="px-4 py-3 font-mono font-medium text-gray-900 dark:text-white">{{ $order['invoice_number'] }}</td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $order['quotation_template'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $order['order_date'] }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">{{ number_format($order['total_amount'], 3) }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-green-600">{{ number_format($order['paid_amount'], 3) }}</td>
                                        <td class="px-4 py-3 text-right font-medium {{ $order['remaining_amount'] > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                            {{ number_format($order['remaining_amount'], 3) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                                {{ $order['status'] === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' }}">
                                                {{ ucfirst($order['status']) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        {{-- ─── Tab: Odoo Orders ───────────────────────────────────── --}}
        @elseif ($activeTab === 'orders')
            {{-- x-init triggers loadOdooOrders() as a separate Livewire request so the tab opens instantly --}}
            <div class="p-4" x-data x-init="$wire.loadOdooOrders()">
                @if (! $odooOrdersFetched)
                    {{-- Spinner: shown on first render while the Odoo fetch is in-flight --}}
                    <div class="py-14 text-center text-gray-400">
                        <svg class="animate-spin h-8 w-8 mx-auto mb-3 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="text-sm">{{ __('Loading orders from Odoo...') }}</p>
                    </div>
                @elseif (! $customer->odoo_id)
                    <div class="py-14 text-center text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        <p class="text-sm font-medium text-gray-500">{{ __('No Odoo account linked') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('Set an Odoo ID on this customer to view their orders.') }}</p>
                    </div>
                @elseif (count($odooOrders) === 0)
                    <div class="py-14 text-center text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-sm">{{ __('No orders found in Odoo') }}</p>
                    </div>
                @else
                        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr class="text-left">
                                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Order Ref') }}</th>
                                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Date') }}</th>
                                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Template') }}</th>
                                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">{{ __('Total') }}</th>
                                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">{{ __('Amount Due') }}</th>
                                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($odooOrders as $order)
                                        @php
                                            $badge = match($order['invoice_status']) {
                                                'invoiced'   => ['label' => __('Invoiced'),   'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'],
                                                'to invoice' => ['label' => __('To Invoice'), 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'],
                                                default      => ['label' => __('Nothing'),    'class' => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'],
                                            };
                                        @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                            <td class="px-4 py-3 font-mono font-medium text-gray-900 dark:text-white">{{ $order['reference'] }}</td>
                                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $order['date'] ?? '—' }}</td>
                                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $order['template'] ?? '—' }}</td>
                                            <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">{{ number_format($order['total'], 3) }}</td>
                                            <td class="px-4 py-3 text-right font-medium {{ $order['amount_due'] > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                                {{ number_format($order['amount_due'], 3) }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badge['class'] }}">
                                                    {{ $badge['label'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                @endif
            </div>

        {{-- ─── Tab: Ratings ────────────────────────────────────────── --}}
        @elseif ($activeTab === 'ratings')
            <div class="p-4">
                @if (count($ratings) === 0)
                    <div class="py-14 text-center text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                        <p class="text-sm">{{ __('No ratings given yet') }}</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($ratings as $rating)
                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 p-4">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white text-sm">
                                            {{ $rating['invoice_number'] ?? __('Request') }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            {{ ucfirst($rating['type'] ?? '') }} • {{ $rating['completed_at'] ?? '' }}
                                        </p>
                                    </div>
                                    {{-- Star visual --}}
                                    @php
                                        $avgStars = (($rating['product_rating'] ?? 0) + ($rating['service_rating'] ?? 0));
                                        $avgStars = $rating['product_rating'] && $rating['service_rating'] ? $avgStars / 2 : max($rating['product_rating'] ?? 0, $rating['service_rating'] ?? 0);
                                    @endphp
                                    <div class="flex">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <svg class="w-4 h-4 {{ $i <= $avgStars ? 'text-amber-400' : 'text-gray-200 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        @endfor
                                    </div>
                                </div>
                                <div class="space-y-1 text-xs text-gray-600 dark:text-gray-300">
                                    @if ($rating['product_rating'])
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">{{ __('Product') }}</span>
                                            <span class="font-medium">{{ $rating['product_rating'] }}/5</span>
                                        </div>
                                    @endif
                                    @if ($rating['service_rating'])
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">{{ __('Service') }}</span>
                                            <span class="font-medium">{{ $rating['service_rating'] }}/5</span>
                                        </div>
                                    @endif
                                    @if ($rating['how_found_us'])
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">{{ __('How found us') }}</span>
                                            <span class="font-medium">{{ $rating['how_found_us'] }}</span>
                                        </div>
                                    @endif
                                </div>
                                @if ($rating['notes'])
                                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400 italic border-t border-gray-200 dark:border-gray-600 pt-2">
                                        "{{ $rating['notes'] }}"
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>

</x-filament-panels::page>
