<x-layouts.admin title="SOS Alert {{ $sos->reference_number }} — DIAL DAPH">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('admin.sos.index') }}" class="text-xs font-bold text-[#34B9D4] hover:underline">&larr; Back to SOS Console</a>
                <h1 class="text-2xl font-black text-white mt-1">{{ $sos->headline() }}</h1>
                <p class="text-xs text-slate-400 font-mono">
                    Status: {{ strtoupper(str_replace('_', ' ', $sos->status)) }} · GPS Status: {{ strtoupper(str_replace('_', ' ', $sos->location_status)) }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($telHref)
                    <a href="{{ $telHref }}" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black">
                        Call {{ $sos->sender_phone }}
                    </a>
                @endif
                @if(!$sos->converted_incident_id)
                    <form method="POST" action="{{ route('admin.sos.convert', $sos->id) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2.5 rounded-xl bg-[#3187D8] hover:bg-[#2671ba] text-white text-xs font-bold cursor-pointer">
                            Convert SOS to Formal Incident
                        </button>
                    </form>
                @else
                    <a href="{{ route('admin.incidents.show', $sos->convertedIncident->reference_number) }}" class="px-4 py-2.5 rounded-xl bg-purple-600 text-white text-xs font-bold">
                        View Linked Incident {{ $sos->convertedIncident->reference_number }}
                    </a>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Left Column: Telemetry & Action Forms --}}
            <div class="lg:col-span-7 space-y-5">
                <div class="eoc-card p-5 space-y-3 text-xs">
                    <h2 class="font-black uppercase text-slate-300">Sender &amp; Location Details</h2>
                    <div class="grid grid-cols-2 gap-3">
                        <div><span class="text-slate-400">Name:</span> <strong class="block text-white">{{ $sos->sender_name }}</strong></div>
                        <div><span class="text-slate-400">Phone:</span> <strong class="block font-mono text-cyan-300">{{ $sos->sender_phone ?? 'Unavailable' }}</strong></div>
                        <div><span class="text-slate-400">Coordinates:</span> <strong class="block font-mono text-white">{{ $sos->latitude ? "{$sos->latitude}, {$sos->longitude}" : 'Unavailable' }}</strong></div>
                        <div><span class="text-slate-400">Verified Barangay:</span> <strong class="block text-white">{{ $sos->barangay?->name ?? 'Unverified Area' }}</strong></div>
                    </div>
                </div>

                {{-- Dispatch Responders --}}
                <form method="POST" action="{{ route('admin.sos.assign', $sos->id) }}" class="eoc-card p-5 space-y-3 text-xs">
                    @csrf
                    <h2 class="font-black uppercase text-rose-300">Dispatch SOS Response Unit</h2>
                    <label class="inline-flex items-center gap-2 text-cyan-300 font-bold">
                        <input type="checkbox" name="use_routing_rule" value="1" class="rounded">
                        Evaluate Active SOS Routing Rule Automatically (Fails safely with warning if none configured)
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-400 mb-1">Or Select Responding Office</label>
                            <select name="office_id" class="w-full px-3 py-2 rounded-xl border bg-slate-900 text-white">
                                <option value="">-- Office --</option>
                                @foreach($offices as $off)
                                    <option value="{{ $off->id }}">{{ $off->name }} ({{ $off->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">Specific Responder (Optional)</label>
                            <select name="personnel_id" class="w-full px-3 py-2 rounded-xl border bg-slate-900 text-white">
                                <option value="">-- Any Available Unit --</option>
                                @foreach($personnelList as $p)
                                    <option value="{{ $p->id }}">{{ $p->user?->name }} ({{ $p->office?->code }} · {{ $p->availability?->operational_status }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <textarea name="notes" rows="2" placeholder="Dispatch instructions..." class="w-full px-3 py-2 rounded-xl border bg-slate-900 text-white"></textarea>
                    <button type="submit" class="w-full py-3 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-black cursor-pointer">
                        ASSIGN &amp; DISPATCH SOS RESPONDER
                    </button>
                </form>

                {{-- Record Call & Resolve --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <form method="POST" action="{{ route('admin.sos.call-attempt', $sos->id) }}" class="eoc-card p-4 space-y-2.5 text-xs">
                        @csrf
                        <h3 class="font-bold uppercase text-slate-300">Log Callback Verification</h3>
                        <select name="outcome" class="w-full px-3 py-2 rounded-xl border bg-slate-900 text-white">
                            <option value="answered">Answered</option>
                            <option value="no_answer">No Answer</option>
                            <option value="busy">Busy</option>
                            <option value="invalid_number">Invalid Number</option>
                        </select>
                        <input type="text" name="notes" placeholder="Notes..." class="w-full px-3 py-2 rounded-xl border bg-slate-900 text-white">
                        <button type="submit" class="w-full py-2 rounded-xl bg-slate-700 hover:bg-slate-600 text-white font-bold cursor-pointer">Save Call Log</button>
                    </form>

                    <form method="POST" action="{{ route('admin.sos.resolve', $sos->id) }}" class="eoc-card p-4 space-y-2.5 text-xs">
                        @csrf
                        <h3 class="font-bold uppercase text-emerald-300">Resolve / Close SOS</h3>
                        <select name="status" class="w-full px-3 py-2 rounded-xl border bg-slate-900 text-white">
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                        <input type="text" name="resolution_notes" required placeholder="Resolution summary..." class="w-full px-3 py-2 rounded-xl border bg-slate-900 text-white">
                        <button type="submit" class="w-full py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold cursor-pointer">Mark Resolved</button>
                    </form>
                </div>
            </div>

            {{-- Right Column: Assignments & Audit Timeline --}}
            <div class="lg:col-span-5 space-y-4">
                <div class="eoc-card p-5 space-y-3 text-xs">
                    <h3 class="font-black uppercase text-slate-300">SOS Response Timeline ({{ $sos->logs->count() }})</h3>
                    <div class="space-y-2 max-h-96 overflow-y-auto alerto-scrollbar">
                        @foreach($sos->logs as $log)
                            <div class="p-3 rounded-xl bg-white/5 border border-white/10">
                                <div class="flex justify-between font-bold text-white">
                                    <span class="uppercase text-[10px] text-amber-300">{{ str_replace('_', ' ', (string) ($log->action_type ?? $log->action ?? 'event')) }}</span>
                                    <span class="font-mono text-[10px] text-slate-400">{{ $log->created_at?->format('H:i:s') }}</span>
                                </div>
                                <p class="text-slate-300 mt-1">{{ $log->notes ?? $log->details }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>

