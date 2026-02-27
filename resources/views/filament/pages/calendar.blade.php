<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ── Calendar ─────────────────────────────────────── --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">

            {{-- Month Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <button wire:click="previousMonth"
                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ \Carbon\Carbon::parse($currentMonth . '-01')->format('F Y') }}
                </h2>
                <button wire:click="nextMonth"
                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>

            {{-- Day Headers --}}
            <div class="grid grid-cols-7 bg-gray-50 dark:bg-gray-700/50">
                @foreach ([__('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat'), __('Sun')] as $day)
                    <div class="py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400">{{ $day }}</div>
                @endforeach
            </div>

            {{-- Days Grid --}}
            @php $days = $this->getCalendarDays(); $today = now()->format('Y-m-d'); @endphp
            <div class="grid grid-cols-7 divide-x divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($days as $date)
                    @if ($date === null)
                        <div class="h-28 bg-gray-50/60 dark:bg-gray-700/20"></div>
                    @else
                        @php
                            $data      = $calendarData[$date] ?? ['total' => 0, 'pending' => 0, 'active' => 0, 'completed' => 0, 'canceled' => 0];
                            $isToday   = $date === $today;
                            $isSelected = $date === $selectedDate;
                        @endphp
                        <div
                            wire:click="selectDate('{{ $date }}')"
                            class="h-28 p-1.5 cursor-pointer transition
                                {{ $isSelected ? 'ring-2 ring-inset ring-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'hover:bg-blue-50 dark:hover:bg-blue-900/10' }}
                                {{ $isToday && !$isSelected ? 'ring-2 ring-inset ring-blue-300' : '' }}"
                        >
                            <div class="flex flex-col h-full gap-1">
                                {{-- Day number --}}
                                <span class="text-xs font-semibold leading-none shrink-0
                                    {{ $isToday ? 'flex items-center justify-center w-5 h-5 rounded-full bg-primary-500 text-white' : 'text-gray-700 dark:text-gray-300' }}">
                                    {{ \Carbon\Carbon::parse($date)->day }}
                                </span>

                                {{-- Status event bars --}}
                                @if($data['total'] > 0)
                                    <div class="flex flex-col gap-0.5 mt-0.5">
                                        @if($data['pending'] > 0)
                                            <div class="flex items-center gap-1.5 rounded px-1.5 py-1 bg-yellow-100 dark:bg-yellow-900/50">
                                                <span class="w-2 h-2 rounded-full bg-yellow-400 shrink-0"></span>
                                                <span class="text-xs font-semibold text-yellow-700 dark:text-yellow-300 leading-none truncate">{{ $data['pending'] }}</span>
                                            </div>
                                        @endif
                                        @if($data['active'] > 0)
                                            <div class="flex items-center gap-1.5 rounded px-1.5 py-1 bg-blue-100 dark:bg-blue-900/50">
                                                <span class="w-2 h-2 rounded-full bg-blue-400 shrink-0"></span>
                                                <span class="text-xs font-semibold text-blue-700 dark:text-blue-300 leading-none truncate">{{ $data['active'] }}</span>
                                            </div>
                                        @endif
                                        @if($data['completed'] > 0)
                                            <div class="flex items-center gap-1.5 rounded px-1.5 py-1 bg-green-100 dark:bg-green-900/50">
                                                <span class="w-2 h-2 rounded-full bg-green-400 shrink-0"></span>
                                                <span class="text-xs font-semibold text-green-700 dark:text-green-300 leading-none truncate">{{ $data['completed'] }}</span>
                                            </div>
                                        @endif
                                        @if($data['canceled'] > 0)
                                            <div class="flex items-center gap-1.5 rounded px-1.5 py-1 bg-red-100 dark:bg-red-900/50">
                                                <span class="w-2 h-2 rounded-full bg-red-400 shrink-0"></span>
                                                <span class="text-xs font-semibold text-red-700 dark:text-red-300 leading-none truncate">{{ $data['canceled'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Legend --}}
            <div class="flex flex-wrap gap-x-5 gap-y-1.5 px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-yellow-400"></span> {{ __('Pending') }}
                </span>
                <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-400"></span> {{ __('Active') }}
                </span>
                <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-green-400"></span> {{ __('Completed') }}
                </span>
                <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span> {{ __('Canceled') }}
                </span>
            </div>
        </div>

        {{-- ── Day Panel ─────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden flex flex-col">

            @if ($selectedDate)
                {{-- Panel header --}}
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 shrink-0">
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        {{ \Carbon\Carbon::parse($selectedDate)->format('l, d F Y') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ count($dayRequests) }} {{ __('request(s) scheduled') }}
                    </p>
                </div>

                @if (count($dayRequests) === 0)
                    {{-- Empty: day selected but no requests --}}
                    <div class="flex-1 flex flex-col items-center justify-center p-8 text-center">
                        <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('No requests') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('Nothing is scheduled for this day.') }}</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-100 dark:divide-gray-700 overflow-y-auto flex-1">
                        @foreach ($dayRequests as $req)
                            <a
                                href="{{ $req['url'] }}"
                                class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition group"
                            >
                                {{-- Row 1: invoice + type badge --}}
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition">
                                        {{ $req['invoice_number'] ?? '#' . $req['id'] }}
                                    </span>
                                    <span class="text-[11px] px-2 py-0.5 rounded-full font-medium
                                        {{ $req['type'] === 'service'
                                            ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
                                            : 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' }}">
                                        {{ ucfirst($req['type']) }}
                                    </span>
                                </div>

                                {{-- Row 2: customer + time --}}
                                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    <span class="flex items-center gap-1 truncate">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        {{ $req['customer'] }}
                                    </span>
                                    @if($req['scheduled_time'])
                                        <span class="flex items-center gap-1 shrink-0 ms-2">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            {{ $req['scheduled_time'] }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Row 3: technician + status --}}
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400 truncate">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        {{ $req['technician'] }}
                                    </span>
                                    @php
                                        $sc = match($req['status']) {
                                            'pending'                        => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                                            'assigned', 'on_way', 'in_progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                            'completed'                      => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                            'canceled'                       => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                            default                          => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                        };
                                    @endphp
                                    <span class="text-[11px] px-2 py-0.5 rounded-full font-medium {{ $sc }} shrink-0 ms-2">
                                        {{ $req['status_label'] }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif

            @else
                {{-- No date selected --}}
                <div class="flex-1 flex flex-col items-center justify-center p-8 text-center">
                    <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Select a day') }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ __('Click any date on the calendar to see its scheduled requests.') }}</p>
                </div>
            @endif
        </div>

    </div>
</x-filament-panels::page>
