{{--
    wire:ignore prevents Livewire from ever morphing this element's DOM after the
    initial render. All state is owned by Alpine. This is the fix for the
    "Map container is already initialized" crash.
--}}
<div
    wire:ignore
    x-data="liveMap(@js($technicianLocations))"
    x-init="init()"
    class="space-y-4"
>
    {{-- ─── ROW 1: Header bar ──────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow px-5 py-3 flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="font-semibold text-gray-900 dark:text-white">{{ __('Live Tracking') }}</span>
        </div>

        <div class="flex items-center gap-4 text-sm">
            <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                <span class="font-semibold text-green-600 dark:text-green-400" x-text="onlineCount"></span>
                <span class="text-gray-500 dark:text-gray-400">{{ __('Online') }}</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                <span class="font-semibold text-gray-500 dark:text-gray-400" x-text="offlineCount"></span>
                <span class="text-gray-500 dark:text-gray-400">{{ __('Offline') }}</span>
            </div>
        </div>

        <div class="flex rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600 text-xs font-medium">
            <button @click="sidebarFilter = 'all'" class="px-3 py-1.5 transition"
                :class="sidebarFilter === 'all' ? 'bg-primary-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
            >{{ __('All') }}</button>
            <button @click="sidebarFilter = 'online'" class="px-3 py-1.5 transition border-l border-gray-200 dark:border-gray-600"
                :class="sidebarFilter === 'online' ? 'bg-primary-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
            >{{ __('Online only') }}</button>
        </div>

        <div class="ml-auto flex items-center gap-1.5 text-xs" x-show="wsConnected">
            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
            <span class="text-gray-500 dark:text-gray-400">{{ __('Real-time') }}</span>
        </div>
    </div>

    {{-- ─── ROW 2: Technician cards ─────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow">
        <div class="flex gap-3 overflow-x-auto px-4 py-3" style="scrollbar-width: thin;">
            <template x-for="tech in filteredTechs" :key="tech.technician_id">
                {{-- div instead of button so the nested "تفاصيل الطلب" button is valid HTML --}}
                <div
                    @click="focusTechnician(tech)"
                    role="button"
                    class="flex-none flex flex-col items-center gap-2 px-4 py-3 rounded-xl border-2 transition cursor-pointer min-w-[140px]"
                    :class="focusedTechId === tech.technician_id
                        ? 'border-primary-400 bg-primary-50 dark:border-primary-500 dark:bg-primary-900/20'
                        : (tech.is_online
                            ? 'border-green-200 dark:border-green-800 hover:border-green-400 bg-green-50/50 dark:bg-green-900/10'
                            : 'border-gray-100 dark:border-gray-700 hover:border-gray-300 bg-gray-50/50 dark:bg-gray-700/30')"
                >
                    <div class="w-11 h-11 rounded-full flex items-center justify-center text-base font-bold relative"
                        :class="tech.is_online
                            ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                            : 'bg-gray-100 text-gray-500 dark:bg-gray-600 dark:text-gray-300'"
                    >
                        <span x-text="tech.name.charAt(0).toUpperCase()"></span>
                        <span class="absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white dark:border-gray-800"
                            :class="tech.is_online ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-500'"
                        ></span>
                    </div>

                    <span class="text-xs font-semibold text-gray-900 dark:text-white text-center leading-tight max-w-[120px]" x-text="tech.name"></span>

                    <div class="flex flex-col items-center gap-1 w-full">
                        {{-- Online/offline status badge --}}
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                            :class="!tech.is_online
                                ? 'bg-gray-100 text-gray-500 dark:bg-gray-600 dark:text-gray-400'
                                : (noGps(tech)
                                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                                    : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400')"
                            :title="noGps(tech) ? 'Online but no GPS update in 5+ min — phone may be in background' : ''"
                            x-text="!tech.is_online ? @js(__('Offline')) : (noGps(tech) ? @js(__('No GPS signal')) : @js(__('Online')))"
                        ></span>
                        {{-- Last update time --}}
                        <span class="text-xs text-gray-400 dark:text-gray-500" x-text="relativeTime(tech.updated_at)"></span>
                        {{-- Active request status chip (x-show avoids nested template scope issues) --}}
                        <span x-show="tech.active_request"
                            class="text-[10px] font-semibold px-2 py-0.5 rounded-full mt-1 truncate"
                            :class="tech.active_request?.status === 'on_way'
                                ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
                                : 'bg-amber-100 text-amber-700'"
                            x-text="tech.active_request?.status === 'on_way' ? 'في الطريق' : 'قيد التنفيذ'">
                        </span>
                        <span x-show="tech.active_request"
                            class="text-[10px] text-gray-500 truncate"
                            x-text="tech.active_request?.invoice_number ?? (tech.active_request ? '#' + tech.active_request.id : '')">
                        </span>
                        {{-- Request details button --}}
                        <button
                            type="button"
                            x-show="tech.is_online"
                            @click.stop.prevent="showTechRequestDetails(tech.technician_id)"
                            class="mt-1 w-full inline-flex items-center justify-center gap-1 text-[10px] font-semibold px-2 py-1 rounded-lg bg-primary-600 hover:bg-primary-700 text-white transition"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            تفاصيل الطلب
                        </button>
                    </div>
                </div>
            </template>

            <template x-if="filteredTechs.length === 0">
                <div class="flex-1 py-6 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('No technicians found') }}</div>
            </template>
        </div>
    </div>

    {{-- ─── ROW 3: Map ──────────────────────────────────────────────────────── --}}
    <div class="sindbad-map-wrapper relative rounded-xl shadow overflow-hidden bg-gray-100 dark:bg-gray-900" style="height: 520px; z-index: 0;">
        <div id="sindbad-live-map" class="w-full h-full"></div>

        {{-- Map overlay buttons --}}
        <div class="absolute top-3 left-3 z-[1000] flex flex-col gap-2">
            <button @click="fitAllMarkers()"
                class="bg-white dark:bg-gray-800 shadow-md rounded-lg px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition flex items-center gap-1.5 border border-gray-200 dark:border-gray-600"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
                {{ __('Fit All') }}
            </button>
            {{-- Fly to customer location --}}
            <button x-show="focusedTechId !== null && selectedTechRequest && selectedTechRequest.customer_lat"
                @click="flyToCustomer()"
                class="bg-white dark:bg-gray-800 shadow-md rounded-lg px-3 py-2 text-xs font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition flex items-center gap-1.5 border border-blue-200 dark:border-blue-700"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ __('Customer Location') }}
            </button>
            <button x-show="focusedTechId !== null" @click="clearFocus()"
                class="bg-white dark:bg-gray-800 shadow-md rounded-lg px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition flex items-center gap-1.5 border border-gray-200 dark:border-gray-600"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                {{ __('Clear Route') }}
            </button>
        </div>

        {{-- Route loading indicator --}}
        <div x-show="routeLoading" class="absolute top-3 right-3 z-[1000] bg-white dark:bg-gray-800 shadow rounded-lg px-3 py-2 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
            <svg class="animate-spin w-3.5 h-3.5 text-blue-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            {{ __('Fetching route…') }}
        </div>

    </div>

    {{-- ─── Request detail modal ─────────────────────────────────────────────
         x-teleport moves this element to <body> so position:fixed is always
         relative to the viewport, unaffected by any parent CSS transform. --}}
    <template x-teleport="body">
    {{-- Overlay wrapper — inline style so position:fixed survives Tailwind purge --}}
    <div x-show="requestPanelOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;">

        {{-- Backdrop — clicking it closes the modal --}}
        <div @click="requestPanelOpen = false"
             style="position:absolute;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);cursor:pointer;"></div>

        {{-- Modal card — absolutely centered, max-width 600px --}}
        <div x-show="requestPanelOpen"
             style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:calc(100% - 2rem);max-width:600px;background:white;border-radius:1rem;box-shadow:0 25px 50px rgba(0,0,0,0.3);z-index:2;overflow:hidden;"
             dir="rtl"
             @click.stop>

            {{-- Modal header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                <div class="flex items-center gap-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span class="text-base font-bold text-gray-900 dark:text-white">تفاصيل الطلب</span>
                    <template x-if="selectedTechRequest">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                            :class="selectedTechRequest.status === 'on_way'
                                ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
                                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'"
                            x-text="selectedTechRequest.status === 'on_way' ? 'في الطريق' : 'قيد التنفيذ'">
                        </span>
                    </template>
                </div>
                <button @click="requestPanelOpen = false"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Modal body: loading --}}
            <template x-if="!selectedTechRequest && detailsLoading">
                <div class="flex flex-col items-center justify-center py-12 text-gray-400 dark:text-gray-500">
                    <svg class="animate-spin w-8 h-8 text-primary-500 mb-3" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    <span class="text-sm">جاري التحميل…</span>
                </div>
            </template>

            {{-- Modal body: no active request --}}
            <template x-if="!selectedTechRequest && !detailsLoading">
                <div class="flex flex-col items-center justify-center py-12 text-gray-400 dark:text-gray-500 gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span class="text-sm font-medium">لا يوجد طلب نشط</span>
                </div>
            </template>

            {{-- Modal body: request data --}}
            <template x-if="selectedTechRequest">
                <div class="px-5 py-5 space-y-4">

                    {{-- Top row: invoice + type --}}
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3">
                            <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 block mb-1">رقم الفاتورة</span>
                            <span class="font-bold text-gray-900 dark:text-white"
                                x-text="selectedTechRequest.invoice_number ?? ('#' + selectedTechRequest.id)">
                            </span>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3">
                            <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 block mb-1">نوع الطلب</span>
                            <span class="font-semibold text-gray-900 dark:text-white"
                                x-text="selectedTechRequest.type === 'service' ? 'صيانة' : (selectedTechRequest.type === 'installation' ? 'تركيب' : (selectedTechRequest.type ?? '—'))">
                            </span>
                        </div>
                    </div>

                    {{-- Customer info card --}}
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-xl p-4 space-y-2.5">
                        <p class="text-[11px] font-semibold text-blue-500 dark:text-blue-400 uppercase tracking-wide">معلومات العميل</p>
                        <div class="flex items-center gap-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span class="font-semibold text-gray-900 dark:text-white text-sm" x-text="selectedTechRequest.customer_name ?? '—'"></span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <a :href="'tel:' + selectedTechRequest.customer_phone"
                               class="font-semibold text-blue-600 dark:text-blue-400 text-sm hover:underline"
                               x-text="selectedTechRequest.customer_phone ?? '—'">
                            </a>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="text-sm text-gray-700 dark:text-gray-300" x-text="selectedTechRequest.address ?? '—'"></span>
                        </div>
                    </div>

                    {{-- Scheduled date --}}
                    <div x-show="selectedTechRequest.scheduled_at" class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span x-text="selectedTechRequest.scheduled_at"></span>
                    </div>

                    {{-- Footer: full request link --}}
                    <template x-if="selectedTechRequest.admin_url">
                        <a :href="selectedTechRequest.admin_url" target="_blank"
                            class="flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            عرض الطلب كاملاً
                        </a>
                    </template>
                </div>
            </template>
        </div>
    </div>
    </template>{{-- end x-teleport --}}

</div>

@push('styles')
<style>
    .leaflet-popup-content { margin: 0 !important; }
    .leaflet-popup-content-wrapper { padding: 0 !important; border-radius: 10px !important; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.15) !important; }
    .leaflet-popup-tip-container { margin-top: -1px; }
    .leaflet-top.leaflet-left { top: 60px; }
    /* Keep the map container below Filament's top navbar when scrolling */
    #sindbad-live-map { isolation: isolate; }
    .sindbad-map-wrapper { z-index: 0 !important; }
</style>
@endpush

@push('scripts')
<script>

function liveMap(initialLocations) {
    return {
        // ── Map state ─────────────────────────────────────────────────────────
        map:          null,
        markers:      {},
        popups:       {},
        trails:       {},
        polylines:    {},
        destMarker:   null,
        routePolyline: null,
        routeLoading:  false,
        focusedTechId: null,

        // ── App state ─────────────────────────────────────────────────────────
        locations:     [...initialLocations],
        sidebarFilter: 'all',
        wsConnected:   false,
        _tick:         0,
        selectedTechRequest: null,
        requestPanelOpen: false,
        detailsLoading: false,

        // ── Computed ─────────────────────────────────────────────────────────
        get onlineCount()  { return this.locations.filter(l => l.is_online).length; },
        get offlineCount() { return this.locations.filter(l => !l.is_online).length; },
        get filteredTechs() {
            void this._tick;
            if (this.sidebarFilter === 'online') return this.locations.filter(l => l.is_online);
            return this.locations;
        },

        // ── Init ─────────────────────────────────────────────────────────────
        init() {
            this.loadLeaflet();
            this.startTickTimer();
            this.startFallbackPoller();
            window.addEventListener('technicianRequestLoaded', (e) => {
                // Livewire 3 named-arg dispatch → e.detail.active_request
                // Livewire 3 positional-array dispatch → e.detail[0].active_request
                const payload = (e.detail?.active_request !== undefined)
                    ? e.detail
                    : (e.detail?.[0] ?? {});
                // Only use server data when we have nothing cached — prevents the server
                // returning a different request (e.g. in_progress) from overwriting the
                // on_way request the user explicitly clicked
                if (!this.selectedTechRequest) {
                    this.selectedTechRequest = payload.active_request ?? null;
                }
                this.detailsLoading = false;
                this.requestPanelOpen = true;
            });
        },

        loadLeaflet() {
            if (typeof L !== 'undefined') { this.setupMap(); return; }
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            document.head.appendChild(link);
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.onload = () => this.setupMap();
            document.head.appendChild(script);
        },

        setupMap() {
            if (this.map) return; // already initialized — prevents "Map container is already initialized"
            this.map = L.map('sindbad-live-map', { center: [23.5880, 58.3829], zoom: 12, zoomControl: true });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(this.map);
            this.locations.forEach(loc => this.syncMarker(loc));
            this.connectEcho();
        },

        // ── Echo WebSocket ────────────────────────────────────────────────────
        connectEcho() {
            if (typeof window.Echo === 'undefined') {
                window.addEventListener('EchoLoaded', () => this.connectEcho());
                return;
            }
            try {
            window.Echo.channel('technician-locations')
                .listen('.TechnicianLocationUpdated', (data) => this.onLocationUpdate(data))
                .listen('.RequestStatusChanged', (data) => this.onRequestStatusChanged(data));

            const conn = window.Echo.connector?.pusher?.connection;
            if (conn) {
                conn.bind('connected',    () => { this.wsConnected = true; });
                conn.bind('disconnected', () => { this.wsConnected = false; });
                conn.bind('unavailable',  () => { this.wsConnected = false; });
                if (conn.state === 'connected') this.wsConnected = true;
            } else {
                this.wsConnected = true; // assume connected if no state to check
            }
            } catch (e) {
                console.warn('Echo/Pusher connection failed:', e.message);
            }
        },

        // ── Location update handler (Echo + polling) ─────────────────────────
        onLocationUpdate(data) {
            const idx = this.locations.findIndex(l => l.technician_id === data.technician_id);
            if (idx >= 0) {
                // Preserve active_request from existing entry when broadcast omits it —
                // location broadcasts only carry GPS fields, not request data
                this.locations[idx] = {
                    ...this.locations[idx],
                    ...data,
                    active_request: data.active_request !== undefined
                        ? data.active_request
                        : this.locations[idx].active_request,
                };
            } else {
                this.locations.push(data);
            }
            this.syncMarker(data);

            // If this is the focused technician, redraw the route live
            if (this.focusedTechId === data.technician_id) {
                if (data.is_online && data.latitude) {
                    // Use the merged location (with preserved active_request)
                    const activeReq = this.locations[idx]?.active_request;
                    if (activeReq?.customer_lat && activeReq?.customer_lng) {
                        this.drawRoute(parseFloat(data.latitude), parseFloat(data.longitude), activeReq.customer_lat, activeReq.customer_lng);
                    }
                    // Don't clear route on update when no customer coords — keep existing
                } else {
                    this.clearFocus();
                }
            }
        },

        // ── Marker management ─────────────────────────────────────────────────
        syncMarker(data) {
            if (!this.map) return;
            const id = data.technician_id;

            if (!data.is_online || !data.latitude) {
                this.removeMarker(id);
                return;
            }

            const pos  = [parseFloat(data.latitude), parseFloat(data.longitude)];
            const icon = this.buildIcon(data.heading, this.noGps(data));

            if (this.markers[id]) {
                this.markers[id].setLatLng(pos);
                this.markers[id].setIcon(icon);
                this.popups[id]?.setContent(this.buildPopupContent(data));
            } else {
                const marker = L.marker(pos, { icon }).addTo(this.map);
                const popup  = L.popup({ maxWidth: 260, closeButton: true }).setContent(this.buildPopupContent(data));
                marker.bindPopup(popup);
                this.markers[id] = marker;
                this.popups[id]  = popup;
            }

            this.updateTrail(id, pos);
        },

        removeMarker(id) {
            this.markers[id]?.remove();   delete this.markers[id];
            this.polylines[id]?.remove(); delete this.polylines[id];
            delete this.trails[id];
            delete this.popups[id];
        },

        updateTrail(id, pos) {
            if (!this.trails[id]) this.trails[id] = [];
            this.trails[id].push(pos);
            if (this.trails[id].length > 5) this.trails[id].shift();
            if (this.trails[id].length < 2) return;

            if (this.polylines[id]) {
                this.polylines[id].setLatLngs(this.trails[id]);
            } else {
                this.polylines[id] = L.polyline(this.trails[id], {
                    color: '#22c55e', weight: 3, opacity: 0.5, dashArray: '6 4',
                }).addTo(this.map);
            }
        },

        buildIcon(heading, noSignal) {
            const color = noSignal ? '#f59e0b' : '#22c55e';
            const arrow = heading != null
                ? `<polygon points="16,5 12,13 20,13" fill="white" fill-opacity="0.95" transform="rotate(${heading} 16 16)"/>`
                : '';
            const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="38" height="38">
                <circle cx="16" cy="16" r="13" fill="${color}" opacity="0.95" stroke="white" stroke-width="2"/>
                ${arrow}
                <circle cx="16" cy="16" r="4" fill="white"/>
            </svg>`;
            return L.divIcon({ html: svg, className: '', iconSize: [38,38], iconAnchor: [19,19], popupAnchor: [0,-22] });
        },

        buildPopupContent(data) {
            const updated = data.updated_at ? this.relativeTime(data.updated_at) : 'N/A';
            const status  = data.is_online ? '<span style="color:#22c55e;">●</span> Online' : '<span style="color:#9ca3af;">●</span> Offline';
            const speed   = data.speed != null ? `<div>⚡ <strong>${Math.round(data.speed)} km/h</strong></div>` : '';
            const hdg     = data.heading != null ? `<div>↗ ${Math.round(data.heading)}°</div>` : '';

            return `<div style="font-family:system-ui,sans-serif;padding:10px 12px;min-width:180px;line-height:1.6;">
                <div style="font-size:14px;font-weight:700;color:#111;margin-bottom:3px;">${data.name || 'Unknown'}</div>
                <div style="font-size:12px;color:#555;">📞 ${data.phone || '—'}</div>
                <div style="font-size:12px;color:#555;margin-top:2px;">${status} · ${updated}</div>
                <div style="font-size:12px;color:#666;margin-top:2px;">${hdg}${speed}</div>
                <div style="font-size:11px;color:#999;margin-top:2px;">📍 ${parseFloat(data.latitude).toFixed(5)}, ${parseFloat(data.longitude).toFixed(5)}</div>
            </div>`;
        },

        // ── Card click: focus technician + draw route (no popup, no panel) ─────
        async focusTechnician(tech) {
            if (!this.map) return;

            // Toggle off if already focused — but don't close modal (user may have just
            // clicked a child button; use the Clear Route button to explicitly clear focus)
            if (this.focusedTechId === tech.technician_id) {
                if (!this.requestPanelOpen) this.clearFocus();
                return;
            }

            this.focusedTechId = tech.technician_id;
            this.selectedTechRequest = tech.active_request ?? null; // needed for "Customer Location" button

            if (!tech.is_online || !tech.latitude) return;

            this.map.flyTo([tech.latitude, tech.longitude], 15, { duration: 0.8 });

            if (tech.active_request?.customer_lat && tech.active_request?.customer_lng) {
                this.updateDestMarker(tech.active_request);
                await this.drawRoute(
                    tech.latitude, tech.longitude,
                    tech.active_request.customer_lat,
                    tech.active_request.customer_lng,
                );
            } else {
                this.clearRouteAndDest();
            }
        },

        // ── "تفاصيل الطلب" button: open detail panel for this technician ────────
        showTechRequestDetails(technicianId) {
            const tech = this.locations.find(l => l.technician_id === technicianId);
            const cached = tech?.active_request ?? null;
            this.selectedTechRequest = cached;
            this.detailsLoading = !cached;
            this.requestPanelOpen = true;
            try {
                this.$wire.loadTechnicianRequest(technicianId);
            } catch (e) {
                this.detailsLoading = false;
            }
        },

        // ── Fly to customer location ──────────────────────────────────────────
        flyToCustomer() {
            if (!this.selectedTechRequest?.customer_lat || !this.selectedTechRequest?.customer_lng) return;
            this.map.flyTo(
                [this.selectedTechRequest.customer_lat, this.selectedTechRequest.customer_lng],
                16, { duration: 0.8 }
            );
            this.destMarker?.openPopup();
        },

        async drawRoute(fromLat, fromLng, toLat, toLng) {
            // Clear previous route polyline (keep destMarker — caller manages it)
            if (this.routePolyline) {
                this.routePolyline.remove();
                this.routePolyline = null;
            }

            if (toLat == null || toLng == null) return;

            // Fetch OSRM driving route
            this.routeLoading = true;
            try {
                const pts = await this.fetchOSRMRoute(fromLat, fromLng, toLat, toLng);
                if (pts) {
                    this.routePolyline = L.polyline(pts, {
                        color: '#3b82f6', weight: 4, opacity: 0.75,
                    }).addTo(this.map);
                } else {
                    // OSRM returned no route (e.g. cross-country) — draw straight line
                    this.routePolyline = L.polyline(
                        [[fromLat, fromLng], [toLat, toLng]],
                        { color: '#3b82f6', weight: 3, opacity: 0.6, dashArray: '10 8' }
                    ).addTo(this.map);
                }
                const bounds = this.routePolyline.getBounds();
                if (bounds.isValid()) {
                    this.map.flyToBounds(bounds, { padding: [80, 80], duration: 0.6 });
                }
            } catch (_) {
                // Network error fallback — straight dashed line
                this.routePolyline = L.polyline(
                    [[fromLat, fromLng], [toLat, toLng]],
                    { color: '#3b82f6', weight: 3, opacity: 0.6, dashArray: '10 8' }
                ).addTo(this.map);
                const bounds = this.routePolyline.getBounds();
                if (bounds.isValid()) {
                    this.map.flyToBounds(bounds, { padding: [80, 80], duration: 0.6 });
                }
            }
            this.routeLoading = false;
        },

        async fetchOSRMRoute(fromLat, fromLng, toLat, toLng) {
            const url = `https://router.project-osrm.org/route/v1/driving/${fromLng},${fromLat};${toLng},${toLat}?overview=full&geometries=geojson`;
            const res = await fetch(url);
            if (!res.ok) return null;
            const data = await res.json();
            if (data.code !== 'Ok' || !data.routes?.[0]) return null;
            return data.routes[0].geometry.coordinates.map(c => [c[1], c[0]]); // [lng,lat] → [lat,lng]
        },

        clearFocus() {
            this.focusedTechId = null;
            this.selectedTechRequest = null;
            this.requestPanelOpen = false;
            this.clearRouteAndDest();
        },

        updateDestMarker(activeRequest) {
            if (this.destMarker) {
                this.destMarker.setLatLng([activeRequest.customer_lat, activeRequest.customer_lng]);
                this.destMarker.bindPopup(this.buildDestPopupContent(activeRequest));
            } else {
                this.destMarker = L.marker([activeRequest.customer_lat, activeRequest.customer_lng], {
                    icon: L.divIcon({
                        className: '',
                        html: '<div style="background:#ef4444;width:16px;height:16px;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.4)"></div>',
                        iconSize: [16, 16],
                        iconAnchor: [8, 8],
                    })
                }).addTo(this.map)
                  .bindPopup(this.buildDestPopupContent(activeRequest));
            }
            // Popup opens only when user clicks "Customer Location" button (flyToCustomer)
        },

        buildDestPopupContent(req) {
            const statusLabel = req.status === 'on_way' ? 'في الطريق' : 'قيد التنفيذ';
            const statusBg    = req.status === 'on_way' ? '#3b82f6' : '#f59e0b';
            const invoice     = req.invoice_number ?? ('#' + req.id);
            const phone       = req.customer_phone
                ? `<a href="tel:${req.customer_phone}"
                       style="color:#3b82f6;text-decoration:none;font-size:12px;display:flex;align-items:center;gap:5px;">
                       <span>📞</span><span>${req.customer_phone}</span>
                   </a>`
                : '';
            const address = req.address
                ? `<div style="display:flex;align-items:flex-start;gap:5px;font-size:11px;color:#6b7280;">
                       <span style="flex-shrink:0;line-height:1.5;">📍</span>
                       <span>${req.address}</span>
                   </div>`
                : '';

            return `<div dir="rtl" style="font-family:system-ui,sans-serif;min-width:210px;max-width:270px;overflow:hidden;">
                <div style="background:${statusBg};padding:8px 12px;display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <span style="font-size:13px;font-weight:700;color:white;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        ${req.customer_name ?? 'عميل'}
                    </span>
                    <span style="flex-shrink:0;font-size:10px;font-weight:600;color:${statusBg};background:white;padding:2px 8px;border-radius:999px;">
                        ${statusLabel}
                    </span>
                </div>
                <div style="padding:8px 12px;display:flex;flex-direction:column;gap:5px;">
                    <div style="font-size:11px;color:#9ca3af;font-weight:500;">🧾 ${invoice}</div>
                    ${phone}
                    ${address}
                </div>
            </div>`;
        },

        clearRouteAndDest() {
            if (this.routePolyline) { this.map.removeLayer(this.routePolyline); this.routePolyline = null; }
            if (this.destMarker) { this.map.removeLayer(this.destMarker); this.destMarker = null; }
        },

        onRequestStatusChanged(data) {
            const idx = this.locations.findIndex(l => l.technician_id === data.technician_id);
            if (idx < 0) return;
            const isFinal = data.status === 'completed' || data.status === 'canceled';
            if (isFinal) {
                this.locations[idx] = { ...this.locations[idx], active_request: null };
                if (this.focusedTechId === data.technician_id) {
                    this.selectedTechRequest = null;
                    this.requestPanelOpen = false;
                    this.clearRouteAndDest();
                }
            } else {
                const newReq = {
                    id: data.request_id,
                    status: data.status,
                    type: data.type,
                    invoice_number: data.invoice_number,
                    address: data.address,
                    customer_lat: data.customer_lat,
                    customer_lng: data.customer_lng,
                    scheduled_at: data.scheduled_at,
                    customer_name: data.customer_name,
                    customer_phone: data.customer_phone,
                };
                this.locations[idx] = { ...this.locations[idx], active_request: newReq };
                if (this.focusedTechId === data.technician_id) {
                    this.selectedTechRequest = { ...this.selectedTechRequest, ...newReq };
                }
            }
            this._tick++;
        },

        fitAllMarkers() {
            this.clearFocus();
            if (!this.map) return;
            const online = this.locations.filter(l => l.is_online && l.latitude);
            if (online.length === 0) return;
            if (online.length === 1) { this.map.flyTo([parseFloat(online[0].latitude), parseFloat(online[0].longitude)], 14); return; }
            this.map.flyToBounds(
                L.latLngBounds(online.map(l => [parseFloat(l.latitude), parseFloat(l.longitude)])),
                { padding: [60, 60], duration: 0.8 }
            );
        },

        // ── Helpers ───────────────────────────────────────────────────────────
        noGps(tech) {
            if (!tech.updated_at) return false;
            return (Date.now() - new Date(tech.updated_at).getTime()) / 60000 > 5;
        },

        relativeTime(iso) {
            void this._tick;
            if (!iso) return '—';
            const s = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
            if (s < 10)    return 'just now';
            if (s < 60)    return s + 's ago';
            if (s < 3600)  return Math.floor(s / 60) + ' min ago';
            if (s < 86400) return Math.floor(s / 3600) + 'h ago';
            return Math.floor(s / 86400) + 'd ago';
        },

        startTickTimer() {
            setInterval(() => {
                this._tick++;
                if (!this.map) return;
                this.locations.forEach(loc => {
                    if (!loc.is_online || !this.markers[loc.technician_id]) return;
                    this.markers[loc.technician_id].setIcon(this.buildIcon(loc.heading, this.noGps(loc)));
                });
            }, 60000);
        },

        startFallbackPoller() {
            window.addEventListener('locationsRefreshed', (e) => {
                (e.detail.locations ?? []).forEach(loc => this.onLocationUpdate(loc));
            });
            // Only poll when Reverb is not connected
            setInterval(() => {
                if (!this.wsConnected) this.$wire.refreshLocations();
            }, 30000);
        },
    };
}
</script>
@endpush
