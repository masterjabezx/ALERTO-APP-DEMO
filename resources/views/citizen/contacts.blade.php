<x-layouts.citizen>
    <div class="space-y-6">

        {{-- Dual Configurable Hotlines (DIAL DAPH COMMAND CENTER & LDRRMO) --}}
        <div class="bg-[#17191C] rounded-[24px] p-5 sm:p-6 border border-white/10 space-y-3 shadow-xl text-[#F5F3EF]">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-extrabold uppercase tracking-wider text-white">Direct Municipal Emergency Lines</h2>
                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-[#C83F43]/20 text-[#F27D80] border border-[#C83F43]/30">24/7 Active Dispatch</span>
            </div>
            <x-dual-hotlines :hotlines="$hotlines" />
        </div>

        {{-- Official Municipal Hotlines Directory --}}
        <div class="alerto-card p-6 sm:p-8 space-y-5">
            <div>
                <h1 class="text-xl font-extrabold text-[var(--text-primary)]">Hinoba-an Emergency Hotlines Directory</h1>
                <p class="text-xs text-[var(--text-secondary)] mt-0.5">Contact numbers configured and managed directly by the Local Disaster Risk Reduction Office.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @forelse($additionalHotlines as $hl)
                    <div class="p-4 rounded-2xl border border-[var(--border-subtle)] bg-[var(--surface-panel)] flex items-center justify-between gap-3 hover:border-[#E87524]/45 transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-11 h-11 rounded-2xl bg-[#17191C] text-[#E87524] flex items-center justify-center shrink-0 shadow-sm border border-white/10">
                                <i data-lucide="phone" class="w-5 h-5"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-xs font-bold text-[var(--text-primary)] truncate">{{ $hl->service_name }}</h3>
                                <span class="text-sm font-mono font-extrabold text-[#B94F12] dark:text-[#F29A56] block mt-0.5">{{ $hl->phone_number }}</span>
                                @if($hl->is_toll_free)
                                    <span class="text-[10px] text-[#27845B] dark:text-[#5BD19A] font-bold uppercase">Toll Free</span>
                                @endif
                            </div>
                        </div>

                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $hl->phone_number) }}" 
                           class="p-3 rounded-xl bg-[var(--surface-card)] border border-[var(--border-subtle)] text-[var(--text-primary)] hover:text-[#E87524] hover:border-[#E87524]/50 shadow-xs shrink-0 touch-target flex items-center justify-center transition-colors"
                           title="Call Hotline">
                            <i data-lucide="phone-outgoing" class="w-4 h-4"></i>
                        </a>
                    </div>
                @empty
                    <p class="text-xs text-[var(--text-muted)] col-span-2">Hotline directory is loading.</p>
                @endforelse
            </div>

            {{-- Official Facebook Page --}}
            @if(!empty($facebookUrl))
                <div class="pt-4 border-t border-[var(--border-subtle)] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#397BA8]/12 text-[#397BA8] dark:text-[#7BB5DB] flex items-center justify-center shrink-0">
                            <i data-lucide="facebook" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-[var(--text-primary)]">Official Facebook Page</h4>
                            <p class="text-[11px] text-[var(--text-secondary)]">Official bulletins, typhoon advisories &amp; public safety alerts.</p>
                        </div>
                    </div>

                    <a href="{{ $facebookUrl }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-[#397BA8] hover:bg-[#2E668D] text-white text-xs font-bold transition-all shadow-sm">
                        <span>Visit Facebook Page</span>
                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            @endif
        </div>

    </div>
</x-layouts.citizen>
