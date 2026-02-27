<div class="overflow-hidden rounded-xl shadow-sm bg-gradient-to-br from-[#E0F5F8] to-[#C2EBF3] dark:from-[#0d2d32] dark:to-[#091e23]">
    <div class="p-6">

        {{-- Header row --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
            <h2 class="text-xl font-bold text-[#006A7A] dark:text-cyan-300">{{ __('Request Statistics') }}</h2>

            {{-- Filter buttons --}}
            <div class="flex flex-wrap gap-2">
                @foreach([
                    'today' => __('Today'),
                    'week'  => __('This Week'),
                    'month' => $monthName,
                    'all'   => __('All Time'),
                ] as $value => $label)
                    <button
                        wire:click="$set('filter', '{{ $value }}')"
                        class="px-3 py-1.5 text-sm font-semibold rounded-lg transition-all duration-150 focus:outline-none
                            {{ $filter === $value
                                ? 'bg-[#008BA0] text-white shadow-sm dark:bg-cyan-600'
                                : 'bg-[rgba(0,139,160,0.15)] text-[#006A7A] dark:bg-cyan-400/10 dark:text-cyan-300' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Service Requests --}}
        <div class="mb-5">
            <p class="text-xs font-semibold uppercase tracking-widest mb-3 text-[#008BA0] dark:text-cyan-400">
                {{ __('Service Requests') }}
            </p>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                @php $s = $stats['service'] ?? []; @endphp
                @include('filament.widgets.partials.stat-box', ['label' => __('Total'),     'value' => $s['total']     ?? 0, 'numClass' => 'text-[#008BA0] dark:text-cyan-400',    'labelClass' => 'text-[#006A7A] dark:text-cyan-300/70'])
                @include('filament.widgets.partials.stat-box', ['label' => __('Pending'),   'value' => $s['pending']   ?? 0, 'numClass' => 'text-amber-600 dark:text-amber-400',    'labelClass' => 'text-[#006A7A] dark:text-cyan-300/70'])
                @include('filament.widgets.partials.stat-box', ['label' => __('Active'),    'value' => $s['active']    ?? 0, 'numClass' => 'text-blue-600 dark:text-blue-400',       'labelClass' => 'text-[#006A7A] dark:text-cyan-300/70'])
                @include('filament.widgets.partials.stat-box', ['label' => __('Completed'), 'value' => $s['completed'] ?? 0, 'numClass' => 'text-emerald-600 dark:text-emerald-400', 'labelClass' => 'text-[#006A7A] dark:text-cyan-300/70'])
                @include('filament.widgets.partials.stat-box', ['label' => __('Canceled'),  'value' => $s['canceled']  ?? 0, 'numClass' => 'text-red-600 dark:text-red-400',         'labelClass' => 'text-[#006A7A] dark:text-cyan-300/70'])
            </div>
        </div>

        {{-- Divider --}}
        <div class="border-t mb-5 border-[rgba(0,139,160,0.2)] dark:border-[rgba(103,212,224,0.15)]"></div>

        {{-- Installation Requests --}}
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest mb-3 text-[#008BA0] dark:text-cyan-400">
                {{ __('Installation Requests') }}
            </p>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                @php $i = $stats['installation'] ?? []; @endphp
                @include('filament.widgets.partials.stat-box', ['label' => __('Total'),     'value' => $i['total']     ?? 0, 'numClass' => 'text-[#008BA0] dark:text-cyan-400',    'labelClass' => 'text-[#006A7A] dark:text-cyan-300/70'])
                @include('filament.widgets.partials.stat-box', ['label' => __('Pending'),   'value' => $i['pending']   ?? 0, 'numClass' => 'text-amber-600 dark:text-amber-400',    'labelClass' => 'text-[#006A7A] dark:text-cyan-300/70'])
                @include('filament.widgets.partials.stat-box', ['label' => __('Active'),    'value' => $i['active']    ?? 0, 'numClass' => 'text-blue-600 dark:text-blue-400',       'labelClass' => 'text-[#006A7A] dark:text-cyan-300/70'])
                @include('filament.widgets.partials.stat-box', ['label' => __('Completed'), 'value' => $i['completed'] ?? 0, 'numClass' => 'text-emerald-600 dark:text-emerald-400', 'labelClass' => 'text-[#006A7A] dark:text-cyan-300/70'])
                @include('filament.widgets.partials.stat-box', ['label' => __('Canceled'),  'value' => $i['canceled']  ?? 0, 'numClass' => 'text-red-600 dark:text-red-400',         'labelClass' => 'text-[#006A7A] dark:text-cyan-300/70'])
            </div>
        </div>

    </div>
</div>
