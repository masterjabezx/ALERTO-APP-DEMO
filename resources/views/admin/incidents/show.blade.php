<x-layouts.admin>
    <div class="space-y-6" x-data="{ 
        showAcceptModal: false, 
        showRejectModal: false, 
        showOverrideModal: false, 
        showDispatchModal: false,
        showAssignModal: false,
        showContactModal: false,
        selectedOfficeId: '{{ $routing['recommended_office']?->id ?? '' }}'
    }">

        {{-- Breadcrumb & Top Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.incidents.index') }}" class="p-2 rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-mono font-bold text-slate-500 uppercase">{{ $incident->source_channel }}</span>
                        <span class="text-slate-300">·</span>
                        <span class="text-xs text-slate-500 font-mono">INCIDENT ID #{{ $incident->id }}</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 font-mono tracking-tight">
                        {{ $incident->reference_number }}
                    </h1>
                </div>
            </div>

            {{-- Status & Quick Action Badges --}}
            <div class="flex flex-wrap items-center gap-2.5">
                @php
                    $statusColors = [
                        'pending_review' => 'bg-amber-100 text-amber-900 border-amber-300',
                        'accepted' => 'bg-blue-100 text-blue-900 border-blue-300',
                        'dispatched' => 'bg-purple-100 text-purple-900 border-purple-300',
                        'acknowledged' => 'bg-indigo-100 text-indigo-900 border-indigo-300',
                        'on_the_way' => 'bg-cyan-100 text-cyan-900 border-cyan-300',
                        'on_scene' => 'bg-emerald-100 text-emerald-900 border-emerald-300',
                        'in_progress' => 'bg-teal-100 text-teal-900 border-teal-300',
                        'resolved' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
                        'closed' => 'bg-slate-100 text-slate-800 border-slate-300',
                        'rejected' => 'bg-rose-100 text-rose-900 border-rose-300',
                    ];
                @endphp
                <div class="px-3 py-1.5 rounded-xl border text-xs font-bold uppercase tracking-wider {{ $statusColors[$incident->status] ?? 'bg-slate-100 text-slate-800' }}">
                    STATUS: {{ strtoupper(str_replace('_', ' ', $incident->status)) }}
                </div>

                {{-- Priority Dropdown --}}
                @if(auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('admin.incidents.priority', $incident->reference_number) }}" class="inline-flex">
                        @csrf
                        <select name="priority" onchange="this.form.submit()" class="px-3 py-1.5 rounded-xl border border-slate-300 text-xs font-bold uppercase bg-white cursor-pointer hover:border-slate-400 focus:ring-2 focus:ring-[#3187D8]">
                            <option value="" {{ !$incident->priority ? 'selected' : '' }} disabled>⏳ Priority Pending Assessment</option>
                            <option value="critical" {{ $incident->priority === 'critical' ? 'selected' : '' }}>🔴 Critical</option>
                            <option value="high" {{ $incident->priority === 'high' ? 'selected' : '' }}>🟠 High</option>
                            <option value="medium" {{ $incident->priority === 'medium' ? 'selected' : '' }}>🔵 Medium</option>
                            <option value="low" {{ $incident->priority === 'low' ? 'selected' : '' }}>⚪ Low</option>
                        </select>
                    </form>
                @else
                    <span class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-bold uppercase bg-white">
                        {{ strtoupper($incident->priority ?? 'UNASSIGNED') }} PRIORITY
                    </span>
                @endif

                <a href="{{ route('admin.messages.incident', $incident->reference_number) }}"
                   class="px-3.5 py-1.5 rounded-xl bg-[#173957] hover:bg-[#122e46] text-white text-xs font-bold transition-colors inline-flex items-center gap-1.5 shadow-xs">
                    <i data-lucide="messages-square" class="w-4 h-4 text-[#3187D8]"></i>
                    Open Incident Chat
                </a>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-semibold flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 flex-shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error') || $errors->any())
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs font-semibold flex items-center gap-3">
                <i data-lucide="alert-octagon" class="w-5 h-5 text-rose-600 flex-shrink-0"></i>
                <div>
                    @if(session('error')) <div>{{ session('error') }}</div> @endif
                    @foreach($errors->all() as $err) <div>{{ $err }}</div> @endforeach
                </div>
            </div>
        @endif

        @if(session('warning'))
            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-300 text-amber-900 text-xs font-semibold flex items-center gap-3">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 flex-shrink-0"></i>
                <span>{{ session('warning') }}</span>
            </div>
        @endif
        @if($incident->has_assignment_warning && $incident->assignment_warning_message)
            <div class="p-4 rounded-2xl bg-amber-100 border-2 border-amber-400 text-amber-950 text-xs font-bold flex items-center gap-3">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-700 shrink-0"></i>
                <span>DEPLOYMENT WARNING: {{ $incident->assignment_warning_message }}</span>
            </div>
        @endif
        @if($incident->processedByAdmin && (int)$incident->processed_by_admin_id !== (int)auth()->id())
            <div class="p-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 text-xs font-semibold">
                Concurrency Notice: This report is currently assigned/viewed by <strong>{{ $incident->processedByAdmin->name }}</strong>.
                @if(auth()->user()->isSuperAdmin()) (You hold Super Admin override authority.) @endif
            </div>
        @endif

        {{-- CALL VERIFICATION & STRUCTURED EMERGENCY ASSESSMENT WORKSPACE --}}
        <div class="bg-white rounded-2xl p-6 border-2 border-[#3187D8]/30 shadow-md space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 text-[10px] font-black uppercase">
                            STAGE: {{ strtoupper(str_replace('_', ' ', $incident->workflow_stage ?? 'new_report')) }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[10px] font-bold uppercase">
                            ASSESSMENT: {{ strtoupper(str_replace('_', ' ', $incident->assessment_status ?? 'unassessed')) }}
                        </span>
                    </div>
                    <h2 class="text-lg sm:text-xl font-black text-slate-900 mt-1">
                        DIAL DAPH Call Verification &amp; Emergency Assessment
                    </h2>
                    <p class="text-xs text-slate-500">
                        Call caller <strong>{{ $incident->caller_name }}</strong> (<span class="font-mono font-bold text-slate-800">{{ $incident->caller_phone }}</span>), verify incident reality manually, classify below, and deploy automated response.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <form method="POST" action="{{ route('admin.incidents.call-attempt', $incident->reference_number) }}"
                          @if($callerTelHref) onsubmit="setTimeout(() => { window.location.href = @js($callerTelHref); }, 150);" @endif>
                        @csrf
                        <button type="submit"
                                class="px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs shadow-lg shadow-emerald-600/25 inline-flex items-center gap-2 cursor-pointer">
                            <i data-lucide="phone-call" class="w-4 h-4"></i>
                            <span>CALL CITIZEN NOW ({{ $incident->caller_phone }})</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                {{-- Left: Record Call Outcome & Call Attempt Log --}}
                <div class="lg:col-span-4 space-y-4">
                    <form method="POST" action="{{ route('admin.incidents.call-outcome', $incident->reference_number) }}"
                          class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3 text-xs">
                        @csrf
                        <h3 class="font-black text-slate-800 uppercase tracking-wider">Record Call Outcome</h3>
                        <select name="call_outcome" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                            <option value="answered">Answered — Spoken to Caller</option>
                            <option value="no_answer">No Answer</option>
                            <option value="busy">Line Busy</option>
                            <option value="callback_required">Callback Required</option>
                            <option value="invalid_number">Invalid / Unreachable Number</option>
                        </select>
                        <textarea name="verification_notes" rows="2" placeholder="Operator manual call notes (never auto-transcribed)..."
                                  class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white"></textarea>
                        <div class="flex flex-col gap-1.5 text-[11px]">
                            <label class="inline-flex items-center gap-2"><input type="checkbox" name="location_confirmed" value="1" class="rounded"> Caller confirmed location pin</label>
                            <label class="inline-flex items-center gap-2"><input type="checkbox" name="category_confirmed" value="1" class="rounded"> Caller described emergency</label>
                            <label class="inline-flex items-center gap-2"><input type="checkbox" name="mark_number_verified" value="1" class="rounded"> Verify caller phone via call</label>
                        </div>
                        <button type="submit" class="w-full py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold cursor-pointer">
                            Save Call Attempt Result
                        </button>
                    </form>

                    <div class="space-y-1.5 text-xs">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Call Attempt Log ({{ $incident->callAttempts->count() }})</span>
                        @forelse($incident->callAttempts->take(5) as $attempt)
                            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between gap-2 text-[11px]">
                                <div>
                                    <span class="font-bold uppercase">{{ str_replace('_', ' ', $attempt->call_outcome) }}</span>
                                    <span class="text-slate-500 block">by {{ $attempt->initiator?->name }}</span>
                                </div>
                                <span class="font-mono text-slate-400">{{ $attempt->initiated_at?->format('H:i') }}</span>
                            </div>
                        @empty
                            <p class="text-[11px] text-slate-400 italic">No calls logged yet.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Right: Structured Emergency Assessment Form --}}
                <form method="POST" action="{{ route('admin.incidents.assess-deploy', $incident->reference_number) }}"
                      class="lg:col-span-8 space-y-4 text-xs"
                      x-data="{ countsUnknown: {{ old('casualty_counts_unknown', $incident->assessment?->casualty_counts_unknown) ? 'true' : 'false' }} }">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Emergency Category *</label>
                            <select name="category_id" required class="w-full px-3 py-2.5 rounded-xl border border-slate-300 bg-white font-bold">
                                <option value="">-- Select Official Emergency Category --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id', $incident->assessment?->category_id ?? $incident->category_id) == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }} (Default: {{ strtoupper($cat->default_priority) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Assessed Priority Level *</label>
                            <select name="priority" required class="w-full px-3 py-2.5 rounded-xl border border-slate-300 bg-white font-bold">
                                @foreach(['critical' => 'CRITICAL — Immediate Life Threat', 'high' => 'HIGH — Urgent Response', 'medium' => 'MEDIUM — Standard Emergency', 'low' => 'LOW — Advisory / Support'] as $pVal => $pLabel)
                                    <option value="{{ $pVal }}" {{ old('priority', $incident->assessment?->priority ?? $incident->priority ?? 'high') === $pVal ? 'selected' : '' }}>
                                        {{ $pLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Verified Barangay (Confirmed by Call / Map)</label>
                            <select name="verified_barangay_id" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                                <option value="">Unverified / Out-of-Polygon</option>
                                @foreach($barangays as $b)
                                    <option value="{{ $b->id }}" {{ old('verified_barangay_id', $incident->assessment?->verified_barangay_id ?? $incident->barangay_id) == $b->id ? 'selected' : '' }}>
                                        {{ $b->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Landmark / Access Details</label>
                            <input type="text" name="landmark_details" value="{{ old('landmark_details', $incident->assessment?->landmark_details ?? $incident->location?->landmark) }}"
                                   placeholder="Near market, bridge, purok..." class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Verified Situation Description *</label>
                        <textarea name="incident_description" rows="2" required placeholder="Verified incident facts obtained during call..."
                                  class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">{{ old('incident_description', $incident->assessment?->incident_description ?? $incident->description) }}</textarea>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-700 uppercase text-[11px]">Casualty &amp; Involvement Estimates</span>
                            <label class="inline-flex items-center gap-1.5 text-[11px] font-bold text-amber-700 cursor-pointer">
                                <input type="checkbox" name="casualty_counts_unknown" value="1" x-model="countsUnknown" class="rounded">
                                Exact Counts Unknown Yet
                            </label>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2" :class="countsUnknown ? 'opacity-40 pointer-events-none' : ''">
                            <div>
                                <label class="block text-[10px] text-slate-500">People Involved</label>
                                <input type="number" min="0" name="people_involved_count" value="{{ old('people_involved_count', $incident->assessment?->people_involved_count) }}" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500">Injured</label>
                                <input type="number" min="0" name="injured_count" value="{{ old('injured_count', $incident->assessment?->injured_count) }}" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500">Needing Rescue</label>
                                <input type="number" min="0" name="needing_rescue_count" value="{{ old('needing_rescue_count', $incident->assessment?->needing_rescue_count) }}" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500">Fatalities</label>
                                <input type="number" min="0" name="fatalities_count" value="{{ old('fatalities_count', $incident->assessment?->fatalities_count) }}" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Additional Supporting Offices (Optional)</label>
                            <div class="max-h-24 overflow-y-auto border border-slate-200 rounded-xl p-2 space-y-1 bg-slate-50">
                                @foreach($offices as $off)
                                    <label class="flex items-center gap-2 text-[11px]">
                                        <input type="checkbox" name="required_office_ids[]" value="{{ $off->id }}"
                                               {{ in_array($off->id, (array) old('required_office_ids', $incident->assessment?->required_office_ids ?? []), true) ? 'checked' : '' }} class="rounded">
                                        <span>{{ $off->name }} ({{ $off->code }})</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Manual Dispatch Override Reason (Only if no routing rule / unit offline)</label>
                            <textarea name="manual_override_reason" rows="3" placeholder="Optional override justification if manually deploying office without available online unit..."
                                      class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white"></textarea>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 text-[11px] font-semibold text-slate-700">
                        <label class="inline-flex items-center gap-1.5"><input type="checkbox" name="location_confirmed" value="1" checked class="rounded"> Location Confirmed</label>
                        <label class="inline-flex items-center gap-1.5"><input type="checkbox" name="category_confirmed" value="1" checked class="rounded"> Classification Verified</label>
                        <label class="inline-flex items-center gap-1.5"><input type="checkbox" name="mark_number_verified" value="1" class="rounded"> Mark Caller Phone Verified</label>
                    </div>

                    <button type="submit"
                            class="w-full py-3.5 rounded-2xl bg-[#DB4545] hover:bg-[#c43838] text-white font-black text-sm shadow-lg shadow-[#DB4545]/25 cursor-pointer">
                        CONFIRM ASSESSMENT &amp; DEPLOY RESPONSE
                    </button>
                </form>
            </div>
        </div>

        {{-- Main Grid: Left Details (7 cols) + Right Operations Panel (5 cols) --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            {{-- LEFT COLUMN: Details, Location, Media, History --}}
            <div class="lg:col-span-7 space-y-6">

                {{-- 1. Incident Overview Card --}}
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase bg-[#173957]/10 text-[#173957]">
                                {{ $incident->category?->name ?? 'Unclassified Emergency' }}
                            </span>
                            <h2 class="text-lg font-bold text-slate-900 mt-2">Emergency Description</h2>
                        </div>
                        <div class="text-right text-xs text-slate-400">
                            <div>Submitted: {{ $incident->submitted_at?->format('M d, Y · H:i') }}</div>
                            <div class="font-mono text-[10px]">{{ $incident->submitted_at?->diffForHumans() }}</div>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 text-slate-700 text-xs leading-relaxed whitespace-pre-line font-sans">
                        {{ $incident->description ?: 'No additional notes provided by reporter.' }}
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2 text-xs">
                        <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100">
                            <span class="text-[10px] font-bold text-slate-400 uppercase block">Barangay</span>
                            <span class="font-bold text-slate-800">{{ $incident->barangay?->name ?? 'Unverified Location' }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100">
                            <span class="text-[10px] font-bold text-slate-400 uppercase block">Municipality</span>
                            <span class="font-bold text-slate-800">Hinoba-an, Neg. Occ.</span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100">
                            <span class="text-[10px] font-bold text-slate-400 uppercase block">Source</span>
                            <span class="font-bold text-slate-800 uppercase font-mono text-[11px]">{{ str_replace('_', ' ', $incident->source_channel) }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100">
                            <span class="text-[10px] font-bold text-slate-400 uppercase block">Version Lock</span>
                            <span class="font-bold text-slate-800 font-mono">v{{ $incident->version_lock }}</span>
                        </div>
                    </div>
                </div>

                {{-- 2. Location & Spatial Intelligence Card --}}
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 flex items-center gap-2">
                            <i data-lucide="map-pin" class="w-4 h-4 text-[#3187D8]"></i>
                            Location &amp; Coordinates
                        </h2>
                        @php
                            $sourceStyles = [
                                'gps_device' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                'map_picker' => 'bg-amber-100 text-amber-800 border-amber-200',
                                'fb_shared_location' => 'bg-blue-100 text-blue-800 border-blue-200',
                                'admin_entered' => 'bg-slate-100 text-slate-800 border-slate-200',
                            ];
                        @endphp
                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase border {{ $sourceStyles[$incident->location?->location_source ?? 'map_picker'] ?? 'bg-slate-100 text-slate-800' }}">
                            SOURCE: {{ strtoupper(str_replace('_', ' ', $incident->location?->location_source ?? 'UNKNOWN')) }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] font-bold text-slate-400 uppercase block">Latitude</span>
                            <span class="font-mono font-bold text-slate-800">{{ $incident->location?->latitude ?? 'N/A' }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] font-bold text-slate-400 uppercase block">Longitude</span>
                            <span class="font-mono font-bold text-slate-800">{{ $incident->location?->longitude ?? 'N/A' }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] font-bold text-slate-400 uppercase block">GPS Accuracy</span>
                            <span class="font-mono font-bold text-slate-800">
                                {{ $incident->location?->accuracy ? number_format((float)$incident->location->accuracy, 1) . ' m' : 'N/A' }}
                            </span>
                        </div>
                    </div>

                    @if($incident->location?->landmark || $incident->location?->street_address)
                        <div class="text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <strong>Landmark / Street:</strong> {{ $incident->location->landmark ?? $incident->location->street_address }}
                        </div>
                    @endif

                    {{-- Map Container Visualizer --}}
                    <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-900 text-white relative h-48 flex items-center justify-center">
                        <div class="absolute inset-0 opacity-40 bg-[radial-gradient(#3187D8_1px,transparent_1px)] [background-size:16px_16px]"></div>
                        <div class="relative z-10 text-center p-4">
                            <i data-lucide="map-pin" class="w-8 h-8 text-[#DB4545] mx-auto animate-bounce"></i>
                            <p class="font-mono text-xs font-bold text-slate-200 mt-2">
                                Lat: {{ $incident->location?->latitude }} · Lng: {{ $incident->location?->longitude }}
                            </p>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                {{ $incident->barangay?->name }}, Hinoba-an Local GIS Coordinate
                            </p>
                            <a href="https://www.google.com/maps?q={{ $incident->location?->latitude }},{{ $incident->location?->longitude }}" 
                               target="_blank" rel="noopener noreferrer" 
                               class="inline-flex items-center gap-1.5 mt-3 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white text-[11px] font-bold transition-colors">
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                <span>Open External Map</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 3. Uploaded Evidence & Geo-Tagged Media --}}
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 flex items-center gap-2">
                            <i data-lucide="camera" class="w-4 h-4 text-[#3187D8]"></i>
                            Evidence &amp; Media Attachments ({{ $incident->evidenceRecords->count() }})
                        </h2>
                        <a href="{{ route('admin.incidents.evidence', ['incident_reference' => $incident->reference_number]) }}"
                           class="text-xs font-bold text-[#3187D8] hover:underline flex items-center gap-1">
                            <span>Open in Evidence Viewer</span>
                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>

                    @if($incident->evidenceRecords->isEmpty())
                        <div class="p-8 text-center text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200 text-xs">
                            <i data-lucide="image-off" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                            No photographic or video evidence attached with this intake report.
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach($incident->evidenceRecords as $evidence)
                                @php 
                                    $media = $evidence->media; 
                                    $geo = $media?->geoMetadata;
                                    $isImg = $media?->isPhoto() ?? false;
                                    $hasCoords = $geo && $geo->hasCoordinates();
                                @endphp
                                @if($media)
                                    <div class="rounded-xl border border-slate-200 overflow-hidden bg-slate-50 flex flex-col justify-between">
                                        <div class="p-2">
                                            @if($isImg)
                                                <a href="{{ route('media.stream', $media->id) }}" target="_blank" class="block aspect-video bg-slate-200 rounded-lg overflow-hidden relative group">
                                                    <img src="{{ route('media.stream', $media->id) }}" alt="Evidence" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold gap-1">
                                                        <i data-lucide="zoom-in" class="w-4 h-4"></i> View Full Size
                                                    </div>
                                                </a>
                                            @elseif($media->isVideo())
                                                <div class="aspect-video bg-slate-900 rounded-lg overflow-hidden">
                                                    <video controls class="w-full h-full object-contain">
                                                        <source src="{{ route('media.stream', $media->id) }}" type="{{ $media->mime_type }}">
                                                    </video>
                                                </div>
                                            @else
                                                <a href="{{ route('media.download', $media->id) }}" class="aspect-video bg-slate-800 rounded-lg flex flex-col items-center justify-center text-white gap-1">
                                                    <i data-lucide="file-text" class="w-8 h-8 text-slate-300"></i>
                                                    <span class="text-[11px] font-bold">Download Document</span>
                                                </a>
                                            @endif
                                        </div>

                                        <div class="p-3 text-[11px] space-y-1 bg-white border-t border-slate-100">
                                            <div class="flex items-center justify-between text-slate-500 gap-2">
                                                <span class="truncate font-mono font-bold text-slate-700">{{ $media->file_name }}</span>
                                                <span class="font-mono shrink-0">{{ $media->formattedSize() }}</span>
                                            </div>
                                            @if($hasCoords)
                                                <div class="text-[10px] text-emerald-700 font-mono flex items-center justify-between gap-1">
                                                    <span>Geo: {{ number_format((float)$geo->latitude, 6) }}, {{ number_format((float)$geo->longitude, 6) }}</span>
                                                    <a href="{{ route('admin.map', ['focus_evidence' => $media->id, 'lat' => $geo->latitude, 'lng' => $geo->longitude]) }}"
                                                       class="text-[#3187D8] font-bold hover:underline">Map</a>
                                                </div>
                                                <div class="text-[9px] text-slate-400 font-mono uppercase">
                                                    Source: {{ str_replace('_', ' ', $geo->location_source ?? $geo->gps_source) }}
                                                    @if($geo->accuracy_meters) · ±{{ number_format((float)$geo->accuracy_meters, 1) }}m @endif
                                                </div>
                                            @else
                                                <div class="text-[10px] text-slate-400 font-mono uppercase">
                                                    Location Unavailable
                                                </div>
                                            @endif
                                            <div class="flex items-center justify-between pt-1">
                                                <span class="text-[9px] text-slate-400 font-mono truncate" title="SHA256: {{ $media->checksum_sha256 }}">
                                                    SHA256: {{ substr($media->checksum_sha256, 0, 14) }}...
                                                </span>
                                                <a href="{{ route('media.download', $media->id) }}" class="text-[10px] font-bold text-[#3187D8] hover:underline">Download</a>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- 4. Status History & Audit Milestone Timeline --}}
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 flex items-center gap-2">
                        <i data-lucide="history" class="w-4 h-4 text-[#3187D8]"></i>
                        Verified Status Milestone Timeline
                    </h2>

                    <div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                        @foreach($incident->statusHistories->sortByDesc('created_at') as $history)
                            <div class="relative">
                                <div class="absolute -left-6 top-1 w-3 h-3 rounded-full bg-[#173957] border-2 border-white ring-2 ring-slate-200"></div>
                                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100 text-xs space-y-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-bold text-slate-800 uppercase tracking-wide">
                                            {{ strtoupper(str_replace('_', ' ', $history->new_status)) }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-mono">
                                            {{ $history->created_at?->format('M d, Y · H:i:s') }}
                                        </span>
                                    </div>
                                    <p class="text-slate-600 text-[11px] leading-relaxed">
                                        {{ $history->change_reason }}
                                    </p>
                                    <div class="text-[10px] text-slate-400 pt-1 flex items-center gap-2">
                                        <span>Authorized by: <strong>{{ $history->changedBy?->name ?? 'System' }}</strong></span>
                                        @if($history->previous_status)
                                            <span>(from {{ strtoupper(str_replace('_', ' ', $history->previous_status)) }})</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

            {{-- RIGHT COLUMN: Operational Controls, Triage, Routing & Dispatch --}}
            <div class="lg:col-span-5 space-y-6">

                {{-- OPERATIONAL CARD 1: TRIAGE REVIEW (PENDING REVIEW) --}}
                @if($incident->status === 'pending_review')
                    <div class="bg-gradient-to-br from-[#173957] to-[#0f2438] rounded-2xl p-6 text-white shadow-xl shadow-[#173957]/20 border border-white/10 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-1 rounded-md bg-[#DB4545] text-white text-[10px] font-bold uppercase tracking-wider animate-pulse">
                                INTAKE TRIAGE ACTIVE
                            </span>
                            <span class="text-xs text-slate-300 font-mono">Pending Human Review</span>
                        </div>

                        <h3 class="text-lg font-bold text-white">Review &amp; Verify Report</h3>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Evaluate caller details, contact validity, and scene coordinates. Acceptance initiates response routing; rejection notifies the reporter.
                        </p>

                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <button 
                                type="button" 
                                x-on:click="showAcceptModal = true"
                                :disabled="!$store.pwa.isOnline"
                                class="py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold text-xs shadow-md transition-all flex items-center justify-center gap-2"
                            >
                                <i data-lucide="check" class="w-4 h-4"></i>
                                <span>ACCEPT REPORT</span>
                            </button>

                            <button 
                                type="button" 
                                x-on:click="showRejectModal = true"
                                :disabled="!$store.pwa.isOnline"
                                class="py-3 px-4 rounded-xl bg-rose-600/90 hover:bg-rose-600 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold text-xs shadow-md transition-all flex items-center justify-center gap-2"
                            >
                                <i data-lucide="x" class="w-4 h-4"></i>
                                <span>REJECT REPORT</span>
                            </button>
                        </div>
                        <p x-show="!$store.pwa.isOnline" x-cloak class="text-[11px] text-rose-300 font-semibold text-center">
                            Offline Mode: Acceptance and rejection require an active server connection.
                        </p>
                    </div>
                @endif

                {{-- OPERATIONAL CARD 2: CALLER & CONTACT VERIFICATION --}}
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 flex items-center gap-2">
                            <i data-lucide="phone-call" class="w-4 h-4 text-[#3187D8]"></i>
                            Caller &amp; Contact Verification
                        </h3>
                        @php
                            $verColors = [
                                'verified' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                'verification_pending' => 'bg-amber-100 text-amber-800 border-amber-200',
                                'failed_verification' => 'bg-rose-100 text-rose-800 border-rose-200',
                                'unverified' => 'bg-slate-100 text-slate-700 border-slate-200',
                            ];
                        @endphp
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase border {{ $verColors[$incident->contact_verification_status] ?? 'bg-slate-100 text-slate-700' }}">
                            {{ strtoupper(str_replace('_', ' ', $incident->contact_verification_status)) }}
                        </span>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 space-y-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Caller Name:</span>
                            <span class="font-bold text-slate-800">{{ $incident->caller_name }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Contact Number:</span>
                            <a href="tel:{{ $incident->caller_phone }}" class="font-mono font-bold text-[#3187D8] hover:underline flex items-center gap-1">
                                <i data-lucide="phone" class="w-3 h-3"></i>
                                {{ $incident->caller_phone }}
                            </a>
                        </div>
                        @if($incident->reporter)
                            <div class="flex items-center justify-between pt-1 border-t border-slate-200/60 text-[11px]">
                                <span class="text-slate-500">Citizen Profile:</span>
                                <span class="font-semibold text-slate-700">{{ $incident->reporter->name }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Contact Verification Form --}}
                    <form method="POST" action="{{ route('admin.incidents.verify-contact', $incident->reference_number) }}" class="space-y-3 pt-2">
                        @csrf
                        <input type="hidden" name="contact_number" value="{{ $incident->caller_phone }}">
                        <input type="hidden" name="verification_method" value="callback">

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Record Callback Result</label>
                            <select name="status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-[#3187D8]">
                                <option value="verified" {{ $incident->contact_verification_status === 'verified' ? 'selected' : '' }}>Verified (Caller Confirmed Incident)</option>
                                <option value="failed_verification" {{ $incident->contact_verification_status === 'failed_verification' ? 'selected' : '' }}>Failed Verification (Unreachable / Fake)</option>
                                <option value="verification_pending" {{ $incident->contact_verification_status === 'verification_pending' ? 'selected' : '' }}>Verification Pending (Ringing / No Answer)</option>
                            </select>
                        </div>

                        <div>
                            <input type="text" name="remarks" placeholder="Reviewer callback remarks / notes..." class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white">
                        </div>

                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="save" class="w-3.5 h-3.5"></i>
                            <span>Save Contact Verification</span>
                        </button>
                    </form>

                    {{-- Verification History Log --}}
                    @if($incident->contactVerifications->isNotEmpty())
                        <div class="pt-2 border-t border-slate-100 space-y-1.5">
                            <span class="text-[10px] font-bold text-slate-400 uppercase">Verification History</span>
                            @foreach($incident->contactVerifications->sortByDesc('created_at')->take(3) as $ver)
                                <div class="text-[11px] p-2 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-between">
                                    <div>
                                        <span class="font-bold text-slate-700 uppercase text-[10px]">{{ str_replace('_', ' ', $ver->status) }}</span>
                                        <span class="text-slate-500">by {{ $ver->verifier?->name ?? 'Officer' }}</span>
                                        @if($ver->remarks) <div class="text-[10px] text-slate-400 italic">{{ $ver->remarks }}</div> @endif
                                    </div>
                                    <span class="text-[10px] font-mono text-slate-400">{{ $ver->verified_at?->format('H:i') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- OPERATIONAL CARD 3: AUTOMATIC ROUTING RECOMMENDATION --}}
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 flex items-center gap-2">
                            <i data-lucide="cpu" class="w-4 h-4 text-[#3187D8]"></i>
                            Routing Engine Recommendation
                        </h3>
                        @if($routing['rule_matched'])
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-blue-100 text-blue-800">
                                RULE MATCHED
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-slate-100 text-slate-600">
                                DEFAULT ROUTE
                            </span>
                        @endif
                    </div>

                    <div class="p-3.5 rounded-xl bg-blue-50/60 border border-blue-100 text-xs space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-blue-700 font-semibold">Recommended Office:</span>
                            <span class="font-bold text-slate-900">{{ $routing['recommended_office']?->name ?? 'None' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-blue-700 font-semibold">Recommended Unit:</span>
                            <span class="font-bold text-slate-900">{{ $routing['recommended_team']?->team_name ?? 'Primary Team' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-blue-700 font-semibold">Responders Available:</span>
                            <span class="font-bold {{ $routing['eligible_personnel']->isEmpty() ? 'text-rose-600' : 'text-emerald-700' }}">
                                {{ $routing['eligible_personnel']->count() }} Online
                            </span>
                        </div>
                        <div class="text-[11px] text-slate-600 pt-1 border-t border-blue-200/60 leading-relaxed">
                            {{ $routing['recommendation_reason'] }}
                        </div>
                    </div>

                    @if($routing['has_offline_warning'])
                        <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-[11px] font-medium flex items-center gap-2">
                            <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 flex-shrink-0"></i>
                            <span>Department responders are currently offline. Manual assignment override recommended.</span>
                        </div>
                    @endif
                </div>

                {{-- OPERATIONAL CARD 4: MANUAL ASSIGNMENT & DISPATCH CONTROLS --}}
                @if(in_array($incident->status, ['accepted', 'dispatched', 'acknowledged', 'in_progress'], true))
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                <i data-lucide="users" class="w-4 h-4 text-[#3187D8]"></i>
                                Response Unit Assignment
                            </h3>
                            <button type="button" x-on:click="showAssignModal = true" :disabled="!$store.pwa.isOnline" class="text-xs font-bold text-[#3187D8] hover:underline disabled:opacity-40 disabled:cursor-not-allowed">
                                {{ $incident->activeAssignments->isNotEmpty() ? 'Modify / Add Responders' : 'Assign Unit' }}
                            </button>
                        </div>

                        {{-- Active Assignments Display --}}
                        @if($incident->activeAssignments->isNotEmpty())
                            <div class="space-y-2">
                                @foreach($incident->activeAssignments as $assign)
                                    <div class="p-3 rounded-xl bg-purple-50/70 border border-purple-100 text-xs flex items-center justify-between">
                                        <div>
                                            <div class="font-bold text-slate-900">{{ $assign->office?->name }}</div>
                                            <div class="text-[11px] text-slate-600">
                                                Team: {{ $assign->team?->team_name ?? 'General Desk' }}
                                                @if($assign->personnel)
                                                    · Responder: <strong>{{ $assign->personnel->user?->name }}</strong> ({{ $assign->personnel->badge_number }})
                                                @endif
                                            </div>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-md text-[9px] font-bold uppercase bg-purple-200 text-purple-900">
                                            ACTIVE
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-4 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-center text-xs text-slate-500">
                                No response unit assigned yet. Please assign an authorized response unit.
                            </div>
                        @endif

                        {{-- DISPATCH ACTION (When Accepted and Assignment Exists) --}}
                        @if($incident->status === 'accepted')
                            <div class="pt-2">
                                @if($incident->activeAssignments->isNotEmpty())
                                    <button 
                                        type="button" 
                                        x-on:click="showDispatchModal = true"
                                        :disabled="!$store.pwa.isOnline"
                                        class="w-full py-4 px-6 rounded-xl bg-[#DB4545] hover:bg-[#c93a3a] disabled:opacity-40 disabled:cursor-not-allowed text-white font-extrabold text-sm shadow-xl shadow-[#DB4545]/25 transition-all flex items-center justify-center gap-2 animate-pulse"
                                    >
                                        <i data-lucide="send" class="w-5 h-5"></i>
                                        <span x-text="$store.pwa.isOnline ? 'AUTHORIZE OFFICIAL DISPATCH' : 'DISPATCH DISABLED OFFLINE'">AUTHORIZE OFFICIAL DISPATCH</span>
                                    </button>
                                    <p class="text-[11px] text-slate-400 text-center mt-2 leading-relaxed">
                                        Mobilizes assigned responders and generates live task alerts.
                                    </p>
                                @else
                                    <button 
                                        type="button" 
                                        disabled
                                        class="w-full py-3.5 px-6 rounded-xl bg-slate-200 text-slate-400 font-bold text-xs cursor-not-allowed flex items-center justify-center gap-2"
                                    >
                                        <i data-lucide="send" class="w-4 h-4"></i>
                                        <span>ASSIGN UNIT FIRST BEFORE DISPATCH</span>
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                {{-- OPERATIONAL CARD 5: ADMIN EMERGENCY OVERRIDE --}}
                @if(auth()->user()->isAdmin() && $incident->status !== 'pending_review')
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs flex items-center justify-between">
                        <div>
                            <span class="font-bold text-slate-800 block">Administrative Override</span>
                            <span class="text-slate-500 text-[11px]">Correct review decision with mandatory audit trail</span>
                        </div>
                        <button 
                            type="button" 
                            x-on:click="showOverrideModal = true" 
                            class="px-3 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold transition-colors"
                        >
                            Execute Override
                        </button>
                    </div>
                @endif

            </div>

        </div>

        {{-- ============================================================== --}}
        {{-- MODAL 1: ACCEPT INCIDENT MODAL --}}
        {{-- ============================================================== --}}
        <div x-show="showAcceptModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" x-cloak>
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4" x-on:click.outside="showAcceptModal = false">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600"></i>
                        Accept Incident Report
                    </h3>
                    <button type="button" x-on:click="showAcceptModal = false" class="text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <p class="text-xs text-slate-600 leading-relaxed">
                    You are accepting incident <strong>{{ $incident->reference_number }}</strong>. This will transition the incident to <strong>ACCEPTED</strong> and trigger the automatic routing engine for dispatch planning.
                </p>

                <form method="POST" action="{{ route('admin.incidents.review', $incident->reference_number) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="decision" value="accepted">

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Intake Review Notes (Optional)</label>
                        <textarea name="review_notes" rows="3" placeholder="Enter review assessment, confirmed details, or triage instructions..." class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" x-on:click="showAcceptModal = false" class="px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-md">
                            Confirm Acceptance
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- MODAL 2: REJECT INCIDENT MODAL --}}
        {{-- ============================================================== --}}
        <div x-show="showRejectModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" x-cloak>
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4" x-on:click.outside="showRejectModal = false">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-rose-600 flex items-center gap-2">
                        <i data-lucide="x-circle" class="w-5 h-5 text-rose-600"></i>
                        Reject Incident Report
                    </h3>
                    <button type="button" x-on:click="showRejectModal = false" class="text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <p class="text-xs text-slate-600 leading-relaxed">
                    A rejection reason is <strong>strictly required</strong>. The incident will not be deleted, but preserved in the records and marked as REJECTED. The reporting citizen will be notified of this reason.
                </p>

                <form method="POST" action="{{ route('admin.incidents.review', $incident->reference_number) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="decision" value="rejected">

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Rejection Reason *</label>
                        <select name="rejection_reason" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white">
                            <option value="">Select Primary Rejection Reason...</option>
                            <option value="Prank / Malicious Report">Prank / Malicious Report</option>
                            <option value="Duplicate Report">Duplicate Report (Already In Progress)</option>
                            <option value="Unreachable / Fake Contact Number">Unreachable / Fake Contact Number</option>
                            <option value="Outside Hinoba-an Municipal Jurisdiction">Outside Hinoba-an Municipal Jurisdiction</option>
                            <option value="Non-Emergency Disturbance">Non-Emergency Disturbance (No Dispatch Required)</option>
                            <option value="Insufficient Scene Information">Insufficient Scene Information</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Detailed Review Remarks (Optional)</label>
                        <textarea name="review_notes" rows="3" placeholder="Additional details or justification for rejection..." class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" x-on:click="showRejectModal = false" class="px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-md">
                            Confirm Rejection
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- MODAL 3: ASSIGN INCIDENT MODAL --}}
        {{-- ============================================================== --}}
        <div x-show="showAssignModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" x-cloak>
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4" x-on:click.outside="showAssignModal = false">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="users" class="w-5 h-5 text-[#3187D8]"></i>
                        Assign Response Unit
                    </h3>
                    <button type="button" x-on:click="showAssignModal = false" class="text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.incidents.assign', $incident->reference_number) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Select Department / Office *</label>
                        <select name="office_id" x-model="selectedOfficeId" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white">
                            @foreach($offices as $off)
                                <option value="{{ $off->id }}">{{ $off->name }} ({{ strtoupper($off->department_type) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Select Response Team</label>
                        <select name="team_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white">
                            <option value="">No Specific Team (General Desk Assignment)</option>
                            @foreach($offices as $off)
                                <optgroup label="{{ $off->name }}">
                                    @foreach($off->teams as $t)
                                        <option value="{{ $t->id }}" {{ ($routing['recommended_team']?->id == $t->id) ? 'selected' : '' }}>
                                            {{ $t->team_name }} ({{ $t->call_sign }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Select Specific Responder (Optional)</label>
                        <select name="personnel_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white">
                            <option value="">Assign to Unit Lead / TBD</option>
                            @foreach($allPersonnel as $p)
                                <option value="{{ $p->id }}">
                                    {{ $p->user?->name }} · {{ $p->badge_number }} ({{ $p->availability?->operational_status ?? 'off_duty' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Tactical Instructions / Notes</label>
                        <textarea name="notes" rows="2" placeholder="Specific dispatch directives, staging area, radio channel..." class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" x-on:click="showAssignModal = false" class="px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#173957] hover:bg-[#0f2438] text-white text-xs font-bold shadow-md">
                            Confirm Assignment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- MODAL 4: OFFICIAL DISPATCH CONFIRMATION MODAL --}}
        {{-- ============================================================== --}}
        <div x-show="showDispatchModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" x-cloak>
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4" x-on:click.outside="showDispatchModal = false">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-black text-[#DB4545] flex items-center gap-2">
                        <i data-lucide="send" class="w-5 h-5 text-[#DB4545]"></i>
                        AUTHORIZE OFFICIAL DISPATCH
                    </h3>
                    <button type="button" x-on:click="showDispatchModal = false" class="text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs leading-relaxed">
                    <strong>CRITICAL ACTION:</strong> This will formally mobilize assigned units for incident <strong>{{ $incident->reference_number }}</strong>. Dispatch alerts will be transmitted exclusively to confirmed assigned personnel and the citizen reporter.
                </div>

                <form method="POST" action="{{ route('admin.incidents.dispatch', $incident->reference_number) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Dispatch Orders / Staging Notes (Optional)</label>
                        <textarea name="dispatch_notes" rows="3" placeholder="Radio channel, staging coordinates, safety directives..." class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" x-on:click="showDispatchModal = false" class="px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#DB4545] hover:bg-[#c93a3a] text-white text-xs font-black shadow-lg shadow-[#DB4545]/30">
                            AUTHORIZE DISPATCH NOW
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- MODAL 5: ADMIN OVERRIDE MODAL --}}
        {{-- ============================================================== --}}
        @if(auth()->user()->isAdmin())
            <div x-show="showOverrideModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" x-cloak>
                <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4" x-on:click.outside="showOverrideModal = false">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <i data-lucide="shield-alert" class="w-5 h-5 text-amber-600"></i>
                            Administrative Decision Override
                        </h3>
                        <button type="button" x-on:click="showOverrideModal = false" class="text-slate-400 hover:text-slate-600">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <p class="text-xs text-slate-600 leading-relaxed">
                        Emergency Operations Center override allows reversing or correcting an earlier review decision. This action is permanently audited.
                    </p>

                    <form method="POST" action="{{ route('admin.incidents.override', $incident->reference_number) }}" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">New Status Target *</label>
                            <select name="new_decision" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white">
                                <option value="accepted">Accepted (Re-open for routing and dispatch)</option>
                                <option value="pending_review">Pending Review (Return to intake triage queue)</option>
                                <option value="rejected">Rejected (Cancel operational assignment)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Mandatory Override Justification *</label>
                            <textarea name="override_reason" required rows="3" placeholder="Reason for reversing previous decision..." class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white"></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="button" x-on:click="showOverrideModal = false" class="px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold shadow-md">
                                Confirm Override
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

    </div>
</x-layouts.admin>

