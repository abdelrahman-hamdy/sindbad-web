<x-filament-panels::page>

{{-- GLightbox: lightweight image lightbox (no jQuery, ~10 KB) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
<script>
    (function () {
        function initGlb() {
            if (window._glb) { window._glb.destroy(); }
            if (window.GLightbox) { window._glb = GLightbox({ loop: true }); }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initGlb);
        } else {
            initGlb();
        }
        document.addEventListener('livewire:updated', initGlb);
    })();
</script>

@php
    $status = $record->status instanceof \App\Enums\RequestStatus
        ? $record->status
        : \App\Enums\RequestStatus::from($record->status);

    $statusTheme = match ($status) {
        \App\Enums\RequestStatus::Pending    => ['dot' => 'bg-amber-400',  'pill' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'],
        \App\Enums\RequestStatus::Assigned   => ['dot' => 'bg-sky-400',    'pill' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300'],
        \App\Enums\RequestStatus::OnWay      => ['dot' => 'bg-violet-400', 'pill' => 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300'],
        \App\Enums\RequestStatus::InProgress => ['dot' => 'bg-blue-400',   'pill' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300'],
        \App\Enums\RequestStatus::Completed  => ['dot' => 'bg-green-400',  'pill' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'],
        \App\Enums\RequestStatus::Canceled   => ['dot' => 'bg-red-400',    'pill' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'],
    };

    $serviceType = $record->service_type instanceof \App\Enums\ServiceType
        ? $record->service_type
        : (is_string($record->service_type) ? \App\Enums\ServiceType::from($record->service_type) : null);

    $serviceTypeTheme = match ($serviceType) {
        \App\Enums\ServiceType::Maintenance => [
            'bg'    => 'bg-blue-50 dark:bg-blue-900/20',
            'border'=> 'border-blue-100 dark:border-blue-800/40',
            'label' => 'text-blue-600 dark:text-blue-400',
            'icon'  => 'bg-blue-100 dark:bg-blue-900/40 text-blue-500 dark:text-blue-400',
        ],
        \App\Enums\ServiceType::Repair => [
            'bg'    => 'bg-orange-50 dark:bg-orange-900/20',
            'border'=> 'border-orange-100 dark:border-orange-800/40',
            'label' => 'text-orange-600 dark:text-orange-400',
            'icon'  => 'bg-orange-100 dark:bg-orange-900/40 text-orange-500 dark:text-orange-400',
        ],
        \App\Enums\ServiceType::Inspection => [
            'bg'    => 'bg-green-50 dark:bg-green-900/20',
            'border'=> 'border-green-100 dark:border-green-800/40',
            'label' => 'text-green-600 dark:text-green-400',
            'icon'  => 'bg-green-100 dark:bg-green-900/40 text-green-500 dark:text-green-400',
        ],
        default => [
            'bg'    => 'bg-gray-50 dark:bg-gray-700/50',
            'border'=> 'border-gray-100 dark:border-gray-700',
            'label' => 'text-gray-500 dark:text-gray-400',
            'icon'  => 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500',
        ],
    };

    $hasLocation     = $record->latitude && $record->longitude;
    $mapsUrl         = "https://www.google.com/maps?q={$record->latitude},{$record->longitude}";
    $customerInitial = strtoupper(mb_substr($record->user?->name ?? '?', 0, 1));
    $techInitial     = $record->technician ? strtoupper(mb_substr($record->technician->name, 0, 1)) : null;
    $rating          = $record->rating;
    $ratingAvg       = $rating ? round(($rating->product_rating + $rating->service_rating) / 2, 1) : null;
    $activities      = $record->activities->sortByDesc('created_at');
@endphp

<div class="space-y-4 pb-10">

    {{-- ── Status Band ─────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-3">

            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 {{ $statusTheme['dot'] }}"></span>
                <span class="text-sm font-bold px-3 py-1 rounded-lg {{ $statusTheme['pill'] }}">
                    {{ $status->label() }}
                </span>
            </div>

            <div class="h-6 w-px bg-gray-200 dark:bg-gray-600 hidden sm:block"></div>

            @if ($record->invoice_number)
                <div class="flex items-center gap-1.5">
                    <span class="text-xs font-medium text-gray-400 dark:text-gray-500">{{ __('Invoice') }}</span>
                    <span class="text-sm font-bold text-gray-800 dark:text-gray-100">T-{{ $record->invoice_number }}</span>
                </div>
                <div class="h-6 w-px bg-gray-200 dark:bg-gray-600 hidden sm:block"></div>
            @endif

            @if ($serviceType)
                <div class="flex items-center gap-1.5">
                    <span class="text-xs font-medium text-gray-400 dark:text-gray-500">{{ __('Service') }}</span>
                    <span class="text-sm font-bold text-gray-800 dark:text-gray-100">{{ $serviceType->label() }}</span>
                </div>
                <div class="h-6 w-px bg-gray-200 dark:bg-gray-600 hidden sm:block"></div>
            @endif

            <div class="flex items-center gap-1.5">
                <span class="text-xs font-medium text-gray-400 dark:text-gray-500">{{ __('Submitted') }}</span>
                <span class="text-sm font-bold text-gray-800 dark:text-gray-100">{{ $record->created_at->diffForHumans() }}</span>
            </div>

            <div class="ml-auto">
                <span class="text-xs text-gray-400 dark:text-gray-500">#{{ $record->id }}</span>
            </div>

        </div>
    </div>

    {{-- ── 2 + 1 Grid ───────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- ════════ LEFT (2/3) ════════ --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Map ─────────────────────────────────────────────────────── --}}
            <div wire:ignore class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">

                @if ($hasLocation)
                    <div id="service-map" style="height: 360px; position: relative; z-index: 0;"></div>
                @else
                    <div class="flex flex-col items-center justify-center gap-3 bg-gray-50 dark:bg-gray-700" style="height: 180px;">
                        <svg class="w-9 h-9 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <span class="text-sm text-gray-400 dark:text-gray-500">{{ __('No location recorded') }}</span>
                    </div>
                @endif

                <div class="flex items-center justify-between gap-4 px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 min-w-0">
                        <svg class="w-3.5 h-3.5 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                        </svg>
                        <span class="truncate">{{ $record->address }}</span>
                    </div>
                    @if ($hasLocation)
                        <a href="{{ $mapsUrl }}" target="_blank"
                           class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            {{ __('Open in Maps') }}
                        </a>
                    @endif
                </div>
            </div>

            {{-- Service Details ──────────────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">

                <div class="flex items-center gap-2 mb-5">
                    <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ __('Service Details') }}</span>
                </div>

                {{-- Service type + Invoice row --}}
                <div class="grid grid-cols-2 gap-3 mb-5">

                    {{-- Service Type --}}
                    <div class="rounded-xl p-4 border {{ $serviceTypeTheme['bg'] }} {{ $serviceTypeTheme['border'] }}">
                        <div class="text-xs font-semibold uppercase tracking-wider {{ $serviceTypeTheme['label'] }} mb-3">{{ __('Service Type') }}</div>
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg {{ $serviceTypeTheme['icon'] }} flex items-center justify-center flex-shrink-0">
                                @if ($serviceType === \App\Enums\ServiceType::Maintenance)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                                    </svg>
                                @elseif ($serviceType === \App\Enums\ServiceType::Repair)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @endif
                            </div>
                            <span class="font-bold text-gray-900 dark:text-white text-base">{{ $serviceType?->label() ?? '—' }}</span>
                        </div>
                    </div>

                    {{-- Invoice --}}
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-700/50 p-4 border border-gray-100 dark:border-gray-700">
                        <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3">{{ __('Invoice Number') }}</div>
                        @if ($record->invoice_number)
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-gray-200 dark:bg-gray-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <span class="font-bold text-gray-900 dark:text-white text-base">T-{{ $record->invoice_number }}</span>
                            </div>
                        @else
                            <span class="text-sm text-gray-400 dark:text-gray-500 italic">{{ __('No invoice assigned') }}</span>
                        @endif
                    </div>

                </div>

                {{-- Description --}}
                @if ($record->description)
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-center gap-1.5 mb-2">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                            </svg>
                            <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">{{ __('Description') }}</span>
                        </div>
                        <p class="text-base font-medium text-gray-800 dark:text-gray-200 leading-relaxed">{{ $record->description }}</p>
                    </div>
                @endif
            </div>

            {{-- Customer Images ──────────────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ __('Customer Images') }}</span>
                    </div>
                    @if ($customerImages->isNotEmpty())
                        <span class="text-xs font-bold text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 rounded-full">{{ $customerImages->count() }}</span>
                    @endif
                </div>
                @if ($customerImages->isNotEmpty())
                    <div class="grid grid-cols-4 gap-2">
                        @foreach ($customerImages as $image)
                            <a href="{{ $image->getUrl() }}"
                               class="glightbox block aspect-square rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700 hover:opacity-80 transition ring-2 ring-transparent hover:ring-primary-400"
                               data-gallery="customer-images-{{ $record->id }}">
                                <img src="{{ $image->getUrl() }}" alt="{{ __('Customer image') }}" class="w-full h-full object-cover"/>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-8 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-dashed border-gray-200 dark:border-gray-700">
                        <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No customer images uploaded') }}</p>
                    </div>
                @endif
            </div>

            {{-- Technician Images ────────────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5"
                 x-data="{ confirmId: null }">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ __('Technician Images') }}</span>
                    </div>
                    @if ($technicianImages->isNotEmpty())
                        <span class="text-xs font-bold text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 rounded-full">{{ $technicianImages->count() }}</span>
                    @endif
                </div>
                @if ($technicianImages->isNotEmpty())
                    <div class="grid grid-cols-4 gap-2">
                        @foreach ($technicianImages as $image)
                            <div class="relative aspect-square">
                                <a href="{{ $image->getUrl() }}"
                                   class="glightbox block w-full h-full rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700 hover:opacity-80 transition ring-2 ring-transparent hover:ring-primary-400"
                                   data-gallery="technician-images-{{ $record->id }}">
                                    <img src="{{ $image->getUrl() }}" alt="{{ __('Technician image') }}" class="w-full h-full object-cover"/>
                                </a>
                                {{-- Always-visible delete button --}}
                                <button @click.prevent="confirmId = {{ $image->id }}; $refs.confirmDialog.showModal()"
                                        class="absolute top-1 left-1 p-1.5 rounded-full bg-red-500 hover:bg-red-600 text-white shadow transition"
                                        title="{{ __('Delete image') }}">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    {{-- Filament-style confirm delete modal (uses browser top-layer) --}}
                    <dialog x-ref="confirmDialog"
                            style="padding:0;border:none;outline:none;background:transparent;max-width:28rem;width:calc(100% - 2rem);border-radius:0.75rem;">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Delete Image') }}</h3>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Are you sure you want to delete this image? This action cannot be undone.') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 flex justify-end gap-3">
                                <button @click="$refs.confirmDialog.close()"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-500 transition">
                                    {{ __('Cancel') }}
                                </button>
                                <button @click="$wire.call('deleteTechnicianImage', confirmId); $refs.confirmDialog.close()"
                                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 border border-transparent rounded-lg transition">
                                    {{ __('Delete') }}
                                </button>
                            </div>
                        </div>
                    </dialog>
                @else
                    <div class="flex flex-col items-center justify-center py-8 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-dashed border-gray-200 dark:border-gray-700">
                        <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No technician images uploaded') }}</p>
                    </div>
                @endif
            </div>

            {{-- Customer Rating ──────────────────────────────────────────── --}}
            @if ($rating)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">

                    {{-- Hero banner --}}
                    <div class="bg-amber-50 dark:bg-amber-900/20 border-b border-amber-100 dark:border-amber-800/40 px-6 py-5 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-amber-600 dark:text-amber-400 mb-2.5">{{ __('Customer Rating') }}</p>
                            <div class="flex items-center gap-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg class="w-5 h-5 {{ $i <= round($ratingAvg) ? 'text-amber-400' : 'text-amber-200 dark:text-amber-800' }}"
                                         fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                @endfor
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-6xl font-black text-gray-900 dark:text-white leading-none tracking-tight">{{ $ratingAvg }}</div>
                            <div class="text-xs text-amber-500 dark:text-amber-400 mt-1.5 font-medium">{{ __('out of 5.0') }}</div>
                        </div>
                    </div>

                    {{-- Metrics --}}
                    <div class="p-5 space-y-5">

                        {{-- Product --}}
                        <div>
                            <div class="flex items-center justify-between mb-2.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                                        </svg>
                                    </div>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ __('Product Quality') }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-0.5">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <svg class="w-3.5 h-3.5 {{ $i <= $rating->product_rating ? 'text-amber-400' : 'text-gray-200 dark:text-gray-600' }}"
                                                 fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                            </svg>
                                        @endfor
                                    </div>
                                    <span class="text-2xl font-black text-gray-900 dark:text-white tabular-nums leading-none">{{ $rating->product_rating }}<span class="text-sm font-medium text-gray-400 dark:text-gray-500">/5</span></span>
                                </div>
                            </div>
                            <div class="h-2.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-amber-400" style="width: {{ ($rating->product_rating / 5) * 100 }}%"></div>
                            </div>
                        </div>

                        {{-- Service --}}
                        <div>
                            <div class="flex items-center justify-between mb-2.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-green-50 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ __('Service Quality') }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-0.5">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <svg class="w-3.5 h-3.5 {{ $i <= $rating->service_rating ? 'text-green-400' : 'text-gray-200 dark:text-gray-600' }}"
                                                 fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                            </svg>
                                        @endfor
                                    </div>
                                    <span class="text-2xl font-black text-gray-900 dark:text-white tabular-nums leading-none">{{ $rating->service_rating }}<span class="text-sm font-medium text-gray-400 dark:text-gray-500">/5</span></span>
                                </div>
                            </div>
                            <div class="h-2.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-green-400" style="width: {{ ($rating->service_rating / 5) * 100 }}%"></div>
                            </div>
                        </div>

                        {{-- Footer extras --}}
                        @if ($rating->how_found_us || $rating->customer_notes)
                            <div class="pt-4 border-t border-gray-100 dark:border-gray-700 space-y-3">
                                @if ($rating->how_found_us)
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-medium text-gray-400 dark:text-gray-500">{{ __('Found via') }}</span>
                                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 px-2.5 py-1 rounded-lg">{{ $rating->how_found_us }}</span>
                                    </div>
                                @endif
                                @if ($rating->customer_notes)
                                    <blockquote class="border-l-[3px] border-amber-300 dark:border-amber-600 pl-3.5">
                                        <p class="text-sm text-gray-600 dark:text-gray-400 italic leading-relaxed">"{{ $rating->customer_notes }}"</p>
                                    </blockquote>
                                @endif
                            </div>
                        @endif

                    </div>
                </div>
            @endif

        </div>{{-- /LEFT --}}

        {{-- ════════ SIDEBAR (1/3) ════════ --}}
        <div class="space-y-4">

            {{-- Customer ─────────────────────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-4">{{ __('Customer') }}</div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-11 h-11 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-300 flex items-center justify-center font-bold text-lg flex-shrink-0">
                        {{ $customerInitial }}
                    </div>
                    <div class="min-w-0">
                        <div class="font-semibold text-gray-900 dark:text-white truncate">{{ $record->user?->name ?? '—' }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 tabular-nums mt-0.5" dir="ltr">{{ $record->user?->phone ?? '—' }}</div>
                    </div>
                </div>
                @if ($record->user?->phone)
                    <a href="tel:{{ $record->user->phone }}"
                       class="flex items-center justify-center gap-1.5 w-full py-2.5 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 border border-blue-100 dark:border-blue-800/50 rounded-xl text-sm font-semibold text-blue-600 dark:text-blue-400 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ __('Call Customer') }}
                    </a>
                @endif
            </div>

            {{-- Technician ──────────────────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-4">{{ __('Assigned Technician') }}</div>
                @if ($record->technician)
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-11 h-11 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 flex items-center justify-center font-bold text-lg flex-shrink-0">
                            {{ $techInitial }}
                        </div>
                        <div class="min-w-0">
                            <div class="font-semibold text-gray-900 dark:text-white truncate">{{ $record->technician->name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 tabular-nums mt-0.5" dir="ltr">{{ $record->technician->phone }}</div>
                        </div>
                    </div>
                    <a href="tel:{{ $record->technician->phone }}"
                       class="flex items-center justify-center gap-1.5 w-full py-2.5 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-semibold text-gray-600 dark:text-gray-300 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        Call Technician
                    </a>
                @else
                    <div class="flex flex-col items-center justify-center py-6 text-center">
                        <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <span class="text-sm text-gray-400 dark:text-gray-500">{{ __('Not yet assigned') }}</span>
                    </div>
                @endif
            </div>

            {{-- Schedule ────────────────────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-4">{{ __('Schedule') }}</div>

                <div class="space-y-3">

                    <div class="flex items-center gap-3 p-3 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800/40">
                        <div class="w-9 h-9 rounded-lg bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center flex-shrink-0">
                            <svg class="text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-blue-500 dark:text-blue-400 mb-0.5">{{ __('Scheduled Date') }}</div>
                            <div class="font-bold text-gray-900 dark:text-white">{{ $record->scheduled_at?->format('M d, Y') ?? '—' }}</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-gray-100 dark:border-gray-700">
                        <div class="w-9 h-9 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                            <svg class="text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-400 dark:text-gray-500 mb-0.5">{{ __('End Date') }}</div>
                            <div class="font-bold text-gray-900 dark:text-white">{{ $record->end_date?->format('M d, Y') ?? '—' }}</div>
                        </div>
                    </div>

                    @if ($record->completed_at)
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800/40">
                            <div class="w-9 h-9 rounded-lg bg-green-100 dark:bg-green-900/40 flex items-center justify-center flex-shrink-0">
                                <svg class="text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-medium text-green-600 dark:text-green-400 mb-0.5">{{ __('Completed') }}</div>
                                <div class="font-bold text-gray-900 dark:text-white">{{ $record->completed_at->format('M d, Y') }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $record->completed_at->format('H:i') }}</div>
                            </div>
                        </div>
                    @endif

                    @if ($record->task_start_time || $record->task_end_time)
                        <div class="rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-gray-100 dark:border-gray-700 p-3">
                            <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2.5">{{ __('Work Duration') }}</div>
                            <div class="grid grid-cols-2 gap-2">
                                @if ($record->task_start_time)
                                    <div class="text-center">
                                        <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $record->task_start_time->format('H:i') }}</div>
                                        <div class="text-[10px] font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mt-0.5">{{ __('Started') }}</div>
                                    </div>
                                @endif
                                @if ($record->task_end_time)
                                    <div class="text-center {{ $record->task_start_time ? 'border-l border-gray-200 dark:border-gray-600' : '' }}">
                                        <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $record->task_end_time->format('H:i') }}</div>
                                        <div class="text-[10px] font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mt-0.5">{{ __('Ended') }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                </div>
            </div>

            {{-- Activity Log ─────────────────────────────────────────────── --}}
            @if ($activities->count())
                @php
                    $statusLabels = [
                        'pending'     => __('Pending'),
                        'assigned'    => __('Assigned'),
                        'on_way'      => __('On the Way'),
                        'in_progress' => __('In Progress'),
                        'completed'   => __('Completed'),
                        'canceled'    => __('Canceled'),
                    ];

                    $buildEvent = function($activity) use ($statusLabels) {
                        $desc  = $activity->description;
                        $attrs = $activity->properties['attributes'] ?? [];
                        $old   = $activity->properties['old'] ?? [];

                        if ($desc === 'created') {
                            return ['phrase' => __('Service request was created'), 'sub' => __('New request submitted and waiting for assignment'), 'icon' => 'created', 'color' => 'blue'];
                        }

                        if ($desc === 'updated') {
                            $hasStatus = array_key_exists('status', $attrs);
                            $hasTech   = array_key_exists('technician_id', $attrs);

                            if ($hasStatus && $hasTech) {
                                $newStatus = $statusLabels[$attrs['status']] ?? $attrs['status'];
                                return ['phrase' => __('Technician assigned and status set to ":status"', ['status' => $newStatus]), 'sub' => null, 'icon' => 'assign', 'color' => 'purple'];
                            }

                            if ($hasStatus) {
                                $newStatus = $statusLabels[$attrs['status']] ?? $attrs['status'];
                                $oldStatus = isset($old['status']) ? ($statusLabels[$old['status']] ?? $old['status']) : null;
                                $sub   = $oldStatus ? __('Changed from ":status"', ['status' => $oldStatus]) : null;
                                $color = match ($attrs['status']) {
                                    'completed'   => 'green',
                                    'canceled'    => 'red',
                                    'on_way'      => 'violet',
                                    'in_progress' => 'indigo',
                                    'assigned'    => 'purple',
                                    default       => 'amber',
                                };
                                $icon = match ($attrs['status']) {
                                    'completed'   => 'completed',
                                    'canceled'    => 'canceled',
                                    'on_way'      => 'on_way',
                                    'in_progress' => 'in_progress',
                                    'assigned'    => 'assign',
                                    default       => 'status',
                                };
                                return ['phrase' => __('Status updated to ":status"', ['status' => $newStatus]), 'sub' => $sub, 'icon' => $icon, 'color' => $color];
                            }

                            if ($hasTech) {
                                if (empty($attrs['technician_id'])) {
                                    return ['phrase' => __('Technician was unassigned'), 'sub' => null, 'icon' => 'unassign', 'color' => 'red'];
                                }
                                $wasReassign = !empty($old['technician_id']);
                                return ['phrase' => $wasReassign ? __('Technician was reassigned') : __('Technician was assigned'), 'sub' => null, 'icon' => 'assign', 'color' => 'purple'];
                            }

                            return ['phrase' => __('Request details were updated'), 'sub' => null, 'icon' => 'edit', 'color' => 'gray'];
                        }

                        if ($desc === 'deleted') {
                            return ['phrase' => __('Request was deleted'), 'sub' => null, 'icon' => 'canceled', 'color' => 'red'];
                        }

                        return ['phrase' => ucfirst($desc), 'sub' => null, 'icon' => 'edit', 'color' => 'gray'];
                    };

                    $iconBg = [
                        'blue'   => 'bg-blue-100 dark:bg-blue-900/40',
                        'purple' => 'bg-purple-100 dark:bg-purple-900/40',
                        'green'  => 'bg-green-100 dark:bg-green-900/40',
                        'amber'  => 'bg-amber-100 dark:bg-amber-900/40',
                        'red'    => 'bg-red-100 dark:bg-red-900/40',
                        'violet' => 'bg-violet-100 dark:bg-violet-900/40',
                        'indigo' => 'bg-indigo-100 dark:bg-indigo-900/40',
                        'gray'   => 'bg-gray-100 dark:bg-gray-700',
                    ];
                    $iconColor = [
                        'blue'   => 'text-blue-500 dark:text-blue-400',
                        'purple' => 'text-purple-500 dark:text-purple-400',
                        'green'  => 'text-green-500 dark:text-green-400',
                        'amber'  => 'text-amber-500 dark:text-amber-400',
                        'red'    => 'text-red-500 dark:text-red-400',
                        'violet' => 'text-violet-500 dark:text-violet-400',
                        'indigo' => 'text-indigo-500 dark:text-indigo-400',
                        'gray'   => 'text-gray-400 dark:text-gray-500',
                    ];
                    $lineBg = [
                        'blue'   => 'bg-blue-200 dark:bg-blue-800',
                        'purple' => 'bg-purple-200 dark:bg-purple-800',
                        'green'  => 'bg-green-200 dark:bg-green-800',
                        'amber'  => 'bg-amber-200 dark:bg-amber-800',
                        'red'    => 'bg-red-200 dark:bg-red-800',
                        'violet' => 'bg-violet-200 dark:bg-violet-800',
                        'indigo' => 'bg-indigo-200 dark:bg-indigo-800',
                        'gray'   => 'bg-gray-200 dark:bg-gray-600',
                    ];
                @endphp

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">

                    <div class="flex items-center justify-between mb-5">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('Activity Log') }}</span>
                        </div>
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 rounded-full">
                            {{ $activities->count() }} {{ __('events') }}
                        </span>
                    </div>

                    <div class="overflow-y-auto" style="max-height: 420px; scrollbar-width: thin;">
                        <div class="relative">
                            @foreach ($activities as $activity)
                                @php
                                    $event  = $buildEvent($activity);
                                    $c      = $event['color'];
                                    $isLast = $loop->last;
                                @endphp

                                <div class="relative flex gap-4 {{ !$isLast ? 'pb-5' : '' }}">

                                    @if (!$isLast)
                                        <div class="absolute left-[17px] top-[36px] bottom-0 w-0.5 {{ $lineBg[$c] }} opacity-40"></div>
                                    @endif

                                    <div class="flex-shrink-0 w-9 h-9 rounded-full {{ $iconBg[$c] }} {{ $iconColor[$c] }} flex items-center justify-center z-10">
                                        @if ($event['icon'] === 'created')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        @elseif ($event['icon'] === 'assign')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                        @elseif ($event['icon'] === 'unassign')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"/></svg>
                                        @elseif ($event['icon'] === 'completed')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @elseif ($event['icon'] === 'canceled')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @elseif ($event['icon'] === 'on_way')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                        @elseif ($event['icon'] === 'in_progress')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        @endif
                                    </div>

                                    <div class="flex-1 min-w-0 pt-1">
                                        <p class="font-semibold text-sm text-gray-900 dark:text-white leading-snug">{{ $event['phrase'] }}</p>
                                        @if ($event['sub'])
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $event['sub'] }}</p>
                                        @endif
                                        <div class="flex items-center flex-wrap gap-x-2 gap-y-0.5 mt-1.5">
                                            <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                                <svg class="w-3 h-3 text-gray-300 dark:text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $activity->causer?->name ?? __('System') }}</span>
                                            </span>
                                            <span class="text-gray-300 dark:text-gray-600">·</span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                                {{ $activity->created_at->format('M d, Y') }}
                                                <span class="text-gray-300 dark:text-gray-600">{{ __('at') }}</span>
                                                {{ $activity->created_at->format('H:i') }}
                                            </span>
                                            <span class="text-gray-300 dark:text-gray-600">·</span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $activity->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>
            @endif

        </div>{{-- /SIDEBAR --}}

    </div>{{-- /grid --}}

</div>{{-- /outer --}}

@push('styles')
<style>
    .leaflet-popup-content { margin: 0 !important; }
    .leaflet-popup-content-wrapper { padding: 0 !important; border-radius: 10px !important; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.15) !important; }
    .leaflet-popup-tip-container { margin-top: -1px; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    function loadLeaflet(cb) {
        if (typeof L !== 'undefined') { cb(); return; }
        var link = document.createElement('link');
        link.rel  = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);
        var script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = cb;
        document.head.appendChild(script);
    }

    @if ($hasLocation)
    var lat   = {{ $record->latitude }};
    var lng   = {{ $record->longitude }};
    var label = @js($record->user?->name ?? __('Client'));
    var addr  = @js($record->address ?? '');

    function boot() {
        var el = document.getElementById('service-map');
        if (!el || el._leaflet_id) return;

        var map = L.map(el, { scrollWheelZoom: false }).setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(map);

        L.marker([lat, lng])
            .addTo(map)
            .bindPopup(
                '<div style="font-family:system-ui,sans-serif;padding:10px 12px;min-width:180px;">'
                + '<div style="font-weight:700;color:#111;margin-bottom:3px;">' + label + '</div>'
                + '<div style="font-size:12px;color:#6b7280;">' + addr + '</div>'
                + '</div>',
                { maxWidth: 220 }
            )
            .openPopup();
    }

    loadLeaflet(boot);
    @endif
})();
</script>
@endpush

</x-filament-panels::page>
