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
                <button
                    @click="focusTechnician(tech)"
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

                    <div class="flex flex-col items-center gap-1">
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                            :class="!tech.is_online
                                ? 'bg-gray-100 text-gray-500 dark:bg-gray-600 dark:text-gray-400'
                                : (noGps(tech)
                                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                                    : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400')"
                            :title="noGps(tech) ? 'Online but no GPS update in 5+ min — phone may be in background' : ''"
                            x-text="!tech.is_online ? @js(__('Offline')) : (noGps(tech) ? @js(__('No GPS signal')) : @js(__('Online')))"
                        ></span>
                        <template x-if="tech.is_online && tech.speed && tech.speed > 0">
                            <span class="text-xs text-blue-600 dark:text-blue-400 font-medium" x-text="Math.round(tech.speed) + ' km/h'"></span>
                        </template>
                        <span class="text-xs text-gray-400 dark:text-gray-500" x-text="relativeTime(tech.updated_at)"></span>
                    </div>
                </button>
            </template>

            <template x-if="filteredTechs.length === 0">
                <div class="flex-1 py-6 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('No technicians found') }}</div>
            </template>
        </div>
    </div>

    {{-- ─── ROW 3: Map ──────────────────────────────────────────────────────── --}}
    <div class="relative rounded-xl shadow overflow-hidden bg-gray-100 dark:bg-gray-900" style="height: 520px;">
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

    {{-- ─── ROW 4: Simulation panel ─────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2"/>
            </svg>
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Simulation Panel') }}</span>
            <span class="text-xs text-gray-400 dark:text-gray-500 ml-1">— test live tracking without a mobile device</span>
        </div>

        <div class="p-5 space-y-4">
            <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 px-4 py-3 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                <div>
                    <span class="text-blue-500 dark:text-blue-400 text-xs font-medium uppercase tracking-wide">{{ __('Technician') }}</span>
                    <p class="font-semibold text-gray-900 dark:text-white">Khalid Al Rashdi</p>
                    <p class="text-gray-500 dark:text-gray-400 text-xs">📞 96891111111</p>
                </div>
                <div>
                    <span class="text-blue-500 dark:text-blue-400 text-xs font-medium uppercase tracking-wide">{{ __('Active Invoice') }}</span>
                    <p class="font-semibold text-gray-900 dark:text-white">T-2026-004</p>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 font-medium text-xs">Assigned</span>
                </div>
                <div>
                    <span class="text-blue-500 dark:text-blue-400 text-xs font-medium uppercase tracking-wide">{{ __('Customer') }}</span>
                    <p class="font-semibold text-gray-900 dark:text-white">Mohammed Rashid</p>
                    <p class="text-gray-500 dark:text-gray-400 text-xs">📍 Al Khuwair, Muscat</p>
                </div>
                <div>
                    <span class="text-blue-500 dark:text-blue-400 text-xs font-medium uppercase tracking-wide">{{ __('Route') }}</span>
                    <p class="font-semibold text-gray-900 dark:text-white">Qurum → Al Khuwair</p>
                    <p class="text-gray-500 dark:text-gray-400 text-xs">8 waypoints · ~12 km</p>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Route progress') }}</span>
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                        Step <span x-text="simStep + 1"></span> / <span x-text="SIM_ROUTE.length"></span>
                        <template x-if="SIM_ROUTE[simStep]">
                            <span x-text="' — ' + Math.round(SIM_ROUTE[simStep].speed) + ' km/h · ' + Math.round(SIM_ROUTE[simStep].heading) + '°'"></span>
                        </template>
                    </span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full bg-primary-500 transition-all duration-500"
                        :style="'width: ' + (simStep / (SIM_ROUTE.length - 1) * 100) + '%'"
                    ></div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button @click="simGoOnline()" :disabled="simRunning"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition border"
                    :class="simRunning ? 'opacity-40 cursor-not-allowed bg-gray-100 dark:bg-gray-700 text-gray-400 border-gray-200 dark:border-gray-600' : 'bg-green-600 hover:bg-green-700 text-white border-green-600 shadow-sm'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728"/>
                    </svg>
                    {{ __('Go Online') }}
                </button>

                <button @click="simStepForward()" :disabled="simStep >= SIM_ROUTE.length - 1 || simRunning"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition border"
                    :class="(simStep >= SIM_ROUTE.length - 1 || simRunning) ? 'opacity-40 cursor-not-allowed bg-gray-100 dark:bg-gray-700 text-gray-400 border-gray-200 dark:border-gray-600' : 'bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-500 shadow-sm'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    {{ __('Step Forward') }}
                </button>

                <button x-show="!simRunning" @click="simStartAuto()" :disabled="simStep >= SIM_ROUTE.length - 1"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition border"
                    :class="simStep >= SIM_ROUTE.length - 1 ? 'opacity-40 cursor-not-allowed bg-gray-100 dark:bg-gray-700 text-gray-400 border-gray-200 dark:border-gray-600' : 'bg-primary-600 hover:bg-primary-700 text-white border-primary-600 shadow-sm'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    {{ __('Auto Drive (3s)') }}
                </button>

                <button x-show="simRunning" @click="simPause()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition border bg-amber-500 hover:bg-amber-600 text-white border-amber-500 shadow-sm"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                    {{ __('Pause') }}
                </button>

                <button @click="simReset()" :disabled="simRunning"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition border"
                    :class="simRunning ? 'opacity-40 cursor-not-allowed bg-gray-100 dark:bg-gray-700 text-gray-400 border-gray-200 dark:border-gray-600' : 'bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-500 shadow-sm'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    {{ __('Reset') }}
                </button>

                <button @click="simGoOffline()" :disabled="simRunning"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition border ml-auto"
                    :class="simRunning ? 'opacity-40 cursor-not-allowed bg-gray-100 dark:bg-gray-700 text-gray-400 border-gray-200 dark:border-gray-600' : 'bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800 shadow-sm'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728M6.343 6.343a9 9 0 000 12.728M12 12h.01"/>
                    </svg>
                    {{ __('Go Offline') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .leaflet-popup-content { margin: 0 !important; }
    .leaflet-popup-content-wrapper { padding: 0 !important; border-radius: 10px !important; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.15) !important; }
    .leaflet-popup-tip-container { margin-top: -1px; }
    .leaflet-top.leaflet-left { top: 60px; }
</style>
@endpush

@push('scripts')
<script>
const SIM_TECH_ID  = 3;
const CUSTOMER_DEST = { lat: 23.5880, lng: 58.3829 };

const SIM_ROUTE_DATA = [
    { lat: 23.6030, lng: 58.5115, heading: 242, speed: 55 },
    { lat: 23.5985, lng: 58.4935, heading: 245, speed: 62 },
    { lat: 23.5945, lng: 58.4738, heading: 248, speed: 58 },
    { lat: 23.5920, lng: 58.4530, heading: 252, speed: 52 },
    { lat: 23.5905, lng: 58.4310, heading: 255, speed: 47 },
    { lat: 23.5893, lng: 58.4105, heading: 260, speed: 40 },
    { lat: 23.5885, lng: 58.3965, heading: 265, speed: 32 },
    { lat: 23.5880, lng: 58.3829, heading: 270, speed: 0  },
];

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

        // ── Simulation state ─────────────────────────────────────────────────
        SIM_ROUTE:   SIM_ROUTE_DATA,
        simStep:     0,
        simRunning:  false,
        simInterval: null,

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
            this.listenSimulationEvents();
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
            this.map = L.map('sindbad-live-map', { center: [23.5880, 58.3829], zoom: 12, zoomControl: true });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(this.map);
            this.addDestMarker();
            this.locations.forEach(loc => this.syncMarker(loc));
            this.connectEcho();
        },

        addDestMarker() {
            const html = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 32" width="28" height="36">
                <path d="M12 0C5.373 0 0 5.373 0 12c0 9 12 20 12 20S24 21 24 12C24 5.373 18.627 0 12 0z" fill="#ef4444"/>
                <circle cx="12" cy="12" r="5" fill="white"/>
            </svg>`;
            const icon = L.divIcon({ html, className: '', iconSize: [28, 36], iconAnchor: [14, 36], popupAnchor: [0, -38] });
            this.destMarker = L.marker([CUSTOMER_DEST.lat, CUSTOMER_DEST.lng], { icon })
                .addTo(this.map)
                .bindPopup(`<div style="font-family:system-ui,sans-serif;padding:10px 12px;min-width:180px;">
                    <div style="font-weight:700;color:#dc2626;margin-bottom:4px;">📍 {{ __('Customer Destination') }}</div>
                    <div style="font-size:13px;color:#444;">Mohammed Rashid · Al Khuwair</div>
                    <div style="font-size:12px;color:#888;margin-top:2px;">Invoice T-2026-004 · Assigned</div>
                </div>`);
        },

        // ── Echo WebSocket ────────────────────────────────────────────────────
        connectEcho() {
            if (typeof window.Echo === 'undefined') {
                window.addEventListener('EchoLoaded', () => this.connectEcho());
                return;
            }
            window.Echo.channel('technician-locations')
                .listen('.TechnicianLocationUpdated', (data) => this.onLocationUpdate(data));

            const conn = window.Echo.connector?.pusher?.connection;
            if (conn) {
                conn.bind('connected',    () => { this.wsConnected = true; });
                conn.bind('disconnected', () => { this.wsConnected = false; });
                conn.bind('unavailable',  () => { this.wsConnected = false; });
                if (conn.state === 'connected') this.wsConnected = true;
            } else {
                this.wsConnected = true; // assume connected if no state to check
            }
        },

        // ── Simulation events from Livewire dispatch ──────────────────────────
        listenSimulationEvents() {
            window.addEventListener('techLocationUpdate', (e) => {
                this.onLocationUpdate(e.detail.location);
            });
        },

        // ── Location update handler (Echo + simulation + polling) ─────────────
        onLocationUpdate(data) {
            const idx = this.locations.findIndex(l => l.technician_id === data.technician_id);
            if (idx >= 0) {
                this.locations[idx] = { ...this.locations[idx], ...data };
            } else {
                this.locations.push(data);
            }
            this.syncMarker(data);

            // If this is the focused technician, redraw the route live
            if (this.focusedTechId === data.technician_id) {
                if (data.is_online && data.latitude) {
                    this.drawRoute(parseFloat(data.latitude), parseFloat(data.longitude));
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
            const updated  = data.updated_at ? this.relativeTime(data.updated_at) : 'N/A';
            const status   = data.is_online ? '<span style="color:#22c55e;">●</span> Online' : '<span style="color:#9ca3af;">●</span> Offline';
            const speed    = data.speed > 0 ? `<div>⚡ <strong>${Math.round(data.speed)} km/h</strong></div>` : '';
            const hdg      = data.heading != null ? `<div>↗ ${Math.round(data.heading)}° heading</div>` : '';
            // Directions from technician's current position to customer destination
            const mapsUrl = `https://www.google.com/maps/dir/?api=1`
                + `&origin=${parseFloat(data.latitude).toFixed(6)},${parseFloat(data.longitude).toFixed(6)}`
                + `&destination=${CUSTOMER_DEST.lat},${CUSTOMER_DEST.lng}`
                + `&travelmode=driving`;

            return `<div style="font-family:system-ui,sans-serif;padding:11px 13px;min-width:210px;line-height:1.65;">
                <div style="font-size:14px;font-weight:700;color:#111;margin-bottom:4px;">${data.name || 'Unknown'}</div>
                <div style="font-size:12px;color:#555;">📞 ${data.phone || '—'}</div>
                <div style="font-size:12px;color:#555;margin-top:3px;">${status} · ${updated}</div>
                <div style="font-size:12px;color:#666;margin-top:3px;">${hdg}${speed}</div>
                <div style="margin-top:3px;font-size:11px;color:#999;">📍 ${parseFloat(data.latitude).toFixed(5)}, ${parseFloat(data.longitude).toFixed(5)}</div>
                <a href="${mapsUrl}" target="_blank"
                   style="display:inline-block;margin-top:8px;font-size:12px;color:#1a73e8;text-decoration:none;font-weight:500;">
                    Directions to customer ↗
                </a>
            </div>`;
        },

        // ── Card click: focus technician + draw route ─────────────────────────
        async focusTechnician(tech) {
            if (!this.map) return;

            // Toggle off if already focused
            if (this.focusedTechId === tech.technician_id) {
                this.clearFocus();
                return;
            }

            this.focusedTechId = tech.technician_id;

            if (!tech.is_online || !tech.latitude) return;

            const techPos = [parseFloat(tech.latitude), parseFloat(tech.longitude)];

            // Step 1: zoom into the technician immediately
            this.map.flyTo(techPos, 15, { duration: 0.8 });
            this.markers[tech.technician_id]?.openPopup();

            // Step 2: draw route (will refit once the route shape is known)
            await this.drawRoute(techPos[0], techPos[1]);
        },

        async drawRoute(fromLat, fromLng) {
            // Clear previous route
            if (this.routePolyline) {
                this.routePolyline.remove();
                this.routePolyline = null;
            }

            const techPos = [fromLat, fromLng];
            const destPos = [CUSTOMER_DEST.lat, CUSTOMER_DEST.lng];

            // Fetch OSRM driving route
            this.routeLoading = true;
            try {
                const pts = await this.fetchOSRMRoute(fromLat, fromLng, CUSTOMER_DEST.lat, CUSTOMER_DEST.lng);
                if (pts) {
                    this.routePolyline = L.polyline(pts, {
                        color: '#3b82f6', weight: 4, opacity: 0.75,
                    }).addTo(this.map);
                    // Re-fit to the actual route shape
                    this.map.flyToBounds(this.routePolyline.getBounds(), { padding: [80, 80], duration: 0.6 });
                }
            } catch (_) { /* route unavailable, map already focused */ }
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
            if (this.routePolyline) { this.routePolyline.remove(); this.routePolyline = null; }
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

        // ── Simulation controls ───────────────────────────────────────────────
        simGoOnline() {
            this.simStep = 0;
            const wp = this.SIM_ROUTE[0];
            this.$wire.simulateStep(SIM_TECH_ID, wp.lat, wp.lng, wp.heading, wp.speed);
            setTimeout(() => this.map?.flyTo([wp.lat, wp.lng], 12, { duration: 1 }), 400);
        },

        simStepForward() {
            if (this.simStep >= this.SIM_ROUTE.length - 1) return;
            this.simStep++;
            const wp = this.SIM_ROUTE[this.simStep];
            this.$wire.simulateStep(SIM_TECH_ID, wp.lat, wp.lng, wp.heading, wp.speed);
        },

        simStartAuto() {
            if (this.simStep >= this.SIM_ROUTE.length - 1) return;
            this.simRunning = true;
            this.simInterval = setInterval(() => {
                if (this.simStep >= this.SIM_ROUTE.length - 1) { this.simPause(); return; }
                this.simStep++;
                const wp = this.SIM_ROUTE[this.simStep];
                this.$wire.simulateStep(SIM_TECH_ID, wp.lat, wp.lng, wp.heading, wp.speed);
            }, 3000);
        },

        simPause()  { this.simRunning = false; clearInterval(this.simInterval); this.simInterval = null; },
        simReset()  { this.simPause(); this.simStep = 0; },
        simGoOffline() { this.simPause(); this.simStep = 0; this.$wire.simulateOffline(SIM_TECH_ID); },

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
