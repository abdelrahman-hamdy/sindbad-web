<x-filament-panels::page>
    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Requests') }}</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Completion Rate') }}</h3>
            <p class="text-3xl font-bold text-green-600 mt-2">{{ $stats['completion_rate'] }}%</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Avg Service Rating') }}</h3>
            <p class="text-3xl font-bold text-yellow-500 mt-2">{{ $ratingStats['avg_service'] }} ⭐</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Technician Leaderboard --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">{{ __('Technician Leaderboard') }}</h2>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                        <th class="pb-2">#</th>
                        <th class="pb-2">{{ __('Technician') }}</th>
                        <th class="pb-2">{{ __('Phone') }}</th>
                        <th class="pb-2">{{ __('Completed') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leaderboard as $i => $tech)
                        <tr class="border-b dark:border-gray-700 last:border-0">
                            <td class="py-2 text-gray-500">{{ $i + 1 }}</td>
                            <td class="py-2 font-medium text-gray-900 dark:text-white">{{ $tech['name'] }}</td>
                            <td class="py-2 text-gray-600 dark:text-gray-300">{{ $tech['phone'] }}</td>
                            <td class="py-2 font-semibold text-primary-600">{{ $tech['completed_count'] ?? 0 }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-center text-gray-400">{{ __('No data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Rating Breakdown --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">{{ __('Rating Breakdown') }}</h2>
            @php $maxRating = max(array_values($ratingBreakdown) ?: [1]); @endphp
            <div class="space-y-3">
                @for ($star = 5; $star >= 1; $star--)
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-600 dark:text-gray-300 w-8">{{ $star }} ⭐</span>
                        <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-4">
                            <div
                                class="bg-yellow-400 h-4 rounded-full transition-all"
                                style="width: {{ $maxRating > 0 ? round(($ratingBreakdown[$star] ?? 0) / $maxRating * 100) : 0 }}%"
                            ></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200 w-8 text-right">{{ $ratingBreakdown[$star] ?? 0 }}</span>
                    </div>
                @endfor
            </div>
        </div>
    </div>

    {{-- Daily Activity --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Daily Activity') }}</h2>
            <input
                type="date"
                wire:model.live="dailyDate"
                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:ring-primary-500 focus:border-primary-500"
            >
        </div>
        @if (count($dailyActivity) === 0)
            <p class="text-center text-gray-400 py-6">{{ __('No completed requests on this date.') }}</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                        <th class="pb-2">{{ __('Invoice') }}</th>
                        <th class="pb-2">{{ __('Type') }}</th>
                        <th class="pb-2">{{ __('Customer') }}</th>
                        <th class="pb-2">{{ __('Technician') }}</th>
                        <th class="pb-2">{{ __('Rating') }}</th>
                        <th class="pb-2">{{ __('Completed At') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dailyActivity as $row)
                        <tr class="border-b dark:border-gray-700 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="py-2 font-medium text-gray-900 dark:text-white">{{ $row['invoice_number'] ?? '#'.$row['id'] }}</td>
                            <td class="py-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $row['type'] === 'service' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' }}">
                                    {{ ucfirst($row['type']) }}
                                </span>
                            </td>
                            <td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['customer'] }}</td>
                            <td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['technician'] }}</td>
                            <td class="py-2">
                                @if ($row['rating'])
                                    <span class="text-yellow-500 font-medium">{{ $row['rating'] }} ⭐</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="py-2 text-gray-500 dark:text-gray-400">{{ $row['completed_at'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Top Customers --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">{{ __('Top Customers by Spending') }}</h2>
        @if (count($topCustomers) === 0)
            <p class="text-center text-gray-400 py-6">{{ __('No customer data yet.') }}</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                        <th class="pb-2">{{ __('Rank') }}</th>
                        <th class="pb-2">{{ __('Name') }}</th>
                        <th class="pb-2">{{ __('Phone') }}</th>
                        <th class="pb-2">{{ __('Orders') }}</th>
                        <th class="pb-2">{{ __('Total Spent (OMR)') }}</th>
                        <th class="pb-2">{{ __('Paid (OMR)') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($topCustomers as $i => $customer)
                        <tr class="border-b dark:border-gray-700 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="py-2 text-gray-500">{{ $i + 1 }}</td>
                            <td class="py-2 font-medium text-gray-900 dark:text-white">{{ $customer['name'] }}</td>
                            <td class="py-2 text-gray-600 dark:text-gray-300">{{ $customer['phone'] }}</td>
                            <td class="py-2 font-semibold">{{ $customer['order_count'] }}</td>
                            <td class="py-2 text-green-600 font-medium">{{ number_format($customer['total_spent'], 3) }}</td>
                            <td class="py-2 text-blue-600">{{ number_format($customer['total_paid'], 3) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Latest Ratings --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
        <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">{{ __('Latest Ratings') }}</h2>
        @if (count($latestRatings) === 0)
            <p class="text-center text-gray-400 py-6">{{ __('No ratings yet.') }}</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($latestRatings as $rating)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $rating['customer'] }}</p>
                                <p class="text-xs text-gray-500">{{ $rating['invoice_number'] }} • {{ ucfirst($rating['type']) }}</p>
                            </div>
                            <div class="text-right">
                                @if ($rating['product_rating'])
                                    <p class="text-xs text-gray-500">{{ __('Product:') }} <span class="text-yellow-500">{{ $rating['product_rating'] }}⭐</span></p>
                                @endif
                                @if ($rating['service_rating'])
                                    <p class="text-xs text-gray-500">{{ __('Service:') }} <span class="text-yellow-500">{{ $rating['service_rating'] }}⭐</span></p>
                                @endif
                            </div>
                        </div>
                        @if ($rating['notes'])
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-2 italic">"{{ $rating['notes'] }}"</p>
                        @endif
                        <p class="text-xs text-gray-400 mt-2">{{ $rating['created_at'] }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
