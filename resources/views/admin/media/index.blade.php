<x-layouts.admin :title="'Centralized Media & Evidence Management — ALERTO EOC'">
    <div class="space-y-6" x-data="{ settingsModal: false, archiveModal: false, archiveTargetId: null, purgeModal: false, purgeTargetId: null }">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#173957]/10 text-[#173957] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="hard-drive" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                    ALERTO Geo-Tagged Media &amp; Evidence Management
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                    Centralized Media &amp; Evidence Governance
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Monitor storage consumption, processing queues, GPS coverage, upload limits, and chain-of-custody retention policies.
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="{{ route('admin.incidents.evidence') }}"
                   class="px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-800 hover:bg-slate-50 text-xs font-bold flex items-center gap-1.5">
                    <i data-lucide="images" class="w-4 h-4 text-[#3187D8]"></i>
                    <span>Evidence Viewer</span>
                </a>
                <button type="button" x-on:click="settingsModal = true"
                        class="px-4 py-2.5 rounded-xl bg-[#173957] hover:bg-[#0f2438] text-white text-xs font-bold flex items-center gap-2 shadow-xs">
                    <i data-lucide="sliders" class="w-4 h-4 text-[#3187D8]"></i>
                    <span>Upload &amp; Retention Policies</span>
                </button>
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-semibold flex items-center gap-2.5">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error') || $errors->any())
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs font-semibold">
                @if(session('error')) <div>{{ session('error') }}</div> @endif
                @foreach($errors->all() as $err) <div>{{ $err }}</div> @endforeach
            </div>
        @endif

        {{-- KPI Analytics Grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
            <div class="p-3.5 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
                <span class="text-[10px] font-bold uppercase text-slate-400 block">Photos</span>
                <p class="text-xl font-black text-slate-900 mt-1">{{ number_format($analytics['total_photos']) }}</p>
            </div>
            <div class="p-3.5 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
                <span class="text-[10px] font-bold uppercase text-slate-400 block">Videos</span>
                <p class="text-xl font-black text-slate-900 mt-1">{{ number_format($analytics['total_videos']) }}</p>
            </div>
            <div class="p-3.5 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
                <span class="text-[10px] font-bold uppercase text-slate-400 block">Documents</span>
                <p class="text-xl font-black text-slate-900 mt-1">{{ number_format($analytics['total_documents']) }}</p>
            </div>
            <div class="p-3.5 rounded-2xl bg-amber-50/70 border border-amber-200/80 shadow-xs">
                <span class="text-[10px] font-bold uppercase text-amber-700 block">Processing</span>
                <p class="text-xl font-black text-amber-900 mt-1">{{ number_format($analytics['pending_processing']) }}</p>
            </div>
            <div class="p-3.5 rounded-2xl bg-rose-50/70 border border-rose-200/80 shadow-xs">
                <span class="text-[10px] font-bold uppercase text-rose-700 block">Failed Queue</span>
                <p class="text-xl font-black text-rose-900 mt-1">{{ number_format($analytics['failed_processing']) }}</p>
            </div>
            <div class="p-3.5 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 shadow-xs">
                <span class="text-[10px] font-bold uppercase text-emerald-700 block">Geo-Tagged</span>
                <p class="text-xl font-black text-emerald-900 mt-1">{{ number_format($analytics['with_geotag']) }}</p>
            </div>
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 shadow-xs">
                <span class="text-[10px] font-bold uppercase text-slate-500 block">Missing GPS</span>
                <p class="text-xl font-black text-slate-800 mt-1">{{ number_format($analytics['missing_location']) }}</p>
            </div>
            <div class="p-3.5 rounded-2xl bg-[#173957] text-white shadow-xs">
                <span class="text-[10px] font-bold uppercase text-slate-300 block">Private Storage</span>
                <p class="text-lg font-black font-mono mt-1">{{ $analytics['storage_human'] }}</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
            <form method="GET" action="{{ route('admin.media.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 text-xs">
                <div>
                    <input type="text" name="incident_reference" value="{{ $filters['incident_reference'] ?? '' }}"
                           placeholder="Incident ref (INC-...)..."
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50/50">
                </div>
                <div>
                    <select name="source_channel" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                        <option value="all">All Upload Sources</option>
                        <option value="citizen_portal" {{ ($filters['source_channel'] ?? '') === 'citizen_portal' ? 'selected' : '' }}>Citizen Portal</option>
                        <option value="internal_messenger_personnel" {{ ($filters['source_channel'] ?? '') === 'internal_messenger_personnel' ? 'selected' : '' }}>Personnel Messenger</option>
                        <option value="internal_messenger_office" {{ ($filters['source_channel'] ?? '') === 'internal_messenger_office' ? 'selected' : '' }}>Office Messenger</option>
                        <option value="internal_messenger_admin" {{ ($filters['source_channel'] ?? '') === 'internal_messenger_admin' ? 'selected' : '' }}>Admin Messenger</option>
                        <option value="facebook_messenger" {{ ($filters['source_channel'] ?? '') === 'facebook_messenger' ? 'selected' : '' }}>Facebook Messenger</option>
                    </select>
                </div>
                <div>
                    <select name="media_type" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                        <option value="all">All Media Types</option>
                        <option value="photo" {{ ($filters['media_type'] ?? '') === 'photo' ? 'selected' : '' }}>Photo</option>
                        <option value="video" {{ ($filters['media_type'] ?? '') === 'video' ? 'selected' : '' }}>Video</option>
                        <option value="document" {{ ($filters['media_type'] ?? '') === 'document' ? 'selected' : '' }}>Document</option>
                    </select>
                </div>
                <div>
                    <select name="processing_status" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                        <option value="all">All Processing States</option>
                        <option value="uploading" {{ ($filters['processing_status'] ?? '') === 'uploading' ? 'selected' : '' }}>Uploading</option>
                        <option value="uploaded" {{ ($filters['processing_status'] ?? '') === 'uploaded' ? 'selected' : '' }}>Uploaded</option>
                        <option value="processing" {{ ($filters['processing_status'] ?? '') === 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="ready" {{ ($filters['processing_status'] ?? '') === 'ready' ? 'selected' : '' }}>Ready</option>
                        <option value="failed" {{ ($filters['processing_status'] ?? '') === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div>
                    <select name="archived" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                        <option value="active" {{ ($filters['archived'] ?? 'active') === 'active' ? 'selected' : '' }}>Active Operational Media</option>
                        <option value="archived" {{ ($filters['archived'] ?? '') === 'archived' ? 'selected' : '' }}>Archived Evidence Only</option>
                        <option value="all" {{ ($filters['archived'] ?? '') === 'all' ? 'selected' : '' }}>All Including Archived</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 py-2 px-3 rounded-xl bg-[#3187D8] hover:bg-[#256fb4] text-white font-bold">
                        Filter
                    </button>
                    <a href="{{ route('admin.media.index') }}" class="p-2 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                </div>
            </form>
        </div>

        {{-- Media Registry Table --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-3 px-4">ID &amp; File</th>
                            <th class="py-3 px-4">Type &amp; Source</th>
                            <th class="py-3 px-4">Linked Incident / Uploader</th>
                            <th class="py-3 px-4">Geo Provenance</th>
                            <th class="py-3 px-4">Processing State</th>
                            <th class="py-3 px-4">Integrity &amp; Custody</th>
                            <th class="py-3 px-4 text-right">Governance Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($mediaItems as $media)
                            @php
                                $geo = $media->geoMetadata;
                                $incident = $media->incident ?? $media->evidenceRecords->first()?->incident;
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900 truncate max-w-[200px]">{{ $media->file_name }}</div>
                                    <div class="font-mono text-[10px] text-slate-400">
                                        #{{ $media->id }} · {{ $media->formattedSize() }} · {{ $media->created_at?->format('M d, H:i') }}
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 font-bold uppercase text-[10px]">
                                        {{ $media->media_type }}
                                    </span>
                                    <div class="text-[10px] text-slate-500 font-mono mt-1">
                                        {{ $media->source_channel }}
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    @if($incident)
                                        <a href="{{ route('admin.incidents.show', $incident->reference_number) }}" class="font-mono font-bold text-[#3187D8] hover:underline block">
                                            {{ $incident->reference_number }}
                                        </a>
                                    @else
                                        <span class="text-slate-400 italic block">Messenger / Standalone</span>
                                    @endif
                                    <span class="text-[10px] text-slate-600">{{ $media->uploader?->name ?? 'External Citizen' }} ({{ $media->uploader_account_type }})</span>
                                </td>
                                <td class="py-3 px-4">
                                    @if($geo && $geo->hasCoordinates())
                                        <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-mono text-[9px] font-bold">
                                            {{ $geo->location_source }}
                                        </span>
                                        <div class="font-mono text-[10px] text-slate-600 mt-1">
                                            {{ number_format((float)$geo->latitude, 5) }}, {{ number_format((float)$geo->longitude, 5) }}
                                        </div>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-500 font-mono text-[9px]">
                                            LOCATION_UNAVAILABLE
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    @php
                                        $procBadge = match($media->processing_status) {
                                            'ready' => 'bg-emerald-100 text-emerald-800',
                                            'failed' => 'bg-rose-100 text-rose-800',
                                            'processing' => 'bg-blue-100 text-blue-800',
                                            default => 'bg-amber-100 text-amber-800',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $procBadge }}">
                                        {{ $media->processing_status }}
                                    </span>
                                    @if($media->processing_error)
                                        <div class="text-[9px] text-rose-600 truncate max-w-[140px] mt-0.5" title="{{ $media->processing_error }}">
                                            {{ $media->processing_error }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-mono text-[10px]">
                                    <div class="text-slate-500">SHA: {{ substr($media->checksum_sha256, 0, 12) }}...</div>
                                    @if($media->is_locked_evidence)
                                        <span class="text-amber-700 font-bold">🔒 Locked Evidence</span>
                                    @endif
                                    @if($media->is_archived)
                                        <span class="text-slate-500 font-bold block">📦 Archived</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap space-x-1.5">
                                    <a href="{{ route('media.download', $media->id) }}"
                                       class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-[10px]">
                                        Download
                                    </a>

                                    @if($media->processing_status === 'failed' || $media->processing_status === 'uploaded')
                                        <form method="POST" action="{{ route('admin.media.retry', $media->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-blue-100 hover:bg-blue-200 text-blue-800 font-bold text-[10px]">
                                                Retry Queue
                                            </button>
                                        </form>
                                    @endif

                                    @if(! $media->is_archived)
                                        <button type="button" x-on:click="archiveTargetId = {{ $media->id }}; archiveModal = true"
                                                class="px-2.5 py-1 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-800 font-bold text-[10px]">
                                            Archive
                                        </button>
                                    @else
                                        <button type="button" x-on:click="purgeTargetId = {{ $media->id }}; purgeModal = true"
                                                class="px-2.5 py-1 rounded-lg bg-rose-100 hover:bg-rose-200 text-rose-800 font-bold text-[10px]">
                                            Retention Purge
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center text-slate-400">
                                    No centralized media attachments found matching current filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($mediaItems->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $mediaItems->links() }}
                </div>
            @endif
        </div>

        {{-- Upload & Retention Policy Settings Modal --}}
        <div x-show="settingsModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200" @click.outside="settingsModal = false">
                <div class="p-5 bg-[#173957] text-white flex items-center justify-between">
                    <h3 class="font-black text-sm">Configurable Upload &amp; Retention Policies</h3>
                    <button type="button" x-on:click="settingsModal = false"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>

                <form method="POST" action="{{ route('admin.media.settings') }}" class="p-5 space-y-3 text-xs">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Max Photo Size (MB)</label>
                            <input type="number" name="max_photo_size_mb" value="{{ $policyConfig['max_photo_size_mb'] }}" required min="1" max="100" class="w-full px-3 py-2 rounded-xl border border-slate-300">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Max Video Size (MB)</label>
                            <input type="number" name="max_video_size_mb" value="{{ $policyConfig['max_video_size_mb'] }}" required min="1" max="500" class="w-full px-3 py-2 rounded-xl border border-slate-300">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Max Document Size (MB)</label>
                            <input type="number" name="max_document_size_mb" value="{{ $policyConfig['max_document_size_mb'] }}" required min="1" max="100" class="w-full px-3 py-2 rounded-xl border border-slate-300">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Max Attachments / Report</label>
                            <input type="number" name="max_upload_count" value="{{ $policyConfig['max_upload_count'] }}" required min="1" max="20" class="w-full px-3 py-2 rounded-xl border border-slate-300">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Allowed Photo Formats</label>
                        <input type="text" name="allowed_photo_types" value="{{ implode(',', $policyConfig['allowed_photo_types']) }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Allowed Video Formats</label>
                        <input type="text" name="allowed_video_types" value="{{ implode(',', $policyConfig['allowed_video_types']) }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Allowed Document Formats</label>
                        <input type="text" name="allowed_document_types" value="{{ implode(',', $policyConfig['allowed_document_types']) }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Evidence Retention Window (Days)</label>
                        <input type="number" name="retention_days" value="{{ $policyConfig['retention_days'] }}" required min="30" max="3650" class="w-full px-3 py-2 rounded-xl border border-slate-300">
                    </div>

                    <div class="flex flex-col gap-2 pt-2">
                        <label class="flex items-center gap-2 font-semibold text-slate-700">
                            <input type="checkbox" name="auto_generate_thumbnails" value="1" {{ $policyConfig['auto_generate_thumbnails'] ? 'checked' : '' }} class="rounded text-[#3187D8]">
                            <span>Enable Asynchronous Thumbnail &amp; Preview Generation</span>
                        </label>
                        <label class="flex items-center gap-2 font-semibold text-slate-700">
                            <input type="checkbox" name="malware_scan_enabled" value="1" {{ $policyConfig['malware_scan_enabled'] ? 'checked' : '' }} class="rounded text-[#3187D8]">
                            <span>Enable External Malware Scanner Hook (ClamAV)</span>
                        </label>
                    </div>

                    <div class="pt-3 flex justify-end gap-2">
                        <button type="button" x-on:click="settingsModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-[#3187D8] text-white font-bold">Save Policies</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Audited Archive Modal --}}
        <div x-show="archiveModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-5 shadow-2xl space-y-4 text-xs" @click.outside="archiveModal = false">
                <h3 class="font-black text-sm text-slate-900">Audited Evidence Archival</h3>
                <p class="text-slate-600">Operational evidence cannot be deleted outright. Archiving preserves original binary checksums and records your audit trail.</p>
                <form method="POST" :action="'/admin/media/' + archiveTargetId + '/archive'" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Archival Reason *</label>
                        <textarea name="reason" rows="2" required minlength="5" placeholder="e.g. Case concluded and archived per LDRRMO retention policy."
                                  class="w-full px-3 py-2 rounded-xl border border-slate-300"></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" x-on:click="archiveModal = false" class="px-3.5 py-2 rounded-xl border border-slate-200 font-bold">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-amber-600 text-white font-bold">Archive Evidence</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Audited Retention Purge Modal --}}
        <div x-show="purgeModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-5 shadow-2xl space-y-4 text-xs" @click.outside="purgeModal = false">
                <h3 class="font-black text-sm text-rose-800">Formal Retention Disposal Authorization</h3>
                <p class="text-slate-600">Only archived media attached to closed/resolved/rejected cases may be formally purged under retention governance.</p>
                <form method="POST" :action="'/admin/media/' + purgeTargetId + '/purge'" class="space-y-3">
                    @csrf
                    @method('DELETE')
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Official Disposal Justification (Min 10 chars) *</label>
                        <textarea name="justification" rows="2" required minlength="10" placeholder="Enter official retention authorization ticket..."
                                  class="w-full px-3 py-2 rounded-xl border border-slate-300"></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" x-on:click="purgeModal = false" class="px-3.5 py-2 rounded-xl border border-slate-200 font-bold">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 text-white font-bold">Authorize Disposal</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-layouts.admin>

