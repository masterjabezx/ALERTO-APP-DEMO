<x-layouts.admin :title="'Command Center Settings — ALERTO EOC'">
    <div class="space-y-6">
        <x-portal.page-header
            badge="Hinoba-an LDRRMO Operations Configuration"
            title="Command Center Settings"
            subtitle="Configure primary emergency hotline details, municipal geofence boundaries, and active dispatch routing rules."
        />

        @if(session('success'))
            <div class="p-4 rounded-2xl bg-[#27845B]/15 border border-[#27845B]/35 text-[#27845B] text-xs font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <form method="POST" action="{{ route('admin.eoc-settings.update') }}" class="lg:col-span-7 alerto-card p-6 space-y-4 text-xs">
                @csrf
                <h2 class="text-sm font-black uppercase tracking-wider border-b border-[#E6E3DE] dark:border-white/10 pb-3">Dual Configurable Emergency Hotlines &amp; Municipal Boundaries</h2>

                @php
                    $dd = $hotlinesConfig['dial_daph'] ?? [];
                    $ld = $hotlinesConfig['ldrrmo'] ?? [];
                @endphp

                <div class="p-4 rounded-xl border border-[#C83F43]/30 bg-[#C83F43]/10 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-black text-[#C83F43] uppercase">Hotline #1 — DIAL DAPH COMMAND CENTER</span>
                        <label class="inline-flex items-center gap-1.5 font-bold cursor-pointer">
                            <input type="checkbox" name="dial_daph_command_center_active" value="1" {{ !empty($dd['is_active']) ? 'checked' : '' }} class="rounded text-[#E87524] focus:ring-[#E87524]">
                            Active
                        </label>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold uppercase text-[#77736E] mb-1">Button Title</label>
                            <input type="text" name="dial_daph_command_center_title" value="{{ $dd['title'] ?? $hotline_title }}" required class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                        </div>
                        <div>
                            <label class="block font-bold uppercase text-[#77736E] mb-1">Phone Number (Clear to Disable)</label>
                            <input type="text" name="dial_daph_command_center_hotline" value="{{ $dd['number'] ?? $hotline }}" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] font-mono">
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold uppercase text-[#77736E] mb-1">Description</label>
                        <input type="text" name="dial_daph_command_center_description" value="{{ $dd['description'] ?? '' }}" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                    </div>
                </div>

                <div class="p-4 rounded-xl border border-[#E87524]/30 bg-[#E87524]/10 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-black text-[#B94F12] dark:text-[#F29A56] uppercase">Hotline #2 — LDRRMO DIRECT LINE</span>
                        <label class="inline-flex items-center gap-1.5 font-bold cursor-pointer">
                            <input type="checkbox" name="ldrrmo_hotline_active" value="1" {{ !empty($ld['is_active']) ? 'checked' : '' }} class="rounded text-[#E87524] focus:ring-[#E87524]">
                            Active
                        </label>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold uppercase text-[#77736E] mb-1">Button Title</label>
                            <input type="text" name="ldrrmo_hotline_title" value="{{ $ld['title'] ?? 'LDRRMO' }}" required class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                        </div>
                        <div>
                            <label class="block font-bold uppercase text-[#77736E] mb-1">Phone Number (Clear to Disable)</label>
                            <input type="text" name="ldrrmo_hotline_number" value="{{ $ld['number'] ?? '' }}" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] font-mono">
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold uppercase text-[#77736E] mb-1">Description</label>
                        <input type="text" name="ldrrmo_hotline_description" value="{{ $ld['description'] ?? '' }}" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                    </div>
                </div>

                <div>
                    <label class="block font-bold uppercase text-[#77736E] mb-1">Official LDRRMO Facebook URL</label>
                    <input type="url" name="facebook_url" value="{{ $facebook_url }}" required class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                    <div>
                        <label class="block font-bold uppercase text-[#77736E] mb-1">Lat Min</label>
                        <input type="text" name="lat_min" value="{{ $lat_min }}" required class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] font-mono">
                    </div>
                    <div>
                        <label class="block font-bold uppercase text-[#77736E] mb-1">Lat Max</label>
                        <input type="text" name="lat_max" value="{{ $lat_max }}" required class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] font-mono">
                    </div>
                    <div>
                        <label class="block font-bold uppercase text-[#77736E] mb-1">Lng Min</label>
                        <input type="text" name="lng_min" value="{{ $lng_min }}" required class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] font-mono">
                    </div>
                    <div>
                        <label class="block font-bold uppercase text-[#77736E] mb-1">Lng Max</label>
                        <input type="text" name="lng_max" value="{{ $lng_max }}" required class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] font-mono">
                    </div>
                </div>

                <div class="pt-3 flex justify-end">
                    <button type="submit" :disabled="!$store.pwa.isOnline" class="alerto-btn-press px-5 py-2.5 rounded-xl bg-[#E87524] hover:bg-[#B94F12] disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold">
                        Save Command Center Configuration
                    </button>
                </div>
            </form>

            <div class="lg:col-span-5 alerto-card p-6 space-y-4 text-xs">
                <h2 class="text-sm font-black uppercase tracking-wider border-b border-[#E6E3DE] dark:border-white/10 pb-3">Active Dispatch Routing Matrix</h2>
                <div class="space-y-2 max-h-80 overflow-y-auto alerto-scrollbar">
                    @foreach($routing_rules as $rule)
                        <div class="p-3 rounded-xl border border-[#E6E3DE] dark:border-white/10 bg-[#F5F3EF] dark:bg-white/5 flex items-center justify-between gap-2">
                            <div>
                                <div class="font-bold">{{ $rule->category?->name ?? 'Any Category' }}</div>
                                <div class="text-[10px] text-[#77736E]">
                                    Barangay: {{ $rule->barangay?->name ?? 'All Municipal' }}
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] font-mono font-bold text-[10px]">
                                {{ $rule->targetOffice?->code ?? 'OFFICE' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
