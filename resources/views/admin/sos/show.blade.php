<x-layouts.admin title="SOS Alert Command">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('admin.sos.index') }}" class="text-xs font-bold text-[#B94F12] dark:text-[#F29A56] hover:underline">&larr; Back to SOS Console</a>
                <h1 class="text-2xl font-black mt-1">{{ $sos->headline() }}</h1>
                <p class="text-xs text-[#77736E] font-mono">
                    Status: {{ strtoupper(str_replace('_', ' ', $sos->status)) }} · GPS Status: {{ strtoupper(str_replace('_', ' ', $sos->location_status)) }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($telHref)
                    <a href="{{ $telHref }}" class="alerto-btn-press px-4 py-2.5 rounded-xl bg-[#27845B] hover:bg-[#1f6b49] text-white text-xs font-black">
                        Call {{ $sos->sender_phone }}
                    </a>
                @endif
                @if(!$sos->converted_incident_id)
                    <form method="POST" action="{{ route('admin.sos.convert', $sos->id) }}">
                        @csrf
                        <button type="submit" class="alerto-btn-press px-4 py-2.5 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white text-xs font-bold cursor-pointer">
                            Convert SOS to Formal Incident
                        </button>
                    </form>
                @else
                    <a href="{{ route('admin.incidents.show', $sos->convertedIncident->reference_number) }}" class="px-4 py-2.5 rounded-xl bg-[#17191C] hover:bg-[#292D32] text-[#F29A56] border border-[#E87524]/40 text-xs font-bold">
                        View Linked Incident {{ $sos->convertedIncident->reference_number }}
                    </a>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Left Column: Telemetry & Action Forms --}}
            <div class="lg:col-span-7 space-y-5">
                <div class="alerto-card p-5 space-y-3 text-xs">
                    <h2 class="font-black uppercase text-[#77736E]">Sender &amp; Location Details</h2>
                    <div class="grid grid-cols-2 gap-3">
                        <div><span class="text-[#77736E]">Name:</span> <strong class="block">{{ $sos->sender_name }}</strong></div>
                        <div><span class="text-[#77736E]">Phone:</span> <strong class="block font-mono text-[#27845B]">{{ $sos->sender_phone ?? 'Unavailable' }}</strong></div>
                        <div><span class="text-[#77736E]">Coordinates:</span> <strong class="block font-mono">{{ $sos->latitude ? "{$sos->latitude}, {$sos->longitude}" : 'Unavailable' }}</strong></div>
                        <div><span class="text-[#77736E]">Verified Barangay:</span> <strong class="block">{{ $sos->barangay?->name ?? 'Unverified Area' }}</strong></div>
                    </div>
                </div>

                {{-- Dispatch Responders --}}
                <form method="POST" action="{{ route('admin.sos.assign', $sos->id) }}" class="alerto-card p-5 space-y-3 text-xs border-[#C83F43]/35">
                    @csrf
                    <h2 class="font-black uppercase text-[#C83F43]">Dispatch SOS Response Unit</h2>
                    <label class="inline-flex items-center gap-2 text-[#B94F12] dark:text-[#F29A56] font-bold">
                        <input type="checkbox" name="use_routing_rule" value="1" class="rounded text-[#E87524] focus:ring-[#E87524]">
                        Evaluate Active SOS Routing Rule Automatically (Fails safely with warning if none configured)
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[#77736E] mb-1">Or Select Responding Office</label>
                            <select name="office_id" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                                <option value="">-- Office --</option>
                                @foreach($offices as $off)
                                    <option value="{{ $off->id }}">{{ $off->name }} ({{ $off->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[#77736E] mb-1">Specific Responder (Optional)</label>
                            <select name="personnel_id" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                                <option value="">-- Any Available Unit --</option>
                                @foreach($personnelList as $p)
                                    <option value="{{ $p->id }}">{{ $p->user?->name }} ({{ $p->office?->code }} · {{ $p->availability?->operational_status }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <textarea name="notes" rows="2" placeholder="Dispatch instructions..." class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]"></textarea>
                    <button type="submit" class="alerto-btn-press w-full py-3 rounded-xl bg-[#C83F43] hover:bg-[#A63034] text-white font-black cursor-pointer">
                        ASSIGN &amp; DISPATCH SOS RESPONDER
                    </button>
                </form>

                {{-- Record Call & Resolve --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <form method="POST" action="{{ route('admin.sos.call-attempt', $sos->id) }}" class="alerto-card p-4 space-y-2.5 text-xs">
                        @csrf
                        <h3 class="font-bold uppercase text-[#77736E]">Log Callback Verification</h3>
                        <select name="outcome" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                            <option value="answered">Answered</option>
                            <option value="no_answer">No Answer</option>
                            <option value="busy">Busy</option>
                            <option value="invalid_number">Invalid Number</option>
                        </select>
                        <input type="text" name="notes" placeholder="Notes..." class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                        <button type="submit" class="w-full py-2 rounded-xl bg-[#17191C] hover:bg-[#292D32] text-white font-bold cursor-pointer">Save Call Log</button>
                    </form>

                    <form method="POST" action="{{ route('admin.sos.resolve', $sos->id) }}" class="alerto-card p-4 space-y-2.5 text-xs">
                        @csrf
                        <h3 class="font-bold uppercase text-[#27845B]">Resolve / Close SOS</h3>
                        <select name="status" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                        <input type="text" name="resolution_notes" required placeholder="Resolution summary..." class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                        <button type="submit" class="alerto-btn-press w-full py-2 rounded-xl bg-[#27845B] hover:bg-[#1f6b49] text-white font-bold cursor-pointer">Mark Resolved</button>
                    </form>
                </div>
            </div>

            {{-- Right Column: Assignments & Audit Timeline --}}
            <div class="lg:col-span-5 space-y-4">
                <div class="alerto-card p-5 space-y-3 text-xs">
                    <h3 class="font-black uppercase text-[#77736E]">SOS Response Timeline ({{ $sos->logs->count() }})</h3>
                    <div class="space-y-2 max-h-96 overflow-y-auto alerto-scrollbar">
                        @foreach($sos->logs as $log)
                            <div class="p-3 rounded-xl bg-[#F5F3EF] dark:bg-white/5 border border-[#E6E3DE] dark:border-white/10">
                                <div class="flex justify-between font-bold">
                                    <span class="uppercase text-[10px] text-[#B94F12] dark:text-[#F29A56]">{{ str_replace('_', ' ', (string) ($log->action_type ?? $log->action ?? 'event')) }}</span>
                                    <span class="font-mono text-[10px] text-[#77736E]">{{ $log->created_at?->format('H:i:s') }}</span>
                                </div>
                                <p class="text-[#77736E] mt-1">{{ $log->notes ?? $log->details }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
