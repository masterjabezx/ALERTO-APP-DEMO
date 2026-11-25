<x-layouts.citizen>
    <div class="space-y-6">

        {{-- Breadcrumb Navigation --}}
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
            <a href="{{ route('citizen.records') }}" class="hover:text-slate-800 transition-colors flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back to Records</span>
            </a>
            <span>/</span>
            <span class="font-mono text-slate-700">{{ $incident->reference_number }}</span>
        </div>

        {{-- Main Incident Overview Card --}}
        <div class="bg-white rounded-[24px] p-6 sm:p-8 border border-slate-200/80 shadow-[0_4px_20px_-2px_rgba(23,57,87,0.06)] space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-[#173957]/10 text-[#173957] flex items-center justify-center flex-shrink-0">
                        <i data-lucide="{{ $incident->category?->icon ?? 'alert-triangle' }}" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <span class="text-xs font-mono font-bold text-slate-400 block">{{ $incident->reference_number }}</span>
                        <h1 class="text-xl font-extrabold text-slate-900">{{ $incident->category?->name ?? 'Emergency Incident' }}</h1>
                    </div>
                </div>

                <x-badge :variant="match($incident->status) {
                    'resolved', 'closed' => 'success',
                    'dispatched', 'on_scene' => 'info',
                    'pending_review', 'submitted' => 'warning',
                    'rejected' => 'emergency',
                    default => 'neutral'
                }" size="md" :dot="true">
                    {{ ucfirst(str_replace('_', ' ', $incident->status)) }}
                </x-badge>
            </div>

            {{-- Key Details Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-slate-400 font-medium block">Barangay Location</span>
                    <span class="font-bold text-slate-800 text-sm mt-0.5 block">{{ $incident->barangay?->name ?? 'Hinoba-an' }}</span>
                    @if($incident->location?->landmark)
                        <span class="text-[11px] text-slate-500 block mt-0.5">{{ $incident->location->landmark }}</span>
                    @endif
                </div>

                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-slate-400 font-medium block">Date &amp; Time Reported</span>
                    <span class="font-bold text-slate-800 text-sm mt-0.5 block">{{ $incident->submitted_at?->format('M d, Y · h:i A') }}</span>
                    <span class="text-[11px] text-slate-500 block mt-0.5">{{ $incident->submitted_at?->diffForHumans() }}</span>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-slate-400 font-medium block">Verified Contact</span>
                    <span class="font-bold text-slate-800 text-sm mt-0.5 block">{{ $incident->caller_phone }}</span>
                    <span class="text-[11px] text-slate-500 block mt-0.5">{{ $incident->caller_name }}</span>
                </div>
            </div>

            @if($incident->description)
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Incident Description</h3>
                    <p class="text-sm text-slate-600 bg-slate-50 p-4 rounded-xl border border-slate-100 leading-relaxed">
                        {{ $incident->description }}
                    </p>
                </div>
            @endif

            {{-- Location Map Visualizer --}}
            @if($incident->location)
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Location Coordinates</h3>
                    <div class="p-4 rounded-2xl bg-[#0f2438] text-white flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i data-lucide="map-pin" class="w-5 h-5 text-[#DB4545]"></i>
                            <span class="font-mono text-xs">{{ $incident->location->latitude }}° N, {{ $incident->location->longitude }}° E</span>
                        </div>
                        <span class="text-[10px] uppercase font-bold text-emerald-400 bg-white/10 px-2 py-0.5 rounded-md">
                            {{ str_replace('_', ' ', $incident->location->location_source) }}
                        </span>
                    </div>
                </div>
            @endif

            {{-- Attached Media & Evidence with Geotags --}}
            <div class="pt-2 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">
                        Submitted Photos &amp; Videos ({{ $incident->evidenceRecords->count() }})
                    </h3>
                    @if($incident->hasLockedEvidence())
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold">
                            <i data-lucide="lock" class="w-3 h-3 text-slate-500"></i>
                            Official Evidence Locked
                        </span>
                    @endif
                </div>

                @if($incident->evidenceRecords->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @foreach($incident->evidenceRecords as $evidence)
                            @if($evidence->media)
                                @php
                                    $media = $evidence->media;
                                    $geo = $media->geoMetadata;
                                    $hasCoords = $geo && $geo->hasCoordinates();
                                @endphp
                                <div class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 flex flex-col">
                                    <div class="aspect-video bg-slate-900 relative overflow-hidden">
                                        @if($media->isVideo())
                                            <video controls class="w-full h-full object-cover">
                                                <source src="{{ route('media.stream', $media->id) }}" type="{{ $media->mime_type }}">
                                            </video>
                                        @else
                                            <a href="{{ route('media.stream', $media->id) }}" target="_blank" class="block w-full h-full">
                                                <img src="{{ route('media.stream', $media->id) }}"
                                                     alt="{{ $media->file_name }}"
                                                     class="w-full h-full object-cover">
                                            </a>
                                        @endif
                                    </div>
                                    <div class="p-2.5 text-[10px] space-y-1 bg-white">
                                        <div class="font-semibold text-slate-800 truncate">{{ $media->file_name }}</div>
                                        @if($hasCoords)
                                            <div class="font-mono text-emerald-700 font-bold">
                                                GPS: {{ number_format((float)$geo->latitude, 5) }}, {{ number_format((float)$geo->longitude, 5) }}
                                            </div>
                                            <div class="text-[9px] text-slate-400 uppercase font-mono">
                                                {{ str_replace('_', ' ', $geo->location_source ?? $geo->gps_source) }}
                                            </div>
                                        @else
                                            <div class="text-slate-400 font-mono uppercase text-[9px]">
                                                LOCATION UNAVAILABLE
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-400 italic">No photos or videos attached to this report.</p>
                @endif

                @if(! $incident->hasLockedEvidence())
                    <form method="POST" action="{{ route('citizen.records.evidence.add', $incident->reference_number) }}" enctype="multipart/form-data"
                          class="p-4 rounded-2xl bg-slate-50 border border-dashed border-slate-300 space-y-3">
                        @csrf
                        <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                            <i data-lucide="plus-circle" class="w-4 h-4 text-[#3187D8]"></i>
                            <span>Add Supplementary Photo or Video (Before Review Acceptance)</span>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input type="file" name="attachment" accept="image/*,video/*" required
                                   class="flex-1 text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#173957] file:text-white">
                            <button type="submit" class="px-4 py-2 rounded-xl bg-[#3187D8] hover:bg-[#256fb4] text-white text-xs font-bold">
                                Upload Evidence
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            {{-- Status Timeline History --}}
            <div class="pt-4 border-t border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-4">Official Response Status History</h3>
                <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                    @forelse($incident->statusHistories->sortByDesc('created_at') as $history)
                        <div class="relative">
                            <span class="absolute -left-6 top-1 w-2.5 h-2.5 rounded-full bg-[#173957] ring-4 ring-white"></span>
                            <div class="text-xs">
                                <span class="font-bold text-slate-900 uppercase tracking-wide">
                                    {{ ucfirst(str_replace('_', ' ', $history->new_status)) }}
                                </span>
                                <span class="text-slate-400 text-[10px] block mt-0.5 font-mono">
                                    {{ $history->created_at?->format('M d, Y · h:i A') }}
                                </span>
                                @if($history->change_reason)
                                    <p class="text-slate-600 mt-1 leading-relaxed">{{ $history->change_reason }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">No status changes recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</x-layouts.citizen>

