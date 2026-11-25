<x-layouts.admin>
    <div 
        x-data="adminLiveMap({{ Js::from($initialData) }})"
        x-init="initMap()"
        class="relative h-[calc(100vh-5rem)] -m-4 sm:-m-6 lg:-m-8 overflow-hidden bg-slate-900"
    >
        {{-- Leaflet CSS & JS --}}
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

        {{-- Top Floating Telemetry & Metric Bar --}}
        <div class="eoc-map-hud absolute top-4 left-4 right-4 z-[1000] pointer-events-none flex flex-wrap items-center justify-between gap-3">
            {{-- Quick Stats Pills --}}
            <div class="pointer-events-auto flex items-center gap-2 bg-[#0B1726]/90 backdrop-blur-md p-1.5 rounded-2xl border border-[#34B9D4]/25 shadow-xl text-xs text-[#EDF4FC]">
                <div class="px-3 py-1 rounded-xl bg-white/10 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="font-bold tracking-wide">LIVE OPERATIONS MAP</span>
                </div>

                <div class="hidden sm:flex items-center gap-4 px-3 py-1 font-mono text-[11px] border-l border-white/10">
                    <div>Total: <span class="font-bold text-slate-200" x-text="stats.total_incidents">0</span></div>
                    <div>Pending: <span class="font-bold text-amber-400" x-text="stats.pending_review">0</span></div>
                    <div>Active: <span class="font-bold text-blue-400" x-text="stats.active_incidents">0</span></div>
                    <div>Resolved: <span class="font-bold text-emerald-400" x-text="stats.resolved_incidents">0</span></div>
                    <div>Online Responders: <span class="font-bold text-emerald-300" x-text="stats.online_personnel">0</span></div>
                </div>
            </div>

            {{-- Controls, Responder Tracking Panel Toggle & Boundary Manager Link --}}
            <div class="pointer-events-auto flex items-center gap-2">
                <button
                    type="button"
                    x-on:click="respondersDrawerOpen = !respondersDrawerOpen"
                    class="px-3 py-2 rounded-xl bg-[#0B1726]/90 hover:bg-[#173957] text-white text-xs font-bold border border-white/15 shadow-lg backdrop-blur-md flex items-center gap-2 transition-all"
                >
                    <i data-lucide="radio" class="w-4 h-4 text-[#19865B]"></i>
                    <span class="hidden md:inline">Units Telemetry</span>
                </button>
                <a 
                    href="{{ route('admin.map.boundaries') }}" 
                    class="px-3 py-2 rounded-xl bg-[#173957]/90 hover:bg-[#173957] text-white text-xs font-bold border border-white/10 shadow-lg backdrop-blur-md flex items-center gap-2 transition-all hover:scale-105"
                    title="Import Official GeoJSON Boundaries"
                >
                    <i data-lucide="layers" class="w-4 h-4 text-[#3187D8]"></i>
                    <span class="hidden md:inline">Boundaries</span>
                </a>

                <button 
                    type="button"
                    x-on:click="filtersOpen = !filtersOpen"
                    class="px-3 py-2 rounded-xl bg-white text-slate-900 text-xs font-bold shadow-lg border border-slate-200 hover:bg-slate-100 flex items-center gap-2 transition-all"
                >
                    <i data-lucide="filter" class="w-4 h-4 text-[#3187D8]"></i>
                    <span>Filters</span>
                    <span x-show="activeFilterCount() > 0" class="px-1.5 py-0.2 rounded-full bg-[#3187D8] text-white text-[10px]" x-text="activeFilterCount()"></span>
                </button>

                <button 
                    type="button"
                    x-on:click="refreshData()"
                    class="p-2 rounded-xl bg-white text-slate-900 shadow-lg border border-slate-200 hover:bg-slate-100 transition-all"
                    title="Refresh Map Data"
                >
                    <i data-lucide="refresh-cw" class="w-4 h-4 text-slate-700" :class="isRefreshing ? 'animate-spin' : ''"></i>
                </button>
            </div>
        </div>

        {{-- Responder Tracking Floating Drawer --}}
        <div
            x-show="respondersDrawerOpen"
            x-transition.opacity
            class="absolute top-16 left-4 z-[1001] w-80 bg-[#0B1726]/95 text-[#EDF4FC] backdrop-blur-md rounded-2xl shadow-2xl border border-[#34B9D4]/25 p-4 max-h-[calc(100vh-10rem)] flex flex-col"
            x-cloak
        >
            <div class="flex items-center justify-between pb-2.5 mb-2.5 border-b border-white/10">
                <h3 class="text-xs font-black uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="navigation" class="w-4 h-4 text-[#34B9D4]"></i>
                    <span>Field Responder GPS Telemetry</span>
                </h3>
                <button type="button" x-on:click="respondersDrawerOpen = false" class="text-[#93A4B8] hover:text-white">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="space-y-2 overflow-y-auto alerto-scrollbar text-xs">
                <template x-for="resp in mapData.responders" :key="resp.id">
                    <button type="button"
                            x-on:click="focusResponder(resp)"
                            class="w-full text-left p-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-between gap-2 transition-colors">
                        <div class="min-w-0">
                            <div class="font-bold truncate" x-text="resp.name + ' (' + resp.badge + ')'"></div>
                            <div class="text-[10px] text-[#93A4B8] truncate" x-text="resp.office + ' · ' + resp.last_update"></div>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[9px] font-mono font-bold uppercase shrink-0"
                              :class="resp.freshness === 'live' ? 'bg-[#19865B]/25 text-[#6AE0B0]' : 'bg-[#F0A82E]/20 text-[#F7C768]'"
                              x-text="resp.freshness === 'live' ? 'LIVE' : 'STALE'"></span>
                    </button>
                </template>
                <template x-if="!mapData.responders || mapData.responders.length === 0">
                    <div class="py-6 text-center text-[#93A4B8] text-[11px]">No responder GPS locations reported yet.</div>
                </template>
            </div>
        </div>

        {{-- Filter Drawer / Modal --}}
        <div 
            x-show="filtersOpen" 
            x-transition.opacity.duration.200ms
            class="absolute top-16 right-4 z-[1001] w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-200 p-5 overflow-y-auto max-h-[calc(100vh-6rem)]"
            x-cloak
        >
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-black text-slate-900 text-sm flex items-center gap-2">
                    <i data-lucide="sliders" class="w-4 h-4 text-[#3187D8]"></i>
                    Operations Map Filters
                </h3>
                <button type="button" x-on:click="filtersOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="space-y-4 py-4 text-xs">
                {{-- Search Filter --}}
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Search Reference / Keyword</label>
                    <input 
                        type="text" 
                        x-model="filters.search" 
                        x-on:input.debounce.400ms="applyFilters()"
                        placeholder="e.g. INC-2026, Culipapa..." 
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#3187D8] focus:border-[#3187D8]"
                    />
                </div>

                {{-- Category Filter --}}
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Incident Category</label>
                    <select x-model="filters.category_id" x-on:change="applyFilters()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Barangay Filter --}}
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Barangay Jurisdiction</label>
                    <select x-model="filters.barangay_id" x-on:change="applyFilters()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
                        <option value="">All 13 Hinoba-an Barangays</option>
                        @foreach($barangays as $barangay)
                            <option value="{{ $barangay->id }}">{{ $barangay->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Filter --}}
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Incident Status</label>
                    <select x-model="filters.status" x-on:change="applyFilters()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
                        <option value="">All Operational Statuses</option>
                        <option value="pending_review">Pending Review (Orange)</option>
                        <option value="accepted">Accepted (Blue)</option>
                        <option value="dispatched">Dispatched (Green)</option>
                        <option value="acknowledged">Acknowledged</option>
                        <option value="on_the_way">On The Way</option>
                        <option value="on_scene">On Scene</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved (Gray)</option>
                        <option value="closed">Closed</option>
                        <option value="rejected">Rejected (Red)</option>
                    </select>
                </div>

                {{-- Office Filter --}}
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Assigned Agency Office</label>
                    <select x-model="filters.office_id" x-on:change="applyFilters()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
                        <option value="">All Partner Agencies</option>
                        @foreach($offices as $office)
                            <option value="{{ $office->id }}">{{ $office->name }} ({{ $office->code }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Layer Visibility Toggles --}}
                <div class="pt-2 border-t border-slate-100">
                    <span class="block font-bold text-slate-700 mb-2">Display Layers</span>
                    <div class="grid grid-cols-2 gap-2 text-[11px]">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="layers.incidents" x-on:change="updateLayerVisibility()" class="rounded text-[#3187D8]">
                            <span>Incidents</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="layers.responders" x-on:change="updateLayerVisibility()" class="rounded text-[#3187D8]">
                            <span>Responders</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="layers.bases" x-on:change="updateLayerVisibility()" class="rounded text-[#3187D8]">
                            <span>Bases &amp; HQ</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="layers.evidence" x-on:change="updateLayerVisibility()" class="rounded text-[#3187D8]">
                            <span>Evidence Media</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="layers.boundaries" x-on:change="updateLayerVisibility()" class="rounded text-[#3187D8]">
                            <span>Barangay Borders</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="layers.routes" x-on:change="updateLayerVisibility()" class="rounded text-[#3187D8]">
                            <span>Response Routes</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                <button type="button" x-on:click="resetFilters()" class="text-xs text-rose-600 font-bold hover:underline">
                    Reset All Filters
                </button>
                <button type="button" x-on:click="filtersOpen = false" class="px-3 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800">
                    Done
                </button>
            </div>
        </div>

        {{-- Map View Container --}}
        <div id="alerto-admin-map" class="w-full h-full z-[1]"></div>

        {{-- Map Legend Bar --}}
        <div class="absolute bottom-4 left-4 z-[1000] pointer-events-auto bg-[#173957]/90 backdrop-blur-md px-3 py-2 rounded-xl border border-white/10 shadow-xl hidden md:flex items-center gap-3 text-[10px] text-white">
            <span class="font-bold text-slate-300">INCIDENTS:</span>
            <div class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span><span>Pending</span></div>
            <div class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span><span>Accepted</span></div>
            <div class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span><span>Dispatched</span></div>
            <div class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span><span>Resolved</span></div>
            <span class="font-bold text-slate-300 ml-1 border-l border-white/10 pl-2.5">EVIDENCE:</span>
            <div class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-violet-500"></span><span>Photo</span></div>
            <div class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-cyan-500"></span><span>Video</span></div>
            <div class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-emerald-500 border border-white"></span><span>Personnel</span></div>
            <div class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-amber-400 border border-white"></span><span>Office</span></div>
        </div>

        {{-- Selected Evidence Media Side Panel --}}
        <div
            x-show="selectedEvidence !== null"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            class="absolute top-0 right-0 bottom-0 z-[1003] w-full sm:w-[430px] bg-white shadow-2xl border-l border-slate-200 flex flex-col"
            x-cloak
        >
            <template x-if="selectedEvidence">
                <div class="flex flex-col h-full">
                    <div class="p-4 border-b border-slate-200 bg-[#173957] text-white flex items-center justify-between">
                        <div>
                            <span class="px-2 py-0.5 rounded bg-[#3187D8] text-white text-[10px] font-bold uppercase tracking-wider"
                                  x-text="(selectedEvidence.media_type || 'media') + ' EVIDENCE'"></span>
                            <h3 class="text-sm font-black truncate max-w-[300px] mt-1" x-text="selectedEvidence.file_name"></h3>
                        </div>
                        <button type="button" x-on:click="selectedEvidence = null" class="text-slate-300 hover:text-white p-1">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto alerto-scrollbar p-5 space-y-4 text-xs">
                        {{-- Media Preview --}}
                        <div class="rounded-2xl overflow-hidden bg-slate-950 border border-slate-200 aspect-video flex items-center justify-center">
                            <template x-if="selectedEvidence.media_type === 'photo'">
                                <img :src="selectedEvidence.stream_url" class="w-full h-full object-cover" alt="Evidence Preview">
                            </template>
                            <template x-if="selectedEvidence.media_type === 'video'">
                                <video controls class="w-full h-full object-contain" :src="selectedEvidence.stream_url"></video>
                            </template>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 space-y-2 text-[11px]">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Related Incident:</span>
                                <span class="font-mono font-bold text-[#173957]" x-text="selectedEvidence.incident_reference || 'Standalone Media'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Media Source:</span>
                                <span class="font-mono font-semibold text-slate-700" x-text="selectedEvidence.source_channel"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Uploader:</span>
                                <span class="font-bold text-slate-800" x-text="selectedEvidence.uploader + ' (' + selectedEvidence.uploader_account_type + ')'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Capture Timestamp:</span>
                                <span class="font-mono text-slate-700" x-text="selectedEvidence.captured_at ? new Date(selectedEvidence.captured_at).toLocaleString() : 'N/A'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Upload Timestamp:</span>
                                <span class="font-mono text-slate-700" x-text="selectedEvidence.uploaded_at ? new Date(selectedEvidence.uploaded_at).toLocaleString() : 'N/A'"></span>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200/70 space-y-1.5 font-mono text-[11px]">
                            <div class="flex justify-between">
                                <span class="text-emerald-700 font-bold">Location Source:</span>
                                <span class="font-bold text-emerald-900" x-text="selectedEvidence.location_source"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-emerald-700">Coordinates:</span>
                                <span class="font-bold text-emerald-950" x-text="selectedEvidence.latitude.toFixed(6) + ', ' + selectedEvidence.longitude.toFixed(6)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-emerald-700">GPS Accuracy:</span>
                                <span class="text-emerald-900" x-text="selectedEvidence.accuracy ? '±' + selectedEvidence.accuracy + ' m' : 'Unavailable'"></span>
                            </div>
                            <template x-if="selectedEvidence.barangay">
                                <div class="flex justify-between">
                                    <span class="text-emerald-700">Barangay:</span>
                                    <span class="font-sans font-bold text-emerald-900" x-text="selectedEvidence.barangay"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="p-4 border-t border-slate-200 bg-slate-50 space-y-2">
                        <template x-if="selectedEvidence.incident_reference">
                            <a :href="'/admin/incidents/' + selectedEvidence.incident_reference"
                               class="w-full py-2.5 px-4 rounded-xl bg-[#173957] hover:bg-[#0f2438] text-white font-bold text-xs flex items-center justify-center gap-2 shadow-sm">
                                <i data-lucide="file-warning" class="w-4 h-4 text-[#3187D8]"></i>
                                <span>OPEN RELATED INCIDENT</span>
                            </a>
                        </template>
                        <a :href="selectedEvidence.stream_url" target="_blank"
                           class="w-full py-2.5 px-4 rounded-xl bg-[#3187D8] hover:bg-[#256fb4] text-white font-bold text-xs flex items-center justify-center gap-2">
                            <i data-lucide="external-link" class="w-4 h-4"></i>
                            <span>VIEW ORIGINAL MEDIA</span>
                        </a>
                    </div>
                </div>
            </template>
        </div>

        {{-- Selected Incident Detail Side Panel --}}
        <div 
            x-show="selectedIncident !== null"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="absolute top-0 right-0 bottom-0 z-[1002] w-full sm:w-[450px] bg-white shadow-2xl border-l border-slate-200 flex flex-col"
            x-cloak
        >
            <template x-if="selectedIncident">
                <div class="flex flex-col h-full">
                    {{-- Header --}}
                    <div class="p-5 border-b border-slate-200 bg-[#173957] text-white flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs font-bold text-[#3187D8]" x-text="selectedIncident.reference_number"></span>
                                <span 
                                    class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                                    :style="'background-color:' + selectedIncident.marker.color + '25; color:' + selectedIncident.marker.color"
                                    x-text="selectedIncident.marker.label"
                                ></span>
                            </div>
                            <h2 class="text-base font-black tracking-tight mt-1" x-text="selectedIncident.category"></h2>
                        </div>
                        <button type="button" x-on:click="selectedIncident = null" class="text-slate-300 hover:text-white p-1">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="flex-1 overflow-y-auto alerto-scrollbar p-5 space-y-5 text-xs">
                        {{-- Priority & Timestamp --}}
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <div>
                                <span class="text-[10px] text-slate-400 font-bold uppercase block">Priority Level</span>
                                <span class="font-bold text-slate-900 uppercase" x-text="selectedIncident.priority"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] text-slate-400 font-bold uppercase block">Submitted</span>
                                <span class="font-medium text-slate-700" x-text="selectedIncident.formatted_time"></span>
                            </div>
                        </div>

                        {{-- Caller Verification Box --}}
                        <template x-if="selectedIncident.caller">
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-800" x-text="selectedIncident.caller.name"></span>
                                    <span 
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                        :class="selectedIncident.caller.verification_status === 'verified' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                        x-text="selectedIncident.caller.verification_status"
                                    ></span>
                                </div>
                                <div class="flex items-center gap-2 text-slate-600">
                                    <i data-lucide="phone" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                                    <a :href="'tel:' + selectedIncident.caller.phone" class="font-mono font-bold text-[#3187D8] hover:underline" x-text="selectedIncident.caller.phone"></a>
                                </div>
                            </div>
                        </template>

                        {{-- Description --}}
                        <div>
                            <span class="font-bold text-slate-800 block mb-1">Incident Description</span>
                            <p class="text-slate-600 leading-relaxed bg-slate-50 p-3 rounded-xl border border-slate-100" x-text="selectedIncident.description"></p>
                        </div>

                        {{-- Geographic Position --}}
                        <div class="space-y-2">
                            <span class="font-bold text-slate-800 block">Location &amp; Coordinates</span>
                            <div class="p-3 rounded-xl border border-slate-200 bg-white space-y-1 font-mono text-[11px]">
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Barangay:</span>
                                    <span class="font-bold text-slate-800" x-text="selectedIncident.barangay"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Latitude / Longitude:</span>
                                    <span class="font-bold text-slate-800" x-text="selectedIncident.latitude.toFixed(6) + ', ' + selectedIncident.longitude.toFixed(6)"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">GPS Accuracy:</span>
                                    <span class="text-slate-600" x-text="selectedIncident.accuracy ? selectedIncident.accuracy + 'm' : 'Standard'"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Active Agency Assignments --}}
                        <div class="space-y-2">
                            <span class="font-bold text-slate-800 block">Confirmed Assignments</span>
                            <template x-if="selectedIncident.assignments && selectedIncident.assignments.length > 0">
                                <div class="space-y-1.5">
                                    <template x-for="asg in selectedIncident.assignments" :key="asg.id">
                                        <div class="p-2.5 rounded-lg border border-slate-100 bg-slate-50 flex items-center justify-between text-xs">
                                            <div>
                                                <span class="font-bold text-slate-900" x-text="asg.office || 'Emergency Agency'"></span>
                                                <span class="text-slate-500 block text-[10px]" x-text="(asg.team ? asg.team + ' · ' : '') + (asg.personnel || 'Dispatch Standby')"></span>
                                            </div>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-100 text-blue-800" x-text="asg.status"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!selectedIncident.assignments || selectedIncident.assignments.length === 0">
                                <div class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs">
                                    No response units assigned yet.
                                </div>
                            </template>
                        </div>

                        {{-- Evidence Attachments --}}
                        <template x-if="selectedIncident.evidence && selectedIncident.evidence.length > 0">
                            <div class="space-y-2">
                                <span class="font-bold text-slate-800 block">Geo-Tagged Evidence</span>
                                <div class="grid grid-cols-2 gap-2">
                                    <template x-for="ev in selectedIncident.evidence" :key="ev.id">
                                        <div class="p-2 rounded-lg border border-slate-200 bg-slate-50 text-[10px]">
                                            <div class="font-bold truncate text-slate-800" x-text="ev.file_name || 'Attachment'"></div>
                                            <div class="text-slate-500 font-mono" x-text="ev.gps_source"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Actions Footer --}}
                    <div class="p-4 border-t border-slate-200 bg-slate-50 space-y-2">
                        <a 
                            :href="'/admin/incidents/' + selectedIncident.reference_number"
                            class="w-full py-2.5 px-4 rounded-xl bg-[#3187D8] text-white font-bold text-xs flex items-center justify-center gap-2 hover:bg-[#286eb3] transition-all shadow-md"
                        >
                            <span>Open Full Incident Record &amp; Dispatch Console</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </a>

                        <a 
                            :href="'https://www.google.com/maps/dir/?api=1&destination=' + selectedIncident.latitude + ',' + selectedIncident.longitude"
                            target="_blank"
                            class="w-full py-2 px-4 rounded-xl bg-white border border-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center gap-2 hover:bg-slate-100 transition-all"
                        >
                            <i data-lucide="navigation" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                            <span>External Google Maps Navigation</span>
                        </a>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Map Alpine Component Script --}}
    <script>
        function adminLiveMap(initialData) {
            return {
                map: null,
                mapData: initialData,
                stats: initialData.stats || {},
                filtersOpen: false,
                respondersDrawerOpen: false,
                isRefreshing: false,
                knownIncidentIds: new Set(),
                selectedIncident: null,
                selectedEvidence: null,
                selectedResponder: null,
                markerLayers: {
                    incidents: null,
                    responders: null,
                    bases: null,
                    evidence: null,
                    boundaries: null,
                    routes: null,
                },
                layers: {
                    incidents: true,
                    responders: true,
                    bases: true,
                    evidence: true,
                    boundaries: true,
                    routes: true,
                },
                filters: {
                    search: '',
                    category_id: '',
                    barangay_id: '',
                    status: '',
                    office_id: '',
                },

                activeFilterCount() {
                    return Object.values(this.filters).filter(v => v !== '').length;
                },

                initMap() {
                    this.$nextTick(() => {
                        if (typeof L === 'undefined') {
                            console.warn('Leaflet library is still loading or unavailable.');
                            return;
                        }

                        // Initialize Hinoba-an Center Map: [9.4975, 122.5850]
                        this.map = L.map('alerto-admin-map', {
                            center: [9.4975, 122.5850],
                            zoom: 12,
                            zoomControl: false,
                        });

                        L.control.zoom({ position: 'bottomright' }).addTo(this.map);

                        // Professional OpenStreetMap CartoDB Dark / Street Tiles
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap &copy; ALERTO EOC Hinoba-an',
                            maxZoom: 19,
                        }).addTo(this.map);

                        // Initialize Layer Groups
                        this.markerLayers.boundaries = L.layerGroup().addTo(this.map);
                        this.markerLayers.bases = L.layerGroup().addTo(this.map);
                        this.markerLayers.routes = L.layerGroup().addTo(this.map);
                        this.markerLayers.incidents = L.layerGroup().addTo(this.map);
                        this.markerLayers.responders = L.layerGroup().addTo(this.map);
                        this.markerLayers.evidence = L.layerGroup().addTo(this.map);

                        this.knownIncidentIds = new Set((this.mapData.incidents || []).map(i => i.id));
                        this.renderAllLayers();

                        if (window.AlertoEoc?.map) {
                            window.AlertoEoc.map.animateOverlayEntry(document);
                        }

                        // Deep-link focus for evidence marker from Evidence Viewer
                        const urlParams = new URLSearchParams(window.location.search);
                        const focusEvidenceId = urlParams.get('focus_evidence');
                        const focusLat = parseFloat(urlParams.get('lat'));
                        const focusLng = parseFloat(urlParams.get('lng'));
                        if (!isNaN(focusLat) && !isNaN(focusLng)) {
                            this.map.setView([focusLat, focusLng], 16);
                        }
                        if (focusEvidenceId && this.mapData.evidence) {
                            const matchEv = this.mapData.evidence.find(e => String(e.id) === String(focusEvidenceId));
                            if (matchEv) {
                                this.selectedEvidence = matchEv;
                            }
                        }

                        // Polling Fallback (every 15s)
                        setInterval(() => {
                            this.refreshData(true);
                        }, 15000);

                        if (window.lucide) {
                            window.lucide.createIcons();
                        }
                    });
                },

                renderAllLayers() {
                    this.renderBoundaries();
                    this.renderBases();
                    this.renderIncidents();
                    this.renderResponders();
                    this.renderEvidence();
                },

                renderIncidents() {
                    this.markerLayers.incidents.clearLayers();
                    if (!this.layers.incidents || !this.mapData.incidents) return;

                    this.mapData.incidents.forEach(inc => {
                        const iconHtml = `
                            <div class="relative group cursor-pointer" style="transform: translate(-50%, -100%);">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white shadow-lg border-2 border-white transition-all transform group-hover:scale-125" style="background-color: ${inc.marker.color}">
                                    <span class="text-xs font-bold font-mono">!</span>
                                </div>
                                ${inc.status === 'pending_review' ? '<span class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-amber-400 animate-ping"></span>' : ''}
                            </div>
                        `;

                        const customIcon = L.divIcon({
                            html: iconHtml,
                            className: 'custom-incident-pin',
                            iconSize: [32, 32],
                        });

                        const marker = L.marker([inc.latitude, inc.longitude], { icon: customIcon });
                        marker.on('click', () => {
                            this.selectedIncident = inc;
                            if (window.lucide) this.$nextTick(() => window.lucide.createIcons());
                        });

                        marker.bindTooltip(`${inc.reference_number} · ${inc.marker.label}`, { direction: 'top', offset: [0, -28] });
                        marker.addTo(this.markerLayers.incidents);
                    });

                    // Render Active Pulsing Red SOS Alerts with distinct badge
                    if (this.mapData.sos_alerts) {
                        this.mapData.sos_alerts.forEach(sos => {
                            if (!sos.latitude || !sos.longitude) return;
                            const sosHtml = `
                                <div class="relative cursor-pointer" style="transform: translate(-50%, -100%);">
                                    <span class="absolute -inset-2 rounded-full bg-red-500 opacity-50 animate-ping"></span>
                                    <div class="relative px-2 py-1 rounded-full bg-red-700 border-2 border-white text-white text-[10px] font-black shadow-2xl whitespace-nowrap">
                                        SOS · ${sos.sender_type.toUpperCase()}
                                    </div>
                                </div>
                            `;
                            const sosIcon = L.divIcon({ html: sosHtml, className: 'custom-sos-pin', iconSize: [70, 28] });
                            const m = L.marker([sos.latitude, sos.longitude], { icon: sosIcon });
                            m.bindPopup(`<div class="text-xs p-1">
                                <strong class="text-red-700 font-black block">${sos.headline}</strong>
                                <div>${sos.sender_name} (${sos.sender_phone || 'no phone'})</div>
                                <div class="text-[10px] text-slate-500">GPS: ${sos.location_status} · ${sos.barangay}</div>
                                <a href="/admin/sos/${sos.id}" class="inline-block mt-1 font-bold text-red-700 underline">Handle SOS Alert &rarr;</a>
                            </div>`);
                            m.addTo(this.markerLayers.incidents);
                        });
                    }
                },

                renderResponders() {
                    this.markerLayers.responders.clearLayers();
                    if (!this.layers.responders || !this.mapData.responders) return;

                    this.mapData.responders.forEach(resp => {
                        if (!resp.latitude || !resp.longitude) return;

                        const isLive = resp.freshness === 'live';
                        const color = isLive ? '#10B981' : '#F59E0B';

                        const iconHtml = `
                            <div class="relative cursor-pointer" style="transform: translate(-50%, -50%);">
                                <div class="w-7 h-7 rounded-full bg-slate-900 border-2 flex items-center justify-center text-white text-[10px] font-bold shadow-md" style="border-color: ${color}">
                                    <span class="w-2 h-2 rounded-full" style="background-color: ${color}"></span>
                                </div>
                            </div>
                        `;

                        const customIcon = L.divIcon({
                            html: iconHtml,
                            className: 'custom-responder-pin',
                            iconSize: [28, 28],
                        });

                        const marker = L.marker([resp.latitude, resp.longitude], { icon: customIcon });
                        marker.bindPopup(`
                            <div class="text-xs p-1">
                                <strong class="text-slate-900 font-bold block">${resp.name} (${resp.badge})</strong>
                                <span class="text-slate-500">${resp.office} · ${resp.team || 'Solo'}</span>
                                <div class="mt-1 font-mono text-[10px] text-emerald-600 uppercase font-bold">${resp.operational_status} · ${resp.freshness}</div>
                            </div>
                        `);

                        marker.addTo(this.markerLayers.responders);
                    });
                },

                renderBases() {
                    this.markerLayers.bases.clearLayers();
                    if (!this.layers.bases || !this.mapData.response_bases) return;

                    this.mapData.response_bases.forEach(base => {
                        const iconHtml = `
                            <div class="w-6 h-6 rounded bg-[#173957] border border-white flex items-center justify-center text-white text-[9px] font-black shadow" style="transform: translate(-50%, -50%);">
                                H
                            </div>
                        `;

                        const customIcon = L.divIcon({
                            html: iconHtml,
                            className: 'custom-base-pin',
                            iconSize: [24, 24],
                        });

                        const marker = L.marker([base.latitude, base.longitude], { icon: customIcon });
                        marker.bindPopup(`
                            <div class="text-xs p-1">
                                <strong class="text-slate-900 font-bold">${base.name}</strong>
                                <div class="text-slate-500 text-[10px]">${base.office || 'LDRRMO Base'}</div>
                            </div>
                        `);
                        marker.addTo(this.markerLayers.bases);
                    });
                },

                renderBoundaries() {
                    this.markerLayers.boundaries.clearLayers();
                    if (!this.layers.boundaries || !this.mapData.boundaries) return;

                    if (this.mapData.boundaries.barangays) {
                        this.mapData.boundaries.barangays.forEach(b => {
                            if (b.geojson) {
                                L.geoJSON(b.geojson, {
                                    style: {
                                        color: '#3187D8',
                                        weight: 1.5,
                                        opacity: 0.6,
                                        fillColor: '#3187D8',
                                        fillOpacity: 0.05,
                                    }
                                }).bindTooltip(b.name, { sticky: true, className: 'barangay-tooltip' }).addTo(this.markerLayers.boundaries);
                            }
                        });
                    }
                },

                renderEvidence() {
                    this.markerLayers.evidence.clearLayers();
                    if (!this.layers.evidence || !this.mapData.evidence) return;

                    this.mapData.evidence.forEach(ev => {
                        const styleConfig = {
                            'photo_evidence': { bg: '#8B5CF6', badge: 'P', label: 'Photo' },
                            'video_evidence': { bg: '#06B6D4', badge: 'V', label: 'Video' },
                            'personnel_evidence': { bg: '#10B981', badge: 'R', label: 'Responder' },
                            'office_evidence': { bg: '#F59E0B', badge: 'O', label: 'Office' },
                        }[ev.marker_style || 'photo_evidence'] || { bg: '#8B5CF6', badge: 'E', label: 'Evidence' };

                        const iconHtml = `
                            <div class="relative cursor-pointer group" style="transform: translate(-50%, -50%);">
                                <div class="w-6 h-6 rounded-lg flex items-center justify-center text-white text-[10px] font-black border-2 border-white shadow-md transition-transform group-hover:scale-125"
                                     style="background-color: ${styleConfig.bg}">
                                    ${styleConfig.badge}
                                </div>
                            </div>
                        `;

                        const customIcon = L.divIcon({
                            html: iconHtml,
                            className: 'custom-evidence-pin',
                            iconSize: [24, 24],
                        });

                        const marker = L.marker([ev.latitude, ev.longitude], { icon: customIcon });
                        marker.on('click', () => {
                            this.selectedIncident = null;
                            this.selectedEvidence = ev;
                            if (window.lucide) this.$nextTick(() => window.lucide.createIcons());
                        });
                        marker.addTo(this.markerLayers.evidence);
                    });
                },

                updateLayerVisibility() {
                    this.renderAllLayers();
                },

                applyFilters() {
                    this.refreshData();
                },

                focusResponder(resp) {
                    if (resp.latitude && resp.longitude && this.map) {
                        this.map.setView([resp.latitude, resp.longitude], 15, { animate: true });
                    }
                },

                resetFilters() {
                    this.filters = { search: '', category_id: '', barangay_id: '', status: '', office_id: '' };
                    this.refreshData();
                },

                async refreshData(silent = false) {
                    if (!silent) this.isRefreshing = true;

                    try {
                        const params = new URLSearchParams();
                        Object.entries(this.filters).forEach(([k, v]) => {
                            if (v) params.append(k, v);
                        });

                        const res = await fetch('/admin/map/data?' + params.toString(), {
                            headers: { 'Accept': 'application/json' }
                        });

                        if (res.ok) {
                            const updatedData = await res.json();
                            if (silent && updatedData.incidents) {
                                updatedData.incidents.forEach(inc => {
                                    if (!this.knownIncidentIds.has(inc.id)) {
                                        window.dispatchEvent(new CustomEvent('show-toast', {
                                            detail: {
                                                title: 'New Incident Telemetry: ' + inc.reference_number,
                                                message: (inc.category || 'Emergency') + ' in ' + (inc.barangay || 'Hinoba-an'),
                                                type: inc.priority === 'critical' ? 'error' : 'info',
                                            }
                                        }));
                                    }
                                });
                            }

                            this.mapData = updatedData;
                            if (this.mapData.stats) this.stats = this.mapData.stats;
                            this.knownIncidentIds = new Set((this.mapData.incidents || []).map(i => i.id));
                            this.renderAllLayers();

                        // Deep-link focus for evidence marker from Evidence Viewer
                        const urlParams = new URLSearchParams(window.location.search);
                        const focusEvidenceId = urlParams.get('focus_evidence');
                        const focusLat = parseFloat(urlParams.get('lat'));
                        const focusLng = parseFloat(urlParams.get('lng'));
                        if (!isNaN(focusLat) && !isNaN(focusLng)) {
                            this.map.setView([focusLat, focusLng], 16);
                        }
                        if (focusEvidenceId && this.mapData.evidence) {
                            const matchEv = this.mapData.evidence.find(e => String(e.id) === String(focusEvidenceId));
                            if (matchEv) {
                                this.selectedEvidence = matchEv;
                            }
                        }
                        }
                    } catch (e) {
                        console.error('Map data refresh error:', e);
                    } finally {
                        if (!silent) this.isRefreshing = false;
                    }
                }
            };
        }
    </script>

    <style>
        .custom-incident-pin, .custom-responder-pin, .custom-base-pin, .custom-evidence-pin {
            background: transparent;
            border: none;
        }
        .barangay-tooltip {
            background: rgba(23, 57, 87, 0.85);
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            padding: 2px 6px;
        }
    </style>
</x-layouts.admin>

