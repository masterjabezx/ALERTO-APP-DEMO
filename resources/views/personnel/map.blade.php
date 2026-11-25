<x-layouts.personnel>
    <div 
        x-data="personnelLiveMap()" 
        x-init="initMap()"
        class="space-y-6"
    >
        {{-- Top Bar & Title --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#173957]/10 text-[#173957] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="map" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                    Tactical GIS · Hinoba-an
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                    Live Tactical Incident Map
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Spatial visualization of emergency incident scenes and your active responder GPS telemetry.
                </p>
            </div>

            {{-- Live GPS Telemetry Broadcast Toggle --}}
            <div class="flex items-center gap-2">
                <button 
                    type="button" 
                    x-on:click="toggleLocationSharing()"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-2"
                    :class="isTracking ? 'bg-rose-600 text-white hover:bg-rose-700' : 'bg-emerald-600 text-white hover:bg-emerald-700'"
                >
                    <span class="w-2 h-2 rounded-full" :class="isTracking ? 'bg-white animate-ping' : 'bg-white/70'"></span>
                    <span x-text="isTracking ? 'Stop Live GPS Tracking' : 'Broadcast Duty GPS Location'"></span>
                </button>
            </div>
        </div>

        {{-- Active Assignment Route Banner --}}
        <template x-if="activeRoute">
            <div class="p-4 rounded-2xl bg-[#173957] text-white shadow-lg border border-white/10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold uppercase">Active Navigation Target</span>
                        <span class="font-mono text-xs font-bold text-[#3187D8]" x-text="activeRoute.reference_number"></span>
                    </div>
                    <div class="text-xs text-slate-300">
                        Distance: <strong class="text-white" x-text="activeRoute.distance_km + ' km'"></strong> · 
                        Est. Travel Time: <strong class="text-white" x-text="activeRoute.duration_minutes + ' mins'"></strong>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a 
                        :href="activeRoute.google_maps_url"
                        target="_blank"
                        class="px-4 py-2 rounded-xl bg-[#3187D8] hover:bg-[#286eb3] text-white text-xs font-bold transition-all shadow-md flex items-center gap-2"
                    >
                        <i data-lucide="navigation" class="w-4 h-4"></i>
                        <span>Start Turn-by-Turn GPS Navigation</span>
                    </a>
                </div>
            </div>
        </template>

        {{-- Map Canvas & Scene Grid --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-bold text-slate-800">Hinoba-an Tactical Scene Grid</span>
                </div>
                <span class="text-xs text-slate-500 font-mono">
                    <span x-text="incidents.length">0</span> authorized scene(s)
                </span>
            </div>

            <div class="p-6 space-y-6">
                {{-- Incident Scene Cards Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <template x-for="inc in incidents" :key="inc.id">
                        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/70 hover:border-[#3187D8] transition-all space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-xs font-bold text-slate-900" x-text="inc.reference_number"></span>
                                <span 
                                    class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                    :style="'background-color:' + inc.marker.color + '20; color:' + inc.marker.color"
                                    x-text="inc.marker.label"
                                ></span>
                            </div>
                            <div class="text-xs font-bold text-slate-800" x-text="inc.category"></div>
                            <div class="text-[11px] text-slate-600 font-medium" x-text="inc.barangay"></div>
                            <div class="text-[11px] text-slate-500 font-mono">
                                Lat: <span x-text="inc.latitude.toFixed(4)"></span>, Lng: <span x-text="inc.longitude.toFixed(4)"></span>
                            </div>
                            <div class="pt-2 border-t border-slate-200 flex items-center justify-between">
                                <a 
                                    :href="'https://www.google.com/maps/dir/?api=1&destination=' + inc.latitude + ',' + inc.longitude" 
                                    target="_blank" 
                                    class="text-[11px] font-bold text-[#3187D8] hover:underline inline-flex items-center gap-1"
                                >
                                    <span>Navigate to Scene</span>
                                    <i data-lucide="external-link" class="w-3 h-3"></i>
                                </a>
                            </div>
                        </div>
                    </template>

                    <template x-if="incidents.length === 0">
                        <div class="col-span-full py-8 text-center text-slate-400 text-xs">
                            No active emergency incident scenes in your authorized jurisdiction.
                        </div>
                    </template>
                </div>

                {{-- Last Reported Operative Location --}}
                <div class="p-4 rounded-xl bg-blue-50/50 border border-blue-200 text-xs flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i data-lucide="navigation" class="w-5 h-5 text-blue-600 shrink-0"></i>
                        <div>
                            <span class="font-bold text-blue-900">Your Duty Telemetry Coordinates:</span>
                            <span class="font-mono text-blue-800 ml-1" x-text="currentCoords ? currentCoords.lat.toFixed(6) + ', ' + currentCoords.lng.toFixed(6) + ' (±' + currentCoords.accuracy + 'm)' : 'Click Broadcast Duty GPS to activate live telemetry.'"></span>
                        </div>
                    </div>
                </div>
                {{-- Scoped Jurisdiction Evidence --}}
                <template x-if="evidence && evidence.length > 0">
                    <div class="pt-4 border-t border-slate-100 space-y-2">
                        <span class="font-bold text-slate-800 text-xs flex items-center gap-2">
                            <i data-lucide="camera" class="w-4 h-4 text-[#3187D8]"></i>
                            Authorized Scene Geo-Tagged Evidence
                        </span>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                            <template x-for="ev in evidence" :key="ev.id">
                                <div class="p-3 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 truncate" x-text="ev.file_name"></div>
                                        <div class="font-mono text-[10px] text-slate-500" x-text="(ev.incident_reference || '') + ' · ' + ev.location_source"></div>
                                    </div>
                                    <a :href="ev.stream_url" target="_blank" class="px-2.5 py-1 rounded-lg bg-[#173957] text-white font-bold text-[10px] shrink-0">View</a>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        function personnelLiveMap() {
            return {
                incidents: [],
                evidence: [],
                activeRoute: null,
                isTracking: false,
                watchId: null,
                currentCoords: null,

                initMap() {
                    this.fetchMapData();
                    setInterval(() => this.fetchMapData(), 20000);
                },

                async fetchMapData() {
                    try {
                        const res = await fetch('{{ route('personnel.map.data') }}', {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (res.ok) {
                            const data = await res.json();
                            this.incidents = data.incidents || [];
                            this.evidence = data.evidence || [];
                            if (data.routes && data.routes.length > 0) {
                                this.activeRoute = data.routes[0];
                            }
                            if (window.lucide) this.$nextTick(() => window.lucide.createIcons());
                        }
                    } catch (e) {
                        console.error('Personnel map error:', e);
                    }
                },

                toggleLocationSharing() {
                    if (this.isTracking) {
                        this.stopTracking();
                    } else {
                        this.startTracking();
                    }
                },

                startTracking() {
                    if (!navigator.geolocation) {
                        alert('Geolocation is not supported by your browser.');
                        return;
                    }

                    this.isTracking = true;
                    this.watchId = navigator.geolocation.watchPosition(
                        (position) => {
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            const accuracy = position.coords.accuracy;

                            this.currentCoords = { lat, lng, accuracy };

                            fetch('{{ route('personnel.location') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    latitude: lat,
                                    longitude: lng,
                                    accuracy: accuracy,
                                    speed: position.coords.speed || 0,
                                    heading: position.coords.heading || 0,
                                })
                            });
                        },
                        (error) => {
                            console.warn('Geolocation watch error:', error);
                            this.stopTracking();
                            alert('Location permission was denied or is unavailable.');
                        },
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                    );
                },

                stopTracking() {
                    if (this.watchId !== null) {
                        navigator.geolocation.clearWatch(this.watchId);
                        this.watchId = null;
                    }
                    this.isTracking = false;
                }
            };
        }
    </script>
</x-layouts.personnel>
