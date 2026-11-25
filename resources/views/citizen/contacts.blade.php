<x-layouts.citizen>
    <div class="space-y-6">

        {{-- Dual Configurable Hotlines (DIAL DAPH COMMAND CENTER & LDRRMO) --}}
        <div class="bg-slate-900 rounded-[24px] p-5 sm:p-6 border border-slate-800 space-y-3 shadow-xl">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-extrabold uppercase tracking-wider text-white">Direct Municipal Emergency Lines</h2>
                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-rose-500/20 text-rose-300">24/7 Active Dispatch</span>
            </div>
            <x-dual-hotlines :hotlines="$hotlines" />
        </div>

        {{-- Official Municipal Hotlines Directory --}}
        <div class="bg-white rounded-[24px] p-6 sm:p-8 border border-slate-200/80 shadow-[0_4px_20px_-2px_rgba(23,57,87,0.06)] space-y-5">
            <div>
                <h1 class="text-xl font-extrabold text-[#173957]">Hinoba-an Emergency Hotlines Directory</h1>
                <p class="text-xs text-slate-500 mt-0.5">Contact numbers configured and managed directly by the Local Disaster Risk Reduction Office.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @forelse($additionalHotlines as $hl)
                    <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50 flex items-center justify-between gap-3 hover:bg-slate-100/60 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-2xl bg-[#173957] text-white flex items-center justify-center flex-shrink-0 shadow-sm">
                                <i data-lucide="phone" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-900">{{ $hl->service_name }}</h3>
                                <span class="text-sm font-mono font-extrabold text-[#173957] block mt-0.5">{{ $hl->phone_number }}</span>
                                @if($hl->is_toll_free)
                                    <span class="text-[10px] text-emerald-600 font-bold uppercase">Toll Free</span>
                                @endif
                            </div>
                        </div>

                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $hl->phone_number) }}" 
                           class="p-3 rounded-xl bg-white border border-slate-200 text-[#173957] hover:bg-slate-50 shadow-xs flex-shrink-0 touch-target flex items-center justify-center"
                           title="Call Hotline">
                            <i data-lucide="phone-outgoing" class="w-4 h-4"></i>
                        </a>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 col-span-2">Hotline directory is loading.</p>
                @endforelse
            </div>

            {{-- Official Facebook Page --}}
            @if(!empty($facebookUrl))
                <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#3187D8]/10 text-[#3187D8] flex items-center justify-center flex-shrink-0">
                            <i data-lucide="facebook" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Official Facebook Page</h4>
                            <p class="text-[11px] text-slate-500">Official bulletins, typhoon advisories &amp; public safety alerts.</p>
                        </div>
                    </div>

                    <a href="{{ $facebookUrl }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-[#3187D8] hover:bg-[#2672ba] text-white text-xs font-bold transition-all shadow-sm">
                        <span>Visit Facebook Page</span>
                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            @endif
        </div>

    </div>
</x-layouts.citizen>

