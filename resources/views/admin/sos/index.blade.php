<x-layouts.admin title="SOS Alerts Queue — DIAL DAPH">
    @php
        $sosRoutePrefix = auth()->user()?->isSuperAdmin() ? 'super-admin.sos' : 'admin.sos';
    @endphp
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-[#C83F43] flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-[#C83F43] animate-ping"></span>
                    EMERGENCY SOS ALERTS CONSOLE
                </h1>
                <p class="text-xs text-[#77736E] mt-1">Immediate Citizen, Guest, and Responder Distress SOS Alerts.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                <a href="{{ route($sosRoutePrefix.'.index', ['tab' => 'active']) }}" class="px-3 py-1.5 rounded-xl font-bold transition-colors {{ $tab === 'active' ? 'bg-[#C83F43] text-white shadow-xs' : 'alerto-card text-[#77736E] hover:text-[#C83F43]' }}">Active ({{ $counts['active'] }})</a>
                <a href="{{ route($sosRoutePrefix.'.index', ['tab' => 'citizen']) }}" class="px-3 py-1.5 rounded-xl font-bold transition-colors {{ $tab === 'citizen' ? 'bg-[#C83F43] text-white shadow-xs' : 'alerto-card text-[#77736E] hover:text-[#C83F43]' }}">Citizen/Guest ({{ $counts['citizen'] }})</a>
                <a href="{{ route($sosRoutePrefix.'.index', ['tab' => 'personnel']) }}" class="px-3 py-1.5 rounded-xl font-bold transition-colors {{ $tab === 'personnel' ? 'bg-[#C83F43] text-white shadow-xs' : 'alerto-card text-[#77736E] hover:text-[#C83F43]' }}">Responder SOS ({{ $counts['personnel'] }})</a>
                <a href="{{ route($sosRoutePrefix.'.index', ['tab' => 'resolved']) }}" class="px-3 py-1.5 rounded-xl font-bold transition-colors {{ $tab === 'resolved' ? 'bg-[#27845B] text-white shadow-xs' : 'alerto-card text-[#77736E] hover:text-[#27845B]' }}">Resolved ({{ $counts['resolved'] }})</a>
            </div>
        </div>

        <div class="space-y-3">
            @forelse($alerts as $sos)
                <div class="alerto-card p-5 border {{ $sos->sender_type === 'personnel' ? 'border-[#C83F43]/70 bg-[#C83F43]/10' : 'border-[#C83F43]/35' }} flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="space-y-1.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono font-black text-sm">{{ $sos->reference_number }}</span>
                            <span class="px-2 py-0.5 rounded bg-[#C83F43] text-white text-[10px] font-black uppercase">{{ strtoupper($sos->sender_type) }} SOS</span>
                            <span class="px-2 py-0.5 rounded bg-[#D99024]/15 text-[#B94F12] dark:text-[#F29A56] text-[10px] font-bold uppercase">{{ str_replace('_', ' ', $sos->status) }}</span>
                            <span class="px-2 py-0.5 rounded bg-black/5 dark:bg-white/10 text-[10px] font-semibold">GPS: {{ str_replace('_', ' ', $sos->location_status) }}</span>
                        </div>
                        <p class="text-sm font-bold">{{ $sos->sender_name }} · <span class="font-mono text-[#27845B]">{{ $sos->sender_phone ?? 'No phone' }}</span></p>
                        <p class="text-xs text-[#77736E]">
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
                                <button type="submit" class="alerto-btn-press px-3.5 py-2 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white text-xs font-black cursor-pointer">Acknowledge</button>
                            </form>
                        @endif
                        <a href="{{ route($sosRoutePrefix.'.show', $sos->id) }}" class="px-4 py-2 rounded-xl bg-[#17191C] hover:bg-[#292D32] text-white text-xs font-bold border border-white/10">
                            Open Handling Panel &rarr;
                        </a>
                    </div>
                </div>
            @empty
                <div class="alerto-card p-10 text-center text-[#77736E] text-xs">No SOS alerts matching the current filter.</div>
            @endforelse
            <div>{{ $alerts->links() }}</div>
        </div>
    </div>
</x-layouts.admin>
