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
                <p class="text-xs text-[var(--text-secondary)] mt-0.5">Emergency contact details are hidden in this testing environment.</p>
            </div>

            <div class="rounded-2xl border border-amber-300/30 bg-amber-300/10 p-4 text-xs text-amber-900 dark:text-amber-100">
                Real hotline numbers and call actions are unavailable in the demo. Do not use this page for an emergency.
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
