@if (request()->routeIs('filament.admin.auth.login'))
    <div class="flex flex-col items-center gap-2">
        <img
            src="{{ asset('images/app-icon.png') }}"
            alt="Sindbad"
            class="h-14 w-14 rounded-xl object-cover"
        >
        <span class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">{{ __('Sindbad Dashboard') }}</span>
        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-teal-100 text-teal-700 dark:bg-teal-900/60 dark:text-teal-300 leading-tight">
            v2.0
        </span>
    </div>
@else
    <div class="flex items-center gap-2.5">
        <img
            src="{{ asset('images/app-icon.png') }}"
            alt="Sindbad"
            class="h-8 w-8 rounded-xl object-cover"
        >
        <div class="flex items-baseline gap-1.5">
            <span class="text-base font-bold text-gray-900 dark:text-white tracking-tight">{{ __('Sindbad Dashboard') }}</span>
            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-teal-100 text-teal-700 dark:bg-teal-900/60 dark:text-teal-300 leading-tight">
                v2.0
            </span>
        </div>
    </div>
@endif
