<x-filament-panels::page>
    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                    <span class="text-2xl font-bold text-primary-700 dark:text-primary-300">
                        {{ strtoupper(substr($technician->name, 0, 1)) }}
                    </span>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $technician->name }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $technician->phone }}</p>
                </div>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $technician->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                {{ $technician->is_active ? __('Active') : __('Inactive') }}
            </span>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 text-center">
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $techStats['total'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Total Assigned') }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 text-center">
            <p class="text-2xl font-bold text-green-600">{{ $techStats['completed'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Completed') }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 text-center">
            <p class="text-2xl font-bold text-yellow-500">
                {{ $techStats['avg_rating'] ? $techStats['avg_rating'] . ' ⭐' : 'N/A' }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Avg Rating') }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $techStats['acceptance_rate'] }}%</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Acceptance Rate') }}</p>
        </div>
    </div>

    {{-- Filter Tabs + Request Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="flex border-b border-gray-200 dark:border-gray-700">
            @foreach (['all' => __('All'), 'service' => __('Service'), 'installation' => __('Installation')] as $val => $label)
                <button
                    wire:click="$set('filter', '{{ $val }}')"
                    class="px-5 py-3 text-sm font-medium transition {{ $filter === $val ? 'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if (count($requests) === 0)
            <div class="p-12 text-center text-gray-400">{{ __('No requests found.') }}</div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr class="text-left text-gray-500 dark:text-gray-300">
                        <th class="px-4 py-3 font-medium">{{ __('Invoice') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Type') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Customer') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Scheduled') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Completed At') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Rating') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($requests as $req)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ $req['invoice_number'] ?? '#'.$req['id'] }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $req['type'] === 'service' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' }}">
                                    {{ ucfirst($req['type']) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $req['customer'] }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColor = match($req['status']) {
                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'assigned', 'on_way', 'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        'canceled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                        default => 'bg-gray-100 text-gray-800',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColor }}">
                                    {{ $req['status_label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $req['scheduled_at'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $req['completed_at'] ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if ($req['rating'])
                                    <span class="text-yellow-500 font-medium">{{ $req['rating'] }} ⭐</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Pagination --}}
            @if ($totalRequests > $perPage)
                <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500">
                        {{ __('Showing') }} {{ (($page - 1) * $perPage) + 1 }}–{{ min($page * $perPage, $totalRequests) }} {{ __('of') }} {{ $totalRequests }}
                    </p>
                    <div class="flex gap-2">
                        <button
                            wire:click="previousPage"
                            @if ($page <= 1) disabled @endif
                            class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 disabled:opacity-40 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                        >
                            {{ __('← Prev') }}
                        </button>
                        <button
                            wire:click="nextPage"
                            @if ($page >= ceil($totalRequests / $perPage)) disabled @endif
                            class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 disabled:opacity-40 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                        >
                            {{ __('Next →') }}
                        </button>
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>
