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
            @if ($technician->avatar_url)
                <img src="{{ $technician->avatar_url }}" alt="{{ $technician->name }}"
                     class="shrink-0 w-20 h-20 rounded-full object-cover shadow-md border-2 border-white dark:border-gray-700">
            @else
                <div class="shrink-0 w-20 h-20 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center shadow-md">
                    <span class="text-3xl font-bold text-white">
                        {{ strtoupper(mb_substr($technician->name, 0, 1)) }}
                    </span>
                </div>
            @endif

            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-3 mb-1">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white truncate">{{ $technician->name }}</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $technician->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                        {{ $technician->is_active ? __('Active') : __('Inactive') }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400">
                        {{ __('Technician') }}
                    </span>
                </div>
                <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ $technician->phone }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ __('Joined') }} {{ $technician->created_at->format('M d, Y') }}
                    </span>
                </div>

                {{-- Default Address --}}
                @if ($technician->default_address)
                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-start gap-2.5 bg-gray-50 dark:bg-gray-700/40 rounded-xl px-3 py-2.5">
                            <svg class="w-4 h-4 text-primary-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 mb-0.5">{{ __('Default Address') }}</p>
                                <p class="text-sm text-gray-700 dark:text-gray-200 leading-snug">{{ $technician->default_address }}</p>
                                @if ($technician->default_latitude && $technician->default_longitude)
                                    <a href="https://maps.google.com/?q={{ $technician->default_latitude }},{{ $technician->default_longitude }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-1 mt-1 text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                        {{ __('View on Map') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ─── Stats Row ───────────────────────────────────────────────────── --}}
    @php
        $statCards = [
            [
                'label' => __('Total Assigned'),
                'value' => $techStats['total'],
                'color' => 'text-gray-900 dark:text-white',
                'icon'  => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            ],
            [
                'label' => __('Completed'),
                'value' => $techStats['completed'],
                'color' => 'text-green-600',
                'icon'  => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0',
            ],
            [
                'label' => __('Pending'),
                'value' => $techStats['pending'],
                'color' => 'text-amber-500',
                'icon'  => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0',
            ],
        ];
    @endphp

    <div class="grid grid-cols-3 gap-4 mb-6">
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

    {{-- ─── Filter Tabs + Request Table ─────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="flex border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
            @foreach (['all' => __('All'), 'service' => __('Service'), 'installation' => __('Installations')] as $val => $label)
                <button
                    wire:click="$set('filter', '{{ $val }}')"
                    class="shrink-0 px-5 py-4 text-sm font-medium whitespace-nowrap transition border-b-2 -mb-px
                        {{ $filter === $val
                            ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600'
                        }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if (count($requests) === 0)
            <div class="p-12 text-center">
                <svg class="w-10 h-10 mx-auto mb-2 text-gray-200 dark:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No requests found.') }}</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr class="text-left text-gray-500 dark:text-gray-300 text-xs uppercase tracking-wide">
                        <th class="px-4 py-3 font-medium">{{ __('Invoice') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Type') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Customer') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Scheduled') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Completed At') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Rating') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($requests as $req)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ $req['invoice_number'] ?? '#'.$req['id'] }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $req['type'] === 'service' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' }}">
                                    {{ ucfirst($req['type']) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $req['customer'] }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColor = match($req['status']) {
                                        'pending'                            => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
                                        'assigned', 'on_way', 'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                        'completed'                          => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        'canceled'                           => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                        default                              => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $statusColor }}">
                                    {{ $req['status_label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $req['scheduled_at'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $req['completed_at'] ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($req['rating'])
                                    <span class="text-amber-500 font-medium flex items-center gap-1">
                                        {{ $req['rating'] }}
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </span>
                                @else
                                    <span class="text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Pagination --}}
            @if ($totalRequests > $perPage)
                <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/20">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Showing') }} {{ (($page - 1) * $perPage) + 1 }}–{{ min($page * $perPage, $totalRequests) }} {{ __('of') }} {{ $totalRequests }}
                    </p>
                    <div class="flex gap-2">
                        <button
                            wire:click="previousPage"
                            @if ($page <= 1) disabled @endif
                            class="px-3 py-1.5 text-sm rounded-lg border border-gray-200 dark:border-gray-600 disabled:opacity-40 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                        >
                            {{ __('← Prev') }}
                        </button>
                        <button
                            wire:click="nextPage"
                            @if ($page >= ceil($totalRequests / $perPage)) disabled @endif
                            class="px-3 py-1.5 text-sm rounded-lg border border-gray-200 dark:border-gray-600 disabled:opacity-40 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                        >
                            {{ __('Next →') }}
                        </button>
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>
