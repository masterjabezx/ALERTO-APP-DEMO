<x-layouts.admin>
    <div class="space-y-6">
        {{-- Header & Navigation --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#173957]/10 text-[#173957] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="layers" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                    GIS Administration · Hinoba-an
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                    Verified GeoJSON Boundary Management
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Import and verify official municipal and barangay spatial boundary polygons. Point-in-polygon resolution strictly relies on these verified boundaries.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a 
                    href="{{ route('admin.map') }}" 
                    class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold transition-all shadow-xs flex items-center gap-2"
                >
                    <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500"></i>
                    <span>Back to Live Operations Map</span>
                </a>
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-3">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600 shrink-0"></i>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Import Form Card --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 lg:col-span-1">
                <h2 class="text-base font-black text-slate-900 mb-1 flex items-center gap-2">
                    <i data-lucide="upload" class="w-4 h-4 text-[#3187D8]"></i>
                    Import Boundary Dataset
                </h2>
                <p class="text-xs text-slate-500 mb-5">
                    Upload official GeoJSON polygons provided by NAMRIA or the Municipal Planning &amp; Development Office (MPDO).
                </p>

                <form action="{{ route('admin.map.boundaries.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Boundary Classification <span class="text-rose-500">*</span></label>
                        <select name="boundary_type" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-[#3187D8]">
                            <option value="barangay">Barangay Boundary</option>
                            <option value="municipality">Municipality Boundary (Hinoba-an)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Target Entity <span class="text-rose-500">*</span></label>
                        <select name="target_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-[#3187D8]">
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
                        <label class="block text-xs font-bold text-slate-700 mb-1">Source / Citation <span class="text-rose-500">*</span></label>
                        <input 
                            type="text" 
                            name="source_information" 
                            value="NAMRIA / Hinoba-an LGU GIS 2026"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#3187D8]"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Upload GeoJSON File (.json, .geojson)</label>
                        <input 
                            type="file" 
                            name="geojson_file" 
                            accept=".json,.geojson,.txt"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#3187D8]/10 file:text-[#3187D8] hover:file:bg-[#3187D8]/20"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Or Direct GeoJSON Text</label>
                        <textarea 
                            name="geojson_content" 
                            rows="5"
                            placeholder='{"type": "Feature", "geometry": {"type": "Polygon", "coordinates": [...]}}'
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono focus:ring-2 focus:ring-[#3187D8]"
                        ></textarea>
                    </div>

                    <div class="pt-2">
                        <button 
                            type="submit" 
                            class="w-full py-2.5 px-4 rounded-xl bg-[#3187D8] text-white text-xs font-bold hover:bg-[#286eb3] transition-all shadow-md flex items-center justify-center gap-2"
                        >
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Validate &amp; Save Boundary</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Verified Barangays Boundary Table --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden lg:col-span-2">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-black text-slate-900">Barangay Boundary Coverage</h2>
                        <p class="text-xs text-slate-500">13 Municipal Barangays of Hinoba-an, Negros Occidental</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                        {{ $barangays->where('boundary.is_verified', true)->count() }} / {{ $barangays->count() }} Verified
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 border-b border-slate-100 text-[11px] uppercase tracking-wider font-bold text-slate-700">
                            <tr>
                                <th class="py-3 px-4">Barangay</th>
                                <th class="py-3 px-4">Center Coordinates</th>
                                <th class="py-3 px-4">Boundary Status</th>
                                <th class="py-3 px-4">Source</th>
                                <th class="py-3 px-4">Imported At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @foreach($barangays as $b)
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-3 px-4 font-bold text-slate-900">
                                        {{ $b->name }}
                                    </td>
                                    <td class="py-3 px-4 font-mono text-[11px] text-slate-600">
                                        {{ number_format((float)$b->center_latitude, 4) }}, {{ number_format((float)$b->center_longitude, 4) }}
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($b->boundary && $b->boundary->is_verified)
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                <i data-lucide="check-circle" class="w-3 h-3 text-emerald-600"></i>
                                                Verified
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                                <i data-lucide="help-circle" class="w-3 h-3 text-amber-600"></i>
                                                Unconfigured
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-slate-500">
                                        {{ $b->boundary?->source_information ?? 'N/A' }}
                                    </td>
                                    <td class="py-3 px-4 text-slate-400 font-mono text-[11px]">
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

