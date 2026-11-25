<x-layouts.admin>
    <div class="space-y-6" x-data="{ addBaseModalOpen: false }">

        {{-- Top Bar & Title --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#173957]/10 text-[#173957] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="home" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                    Staging Facilities · Hinoba-an
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                    Response Bases &amp; Staging Posts
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Physical emergency deployment bases, ambulance outposts, and sub-stations across Hinoba-an.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" 
                        @click="addBaseModalOpen = true" 
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#173957] hover:bg-[#1f4b72] text-white font-bold text-xs shadow-md transition-all">
                    <i data-lucide="plus" class="w-4 h-4 text-[#3187D8]"></i>
                    <span>Add Response Base</span>
                </button>
            </div>
        </div>

        {{-- Sub-navigation Tabs --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-slate-200">
            <a href="{{ route('admin.personnel.index') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.index') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="users" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Directory
            </a>
            <a href="{{ route('admin.personnel.teams') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.teams') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="shield" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Response Teams
            </a>
            <a href="{{ route('admin.personnel.jurisdictions') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.jurisdictions') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="map-pin" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Jurisdictions
            </a>
            <a href="{{ route('admin.personnel.availability') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.availability') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="activity" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Availability Monitor
            </a>
            <a href="{{ route('admin.personnel.assignments') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.assignments') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="radio" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Active Assignments
            </a>
            <a href="{{ route('admin.personnel.bases') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.bases') ? 'bg-[#173957] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <i data-lucide="home" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Response Bases ({{ $bases->count() }})
            </a>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-3">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- Bases Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($bases as $base)
                <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs hover:border-[#3187D8]/50 transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-blue-100 text-blue-800">
                                    {{ $base->office?->name ?? 'LDRRMO Station' }}
                                </span>
                                <h3 class="text-base font-black text-slate-900 mt-1">{{ $base->base_name }}</h3>
                            </div>
                            <span class="p-2 rounded-xl bg-[#173957]/10 text-[#173957]">
                                <i data-lucide="home" class="w-4 h-4"></i>
                            </span>
                        </div>

                        <div class="space-y-2 text-xs border-t border-slate-100 pt-3">
                            <div class="text-slate-600">
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Address &amp; Barangay:</span>
                                <span class="font-medium text-slate-800">{{ $base->address ?? 'Poblacion' }}, {{ $base->barangay?->name ?? 'Hinoba-an' }}</span>
                            </div>

                            <div class="flex items-center justify-between text-slate-600">
                                <span class="text-slate-400">GPS Coordinates:</span>
                                <span class="font-mono text-slate-700">{{ number_format($base->latitude, 4) }}, {{ number_format($base->longitude, 4) }}</span>
                            </div>

                            <div class="flex items-center justify-between text-slate-600">
                                <span class="text-slate-400">Hotline Phone:</span>
                                <span class="font-bold text-slate-800 font-mono">{{ $base->contact_phone ?? 'N/A' }}</span>
                            </div>

                            <div class="flex items-center justify-between text-slate-600">
                                <span class="text-slate-400">Attached Units:</span>
                                <span class="font-bold text-[#173957]">{{ $base->teams->count() }} Team(s)</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-[10px] text-slate-400">Facility ID: #{{ $base->id }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $base->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                            {{ $base->is_active ? 'Operational' : 'Inactive' }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-slate-400 bg-white rounded-2xl border border-slate-200">
                    <i data-lucide="home" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                    <p class="font-bold text-slate-600">No response bases registered.</p>
                    <p class="text-xs text-slate-400 mt-1">Click "Add Response Base" to register deployment staging posts.</p>
                </div>
            @endforelse
        </div>

        {{-- Add Base Modal --}}
        <div x-show="addBaseModalOpen" x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
             @keydown.escape.window="addBaseModalOpen = false">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl p-6 border border-slate-200" @click.away="addBaseModalOpen = false">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="text-base font-bold text-slate-900">Add Response Base</h3>
                    <button type="button" @click="addBaseModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.personnel.bases.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Base / Station Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="base_name" required placeholder="e.g., Hinoba-an Central Rescue HQ" 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Office / Station <span class="text-rose-500">*</span></label>
                            <select name="office_id" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
                                @foreach($offices as $office)
                                    <option value="{{ $office->id }}">{{ $office->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Barangay <span class="text-rose-500">*</span></label>
                            <select name="barangay_id" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
                                @foreach($barangays as $bg)
                                    <option value="{{ $bg->id }}">{{ $bg->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Street Address</label>
                        <input type="text" name="address" placeholder="e.g., National Highway, Poblacion 1" 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Latitude <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.00000001" name="latitude" required value="9.4975" 
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Longitude <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.00000001" name="longitude" required value="122.5850" 
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Contact Phone</label>
                        <input type="text" name="contact_phone" placeholder="e.g., 0917-000-0000" 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="addBaseModalOpen = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-[#173957] text-white hover:bg-[#1f4b72] rounded-xl shadow-xs">
                            Register Facility
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-layouts.admin>
