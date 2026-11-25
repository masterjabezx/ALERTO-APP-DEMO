<x-layouts.admin :title="'Evidence Viewer — ALERTO EOC'">
    <div class="space-y-6" x-data="evidenceViewerConsole()">

        {{-- Top Bar --}}
        <x-portal.page-header
            badge="Incident Management · Forensic Evidence Viewer"
            title="Geo-Tagged Incident Evidence Viewer"
            subtitle="Inspect all authorized incident photographs, response videos, EXIF capture metadata, and verified GPS origins."
        >
            <x-slot:actions>
                <a href="{{ route('admin.incidents.index') }}"
                   class="px-3.5 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#292D32] hover:bg-[#F5F3EF] dark:hover:bg-white/10 text-xs font-bold flex items-center gap-1.5 transition-colors">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Incident Queue</span>
                </a>
                <a href="{{ route('admin.media.index') }}"
                   class="alerto-btn-press px-3.5 py-2 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white text-xs font-bold flex items-center gap-1.5 shadow-xs transition-colors">
                    <i data-lucide="hard-drive" class="w-3.5 h-3.5"></i>
                    <span>Media Governance Console</span>
                </a>
            </x-slot:actions>
        </x-portal.page-header>

        {{-- Advanced Evidence Filter Bar --}}
        <div class="alerto-card p-4 sm:p-5">
            <form method="GET" action="{{ route('admin.incidents.evidence') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 text-xs">
                <div class="lg:col-span-2 relative">
                    <i data-lucide="search" class="w-4 h-4 text-[#77736E] absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="incident_reference" value="{{ $filters['incident_reference'] ?? '' }}"
                           placeholder="Search incident ref (INC-...), filename, uploader..."
                           class="w-full pl-9 pr-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-[#F5F3EF] dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                </div>

                <div>
                    <select name="category_id" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select name="barangay_id" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                        <option value="">All Barangays</option>
                        @foreach($barangays as $brgy)
                            <option value="{{ $brgy->id }}" {{ ($filters['barangay_id'] ?? '') == $brgy->id ? 'selected' : '' }}>{{ $brgy->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select name="media_type" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                        <option value="all">Photos &amp; Videos</option>
                        <option value="photo" {{ ($filters['media_type'] ?? '') === 'photo' ? 'selected' : '' }}>Photographs Only</option>
                        <option value="video" {{ ($filters['media_type'] ?? '') === 'video' ? 'selected' : '' }}>Videos Only</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="alerto-btn-press flex-1 py-2 px-3 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white font-bold flex items-center justify-center gap-1.5 transition-colors">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        <span>Apply</span>
                    </button>
                    <a href="{{ route('admin.incidents.evidence') }}" class="p-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10" title="Clear Filters">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                </div>

                <div>
                    <select name="uploader_id" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                        <option value="">All Uploaders</option>
                        @foreach($uploaders as $u)
                            <option value="{{ $u->id }}" {{ ($filters['uploader_id'] ?? '') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ ucfirst($u->account_type) }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select name="location_source" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                        <option value="all">All GPS Sources</option>
                        @foreach($locationSources as $src)
                            <option value="{{ $src }}" {{ ($filters['location_source'] ?? '') === $src ? 'selected' : '' }}>{{ $src }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]" title="Date From">
                </div>

                <div>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]" title="Date To">
                </div>
            </form>
        </div>

        {{-- Evidence Cards Grid --}}
        @if($evidenceItems->isEmpty())
            <x-empty-state
                icon="camera-off"
                title="No Evidence Matches Filter Criteria"
                description="Adjust search terms, date window, or media filters."
            />
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 evidence-grid">
                @foreach($evidenceItems as $item)
                    @php
                        $geo = $item->geoMetadata;
                        $incident = $item->incident ?? $item->evidenceRecords->first()?->incident;
                        $hasCoords = $geo && $geo->hasCoordinates();
                        $locSource = $geo?->location_source ?? 'LOCATION_UNAVAILABLE';
                    @endphp
                    <div class="evidence-card alerto-interactive-card alerto-card overflow-hidden flex flex-col justify-between">
                        {{-- Media Thumbnail Preview --}}
                        <div class="relative aspect-video bg-[#101214] overflow-hidden group cursor-pointer"
                             x-on:click="openModal({{ $item->id }})">
                            @if($item->isPhoto())
                                <img src="{{ route('media.thumbnail', $item->id) }}" alt="{{ $item->file_name }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center text-white bg-[#101214]">
                                    <i data-lucide="play-circle" class="w-12 h-12 text-[#E87524] mb-1"></i>
                                    <span class="text-[11px] font-mono text-[#CFCBC4]">
                                        VIDEO {{ $item->duration_seconds ? '· '.number_format((float)$item->duration_seconds, 1).'s' : '' }}
                                    </span>
                                </div>
                            @endif

                            {{-- Top Badges --}}
                            <div class="absolute top-2.5 left-2.5 flex items-center gap-1.5">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $item->isVideo() ? 'bg-[#E87524] text-white' : 'bg-[#17191C]/90 text-white border border-white/15' }}">
                                    {{ strtoupper($item->media_type) }}
                                </span>
                                <span class="px-2 py-0.5 rounded-md bg-black/75 text-white text-[10px] font-mono">
                                    {{ str_replace('_', ' ', strtoupper($item->source_channel)) }}
                                </span>
                            </div>

                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold gap-1.5">
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
                                           class="font-mono font-black text-[#B94F12] dark:text-[#F29A56] hover:text-[#E87524]">
                                            {{ $incident->reference_number }}
                                        </a>
                                    @else
                                        <span class="font-mono text-[#77736E]">Unlinked Media #{{ $item->id }}</span>
                                    @endif

                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase {{ $hasCoords ? 'bg-[#27845B]/15 text-[#27845B]' : 'bg-black/5 dark:bg-white/10 text-[#77736E]' }}">
                                        {{ $locSource }}
                                    </span>
                                </div>

                                <div class="text-[11px] font-semibold truncate" title="{{ $item->file_name }}">
                                    {{ $item->file_name }}
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-[10px] bg-[#F5F3EF] dark:bg-white/5 p-2.5 rounded-xl border border-[#E6E3DE] dark:border-white/10">
                                    <div>
                                        <span class="text-[#77736E] block uppercase font-bold">Sender</span>
                                        <span class="font-bold truncate block">{{ $item->uploader?->name ?? 'External Citizen' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-[#77736E] block uppercase font-bold">Barangay</span>
                                        <span class="font-bold truncate block">{{ $geo?->barangay?->name ?? $incident?->barangay?->name ?? 'Pending GIS' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-[#77736E] block uppercase font-bold">Captured</span>
                                        <span class="font-mono block">{{ $geo?->captured_at?->format('M d, H:i') ?? 'N/A' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-[#77736E] block uppercase font-bold">Uploaded</span>
                                        <span class="font-mono block">{{ $item->created_at?->format('M d, H:i') }}</span>
                                    </div>
                                </div>

                                @if($hasCoords)
                                    <div class="flex items-center justify-between text-[10px] font-mono text-[#27845B] bg-[#27845B]/10 px-2.5 py-1.5 rounded-lg border border-[#27845B]/25">
                                        <span>{{ number_format((float)$geo->latitude, 5) }}, {{ number_format((float)$geo->longitude, 5) }}</span>
                                        <span>{{ $geo->accuracy_meters ? '±'.round((float)$geo->accuracy_meters).'m' : 'Exact' }}</span>
                                    </div>
                                @else
                                    <div class="text-[10px] font-mono text-[#77736E] bg-[#F5F3EF] dark:bg-white/5 px-2.5 py-1.5 rounded-lg border border-[#E6E3DE] dark:border-white/10">
                                        GPS Coordinates Unavailable
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 pt-2 border-t border-[#E6E3DE] dark:border-white/10">
                                <button type="button" x-on:click="openModal({{ $item->id }})"
                                        class="alerto-btn-press flex-1 py-1.5 px-3 rounded-lg bg-[#17191C] hover:bg-[#292D32] text-white font-bold text-[11px] flex items-center justify-center gap-1 border border-white/10">
                                    <i data-lucide="eye" class="w-3.5 h-3.5 text-[#E87524]"></i>
                                    <span>Inspect</span>
                                </button>
                                @if($hasCoords)
                                    <a href="{{ route('admin.map', ['focus_evidence' => $item->id, 'lat' => $geo->latitude, 'lng' => $geo->longitude]) }}"
                                       class="alerto-btn-press py-1.5 px-3 rounded-lg bg-[#E87524] hover:bg-[#B94F12] text-white font-bold text-[11px] flex items-center gap-1">
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
                <div class="alerto-card p-4">
                    {{ $evidenceItems->links() }}
                </div>
            @endif
        @endif

        {{-- Evidence Lightbox & Forensic Metadata Modal --}}
        <div x-show="modalOpen" x-cloak
             class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
             x-transition.opacity>
            <div class="evidence-modal-panel alerto-card max-w-4xl w-full max-h-[92vh] overflow-hidden shadow-2xl flex flex-col"
                 @click.outside="closeModal()">
                <div class="p-4 sm:p-5 bg-[#101214] text-white flex items-center justify-between border-b border-white/10">
                    <div>
                        <h3 class="font-black text-sm sm:text-base" x-text="activeMedia ? activeMedia.file_name : 'Loading Evidence...'"></h3>
                        <p class="text-[11px] text-[#9E9993] font-mono" x-text="activeMedia ? ('Channel: ' + activeMedia.source_channel + ' · SHA256 Verified: ' + (integrityVerified ? 'YES' : 'MISMATCH')) : ''"></p>
                    </div>
                    <button type="button" x-on:click="closeModal()" class="p-1.5 rounded-lg hover:bg-white/10 text-[#9E9993] hover:text-white">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto grid grid-cols-1 lg:grid-cols-12" x-show="activeMedia">
                    {{-- Left: Full Media Preview --}}
                    <div class="lg:col-span-7 bg-[#101214] flex items-center justify-center p-4 min-h-[300px]">
                        <template x-if="activeMedia && activeMedia.media_type === 'photo'">
                            <img :src="streamUrl" class="max-h-[65vh] w-auto object-contain rounded-xl shadow-lg" alt="Full Evidence">
                        </template>
                        <template x-if="activeMedia && activeMedia.media_type === 'video'">
                            <video controls class="max-h-[65vh] w-full rounded-xl shadow-lg bg-black" :src="streamUrl"></video>
                        </template>
                    </div>

                    {{-- Right: Metadata Ledger --}}
                    <div class="lg:col-span-5 p-5 space-y-4 text-xs overflow-y-auto">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-[#77736E] block">Geographic Provenance</span>
                            <span class="inline-block mt-1 px-2.5 py-1 rounded-lg bg-[#27845B]/15 text-[#27845B] font-mono font-bold text-[11px]"
                                  x-text="activeMedia?.geo_metadata?.location_source || 'LOCATION_UNAVAILABLE'"></span>
                        </div>

                        <div class="p-3 rounded-xl bg-[#F5F3EF] dark:bg-white/5 border border-[#E6E3DE] dark:border-white/10 space-y-1.5 font-mono text-[11px]">
                            <div class="flex justify-between">
                                <span class="text-[#77736E]">Primary Coords:</span>
                                <span class="font-bold" x-text="activeMedia?.geo_metadata?.latitude ? (activeMedia.geo_metadata.latitude + ', ' + activeMedia.geo_metadata.longitude) : 'Unavailable'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#77736E]">EXIF Capture GPS:</span>
                                <span x-text="activeMedia?.geo_metadata?.exif_latitude ? (activeMedia.geo_metadata.exif_latitude + ', ' + activeMedia.geo_metadata.exif_longitude) : 'None'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#77736E]">Upload Location:</span>
                                <span x-text="activeMedia?.geo_metadata?.upload_latitude ? (activeMedia.geo_metadata.upload_latitude + ', ' + activeMedia.geo_metadata.upload_longitude) : 'None'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#77736E]">Inherited Coords:</span>
                                <span x-text="activeMedia?.geo_metadata?.inherited_latitude ? (activeMedia.geo_metadata.inherited_latitude + ', ' + activeMedia.geo_metadata.inherited_longitude) : 'None'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#77736E]">Accuracy:</span>
                                <span x-text="activeMedia?.geo_metadata?.accuracy_meters ? ('±' + activeMedia.geo_metadata.accuracy_meters + 'm') : 'N/A'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#77736E]">Barangay:</span>
                                <span class="font-bold" x-text="activeMedia?.geo_metadata?.barangay?.name || activeMedia?.incident?.barangay?.name || 'N/A'"></span>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-[#F5F3EF] dark:bg-white/5 border border-[#E6E3DE] dark:border-white/10 space-y-1.5 text-[11px]">
                            <div class="flex justify-between">
                                <span class="text-[#77736E]">Uploader:</span>
                                <span class="font-bold" x-text="activeMedia?.uploader?.name || 'External / Citizen'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#77736E]">Camera Device:</span>
                                <span class="font-mono" x-text="(activeMedia?.geo_metadata?.camera_make || '') + ' ' + (activeMedia?.geo_metadata?.camera_model || 'N/A')"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#77736E]">File Size / Type:</span>
                                <span class="font-mono" x-text="formattedSize + ' · ' + (activeMedia?.mime_type || '')"></span>
                            </div>
                            <div>
                                <span class="text-[#77736E] block">SHA-256 Hash:</span>
                                <span class="font-mono text-[10px] break-all" x-text="activeMedia?.checksum_sha256"></span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 pt-2">
                            <template x-if="activeMedia?.incident">
                                <a :href="'/admin/incidents/' + activeMedia.incident.reference_number"
                                   class="alerto-btn-press w-full py-2.5 px-4 rounded-xl bg-[#17191C] hover:bg-[#292D32] text-white font-bold text-center flex items-center justify-center gap-2 border border-white/10">
                                    <i data-lucide="file-warning" class="w-4 h-4 text-[#E87524]"></i>
                                    <span>Open Related Incident</span>
                                </a>
                            </template>
                            <template x-if="activeMedia?.geo_metadata?.latitude">
                                <a :href="'/admin/map?focus_evidence=' + activeMedia.id + '&lat=' + activeMedia.geo_metadata.latitude + '&lng=' + activeMedia.geo_metadata.longitude"
                                   class="alerto-btn-press w-full py-2 px-4 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white font-bold text-center flex items-center justify-center gap-2">
                                    <i data-lucide="map" class="w-4 h-4"></i>
                                    <span>Open Evidence on Municipal Map</span>
                                </a>
                            </template>
                            <a :href="downloadUrl"
                               class="w-full py-2 px-4 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-[#F5F3EF] dark:bg-white/10 hover:bg-[#E6E3DE] dark:hover:bg-white/20 font-bold text-center flex items-center justify-center gap-2">
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
