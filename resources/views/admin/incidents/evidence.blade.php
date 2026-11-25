<x-layouts.admin :title="'Evidence Viewer — ALERTO EOC'">
    <div class="space-y-6" x-data="evidenceViewerConsole()">

        {{-- Top Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#173957]/10 text-[#173957] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="images" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                    Incident Management · Forensic Evidence Viewer
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                    Geo-Tagged Incident Evidence Viewer
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Inspect all authorized incident photographs, response videos, EXIF capture metadata, and verified GPS origins.
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="{{ route('admin.incidents.index') }}"
                   class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold flex items-center gap-1.5">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Incident Queue</span>
                </a>
                <a href="{{ route('admin.media.index') }}"
                   class="px-3.5 py-2 rounded-xl bg-[#173957] hover:bg-[#0f2438] text-white text-xs font-bold flex items-center gap-1.5 shadow-xs">
                    <i data-lucide="hard-drive" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                    <span>Media Governance Console</span>
                </a>
            </div>
        </div>

        {{-- Advanced Evidence Filter Bar --}}
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs">
            <form method="GET" action="{{ route('admin.incidents.evidence') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 text-xs">
                <div class="lg:col-span-2 relative">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="incident_reference" value="{{ $filters['incident_reference'] ?? '' }}"
                           placeholder="Search incident ref (INC-...), filename, uploader..."
                           class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-300 bg-slate-50/50 focus:ring-2 focus:ring-[#3187D8]">
                </div>

                <div>
                    <select name="category_id" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select name="barangay_id" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                        <option value="">All Barangays</option>
                        @foreach($barangays as $brgy)
                            <option value="{{ $brgy->id }}" {{ ($filters['barangay_id'] ?? '') == $brgy->id ? 'selected' : '' }}>{{ $brgy->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select name="media_type" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                        <option value="all">Photos &amp; Videos</option>
                        <option value="photo" {{ ($filters['media_type'] ?? '') === 'photo' ? 'selected' : '' }}>Photographs Only</option>
                        <option value="video" {{ ($filters['media_type'] ?? '') === 'video' ? 'selected' : '' }}>Videos Only</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 py-2 px-3 rounded-xl bg-[#3187D8] hover:bg-[#256fb4] text-white font-bold flex items-center justify-center gap-1.5">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        <span>Apply</span>
                    </button>
                    <a href="{{ route('admin.incidents.evidence') }}" class="p-2 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100" title="Clear Filters">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                </div>

                <div>
                    <select name="uploader_id" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                        <option value="">All Uploaders</option>
                        @foreach($uploaders as $u)
                            <option value="{{ $u->id }}" {{ ($filters['uploader_id'] ?? '') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ ucfirst($u->account_type) }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select name="location_source" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                        <option value="all">All GPS Sources</option>
                        @foreach($locationSources as $src)
                            <option value="{{ $src }}" {{ ($filters['location_source'] ?? '') === $src ? 'selected' : '' }}>{{ $src }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white" title="Date From">
                </div>

                <div>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white" title="Date To">
                </div>
            </form>
        </div>

        {{-- Evidence Cards Grid --}}
        @if($evidenceItems->isEmpty())
            <div class="bg-white rounded-3xl p-12 text-center border border-slate-200/80 shadow-xs">
                <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="camera-off" class="w-8 h-8"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800">No Evidence Matches Filter Criteria</h3>
                <p class="text-xs text-slate-500 mt-1">Adjust search terms, date window, or media filters.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 evidence-grid">
                @foreach($evidenceItems as $item)
                    @php
                        $geo = $item->geoMetadata;
                        $incident = $item->incident ?? $item->evidenceRecords->first()?->incident;
                        $hasCoords = $geo && $geo->hasCoordinates();
                        $locSource = $geo?->location_source ?? 'LOCATION_UNAVAILABLE';
                    @endphp
                    <div class="evidence-card bg-white rounded-[20px] border border-slate-200/80 shadow-xs overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
                        {{-- Media Thumbnail Preview --}}
                        <div class="relative aspect-video bg-slate-900 overflow-hidden group cursor-pointer"
                             x-on:click="openModal({{ $item->id }})">
                            @if($item->isPhoto())
                                <img src="{{ route('media.thumbnail', $item->id) }}" alt="{{ $item->file_name }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center text-white bg-slate-900">
                                    <i data-lucide="play-circle" class="w-12 h-12 text-[#3187D8] mb-1"></i>
                                    <span class="text-[11px] font-mono text-slate-300">
                                        VIDEO {{ $item->duration_seconds ? '· '.number_format((float)$item->duration_seconds, 1).'s' : '' }}
                                    </span>
                                </div>
                            @endif

                            {{-- Top Badges --}}
                            <div class="absolute top-2.5 left-2.5 flex items-center gap-1.5">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $item->isVideo() ? 'bg-indigo-600 text-white' : 'bg-[#173957] text-white' }}">
                                    {{ strtoupper($item->media_type) }}
                                </span>
                                <span class="px-2 py-0.5 rounded-md bg-black/70 text-white text-[10px] font-mono">
                                    {{ str_replace('_', ' ', strtoupper($item->source_channel)) }}
                                </span>
                            </div>

                            <div class="absolute inset-0 bg-black/35 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold gap-1.5">
                                <i data-lucide="maximize-2" class="w-4 h-4"></i>
                                <span>Inspect Evidence &amp; Metadata</span>
                            </div>
                        </div>

                        {{-- Evidence Metadata Body --}}
                        <div class="p-4 space-y-3 text-xs flex-1 flex flex-col justify-between">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-2">
                                    @if($incident)
                                        <a href="{{ route('admin.incidents.show', $incident->reference_number) }}"
                                           class="font-mono font-black text-[#173957] hover:text-[#3187D8]">
                                            {{ $incident->reference_number }}
                                        </a>
                                    @else
                                        <span class="font-mono text-slate-400">Unlinked Media #{{ $item->id }}</span>
                                    @endif

                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase {{ $hasCoords ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $locSource }}
                                    </span>
                                </div>

                                <div class="text-[11px] text-slate-700 font-semibold truncate" title="{{ $item->file_name }}">
                                    {{ $item->file_name }}
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-[10px] bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                    <div>
                                        <span class="text-slate-400 block uppercase font-bold">Sender</span>
                                        <span class="font-bold text-slate-800 truncate block">{{ $item->uploader?->name ?? 'External Citizen' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block uppercase font-bold">Barangay</span>
                                        <span class="font-bold text-slate-800 truncate block">{{ $geo?->barangay?->name ?? $incident?->barangay?->name ?? 'Pending GIS' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block uppercase font-bold">Captured</span>
                                        <span class="font-mono text-slate-700 block">{{ $geo?->captured_at?->format('M d, H:i') ?? 'N/A' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block uppercase font-bold">Uploaded</span>
                                        <span class="font-mono text-slate-700 block">{{ $item->created_at?->format('M d, H:i') }}</span>
                                    </div>
                                </div>

                                @if($hasCoords)
                                    <div class="flex items-center justify-between text-[10px] font-mono text-emerald-700 bg-emerald-50/70 px-2.5 py-1.5 rounded-lg border border-emerald-200/60">
                                        <span>{{ number_format((float)$geo->latitude, 5) }}, {{ number_format((float)$geo->longitude, 5) }}</span>
                                        <span>{{ $geo->accuracy_meters ? '±'.round((float)$geo->accuracy_meters).'m' : 'Exact' }}</span>
                                    </div>
                                @else
                                    <div class="text-[10px] font-mono text-slate-400 bg-slate-50 px-2.5 py-1.5 rounded-lg border border-slate-100">
                                        GPS Coordinates Unavailable
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                                <button type="button" x-on:click="openModal({{ $item->id }})"
                                        class="flex-1 py-1.5 px-3 rounded-lg bg-[#173957] hover:bg-[#0f2438] text-white font-bold text-[11px] flex items-center justify-center gap-1">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    <span>Inspect</span>
                                </button>
                                @if($hasCoords)
                                    <a href="{{ route('admin.map', ['focus_evidence' => $item->id, 'lat' => $geo->latitude, 'lng' => $geo->longitude]) }}"
                                       class="py-1.5 px-3 rounded-lg bg-[#3187D8] hover:bg-[#256fb4] text-white font-bold text-[11px] flex items-center gap-1">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                        <span>Map</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($evidenceItems->hasPages())
                <div class="bg-white rounded-2xl p-4 border border-slate-200">
                    {{ $evidenceItems->links() }}
                </div>
            @endif
        @endif

        {{-- Evidence Lightbox & Forensic Metadata Modal --}}
        <div x-show="modalOpen" x-cloak
             class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
             x-transition.opacity>
            <div class="evidence-modal-panel bg-white rounded-3xl max-w-4xl w-full max-h-[92vh] overflow-hidden shadow-2xl border border-slate-200 flex flex-col"
                 @click.outside="closeModal()">
                <div class="p-4 sm:p-5 bg-[#173957] text-white flex items-center justify-between">
                    <div>
                        <h3 class="font-black text-sm sm:text-base" x-text="activeMedia ? activeMedia.file_name : 'Loading Evidence...'"></h3>
                        <p class="text-[11px] text-slate-300 font-mono" x-text="activeMedia ? ('Channel: ' + activeMedia.source_channel + ' · SHA256 Verified: ' + (integrityVerified ? 'YES' : 'MISMATCH')) : ''"></p>
                    </div>
                    <button type="button" x-on:click="closeModal()" class="p-1.5 rounded-lg hover:bg-white/10 text-slate-300 hover:text-white">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto grid grid-cols-1 lg:grid-cols-12" x-show="activeMedia">
                    {{-- Left: Full Media Preview --}}
                    <div class="lg:col-span-7 bg-slate-950 flex items-center justify-center p-4 min-h-[300px]">
                        <template x-if="activeMedia && activeMedia.media_type === 'photo'">
                            <img :src="streamUrl" class="max-h-[65vh] w-auto object-contain rounded-xl shadow-lg" alt="Full Evidence">
                        </template>
                        <template x-if="activeMedia && activeMedia.media_type === 'video'">
                            <video controls class="max-h-[65vh] w-full rounded-xl shadow-lg bg-black" :src="streamUrl"></video>
                        </template>
                    </div>

                    {{-- Right: Metadata Ledger --}}
                    <div class="lg:col-span-5 p-5 space-y-4 text-xs bg-white overflow-y-auto">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Geographic Provenance</span>
                            <span class="inline-block mt-1 px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-900 font-mono font-bold text-[11px]"
                                  x-text="activeMedia?.geo_metadata?.location_source || 'LOCATION_UNAVAILABLE'"></span>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1.5 font-mono text-[11px]">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Primary Coords:</span>
                                <span class="font-bold text-slate-900" x-text="activeMedia?.geo_metadata?.latitude ? (activeMedia.geo_metadata.latitude + ', ' + activeMedia.geo_metadata.longitude) : 'Unavailable'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">EXIF Capture GPS:</span>
                                <span class="text-slate-700" x-text="activeMedia?.geo_metadata?.exif_latitude ? (activeMedia.geo_metadata.exif_latitude + ', ' + activeMedia.geo_metadata.exif_longitude) : 'None'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Upload Location:</span>
                                <span class="text-slate-700" x-text="activeMedia?.geo_metadata?.upload_latitude ? (activeMedia.geo_metadata.upload_latitude + ', ' + activeMedia.geo_metadata.upload_longitude) : 'None'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Inherited Coords:</span>
                                <span class="text-slate-700" x-text="activeMedia?.geo_metadata?.inherited_latitude ? (activeMedia.geo_metadata.inherited_latitude + ', ' + activeMedia.geo_metadata.inherited_longitude) : 'None'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Accuracy:</span>
                                <span class="text-slate-700" x-text="activeMedia?.geo_metadata?.accuracy_meters ? ('±' + activeMedia.geo_metadata.accuracy_meters + 'm') : 'N/A'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Barangay:</span>
                                <span class="font-bold text-slate-800" x-text="activeMedia?.geo_metadata?.barangay?.name || activeMedia?.incident?.barangay?.name || 'N/A'"></span>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1.5 text-[11px]">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Uploader:</span>
                                <span class="font-bold text-slate-800" x-text="activeMedia?.uploader?.name || 'External / Citizen'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Camera Device:</span>
                                <span class="font-mono text-slate-700" x-text="(activeMedia?.geo_metadata?.camera_make || '') + ' ' + (activeMedia?.geo_metadata?.camera_model || 'N/A')"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">File Size / Type:</span>
                                <span class="font-mono text-slate-700" x-text="formattedSize + ' · ' + (activeMedia?.mime_type || '')"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block">SHA-256 Hash:</span>
                                <span class="font-mono text-[10px] break-all text-slate-600" x-text="activeMedia?.checksum_sha256"></span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 pt-2">
                            <template x-if="activeMedia?.incident">
                                <a :href="'/admin/incidents/' + activeMedia.incident.reference_number"
                                   class="w-full py-2.5 px-4 rounded-xl bg-[#173957] hover:bg-[#0f2438] text-white font-bold text-center flex items-center justify-center gap-2">
                                    <i data-lucide="file-warning" class="w-4 h-4 text-[#3187D8]"></i>
                                    <span>Open Related Incident</span>
                                </a>
                            </template>
                            <template x-if="activeMedia?.geo_metadata?.latitude">
                                <a :href="'/admin/map?focus_evidence=' + activeMedia.id + '&lat=' + activeMedia.geo_metadata.latitude + '&lng=' + activeMedia.geo_metadata.longitude"
                                   class="w-full py-2 px-4 rounded-xl bg-[#3187D8] hover:bg-[#256fb4] text-white font-bold text-center flex items-center justify-center gap-2">
                                    <i data-lucide="map" class="w-4 h-4"></i>
                                    <span>Open Evidence on Municipal Map</span>
                                </a>
                            </template>
                            <a :href="downloadUrl"
                               class="w-full py-2 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-center flex items-center justify-center gap-2">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                <span>Download Preserved Original</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function evidenceViewerConsole() {
            return {
                modalOpen: false,
                activeMedia: null,
                streamUrl: '',
                downloadUrl: '',
                formattedSize: '',
                integrityVerified: true,

                init() {
                    if (window.gsap && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        window.gsap.from('.evidence-card', {
                            y: 16,
                            opacity: 0,
                            duration: 0.4,
                            stagger: 0.05,
                            ease: 'power2.out',
                            clearProps: 'transform,opacity',
                        });
                    }
                },

                async openModal(mediaId) {
                    this.modalOpen = true;
                    this.activeMedia = null;
                    try {
                        const res = await fetch('/admin/media/' + mediaId, {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (res.ok) {
                            const data = await res.json();
                            this.activeMedia = data.media;
                            this.streamUrl = data.stream_url;
                            this.downloadUrl = data.download_url;
                            this.formattedSize = data.formatted_size;
                            this.integrityVerified = data.integrity_verified;

                            this.$nextTick(() => {
                                if (window.refreshIcons) window.refreshIcons();
                                if (window.gsap && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                                    window.gsap.fromTo('.evidence-modal-panel', { scale: 0.96, opacity: 0 }, { scale: 1, opacity: 1, duration: 0.25, ease: 'power2.out' });
                                }
                            });
                        }
                    } catch (e) {
                        console.error('Failed to load evidence details:', e);
                    }
                },

                closeModal() {
                    this.modalOpen = false;
                    this.activeMedia = null;
                }
            };
        }
    </script>
</x-layouts.admin>

