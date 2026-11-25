<x-layouts.office>
    <div class="space-y-6 max-w-5xl" x-data="{ rejectModalOpen: false }">

        {{-- Breadcrumb & Title --}}
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('office.incidents') }}" class="hover:text-[#173957] font-medium">Agency Incident Monitoring</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-slate-800 font-bold font-mono">{{ $incident->reference_number }}</span>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                        Incident #{{ $incident->reference_number }}
                    </h1>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Emergency incident record under {{ $office->name }}.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('office.messages.incident', $incident->reference_number) }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-[#173957] hover:bg-[#122e46] text-white text-xs font-bold transition-colors shadow-xs">
                        <i data-lucide="message-square" class="w-4 h-4 text-[#3187D8]"></i>
                        Message Admin About This Incident
                    </a>
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase
                        {{ $incident->priority === 'critical' ? 'bg-red-500 text-white' : 'bg-amber-100 text-amber-800' }}">
                        {{ ucfirst($incident->priority) }} Priority
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase
                        {{ $incident->status === 'pending_review' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                        {{ str_replace('_', ' ', strtoupper($incident->status)) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-3">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium flex items-center gap-3">
                <i data-lucide="alert-octagon" class="w-4 h-4 text-rose-600 shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Incident Review Panel (Office Admin Authorization Only) --}}
        @if($officeUser?->office_level === 'office_admin' && $officeUser?->can_review_incidents && $incident->status === 'pending_review')
            <div class="p-6 rounded-2xl bg-amber-50/70 border border-amber-200 shadow-xs">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-amber-200/80">
                    <div class="flex items-center gap-2">
                        <i data-lucide="shield-alert" class="w-5 h-5 text-amber-700"></i>
                        <h3 class="text-sm font-bold text-amber-900 uppercase tracking-wider">Office Intake &amp; Review Action</h3>
                    </div>
                    <span class="text-xs font-bold text-amber-800">Authorized Commander Role</span>
                </div>
                <p class="text-xs text-amber-800 mb-4">
                    As an authorized Office Admin, you may verify and accept this incoming incident for response coordination, or decline with a documented justification.
                </p>

                <div class="flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('office.incidents.review', $incident->reference_number) }}">
                        @csrf
                        <input type="hidden" name="action" value="accept">
                        <input type="hidden" name="review_remarks" value="Accepted by {{ $user->name }} ({{ $office->code }} Command).">
                        <button type="submit" :disabled="!$store.pwa.isOnline" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed text-white shadow-xs flex items-center gap-1.5 transition-all">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Accept Incident for Agency</span>
                        </button>
                    </form>

                    <button type="button" 
                            @click="rejectModalOpen = true"
                            :disabled="!$store.pwa.isOnline"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 disabled:opacity-40 disabled:cursor-not-allowed text-white shadow-xs flex items-center gap-1.5 transition-all">
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
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Incident Overview</h3>
                    <p class="text-xs text-slate-700 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-100">
                        {{ $incident->description }}
                    </p>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs pt-2">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Category</span>
                            <span class="font-bold text-slate-800">{{ $incident->category?->name }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Scene Barangay</span>
                            <span class="font-bold text-slate-800">{{ $incident->barangay?->name }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Street Location</span>
                            <span class="font-bold text-slate-800">{{ $incident->location?->street_address ?? $incident->location?->street_name ?? 'Coordinates On File' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Spatial Coordinates & GPS --}}
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Scene Coordinates</h3>
                        @if($incident->location)
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $incident->location->latitude }},{{ $incident->location->longitude }}" 
                               target="_blank" 
                               class="text-xs font-bold text-[#3187D8] hover:underline inline-flex items-center gap-1">
                                <span>Open Google Maps</span>
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                            </a>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-xs font-mono text-slate-700">
                        <div class="p-3 rounded-xl bg-slate-50">
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Latitude</span>
                            <span class="text-sm font-bold text-slate-900">{{ $incident->location?->latitude ?? '9.4975' }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50">
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Longitude</span>
                            <span class="text-sm font-bold text-slate-900">{{ $incident->location?->longitude ?? '122.5850' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Attached Evidence Media --}}
                @if($incident->media->isNotEmpty())
                    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3">Geo-Tagged Media &amp; Photos</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @foreach($incident->media as $evidence)
                                @if($evidence->media)
                                    @php
                                        $m = $evidence->media;
                                        $g = $m->geoMetadata;
                                    @endphp
                                    <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-50">
                                        @if($m->isVideo())
                                            <video controls class="w-full h-32 object-cover bg-slate-900">
                                                <source src="{{ route('media.stream', $m->id) }}" type="{{ $m->mime_type }}">
                                            </video>
                                        @else
                                            <a href="{{ route('media.stream', $m->id) }}" target="_blank">
                                                <img src="{{ route('media.stream', $m->id) }}" alt="{{ $m->file_name }}" class="w-full h-32 object-cover">
                                            </a>
                                        @endif
                                        <div class="p-2 text-[10px] bg-white">
                                            <div class="truncate font-semibold text-slate-700">{{ $m->file_name }}</div>
                                            @if($g && $g->hasCoordinates())
                                                <div class="font-mono text-emerald-700 text-[9px]">
                                                    {{ number_format((float)$g->latitude, 5) }}, {{ number_format((float)$g->longitude, 5) }}
                                                </div>
                                            @else
                                                <div class="text-slate-400 text-[9px] font-mono">LOCATION UNAVAILABLE</div>
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
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Citizen Reporter</h3>
                    <div class="text-xs">
                        <div class="font-bold text-slate-900 text-sm">{{ $incident->caller_name ?? 'Protected Citizen' }}</div>
                        <div class="font-mono text-slate-600 mt-1">
                            @if($incident->caller_phone)
                                <a href="tel:{{ $incident->caller_phone }}" class="text-[#3187D8] font-bold hover:underline flex items-center gap-1">
                                    <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                                    <span>{{ $incident->caller_phone }}</span>
                                </a>
                            @else
                                <span class="text-slate-400 italic">No phone recorded</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Status Timeline --}}
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Status Timeline</h3>
                    <div class="space-y-3 border-l-2 border-slate-200 ml-2 pl-3">
                        @foreach($incident->statusHistories as $history)
                            <div class="text-xs">
                                <div class="font-bold text-slate-900 uppercase font-mono text-[11px]">
                                    {{ str_replace('_', ' ', $history->new_status) }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono">
                                    {{ $history->created_at ? $history->created_at->format('M d, H:i') : '' }}
                                    by {{ $history->changedBy?->name ?? 'System' }}
                                </div>
                                @if($history->change_reason)
                                    <p class="text-[11px] text-slate-600 mt-0.5">{{ $history->change_reason }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

        </div>

        {{-- Reject Modal --}}
        <div x-show="rejectModalOpen" x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
             @keydown.escape.window="rejectModalOpen = false">
            <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-6 border border-slate-200" @click.away="rejectModalOpen = false">
                <h3 class="text-base font-bold text-slate-900 mb-1">Reject Incident Report</h3>
                <p class="text-xs text-slate-500 mb-4">A documented justification is mandatory when rejecting emergency reports.</p>

                <form method="POST" action="{{ route('office.incidents.review', $incident->reference_number) }}" class="space-y-3.5">
                    @csrf
                    <input type="hidden" name="action" value="reject">

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Rejection Category <span class="text-rose-500">*</span></label>
                        <select name="rejection_category" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
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
                        <label class="block text-xs font-bold text-slate-700 mb-1">Mandatory Rejection Reason <span class="text-rose-500">*</span></label>
                        <textarea name="rejection_reason" required rows="3" placeholder="Provide factual documented justification..." 
                                  class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="rejectModalOpen = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white rounded-xl shadow-xs">
                            Confirm Rejection
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-layouts.office>
