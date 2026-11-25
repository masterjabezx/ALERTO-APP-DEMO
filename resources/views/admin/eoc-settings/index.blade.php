<x-layouts.admin :title="'Command Center Settings — ALERTO EOC'">
    <div class="space-y-6">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#3187D8]/15 text-[#34B9D4] text-[11px] font-bold uppercase tracking-wider mb-1">
                <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
                Hinoba-an LDRRMO Operations Configuration
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Command Center Settings</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Configure primary emergency hotline details, municipal geofence boundaries, and active dispatch routing rules.
            </p>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-2xl bg-[#19865B]/15 border border-[#19865B]/40 text-[#6AE0B0] text-xs font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <form method="POST" action="{{ route('admin.eoc-settings.update') }}" class="lg:col-span-7 eoc-card p-6 space-y-4 text-xs">
                @csrf
                <h2 class="text-sm font-black uppercase tracking-wider border-b border-slate-400/15 pb-3">Dual Configurable Emergency Hotlines &amp; Municipal Boundaries</h2>

                @php
                    $dd = $hotlinesConfig['dial_daph'] ?? [];
                    $ld = $hotlinesConfig['ldrrmo'] ?? [];
                @endphp

                <div class="p-4 rounded-xl border border-rose-500/30 bg-rose-950/15 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-black text-rose-300 uppercase">Hotline #1 — DIAL DAPH COMMAND CENTER</span>
                        <label class="inline-flex items-center gap-1.5 font-bold cursor-pointer">
                            <input type="checkbox" name="dial_daph_command_center_active" value="1" {{ !empty($dd['is_active']) ? 'checked' : '' }} class="rounded">
                            Active
                        </label>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold uppercase text-slate-400 mb-1">Button Title</label>
                            <input type="text" name="dial_daph_command_center_title" value="{{ $dd['title'] ?? $hotline_title }}" required class="w-full px-3 py-2 rounded-xl border">
                        </div>
                        <div>
                            <label class="block font-bold uppercase text-slate-400 mb-1">Phone Number (Clear to Disable)</label>
                            <input type="text" name="dial_daph_command_center_hotline" value="{{ $dd['number'] ?? $hotline }}" class="w-full px-3 py-2 rounded-xl border font-mono">
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold uppercase text-slate-400 mb-1">Description</label>
                        <input type="text" name="dial_daph_command_center_description" value="{{ $dd['description'] ?? '' }}" class="w-full px-3 py-2 rounded-xl border">
                    </div>
                </div>

                <div class="p-4 rounded-xl border border-amber-500/30 bg-amber-950/15 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-black text-amber-300 uppercase">Hotline #2 — LDRRMO DIRECT LINE</span>
                        <label class="inline-flex items-center gap-1.5 font-bold cursor-pointer">
                            <input type="checkbox" name="ldrrmo_hotline_active" value="1" {{ !empty($ld['is_active']) ? 'checked' : '' }} class="rounded">
                            Active
                        </label>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold uppercase text-slate-400 mb-1">Button Title</label>
                            <input type="text" name="ldrrmo_hotline_title" value="{{ $ld['title'] ?? 'LDRRMO' }}" required class="w-full px-3 py-2 rounded-xl border">
                        </div>
                        <div>
                            <label class="block font-bold uppercase text-slate-400 mb-1">Phone Number (Clear to Disable)</label>
                            <input type="text" name="ldrrmo_hotline_number" value="{{ $ld['number'] ?? '' }}" class="w-full px-3 py-2 rounded-xl border font-mono">
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold uppercase text-slate-400 mb-1">Description</label>
                        <input type="text" name="ldrrmo_hotline_description" value="{{ $ld['description'] ?? '' }}" class="w-full px-3 py-2 rounded-xl border">
                    </div>
                </div>

                <div>
                    <label class="block font-bold uppercase text-slate-400 mb-1">Official LDRRMO Facebook URL</label>
                    <input type="url" name="facebook_url" value="{{ $facebook_url }}" required class="w-full px-3 py-2 rounded-xl border">
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                    <div>
                        <label class="block font-bold uppercase text-slate-400 mb-1">Lat Min</label>
                        <input type="text" name="lat_min" value="{{ $lat_min }}" required class="w-full px-3 py-2 rounded-xl border font-mono">
                    </div>
                    <div>
                        <label class="block font-bold uppercase text-slate-400 mb-1">Lat Max</label>
                        <input type="text" name="lat_max" value="{{ $lat_max }}" required class="w-full px-3 py-2 rounded-xl border font-mono">
                    </div>
                    <div>
                        <label class="block font-bold uppercase text-slate-400 mb-1">Lng Min</label>
                        <input type="text" name="lng_min" value="{{ $lng_min }}" required class="w-full px-3 py-2 rounded-xl border font-mono">
                    </div>
                    <div>
                        <label class="block font-bold uppercase text-slate-400 mb-1">Lng Max</label>
                        <input type="text" name="lng_max" value="{{ $lng_max }}" required class="w-full px-3 py-2 rounded-xl border font-mono">
                    </div>
                </div>

                <div class="pt-3 flex justify-end">
                    <button type="submit" :disabled="!$store.pwa.isOnline" class="px-5 py-2.5 rounded-xl bg-[#3187D8] hover:bg-[#2770b6] disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold">
                        Save Command Center Configuration
                    </button>
                </div>
            </form>

            <div class="lg:col-span-5 eoc-card p-6 space-y-4 text-xs">
                <h2 class="text-sm font-black uppercase tracking-wider border-b border-slate-400/15 pb-3">Active Dispatch Routing Matrix</h2>
                <div class="space-y-2 max-h-80 overflow-y-auto alerto-scrollbar">
                    @foreach($routing_rules as $rule)
                        <div class="p-3 rounded-xl border border-slate-400/15 flex items-center justify-between gap-2">
                            <div>
                                <div class="font-bold">{{ $rule->category?->name ?? 'Any Category' }}</div>
                                <div class="text-[10px] text-slate-400">
                                    Barangay: {{ $rule->barangay?->name ?? 'All Municipal' }}
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded bg-[#173957] text-[#34B9D4] font-mono font-bold text-[10px]">
                                {{ $rule->targetOffice?->code ?? 'OFFICE' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>

