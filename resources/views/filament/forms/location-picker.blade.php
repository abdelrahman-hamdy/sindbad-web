<div
    x-data="locationPicker()"
    x-init="init()"
    class="space-y-3"
>
    {{-- Method tabs --}}
    <div class="flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden text-sm">
        <button type="button"
            @click="activeTab = 'map'"
            :class="activeTab === 'map'
                ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 font-medium'
                : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800'"
            class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 transition"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            {{ __('Pick from Map') }}
        </button>
        <button type="button"
            @click="activeTab = 'link'"
            :class="activeTab === 'link'
                ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 font-medium'
                : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800'"
            class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 border-l border-gray-200 dark:border-gray-700 transition"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            {{ __('Google Maps Link') }}
        </button>
    </div>

    {{-- Map tab --}}
    <div x-show="activeTab === 'map'">
        <div wire:ignore>
            <div x-ref="mapEl" style="height: 300px; border-radius: 0.5rem; border: 1px solid #e5e7eb; overflow: hidden; position: relative; z-index: 0;"></div>
        </div>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5">{{ __('Click anywhere on the map to pin the location.') }}</p>
    </div>

    {{-- Link tab --}}
    <div x-show="activeTab === 'link'" class="space-y-2">
        <input
            type="url"
            x-model="mapsLink"
            @input.debounce.700ms="parseLink()"
            placeholder="https://www.google.com/maps?q=..."
            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition"
        />
        <p x-show="linkError" x-cloak class="text-xs text-red-500" x-text="linkError"></p>
        <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('Paste a Google Maps share link and the coordinates will be extracted automatically.') }}</p>
    </div>

    {{-- Geocoding spinner --}}
    <div x-show="geocoding" x-cloak class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        {{ __('Looking up address…') }}
    </div>

    {{-- Location confirmed indicator --}}
    <div x-show="isSet && !geocoding" x-cloak class="flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span x-text="coordsText"></span>
    </div>
</div>

@script
<script>
    Alpine.data('locationPicker', function () {
        return {
            activeTab: 'map',
            map: null,
            marker: null,
            mapsLink: '',
            linkError: '',
            isSet: false,
            geocoding: false,
            coordsText: '',

            async init() {
                await this.loadLeaflet();

                const el = this.$refs.mapEl;

                // Default center: Muscat, Oman
                let initLat = 23.5880, initLng = 58.3829, initZoom = 10;

                // In edit mode, read existing values from Livewire state
                const existingLat = parseFloat(await this.$wire.get('data.latitude'));
                const existingLng = parseFloat(await this.$wire.get('data.longitude'));
                if (!isNaN(existingLat) && !isNaN(existingLng) && existingLat !== 0) {
                    initLat = existingLat;
                    initLng = existingLng;
                    initZoom = 15;
                    this.isSet = true;
                    this.coordsText = @js(__('Pinned')) + ': ' + existingLat.toFixed(5) + ', ' + existingLng.toFixed(5);
                }

                this.map = L.map(el, { scrollWheelZoom: false }).setView([initLat, initLng], initZoom);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(this.map);

                if (this.isSet) {
                    this.marker = L.marker([initLat, initLng]).addTo(this.map);
                }

                this.map.on('click', (e) => {
                    this.setLocation(e.latlng.lat, e.latlng.lng);
                });
            },

            loadLeaflet() {
                return new Promise(resolve => {
                    if (window.L) return resolve();

                    const link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    document.head.appendChild(link);

                    const script = document.createElement('script');
                    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    script.onload = resolve;
                    document.head.appendChild(script);
                });
            },

            async setLocation(lat, lng) {
                if (this.marker) {
                    this.marker.setLatLng([lat, lng]);
                } else {
                    this.marker = L.marker([lat, lng]).addTo(this.map);
                }
                this.map.setView([lat, lng], 15);

                this.$wire.set('data.latitude', String(lat));
                this.$wire.set('data.longitude', String(lng));

                this.isSet = true;
                this.coordsText = @js(__('Pinned')) + ': ' + lat.toFixed(5) + ', ' + lng.toFixed(5);

                // Reverse geocode via Nominatim
                this.geocoding = true;
                try {
                    const res = await fetch(
                        `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`,
                        { headers: { 'Accept-Language': 'en' } }
                    );
                    const data = await res.json();
                    const address = data.display_name ?? `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
                    this.$wire.set('data.address', address);
                } catch {
                    this.$wire.set('data.address', `${lat.toFixed(5)}, ${lng.toFixed(5)}`);
                } finally {
                    this.geocoding = false;
                }
            },

            parseLink() {
                this.linkError = '';
                const url = this.mapsLink.trim();
                if (!url) return;

                let lat = null, lng = null;

                // ?q=LAT,LNG or &q=LAT,LNG
                let m = url.match(/[?&]q=(-?\d+\.?\d*),\s*(-?\d+\.?\d*)/);
                if (m) { lat = parseFloat(m[1]); lng = parseFloat(m[2]); }

                // /@LAT,LNG,zoom
                if (!lat) {
                    m = url.match(/@(-?\d+\.?\d*),\s*(-?\d+\.?\d*)/);
                    if (m) { lat = parseFloat(m[1]); lng = parseFloat(m[2]); }
                }

                // !3d LAT !4d LNG (embedded in place URLs)
                if (!lat) {
                    m = url.match(/!3d(-?\d+\.?\d*)!4d(-?\d+\.?\d*)/);
                    if (m) { lat = parseFloat(m[1]); lng = parseFloat(m[2]); }
                }

                if (!lat || !lng || isNaN(lat) || isNaN(lng)) {
                    this.linkError = @js(__('Could not extract coordinates. Try copying the share link directly from Google Maps.'));
                    return;
                }

                this.activeTab = 'map';
                this.$nextTick(() => this.setLocation(lat, lng));
            },
        };
    });
</script>
@endscript
