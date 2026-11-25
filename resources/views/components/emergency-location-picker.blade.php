@props([
    'defaultLat' => 9.58970000,
    'defaultLng' => 122.46750000,
    'latName' => 'latitude',
    'lngName' => 'longitude',
    'accuracyName' => 'accuracy',
    'sourceName' => 'location_source',
    'gpsOnly' => false,
])

@once
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
@endonce

@php
    $initialLat = old($latName, $gpsOnly ? '' : number_format((float) $defaultLat, 8, '.', ''));
    $initialLng = old($lngName, $gpsOnly ? '' : number_format((float) $defaultLng, 8, '.', ''));
@endphp

<div x-data="{
        gpsOnly: @js((bool) $gpsOnly),
        centerLat: {{ (float) $defaultLat }},
        centerLng: {{ (float) $defaultLng }},
        lat: @js((string) $initialLat),
        lng: @js((string) $initialLng),
        accuracy: @js((string) old($accuracyName, '')),
        locationSource: @js((string) old($sourceName, 'gps_device')),
        gpsStatusText: @js($gpsOnly ? 'Acquiring your live GPS location... Map is display-only.' : 'Detecting your GPS location...'),
        gpsLocked: false,
        gpsError: false,
        detecting: false,
        map: null,
        marker: null,
        accuracyCircle: null,

        init() {
            this.$nextTick(() => {
                this.initLeafletMap();
                this.detectGps();
            });
        },

        initLeafletMap() {
            const container = this.$refs.mapContainer;
            if (!container || typeof L === 'undefined') {
                this.gpsStatusText = 'Map preview offline — GPS coordinates will still be recorded automatically.';
                return;
            }
            const startLat = parseFloat(this.lat) || this.centerLat || 9.5897;
            const startLng = parseFloat(this.lng) || this.centerLng || 122.4675;

            this.map = L.map(container, {
                dragging: !this.gpsOnly,
                touchZoom: !this.gpsOnly,
                scrollWheelZoom: !this.gpsOnly,
                doubleClickZoom: !this.gpsOnly,
                boxZoom: !this.gpsOnly,
                keyboard: !this.gpsOnly,
                zoomControl: !this.gpsOnly,
                tap: !this.gpsOnly,
            }).setView([startLat, startLng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(this.map);

            this.marker = L.marker([startLat, startLng], { draggable: !this.gpsOnly }).addTo(this.map);

            if (!this.gpsOnly) {
                this.marker.on('dragend', (e) => {
                    const pos = e.target.getLatLng();
                    this.setManualPin(pos.lat, pos.lng);
                });
                this.map.on('click', (e) => {
                    this.setManualPin(e.latlng.lat, e.latlng.lng);
                });
            }
        },

        setManualPin(newLat, newLng) {
            if (this.gpsOnly) {
                return;
            }
            this.lat = Number(newLat).toFixed(8);
            this.lng = Number(newLng).toFixed(8);
            this.locationSource = 'map_picker';
            this.accuracy = '';
            this.gpsLocked = false;
            this.gpsError = false;
            this.gpsStatusText = 'Manual Map Pin set (' + this.lat + ', ' + this.lng + ')';
            if (this.marker) this.marker.setLatLng([newLat, newLng]);
            this.broadcastGps();
        },

        updateAccuracyCircle(lt, lg, acc) {
            if (!this.map || typeof L === 'undefined') {
                return;
            }
            if (this.accuracyCircle) {
                this.map.removeLayer(this.accuracyCircle);
                this.accuracyCircle = null;
            }
            if (acc && acc > 0) {
                this.accuracyCircle = L.circle([lt, lg], {
                    radius: acc,
                    color: '#10b981',
                    fillColor: '#10b981',
                    fillOpacity: 0.14,
                    weight: 1.5,
                }).addTo(this.map);
            }
        },

        broadcastGps() {
            window.dispatchEvent(new CustomEvent('alerto:gps-updated', {
                detail: {
                    latitude: this.lat,
                    longitude: this.lng,
                    accuracy: this.accuracy,
                    locationSource: this.locationSource,
                    locked: this.gpsLocked,
                }
            }));
        },

        detectGps() {
            if (!navigator.geolocation) {
                this.gpsLocked = false;
                this.gpsError = true;
                if (this.gpsOnly) {
                    this.locationSource = 'gps_device';
                    this.gpsStatusText = 'Browser GPS unavailable. Please use a GPS-enabled browser/device and grant location access.';
                } else {
                    this.locationSource = 'map_picker';
                    this.gpsStatusText = 'Browser GPS unavailable. Drag the map pin to your exact location.';
                }
                return;
            }

            this.detecting = true;
            this.gpsError = false;
            this.gpsStatusText = 'Acquiring high-accuracy GPS signal...';

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.detecting = false;
                    const lt = pos.coords.latitude;
                    const lg = pos.coords.longitude;
                    this.lat = lt.toFixed(8);
                    this.lng = lg.toFixed(8);
                    this.accuracy = pos.coords.accuracy ? String(Math.round(pos.coords.accuracy)) : '';
                    this.locationSource = 'gps_device';
                    this.gpsLocked = true;
                    this.gpsError = false;
                    this.gpsStatusText = this.gpsOnly
                        ? 'GPS Locked (±' + (this.accuracy || '?') + 'm accuracy). Map is display-only.'
                        : 'GPS Locked (±' + (this.accuracy || '?') + 'm accuracy). Drag pin if incident is nearby.';

                    if (this.marker && this.map) {
                        this.marker.setLatLng([lt, lg]);
                        this.map.setView([lt, lg], 16);
                        this.updateAccuracyCircle(lt, lg, Number(this.accuracy) || 0);
                    }
                    this.broadcastGps();
                },
                () => {
                    this.detecting = false;
                    this.gpsLocked = false;
                    this.gpsError = true;
                    if (this.gpsOnly) {
                        this.locationSource = 'gps_device';
                        this.gpsStatusText = 'GPS signal blocked or unavailable. Allow Location access and tap Re-Detect My GPS.';
                    } else {
                        this.locationSource = 'map_picker';
                        this.gpsStatusText = 'Could not lock GPS automatically. Click or drag the pin on the Hinoba-an map.';
                    }
                    this.broadcastGps();
                },
                { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
            );
        }
     }"
     class="space-y-3">

    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-[#F5F3EF]">Exact Incident Location *</span>
                @if($gpsOnly)
                    <span class="px-2 py-0.5 rounded bg-[#E87524]/20 border border-[#E87524]/40 text-[#F29A56] text-[10px] font-bold uppercase tracking-wider">
                        Display-Only GPS Map
                    </span>
                @endif
            </div>
            <p class="text-[11px] mt-0.5" :class="gpsError ? 'text-[#F6BA53] font-semibold' : 'text-[#A39E98]'" x-text="gpsStatusText"></p>
        </div>
        <button type="button" @click="detectGps()" :disabled="detecting"
                class="px-3.5 py-2 rounded-lg bg-[#27845B] hover:bg-[#1E6A49] disabled:opacity-60 text-white text-xs font-bold transition-colors cursor-pointer">
            <span x-text="detecting ? 'Detecting GPS...' : (gpsLocked ? 'Re-Detect My GPS' : 'Detect My GPS')"></span>
        </button>
    </div>

    <div class="relative rounded-2xl border border-white/15 overflow-hidden bg-[#101214]">
        <div x-ref="mapContainer"
             class="w-full h-64 z-0 {{ $gpsOnly ? 'pointer-events-none select-none' : '' }}"></div>
        @if($gpsOnly)
            <div class="absolute inset-0 z-10 cursor-default" aria-hidden="true" title="Map is display-only. Use Re-Detect My GPS to update your GPS position."></div>
            <div class="absolute bottom-2 left-2 z-20 px-2.5 py-1 rounded-lg bg-[#101214]/90 border border-white/15 text-[10px] font-semibold text-[#F5F3EF] pointer-events-none">
                Map view only — GPS auto-detected from your device
            </div>
        @endif
    </div>

    @if($gpsOnly)
        <div x-show="gpsError" style="display:none;"
             class="p-3 rounded-xl bg-[#D99024]/15 border border-[#D99024]/40 text-[#F6BA53] text-xs font-semibold">
            Live device GPS is required to verify your exact location. Enable Location/GPS in your browser settings and click <strong>Re-Detect My GPS</strong>.
        </div>
    @endif

    <input type="hidden" name="{{ $accuracyName }}" x-model="accuracy">
    <input type="hidden" name="{{ $sourceName }}" x-model="locationSource">

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
            <label class="block text-[11px] font-medium text-[#A39E98] mb-1">Latitude *</label>
            <input type="number" step="0.00000001" name="{{ $latName }}" x-model="lat" required
                   @if($gpsOnly) readonly tabindex="-1" placeholder="Waiting for GPS..." @endif
                   class="w-full px-3 py-2 rounded-xl bg-[#101214] border border-white/15 text-white text-xs font-mono-tactical {{ $gpsOnly ? 'opacity-80 cursor-not-allowed' : '' }}">
        </div>
        <div>
            <label class="block text-[11px] font-medium text-[#A39E98] mb-1">Longitude *</label>
            <input type="number" step="0.00000001" name="{{ $lngName }}" x-model="lng" required
                   @if($gpsOnly) readonly tabindex="-1" placeholder="Waiting for GPS..." @endif
                   class="w-full px-3 py-2 rounded-xl bg-[#101214] border border-white/15 text-white text-xs font-mono-tactical {{ $gpsOnly ? 'opacity-80 cursor-not-allowed' : '' }}">
        </div>
        <div>
            <label class="block text-[11px] font-medium text-[#A39E98] mb-1">Location Source</label>
            <div class="px-3 py-2 rounded-xl bg-[#101214] border border-white/10 text-xs font-semibold"
                 :class="gpsLocked ? 'text-[#5BD19A]' : 'text-[#F6BA53]'"
                 x-text="gpsLocked ? ('Device GPS (±' + (accuracy || '?') + 'm)') : (gpsOnly ? 'Waiting for Device GPS...' : 'Interactive Map Pin')"></div>
        </div>
    </div>
</div>
