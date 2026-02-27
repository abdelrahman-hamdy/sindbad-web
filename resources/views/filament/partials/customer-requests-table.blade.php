{{-- Shared requests table partial --}}
{{-- Variables: $requests, $total, $currentPage, $perPage, $prevAction, $nextAction, $emptyMessage --}}
<div class="p-4">
    @if (count($requests) === 0)
        <div class="py-14 text-center text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-sm">{{ $emptyMessage }}</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr class="text-left">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Invoice') }}</th>
                        @if (isset($requests[0]) && $requests[0]['type'] === 'service')
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Service Type') }}</th>
                        @else
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Product') }}</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Qty') }}</th>
                        @endif
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Technician') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Scheduled') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Completed') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Rating') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($requests as $req)
                        @php
                            $statusClasses = match($req['status_color']) {
                                'success' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
                                'info'    => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                'primary' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
                                'danger'  => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                default   => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-4 py-3 font-mono font-medium text-gray-900 dark:text-white text-xs">
                                {{ $req['invoice_number'] ?? '#'.$req['id'] }}
                            </td>
                            @if ($req['type'] === 'service')
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ ucfirst($req['service_type'] ?? '—') }}
                                </td>
                            @else
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $req['product_type'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $req['quantity'] ?? 1 }}</td>
                            @endif
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusClasses }}">
                                    {{ $req['status_label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $req['technician'] }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $req['scheduled_at'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $req['completed_at'] ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($req['rating'])
                                    <span class="text-amber-500 font-semibold">{{ $req['rating'] }} ⭐</span>
                                @else
                                    <span class="text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($total > $perPage)
            <div class="flex items-center justify-between mt-3 px-1">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Showing') }} {{ (($currentPage - 1) * $perPage) + 1 }}–{{ min($currentPage * $perPage, $total) }}
                    {{ __('of') }} {{ $total }}
                </p>
                <div class="flex gap-2">
                    <button wire:click="{{ $prevAction }}"
                            @if ($currentPage <= 1) disabled @endif
                            class="px-3 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 disabled:opacity-40 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        {{ __('← Prev') }}
                    </button>
                    <button wire:click="{{ $nextAction }}"
                            @if ($currentPage >= ceil($total / $perPage)) disabled @endif
                            class="px-3 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 disabled:opacity-40 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        {{ __('Next →') }}
                    </button>
                </div>
            </div>
        @endif
    @endif
</div>
