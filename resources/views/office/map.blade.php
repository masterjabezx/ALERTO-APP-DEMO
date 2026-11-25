<x-layouts.office>
    <div 
        x-data="officeLiveMap()" 
        x-init="initMap()"
        class="space-y-6"
    >
        {{-- Top Bar & Title --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#173957]/10 text-[#173957] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="map" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                    Tactical Operations GIS · {{ $office->code }}
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                    Agency Incident Map
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Real-time spatial visualization of active emergency incidents assigned to {{ $office->name }}, assigned response teams, and field unit locations.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button 
                    type="button" 
                    x-on:click="fetchMapData()"
                    class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold transition-all shadow-xs flex items-center gap-2"
                >
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-[#3187D8]" :class="loading ? 'animate-spin' : ''"></i>
                    <span>Refresh Map</span>
                </button>
            </div>
        </div>

        {{-- Map Card --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-bold text-slate-800">Tactical Emergency Scene Canvas</span>
                </div>
                <div class="flex items-center gap-4 text-xs font-mono text-slate-600">
                    <span>Incidents: <strong class="text-slate-900" x-text="incidents.length">0</strong></span>
                    <span>Responders: <strong class="text-emerald-600" x-text="responders.length">0</strong></span>
                </div>
            </div>

            <div class="p-6 space-y-6">
                {{-- Incident Scene Cards Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
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
                            <div class="text-[11px] text-slate-500" x-text="inc.barangay"></div>
                            <div class="text-[11px] font-mono text-slate-400">
                                Lat: <span x-text="inc.latitude.toFixed(4)"></span>, Lng: <span x-text="inc.longitude.toFixed(4)"></span>
                            </div>
                            <div class="pt-2 border-t border-slate-200 flex items-center justify-between">
                                <a 
                                    :href="'https://www.google.com/maps/dir/?api=1&destination=' + inc.latitude + ',' + inc.longitude" 
                                    target="_blank"
                                    class="text-[11px] font-bold text-[#3187D8] hover:underline flex items-center gap-1"
                                >
                                    <i data-lucide="navigation" class="w-3 h-3"></i>
                                    <span>Directions</span>
                                </a>
                                <a 
                                    :href="'/office/incidents/' + inc.reference_number" 
                                    class="text-[11px] font-bold text-slate-700 hover:text-slate-900"
                                >
                                    Details &rarr;
                                </a>
                            </div>
                        </div>
                    </template>

                    <template x-if="incidents.length === 0">
                        <div class="col-span-full py-12 text-center text-slate-400 text-xs">
                            No active emergency incidents currently assigned to {{ $office->code }}.
                        </div>
                    </template>
                </div>

                {{-- Staging Bases & Personnel Presence Summary --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-slate-100">
                    <div class="p-4 rounded-xl bg-blue-50/50 border border-blue-100 text-xs space-y-2">
                        <span class="font-bold text-blue-900 flex items-center gap-2">
                            <i data-lucide="building-2" class="w-4 h-4 text-[#3187D8]"></i>
                            Agency Staging Bases
                        </span>
                        <template x-for="base in bases" :key="base.id">
                            <div class="p-2.5 rounded-lg bg-white border border-blue-100 flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-slate-800" x-text="base.name"></div>
                                    <div class="text-[10px] text-slate-500" x-text="base.address"></div>
                                </div>
                                <span class="font-mono text-[10px] text-blue-700 font-bold" x-text="base.latitude.toFixed(4) + ', ' + base.longitude.toFixed(4)"></span>
                            </div>
                        </template>
                    </div>

                    <div class="p-4 rounded-xl bg-emerald-50/50 border border-emerald-100 text-xs space-y-2">
                        <span class="font-bold text-emerald-900 flex items-center gap-2">
                            <i data-lucide="users" class="w-4 h-4 text-emerald-600"></i>
                            Field Responder Telemetry
                        </span>
                        <template x-for="resp in responders" :key="resp.id">
                            <div class="p-2.5 rounded-lg bg-white border border-emerald-100 flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-slate-800" x-text="resp.name + ' (' + resp.badge + ')'"></div>
                                    <div class="text-[10px] text-slate-500" x-text="resp.team || 'Solo Unit'"></div>
                                </div>
                                <span 
                                    class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                    :class="resp.freshness === 'live' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                    x-text="resp.freshness"
                                ></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Scoped Geo-Tagged Evidence Panel --}}
                <template x-if="evidence && evidence.length > 0">
                    <div class="pt-4 border-t border-slate-100 space-y-2">
                        <span class="font-bold text-slate-800 text-xs flex items-center gap-2">
                            <i data-lucide="camera" class="w-4 h-4 text-[#3187D8]"></i>
                            Scoped Agency Geo-Tagged Evidence
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
        function officeLiveMap() {
            return {
                loading: false,
                incidents: [],
                responders: [],
                bases: [],
                evidence: [],

                initMap() {
                    this.fetchMapData();
                    setInterval(() => this.fetchMapData(), 20000);
                },

                async fetchMapData() {
                    this.loading = true;
                    try {
                        const res = await fetch('{{ route('office.map.data') }}', {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (res.ok) {
                            const data = await res.json();
                            this.incidents = data.incidents || [];
                            this.responders = data.responders || [];
                            this.bases = data.response_bases || [];
                            this.evidence = data.evidence || [];
                            if (window.lucide) this.$nextTick(() => window.lucide.createIcons());
                        }
                    } catch (e) {
                        console.error('Office map fetch error:', e);
                    } finally {
                        this.loading = false;
                    }
                }
            };
        }
    </script>
</x-layouts.office>
