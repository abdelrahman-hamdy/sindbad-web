<x-filament-panels::page>

    {{-- ── Legend ────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center gap-x-5 gap-y-2 mb-4 text-xs font-medium">
        <span class="flex items-center gap-1.5">
            <span class="inline-block w-3 h-3 rounded-full" style="background:#f59e0b"></span>
            {{ __('Pending') }}
        </span>
        <span class="flex items-center gap-1.5">
            <span class="inline-block w-3 h-3 rounded-full" style="background:#3b82f6"></span>
            {{ __('Assigned') }}
        </span>
        <span class="flex items-center gap-1.5">
            <span class="inline-block w-3 h-3 rounded-full" style="background:#8b5cf6"></span>
            {{ __('On Way') }}
        </span>
        <span class="flex items-center gap-1.5">
            <span class="inline-block w-3 h-3 rounded-full" style="background:#06b6d4"></span>
            {{ __('In Progress') }}
        </span>
        <span class="ms-auto text-xs text-gray-400 dark:text-gray-500 italic">
            {{ __('Click any event to open it · Drag to reschedule') }}
        </span>
    </div>

    {{-- ── Calendar ─────────────────────────────────────────────────── --}}
    @livewire(\App\Filament\Widgets\BookingCalendarWidget::class)

    {{-- ── Bottom panels ──────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">

        {{-- ── Pending / Unassigned Requests ────────────────────── --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">

            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-white/10">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-clock class="w-5 h-5 text-amber-500" />
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                        {{ __('Pending / Unassigned') }}
                    </h3>
                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">
                        {{ count($pendingRequests) }}
                    </span>
                </div>
                <button
                    wire:click="loadSidebarData"
                    class="text-xs text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                    title="{{ __('Refresh') }}"
                >
                    <x-heroicon-o-arrow-path class="w-4 h-4" wire:loading.class="animate-spin" wire:target="loadSidebarData" />
                </button>
            </div>

            {{-- List --}}
            <div class="divide-y divide-gray-100 dark:divide-white/10 max-h-80 overflow-y-auto">
                @forelse ($pendingRequests as $req)
                    <a
                        href="{{ $req['view_url'] }}"
                        class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors group"
                    >
                        {{-- Type icon --}}
                        <div @class([
                            'flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center',
                            'bg-blue-100 dark:bg-blue-500/20'  => $req['type'] === 'service',
                            'bg-violet-100 dark:bg-violet-500/20' => $req['type'] === 'installation',
                        ])>
                            @if ($req['type'] === 'service')
                                <x-heroicon-s-wrench-screwdriver class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                            @else
                                <x-heroicon-s-home-modern class="w-4 h-4 text-violet-600 dark:text-violet-400" />
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-gray-500 dark:text-gray-400">#{{ $req['id'] }}</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $req['customer'] }}</span>
                            </div>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span @class([
                                    'inline-flex items-center rounded-full px-1.5 py-0.5 text-xs font-medium',
                                    'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' => $req['type'] === 'service',
                                    'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400' => $req['type'] === 'installation',
                                ])>{{ $req['type_label'] }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $req['date'] }}</span>
                            </div>
                        </div>

                        {{-- Arrow --}}
                        <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4 text-gray-300 group-hover:text-primary-500 flex-shrink-0 transition-colors" />
                    </a>
                @empty
                    <div class="flex flex-col items-center justify-center py-10 text-center gap-2">
                        <x-heroicon-o-check-circle class="w-8 h-8 text-green-400" />
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('All requests are assigned') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ── Technicians Today ─────────────────────────────────── --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">

            {{-- Header --}}
            <div class="flex items-center gap-2 px-5 py-4 border-b border-gray-100 dark:border-white/10">
                <x-heroicon-o-users class="w-5 h-5 text-primary-500" />
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                    {{ __("Technicians — Today") }}
                </h3>
                <span class="inline-flex items-center rounded-full bg-primary-100 px-2 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-500/20 dark:text-primary-400">
                    {{ count($techniciansToday) }}
                </span>
            </div>

            {{-- Grid --}}
            <div class="divide-y divide-gray-100 dark:divide-white/10 max-h-80 overflow-y-auto">
                @forelse ($techniciansToday as $tech)
                    <div class="flex items-center gap-3 px-5 py-3">

                        {{-- Avatar --}}
                        <div @class([
                            'flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold',
                            'bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400'    => $tech['on_holiday'],
                            'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400' => !$tech['on_holiday'] && $tech['service_full'],
                            'bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-400' => !$tech['on_holiday'] && !$tech['service_full'],
                        ])>
                            {{ strtoupper(mb_substr($tech['name'], 0, 2)) }}
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $tech['name'] }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                @if ($tech['on_holiday'])
                                    <span class="text-red-500 font-medium">{{ __('On Holiday') }}</span>
                                @else
                                    {{ $tech['today_count'] }} {{ __('request(s) today') }}
                                @endif
                            </p>
                        </div>

                        {{-- Load bar (service slots) --}}
                        @if (!$tech['on_holiday'])
                            @php
                                $maxSlots = (int) \App\Models\AppSetting::get('booking_max_service_per_tech_per_day', 4);
                                $filled   = min($tech['service_count'], $maxSlots);
                            @endphp
                            <div class="flex-shrink-0 flex items-center gap-0.5" title="{{ $filled }}/{{ $maxSlots }} {{ __('service slots') }}">
                                @for ($i = 0; $i < $maxSlots; $i++)
                                    <span @class([
                                        'w-3 h-3 rounded-sm',
                                        'bg-primary-500 dark:bg-primary-400' => $i < $filled,
                                        'bg-gray-200 dark:bg-white/10'       => $i >= $filled,
                                    ])></span>
                                @endfor
                            </div>

                            {{-- Status badge --}}
                            <span @class([
                                'flex-shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold',
                                'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400' => !$tech['service_full'],
                                'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400'         => $tech['service_full'],
                            ])>
                                {{ $tech['service_full'] ? __('Full') : __('Available') }}
                            </span>
                        @else
                            <span class="flex-shrink-0 inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500 dark:bg-white/5 dark:text-gray-400">
                                {{ __('Unavailable') }}
                            </span>
                        @endif
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-10 gap-2">
                        <x-heroicon-o-user-minus class="w-8 h-8 text-gray-300" />
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No active technicians') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</x-filament-panels::page>
