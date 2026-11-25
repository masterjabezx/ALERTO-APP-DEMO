<x-layouts.admin title="SOS Alerts Command Console — DIAL DAPH">
    @php
        $sosRoutePrefix = auth()->user()?->isSuperAdmin() ? 'super-admin.sos' : 'admin.sos';
    @endphp
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-rose-400 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-rose-500 animate-ping"></span>
                    EMERGENCY SOS ALERTS CONSOLE
                </h1>
                <p class="text-xs text-slate-400 mt-1">Immediate Citizen, Guest, and Responder Distress SOS Alerts.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                <a href="{{ route($sosRoutePrefix.'.index', ['tab' => 'active']) }}" class="px-3 py-1.5 rounded-xl font-bold {{ $tab === 'active' ? 'bg-rose-600 text-white' : 'eoc-card border border-white/10 text-slate-300' }}">Active ({{ $counts['active'] }})</a>
                <a href="{{ route($sosRoutePrefix.'.index', ['tab' => 'citizen']) }}" class="px-3 py-1.5 rounded-xl font-bold {{ $tab === 'citizen' ? 'bg-rose-600 text-white' : 'eoc-card border border-white/10 text-slate-300' }}">Citizen/Guest ({{ $counts['citizen'] }})</a>
                <a href="{{ route($sosRoutePrefix.'.index', ['tab' => 'personnel']) }}" class="px-3 py-1.5 rounded-xl font-bold {{ $tab === 'personnel' ? 'bg-rose-600 text-white' : 'eoc-card border border-white/10 text-slate-300' }}">Responder SOS ({{ $counts['personnel'] }})</a>
                <a href="{{ route($sosRoutePrefix.'.index', ['tab' => 'resolved']) }}" class="px-3 py-1.5 rounded-xl font-bold {{ $tab === 'resolved' ? 'bg-emerald-600 text-white' : 'eoc-card border border-white/10 text-slate-300' }}">Resolved ({{ $counts['resolved'] }})</a>
            </div>
        </div>

        <div class="space-y-3">
            @forelse($alerts as $sos)
                <div class="eoc-card p-5 border {{ $sos->sender_type === 'personnel' ? 'border-red-500/60 bg-red-950/20' : 'border-rose-500/35' }} flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="space-y-1.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono font-black text-white text-sm">{{ $sos->reference_number }}</span>
                            <span class="px-2 py-0.5 rounded bg-rose-600 text-white text-[10px] font-black uppercase">{{ strtoupper($sos->sender_type) }} SOS</span>
                            <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 text-[10px] font-bold uppercase">{{ str_replace('_', ' ', $sos->status) }}</span>
                            <span class="px-2 py-0.5 rounded bg-white/10 text-slate-200 text-[10px] font-semibold">GPS: {{ str_replace('_', ' ', $sos->location_status) }}</span>
                        </div>
                        <p class="text-sm font-bold text-white">{{ $sos->sender_name }} · <span class="font-mono text-cyan-300">{{ $sos->sender_phone ?? 'No phone' }}</span></p>
                        <p class="text-xs text-slate-300">
                            {{ $sos->office ? "Unit: {$sos->office->name} · " : '' }}
                            {{ $sos->barangay?->name ?? 'Unverified Polygon Area' }}
                            @if($sos->latitude && $sos->longitude)
                                · <span class="font-mono">({{ number_format((float)$sos->latitude, 5) }}, {{ number_format((float)$sos->longitude, 5) }})</span>
                            @endif
                            · Triggered {{ $sos->triggered_at?->diffForHumans() }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if($sos->status === 'active')
                            <form method="POST" action="{{ route('admin.sos.acknowledge', $sos->id) }}">
                                @csrf
                                <button type="submit" class="px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black cursor-pointer">Acknowledge</button>
                            </form>
                        @endif
                        <a href="{{ route($sosRoutePrefix.'.show', $sos->id) }}" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold">
                            Open Handling Panel &rarr;
                        </a>
                    </div>
                </div>
            @empty
                <div class="eoc-card p-10 text-center text-slate-400 text-xs">No SOS alerts matching the current filter.</div>
            @endforelse
            <div>{{ $alerts->links() }}</div>
        </div>
    </div>
</x-layouts.admin>

