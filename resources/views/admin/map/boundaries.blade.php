<x-layouts.admin :title="'GeoJSON Boundaries — ALERTO EOC'">
    <div class="space-y-6">
        {{-- Header & Navigation --}}
        <x-portal.page-header
            badge="GIS Administration · Hinoba-an"
            title="Verified GeoJSON Boundary Management"
            subtitle="Import and verify official municipal and barangay spatial boundary polygons. Point-in-polygon resolution strictly relies on these verified boundaries."
        >
            <x-slot:actions>
                <a
                    href="{{ route('admin.map') }}"
                    class="px-3.5 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#292D32] hover:bg-[#F5F3EF] dark:hover:bg-white/10 text-xs font-bold transition-all shadow-xs flex items-center gap-2"
                >
                    <i data-lucide="arrow-left" class="w-4 h-4 text-[#E87524]"></i>
                    <span>Back to Live Operations Map</span>
                </a>
            </x-slot:actions>
        </x-portal.page-header>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="p-4 rounded-xl bg-[#27845B]/15 border border-[#27845B]/30 text-[#27845B] text-xs flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-[#27845B] shrink-0"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-xl bg-[#C83F43]/15 border border-[#C83F43]/30 text-[#C83F43] text-xs flex items-center gap-3">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-[#C83F43] shrink-0"></i>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Import Form Card --}}
            <div class="alerto-card p-6 lg:col-span-1">
                <h2 class="text-base font-black mb-1 flex items-center gap-2">
                    <i data-lucide="upload" class="w-4 h-4 text-[#E87524]"></i>
                    Import Boundary Dataset
                </h2>
                <p class="text-xs text-[#77736E] mb-5">
                    Upload official GeoJSON polygons provided by NAMRIA or the Municipal Planning &amp; Development Office (MPDO).
                </p>

                <form action="{{ route('admin.map.boundaries.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold mb-1">Boundary Classification <span class="text-[#C83F43]">*</span></label>
                        <select name="boundary_type" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] text-xs font-medium focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                            <option value="barangay">Barangay Boundary</option>
                            <option value="municipality">Municipality Boundary (Hinoba-an)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Target Entity <span class="text-[#C83F43]">*</span></label>
                        <select name="target_id" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] text-xs font-medium focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                            <optgroup label="Barangays">
                                @foreach($barangays as $bg)
                                    <option value="{{ $bg->id }}">{{ $bg->name }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Municipalities">
                                @foreach($municipalities as $mun)
                                    <option value="{{ $mun->id }}">{{ $mun->name }}, {{ $mun->province }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Source / Citation <span class="text-[#C83F43]">*</span></label>
                        <input
                            type="text"
                            name="source_information"
                            value="NAMRIA / Hinoba-an LGU GIS 2026"
                            class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] text-xs focus:outline-hidden focus:ring-2 focus:ring-[#E87524]"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Upload GeoJSON File (.json, .geojson)</label>
                        <input
                            type="file"
                            name="geojson_file"
                            accept=".json,.geojson,.txt"
                            class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] text-xs file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#E87524]/15 file:text-[#B94F12] dark:file:text-[#F29A56] hover:file:bg-[#E87524]/25"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Or Direct GeoJSON Text</label>
                        <textarea
                            name="geojson_content"
                            rows="5"
                            placeholder='{"type": "Feature", "geometry": {"type": "Polygon", "coordinates": [...]}}'
                            class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] text-xs font-mono focus:outline-hidden focus:ring-2 focus:ring-[#E87524]"
                        ></textarea>
                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            class="alerto-btn-press w-full py-2.5 px-4 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white text-xs font-bold transition-all shadow-md flex items-center justify-center gap-2"
                        >
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Validate &amp; Save Boundary</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Verified Barangays Boundary Table --}}
            <div class="alerto-card overflow-hidden lg:col-span-2">
                <div class="p-5 border-b border-[#E6E3DE] dark:border-white/10 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-black">Barangay Boundary Coverage</h2>
                        <p class="text-xs text-[#77736E]">13 Municipal Barangays of Hinoba-an, Negros Occidental</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56]">
                        {{ $barangays->where('boundary.is_verified', true)->count() }} / {{ $barangays->count() }} Verified
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#F5F3EF] dark:bg-white/5 border-b border-[#E6E3DE] dark:border-white/10 text-[11px] uppercase tracking-wider font-bold text-[#77736E]">
                            <tr>
                                <th class="py-3 px-4">Barangay</th>
                                <th class="py-3 px-4">Center Coordinates</th>
                                <th class="py-3 px-4">Boundary Status</th>
                                <th class="py-3 px-4">Source</th>
                                <th class="py-3 px-4">Imported At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E6E3DE] dark:divide-white/10 font-medium">
                            @foreach($barangays as $b)
                                <tr class="hover:bg-[#FFF0E4]/40 dark:hover:bg-white/5 transition-colors">
                                    <td class="py-3 px-4 font-bold">
                                        {{ $b->name }}
                                    </td>
                                    <td class="py-3 px-4 font-mono text-[11px] text-[#77736E]">
                                        {{ number_format((float)$b->center_latitude, 4) }}, {{ number_format((float)$b->center_longitude, 4) }}
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($b->boundary && $b->boundary->is_verified)
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#27845B]/15 text-[#27845B]">
                                                <i data-lucide="check-circle" class="w-3 h-3 text-[#27845B]"></i>
                                                Verified
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#D99024]/15 text-[#B94F12] dark:text-[#F29A56]">
                                                <i data-lucide="help-circle" class="w-3 h-3 text-[#D99024]"></i>
                                                Unconfigured
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-[#77736E]">
                                        {{ $b->boundary?->source_information ?? 'N/A' }}
                                    </td>
                                    <td class="py-3 px-4 text-[#77736E] font-mono text-[11px]">
                                        {{ $b->boundary?->imported_at ? $b->boundary->imported_at->format('M d, Y') : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
