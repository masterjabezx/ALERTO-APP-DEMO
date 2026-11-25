<x-layouts.office :title="'Office Incident Map — ' . $office->code">
    <div
        x-data="officeLiveMap()"
        x-init="initMap()"
        class="space-y-6"
    >
        {{-- Top Bar & Title --}}
        <x-portal.page-header
            :badge="'Tactical Operations GIS · ' . $office->code"
            title="Agency Incident Map"
            :subtitle="'Real-time spatial visualization of active emergency incidents assigned to ' . $office->name . ', assigned response teams, and field unit locations.'"
        >
            <x-slot:actions>
                <button
                    type="button"
                    x-on:click="fetchMapData()"
                    class="alerto-btn-press px-3.5 py-2 rounded-xl bg-white dark:bg-[#292D32] border border-[#E6E3DE] dark:border-white/15 hover:bg-[#F5F3EF] dark:hover:bg-white/10 text-xs font-bold transition-all shadow-xs flex items-center gap-2"
                >
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-[#E87524]" :class="loading ? 'animate-spin' : ''"></i>
                    <span>Refresh Map</span>
                </button>
            </x-slot:actions>
        </x-portal.page-header>

        {{-- Map Card --}}
        <div class="alerto-card overflow-hidden">
            <div class="p-4 bg-[#F5F3EF] dark:bg-white/5 border-b border-[#E6E3DE] dark:border-white/10 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#27845B] animate-pulse"></span>
                    <span class="text-xs font-bold">Tactical Emergency Scene Canvas</span>
                </div>
                <div class="flex items-center gap-4 text-xs font-mono text-[#77736E]">
                    <span>Incidents: <strong class="text-[#E87524]" x-text="incidents.length">0</strong></span>
                    <span>Responders: <strong class="text-[#27845B]" x-text="responders.length">0</strong></span>
                </div>
            </div>

            <div class="p-6 space-y-6">
                {{-- Incident Scene Cards Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="inc in incidents" :key="inc.id">
                        <div class="p-4 rounded-xl border border-[#E6E3DE] dark:border-white/10 bg-[#F5F3EF]/60 dark:bg-white/5 hover:border-[#E87524] transition-all space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-xs font-bold" x-text="inc.reference_number"></span>
                                <span
                                    class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                    :style="'background-color:' + inc.marker.color + '20; color:' + inc.marker.color"
                                    x-text="inc.marker.label"
                                ></span>
                            </div>
                            <div class="text-xs font-bold" x-text="inc.category"></div>
                            <div class="text-[11px] text-[#77736E]" x-text="inc.barangay"></div>
                            <div class="text-[11px] font-mono text-[#77736E]">
                                Lat: <span x-text="inc.latitude.toFixed(4)"></span>, Lng: <span x-text="inc.longitude.toFixed(4)"></span>
                            </div>
                            <div class="pt-2 border-t border-[#E6E3DE] dark:border-white/10 flex items-center justify-between">
                                <a
                                    :href="'https://www.google.com/maps/dir/?api=1&destination=' + inc.latitude + ',' + inc.longitude"
                                    target="_blank"
                                    class="text-[11px] font-bold text-[#B94F12] dark:text-[#F29A56] hover:underline flex items-center gap-1"
                                >
                                    <i data-lucide="navigation" class="w-3 h-3"></i>
                                    <span>Directions</span>
                                </a>
                                <a
                                    :href="'/office/incidents/' + inc.reference_number"
                                    class="text-[11px] font-bold hover:text-[#E87524]"
                                >
                                    Details &rarr;
                                </a>
                            </div>
                        </div>
                    </template>

                    <template x-if="incidents.length === 0">
                        <div class="col-span-full py-12 text-center text-[#77736E] text-xs">
                            No active emergency incidents currently assigned to {{ $office->code }}.
                        </div>
                    </template>
                </div>

                {{-- Staging Bases & Personnel Presence Summary --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-[#E6E3DE] dark:border-white/10">
                    <div class="p-4 rounded-xl bg-[#E87524]/10 border border-[#E87524]/25 text-xs space-y-2">
                        <span class="font-bold text-[#B94F12] dark:text-[#F29A56] flex items-center gap-2">
                            <i data-lucide="building-2" class="w-4 h-4 text-[#E87524]"></i>
                            Agency Staging Bases
                        </span>
                        <template x-for="base in bases" :key="base.id">
                            <div class="p-2.5 rounded-lg bg-white dark:bg-[#17191C] border border-[#E87524]/20 flex items-center justify-between">
                                <div>
                                    <div class="font-bold" x-text="base.name"></div>
                                    <div class="text-[10px] text-[#77736E]" x-text="base.address"></div>
                                </div>
                                <span class="font-mono text-[10px] text-[#B94F12] dark:text-[#F29A56] font-bold" x-text="base.latitude.toFixed(4) + ', ' + base.longitude.toFixed(4)"></span>
                            </div>
                        </template>
                    </div>

                    <div class="p-4 rounded-xl bg-[#27845B]/10 border border-[#27845B]/25 text-xs space-y-2">
                        <span class="font-bold text-[#27845B] flex items-center gap-2">
                            <i data-lucide="users" class="w-4 h-4 text-[#27845B]"></i>
                            Field Responder Telemetry
                        </span>
                        <template x-for="resp in responders" :key="resp.id">
                            <div class="p-2.5 rounded-lg bg-white dark:bg-[#17191C] border border-[#27845B]/20 flex items-center justify-between">
                                <div>
                                    <div class="font-bold" x-text="resp.name + ' (' + resp.badge + ')'"></div>
                                    <div class="text-[10px] text-[#77736E]" x-text="resp.team || 'Solo Unit'"></div>
                                </div>
                                <span
                                    class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                    :class="resp.freshness === 'live' ? 'bg-[#27845B]/15 text-[#27845B]' : 'bg-[#D99024]/15 text-[#D99024]'"
                                    x-text="resp.freshness"
                                ></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Scoped Geo-Tagged Evidence Panel --}}
                <template x-if="evidence && evidence.length > 0">
                    <div class="pt-4 border-t border-[#E6E3DE] dark:border-white/10 space-y-2">
                        <span class="font-bold text-xs flex items-center gap-2">
                            <i data-lucide="camera" class="w-4 h-4 text-[#E87524]"></i>
                            Scoped Agency Geo-Tagged Evidence
                        </span>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                            <template x-for="ev in evidence" :key="ev.id">
                                <div class="p-3 rounded-xl border border-[#E6E3DE] dark:border-white/10 bg-[#F5F3EF] dark:bg-white/5 flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="font-bold truncate" x-text="ev.file_name"></div>
                                        <div class="font-mono text-[10px] text-[#77736E]" x-text="(ev.incident_reference || '') + ' · ' + ev.location_source"></div>
                                    </div>
                                    <a :href="ev.stream_url" target="_blank" class="px-2.5 py-1 rounded-lg bg-[#E87524] hover:bg-[#B94F12] text-white font-bold text-[10px] shrink-0">View</a>
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
