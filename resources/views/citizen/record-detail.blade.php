<x-layouts.citizen>
    <div class="space-y-6">

        {{-- Breadcrumb Navigation --}}
        <div class="flex items-center gap-2 text-xs font-semibold text-[var(--text-muted)]">
            <a href="{{ route('citizen.records') }}" class="hover:text-[#E87524] transition-colors flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back to Records</span>
            </a>
            <span>/</span>
            <span class="font-mono text-[var(--text-primary)]">{{ $incident->reference_number }}</span>
        </div>

        {{-- Main Incident Overview Card --}}
        <div class="alerto-card p-6 sm:p-8 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-[var(--border-subtle)] gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] border border-[#E87524]/25 flex items-center justify-center shrink-0">
                        <i data-lucide="{{ $incident->category?->icon ?? 'alert-triangle' }}" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <span class="text-xs font-mono font-bold text-[var(--text-muted)] block">{{ $incident->reference_number }}</span>
                        <h1 class="text-xl font-extrabold text-[var(--text-primary)]">{{ $incident->category?->name ?? 'Emergency Incident' }}</h1>
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
                <div class="p-3.5 rounded-xl bg-[var(--surface-panel)] border border-[var(--border-subtle)]">
                    <span class="text-[var(--text-muted)] font-medium block">Barangay Location</span>
                    <span class="font-bold text-[var(--text-primary)] text-sm mt-0.5 block">{{ $incident->barangay?->name ?? 'Hinoba-an' }}</span>
                    @if($incident->location?->landmark)
                        <span class="text-[11px] text-[var(--text-secondary)] block mt-0.5">{{ $incident->location->landmark }}</span>
                    @endif
                </div>

                <div class="p-3.5 rounded-xl bg-[var(--surface-panel)] border border-[var(--border-subtle)]">
                    <span class="text-[var(--text-muted)] font-medium block">Date &amp; Time Reported</span>
                    <span class="font-bold text-[var(--text-primary)] text-sm mt-0.5 block">{{ $incident->submitted_at?->format('M d, Y · h:i A') }}</span>
                    <span class="text-[11px] text-[var(--text-secondary)] block mt-0.5">{{ $incident->submitted_at?->diffForHumans() }}</span>
                </div>

                <div class="p-3.5 rounded-xl bg-[var(--surface-panel)] border border-[var(--border-subtle)]">
                    <span class="text-[var(--text-muted)] font-medium block">Verified Contact</span>
                    <span class="font-bold text-[var(--text-primary)] text-sm mt-0.5 block">{{ $incident->caller_phone }}</span>
                    <span class="text-[11px] text-[var(--text-secondary)] block mt-0.5">{{ $incident->caller_name }}</span>
                </div>
            </div>

            @if($incident->description)
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[var(--text-secondary)] mb-1">Incident Description</h3>
                    <p class="text-sm text-[var(--text-primary)] bg-[var(--surface-panel)] p-4 rounded-xl border border-[var(--border-subtle)] leading-relaxed">
                        {{ $incident->description }}
                    </p>
                </div>
            @endif

            {{-- Location Map Visualizer --}}
            @if($incident->location)
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[var(--text-secondary)] mb-2">Location Coordinates</h3>
                    <div class="p-4 rounded-2xl bg-[#17191C] text-[#F5F3EF] border border-white/10 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <i data-lucide="map-pin" class="w-5 h-5 text-[#C83F43]"></i>
                            <span class="font-mono text-xs">{{ $incident->location->latitude }}° N, {{ $incident->location->longitude }}° E</span>
                        </div>
                        <span class="text-[10px] uppercase font-bold text-[#5BD19A] bg-[#27845B]/20 border border-[#27845B]/35 px-2.5 py-0.5 rounded-md">
                            {{ str_replace('_', ' ', $incident->location->location_source) }}
                        </span>
                    </div>
                </div>
            @endif

            {{-- Attached Media & Evidence with Geotags --}}
            <div class="pt-2 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[var(--text-secondary)]">
                        Submitted Photos &amp; Videos ({{ $incident->evidenceRecords->count() }})
                    </h3>
                    @if($incident->hasLockedEvidence())
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[var(--surface-panel)] border border-[var(--border-subtle)] text-[var(--text-secondary)] text-[10px] font-bold">
                            <i data-lucide="lock" class="w-3 h-3 text-[var(--text-muted)]"></i>
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
                                <div class="rounded-2xl overflow-hidden border border-[var(--border-subtle)] bg-[var(--surface-panel)] flex flex-col">
                                    <div class="aspect-video bg-black relative overflow-hidden">
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
                                    <div class="p-2.5 text-[10px] space-y-1 bg-[var(--surface-card)]">
                                        <div class="font-semibold text-[var(--text-primary)] truncate">{{ $media->file_name }}</div>
                                        @if($hasCoords)
                                            <div class="font-mono text-[#27845B] dark:text-[#5BD19A] font-bold">
                                                GPS: {{ number_format((float)$geo->latitude, 5) }}, {{ number_format((float)$geo->longitude, 5) }}
                                            </div>
                                            <div class="text-[9px] text-[var(--text-muted)] uppercase font-mono">
                                                {{ str_replace('_', ' ', $geo->location_source ?? $geo->gps_source) }}
                                            </div>
                                        @else
                                            <div class="text-[var(--text-muted)] font-mono uppercase text-[9px]">
                                                LOCATION UNAVAILABLE
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-[var(--text-muted)] italic">No photos or videos attached to this report.</p>
                @endif

                @if(! $incident->hasLockedEvidence())
                    <form method="POST" action="{{ route('citizen.records.evidence.add', $incident->reference_number) }}" enctype="multipart/form-data"
                          class="p-4 rounded-2xl bg-[var(--surface-panel)] border border-dashed border-[var(--border-strong)] space-y-3">
                        @csrf
                        <div class="text-xs font-bold text-[var(--text-primary)] flex items-center gap-1.5">
                            <i data-lucide="plus-circle" class="w-4 h-4 text-[#E87524]"></i>
                            <span>Add Supplementary Photo or Video (Before Review Acceptance)</span>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input type="file" name="attachment" accept="image/*,video/*" required
                                   class="flex-1 text-xs text-[var(--text-secondary)] file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#17191C] file:text-white cursor-pointer">
                            <button type="submit" class="px-4 py-2 rounded-xl bg-[#E87524] hover:bg-[#D16419] text-white text-xs font-bold cursor-pointer transition-colors">
                                Upload Evidence
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            {{-- Status Timeline History --}}
            <div class="pt-4 border-t border-[var(--border-subtle)]">
                <h3 class="text-xs font-bold uppercase tracking-wider text-[var(--text-secondary)] mb-4">Official Response Status History</h3>
                <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-[var(--border-subtle)]">
                    @forelse($incident->statusHistories->sortByDesc('created_at') as $history)
                        <div class="relative">
                            <span class="absolute -left-6 top-1 w-2.5 h-2.5 rounded-full bg-[#E87524] ring-4 ring-[var(--surface-card)]"></span>
                            <div class="text-xs">
                                <span class="font-bold text-[var(--text-primary)] uppercase tracking-wide">
                                    {{ ucfirst(str_replace('_', ' ', $history->new_status)) }}
                                </span>
                                <span class="text-[var(--text-muted)] text-[10px] block mt-0.5 font-mono">
                                    {{ $history->created_at?->format('M d, Y · h:i A') }}
                                </span>
                                @if($history->change_reason)
                                    <p class="text-[var(--text-secondary)] mt-1 leading-relaxed">{{ $history->change_reason }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-[var(--text-muted)]">No status changes recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</x-layouts.citizen>
