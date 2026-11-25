<x-layouts.office :title="'Incident #'. $incident->reference_number .' — Office Desk'">
    <div class="space-y-6 max-w-5xl" x-data="{ rejectModalOpen: false }">

        {{-- Breadcrumb & Title --}}
        <div>
            <div class="flex items-center gap-2 text-xs text-[var(--text-muted)] mb-1">
                <a href="{{ route('office.incidents') }}" class="hover:text-[#E87524] font-medium transition-colors">Agency Incident Monitoring</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-[var(--text-primary)] font-bold font-mono">{{ $incident->reference_number }}</span>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-[var(--text-primary)]">
                        Incident #{{ $incident->reference_number }}
                    </h1>
                    <p class="text-xs text-[var(--text-secondary)] mt-0.5">
                        Emergency incident record under {{ $office->name }}.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('office.messages.incident', $incident->reference_number) }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#17191C] hover:bg-[#292D32] text-white text-xs font-bold transition-colors shadow-xs border border-white/10">
                        <i data-lucide="message-square" class="w-4 h-4 text-[#E87524]"></i>
                        Message Admin About This Incident
                    </a>
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase
                        {{ $incident->priority === 'critical' ? 'bg-[#C83F43] text-white' : 'bg-[#D99024]/15 text-[#9A5D07] dark:text-[#F6BA53] border border-[#D99024]/30' }}">
                        {{ ucfirst($incident->priority) }} Priority
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase
                        {{ $incident->status === 'pending_review' ? 'bg-[#D99024]/15 text-[#9A5D07] dark:text-[#F6BA53]' : 'bg-[#397BA8]/15 text-[#25597E] dark:text-[#7BB5DB]' }}">
                        {{ str_replace('_', ' ', strtoupper($incident->status)) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="p-4 rounded-xl bg-[#27845B]/12 border border-[#27845B]/30 text-[#1B6644] dark:text-[#5BD19A] text-xs font-medium flex items-center gap-3">
                <i data-lucide="check-circle" class="w-4 h-4 text-[#27845B] dark:text-[#5BD19A] shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 rounded-xl bg-[#C83F43]/12 border border-[#C83F43]/30 text-[#9F292D] dark:text-[#F27D80] text-xs font-medium flex items-center gap-3">
                <i data-lucide="alert-octagon" class="w-4 h-4 text-[#C83F43] dark:text-[#F27D80] shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Incident Review Panel (Office Admin Authorization Only) --}}
        @if($officeUser?->office_level === 'office_admin' && $officeUser?->can_review_incidents && $incident->status === 'pending_review')
            <div class="p-6 rounded-2xl bg-[#D99024]/10 border border-[#D99024]/35 shadow-xs">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-[#D99024]/25">
                    <div class="flex items-center gap-2">
                        <i data-lucide="shield-alert" class="w-5 h-5 text-[#B94F12] dark:text-[#F6BA53]"></i>
                        <h3 class="text-sm font-bold text-[#8A5409] dark:text-[#F6BA53] uppercase tracking-wider">Office Intake &amp; Review Action</h3>
                    </div>
                    <span class="text-xs font-bold text-[#B94F12] dark:text-[#F29A56]">Authorized Commander Role</span>
                </div>
                <p class="text-xs text-[var(--text-secondary)] mb-4">
                    As an authorized Office Admin, you may verify and accept this incoming incident for response coordination, or decline with a documented justification.
                </p>

                <div class="flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('office.incidents.review', $incident->reference_number) }}">
                        @csrf
                        <input type="hidden" name="action" value="accept">
                        <input type="hidden" name="review_remarks" value="Accepted by {{ $user->name }} ({{ $office->code }} Command).">
                        <button type="submit" :disabled="!$store.pwa.isOnline" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-[#27845B] hover:bg-[#1E6A49] disabled:opacity-40 disabled:cursor-not-allowed text-white shadow-xs flex items-center gap-1.5 transition-all cursor-pointer">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Accept Incident for Agency</span>
                        </button>
                    </form>

                    <button type="button" 
                            @click="rejectModalOpen = true"
                            :disabled="!$store.pwa.isOnline"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold bg-[#C83F43] hover:bg-[#A83135] disabled:opacity-40 disabled:cursor-not-allowed text-white shadow-xs flex items-center gap-1.5 transition-all cursor-pointer">
                        <i data-lucide="x" class="w-4 h-4"></i>
                        <span>Reject Report</span>
                    </button>
                </div>
            </div>
        @endif

        {{-- Main Incident Details Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- 2 Columns: Description, Location & Media --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Incident Overview --}}
                <div class="alerto-card p-6 space-y-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider">Incident Overview</h3>
                    <p class="text-xs leading-relaxed bg-[#F5F3EF] dark:bg-white/5 p-4 rounded-xl border border-[#E6E3DE] dark:border-white/10">
                        {{ $incident->description }}
                    </p>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs pt-2">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-[#77736E] block">Category</span>
                            <span class="font-bold">{{ $incident->category?->name }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-[#77736E] block">Scene Barangay</span>
                            <span class="font-bold">{{ $incident->barangay?->name }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-[#77736E] block">Street Location</span>
                            <span class="font-bold">{{ $incident->location?->street_address ?? $incident->location?->street_name ?? 'Coordinates On File' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Spatial Coordinates & GPS --}}
                <div class="alerto-card p-6">
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-[#E6E3DE] dark:border-white/10">
                        <h3 class="text-sm font-bold uppercase tracking-wider">Scene Coordinates</h3>
                        @if($incident->location)
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $incident->location->latitude }},{{ $incident->location->longitude }}"
                               target="_blank"
                               class="text-xs font-bold text-[#B94F12] dark:text-[#F29A56] hover:underline inline-flex items-center gap-1">
                                <span>Open Google Maps</span>
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                            </a>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-xs font-mono">
                        <div class="p-3 rounded-xl bg-[#F5F3EF] dark:bg-white/5 border border-[#E6E3DE] dark:border-white/10">
                            <span class="text-[10px] font-bold uppercase text-[#77736E] block">Latitude</span>
                            <span class="text-sm font-bold">{{ $incident->location?->latitude ?? '9.4975' }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-[#F5F3EF] dark:bg-white/5 border border-[#E6E3DE] dark:border-white/10">
                            <span class="text-[10px] font-bold uppercase text-[#77736E] block">Longitude</span>
                            <span class="text-sm font-bold">{{ $incident->location?->longitude ?? '122.5850' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Attached Evidence Media --}}
                @if($incident->media->isNotEmpty())
                    <div class="alerto-card p-6">
                        <h3 class="text-sm font-bold uppercase tracking-wider mb-3">Geo-Tagged Media &amp; Photos</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @foreach($incident->media as $evidence)
                                @if($evidence->media)
                                    @php
                                        $m = $evidence->media;
                                        $g = $m->geoMetadata;
                                    @endphp
                                    <div class="rounded-xl overflow-hidden border border-[#E6E3DE] dark:border-white/10 bg-[#F5F3EF] dark:bg-white/5">
                                        @if($m->isVideo())
                                            <video controls class="w-full h-32 object-cover bg-[#101214]">
                                                <source src="{{ route('media.stream', $m->id) }}" type="{{ $m->mime_type }}">
                                            </video>
                                        @else
                                            <a href="{{ route('media.stream', $m->id) }}" target="_blank">
                                                <img src="{{ route('media.stream', $m->id) }}" alt="{{ $m->file_name }}" class="w-full h-32 object-cover">
                                            </a>
                                        @endif
                                        <div class="p-2 text-[10px] bg-white dark:bg-[#17191C]">
                                            <div class="truncate font-semibold">{{ $m->file_name }}</div>
                                            @if($g && $g->hasCoordinates())
                                                <div class="font-mono text-[#27845B] text-[9px]">
                                                    {{ number_format((float)$g->latitude, 5) }}, {{ number_format((float)$g->longitude, 5) }}
                                                </div>
                                            @else
                                                <div class="text-[#77736E] text-[9px] font-mono">LOCATION UNAVAILABLE</div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- 1 Column: Reporter & Status History --}}
            <div class="space-y-6">

                {{-- Citizen Reporter Information --}}
                <div class="alerto-card p-6 space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#77736E]">Citizen Reporter</h3>
                    <div class="text-xs">
                        <div class="font-bold text-sm">{{ $incident->caller_name ?? 'Protected Citizen' }}</div>
                        <div class="font-mono mt-1">
                            <span class="text-amber-700 dark:text-amber-200">Contact calls are disabled in this demo.</span>
                        </div>
                    </div>
                </div>

                {{-- Status Timeline --}}
                <div class="alerto-card p-6">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#77736E] mb-3">Status Timeline</h3>
                    <div class="space-y-3 border-l-2 border-[#E87524]/40 ml-2 pl-3">
                        @foreach($incident->statusHistories as $history)
                            <div class="text-xs">
                                <div class="font-bold uppercase font-mono text-[11px]">
                                    {{ str_replace('_', ' ', $history->new_status) }}
                                </div>
                                <div class="text-[10px] text-[#77736E] font-mono">
                                    {{ $history->created_at ? $history->created_at->format('M d, H:i') : '' }}
                                    by {{ $history->changedBy?->name ?? 'System' }}
                                </div>
                                @if($history->change_reason)
                                    <p class="text-[11px] text-[#77736E] mt-0.5">{{ $history->change_reason }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

        </div>

        {{-- Reject Modal --}}
        <div x-show="rejectModalOpen" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/65 backdrop-blur-xs"
             @keydown.escape.window="rejectModalOpen = false">
            <div class="w-full max-w-md alerto-card shadow-xl p-6" @click.away="rejectModalOpen = false">
                <h3 class="text-base font-bold mb-1">Reject Incident Report</h3>
                <p class="text-xs text-[#77736E] mb-4">A documented justification is mandatory when rejecting emergency reports.</p>

                <form method="POST" action="{{ route('office.incidents.review', $incident->reference_number) }}" class="space-y-3.5">
                    @csrf
                    <input type="hidden" name="action" value="reject">

                    <div>
                        <label class="block text-xs font-bold mb-1">Rejection Category <span class="text-[#C83F43]">*</span></label>
                        <select name="rejection_category" required class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                            <option value="">Select Category</option>
                            <option value="duplicate">Duplicate Report</option>
                            <option value="invalid_information">Invalid / Unverified Information</option>
                            <option value="out_of_jurisdiction">Outside Municipal Jurisdiction</option>
                            <option value="hoax_or_malicious">Hoax or Malicious Report</option>
                            <option value="unresponsive_caller">Unresponsive Caller / Cannot Confirm</option>
                            <option value="other">Other Legitimate Ground</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Mandatory Rejection Reason <span class="text-[#C83F43]">*</span></label>
                        <textarea name="rejection_reason" required rows="3" placeholder="Provide factual documented justification..."
                                  class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#E6E3DE] dark:border-white/10">
                        <button type="button" @click="rejectModalOpen = false" class="px-4 py-2 text-xs font-bold text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10 rounded-xl">
                            Cancel
                        </button>
                        <button type="submit" class="alerto-btn-press px-5 py-2 text-xs font-bold bg-[#C83F43] hover:bg-[#A63034] text-white rounded-xl shadow-xs">
                            Confirm Rejection
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-layouts.office>
