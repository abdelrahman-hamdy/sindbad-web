<x-filament::section :heading="__('User Statistics')">
    <x-slot name="afterHeader">
        <x-filament::icon
            icon="heroicon-o-user-group"
            class="h-5 w-5 text-gray-400"
        />
    </x-slot>

    <div class="flex flex-col gap-5">

        {{-- Key counts --}}
        <div class="grid grid-cols-3 gap-3">
            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3 text-center">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $total }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Total') }}</p>
            </div>
            <div class="rounded-lg bg-blue-50 dark:bg-blue-950 p-3 text-center">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $customers }}</p>
                <p class="text-xs text-blue-500 dark:text-blue-400 mt-0.5">{{ __('Customers') }}</p>
            </div>
            <div class="rounded-lg bg-emerald-50 dark:bg-emerald-950 p-3 text-center">
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $technicians }}</p>
                <p class="text-xs text-emerald-500 dark:text-emerald-400 mt-0.5">{{ __('Technicians') }}</p>
            </div>
        </div>

        {{-- Customer vs Technician ratio bar --}}
        <div>
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-2">
                <span class="flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-blue-500 shrink-0"></span>
                    {{ __('Customers') }} &nbsp;{{ $customerPct }}%
                </span>
                <span class="flex items-center gap-1.5">
                    {{ __('Technicians') }} &nbsp;{{ $technicianPct }}%
                    <span class="h-2 w-2 rounded-full bg-emerald-500 shrink-0"></span>
                </span>
            </div>
            <div class="flex h-3 w-full overflow-hidden rounded-full">
                <div
                    class="bg-blue-500 transition-all duration-500"
                    style="width: {{ $customerPct }}%"
                ></div>
                <div class="flex-1 bg-emerald-500 transition-all duration-500"></div>
            </div>
        </div>

        {{-- Active users + new this month --}}
        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Active Users') }}</p>
                <p class="mt-1 text-xl font-bold text-gray-800 dark:text-gray-200">
                    {{ $active }}
                    <span class="text-xs font-normal text-gray-400">/ {{ $total }}</span>
                </p>
            </div>
            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('New This Month') }}</p>
                <p class="mt-1 text-xl font-bold text-gray-800 dark:text-gray-200">{{ $newThisMonth }}</p>
            </div>
        </div>

    </div>
</x-filament::section>
