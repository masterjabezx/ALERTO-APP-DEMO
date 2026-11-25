<x-layouts.admin :title="'Response Bases — ALERTO EOC'">
    <div class="space-y-6" x-data="{ addBaseModalOpen: false }">

        {{-- Top Bar & Title --}}
        <x-portal.page-header
            badge="Staging Facilities · Hinoba-an"
            title="Response Bases &amp; Staging Posts"
            subtitle="Physical emergency deployment bases, ambulance outposts, and sub-stations across Hinoba-an."
        >
            <x-slot:actions>
                <button type="button"
                        @click="addBaseModalOpen = true"
                        class="alerto-btn-press inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white font-bold text-xs shadow-md transition-all">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Add Response Base</span>
                </button>
            </x-slot:actions>
        </x-portal.page-header>

        {{-- Sub-navigation Tabs --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-[#E6E3DE] dark:border-white/10">
            <a href="{{ route('admin.personnel.index') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.index') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="users" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Directory
            </a>
            <a href="{{ route('admin.personnel.teams') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.teams') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="shield" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Response Teams
            </a>
            <a href="{{ route('admin.personnel.jurisdictions') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.jurisdictions') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="map-pin" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Jurisdictions
            </a>
            <a href="{{ route('admin.personnel.availability') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.availability') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="activity" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Availability Monitor
            </a>
            <a href="{{ route('admin.personnel.assignments') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.assignments') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="radio" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Active Assignments
            </a>
            <a href="{{ route('admin.personnel.bases') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ request()->routeIs('admin.personnel.bases') ? 'bg-[#E87524] text-white shadow-xs' : 'text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10' }}">
                <i data-lucide="home" class="w-3.5 h-3.5 inline mr-1.5"></i>
                Response Bases ({{ $bases->count() }})
            </a>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-[#27845B]/15 border border-[#27845B]/30 text-[#27845B] text-xs font-medium flex items-center gap-3">
                <i data-lucide="check-circle" class="w-4 h-4 text-[#27845B] shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- Bases Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($bases as $base)
                <div class="alerto-interactive-card alerto-card p-5 flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56]">
                                    {{ $base->office?->name ?? 'LDRRMO Station' }}
                                </span>
                                <h3 class="text-base font-black mt-1">{{ $base->base_name }}</h3>
                            </div>
                            <span class="p-2 rounded-xl bg-[#E87524]/15 text-[#E87524]">
                                <i data-lucide="home" class="w-4 h-4"></i>
                            </span>
                        </div>

                        <div class="space-y-2 text-xs border-t border-[#E6E3DE] dark:border-white/10 pt-3">
                            <div>
                                <span class="text-[#77736E] block text-[10px] uppercase font-bold">Address &amp; Barangay:</span>
                                <span class="font-medium">{{ $base->address ?? 'Poblacion' }}, {{ $base->barangay?->name ?? 'Hinoba-an' }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-[#77736E]">GPS Coordinates:</span>
                                <span class="font-mono">{{ number_format($base->latitude, 4) }}, {{ number_format($base->longitude, 4) }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-[#77736E]">Hotline Phone:</span>
                                <span class="font-bold font-mono">{{ $base->contact_phone ?? 'N/A' }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-[#77736E]">Attached Units:</span>
                                <span class="font-bold text-[#B94F12] dark:text-[#F29A56]">{{ $base->teams->count() }} Team(s)</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-[#E6E3DE] dark:border-white/10 flex items-center justify-between">
                        <span class="text-[10px] text-[#77736E]">Facility ID: #{{ $base->id }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $base->is_active ? 'bg-[#27845B]/15 text-[#27845B]' : 'bg-black/5 dark:bg-white/10 text-[#77736E]' }}">
                            {{ $base->is_active ? 'Operational' : 'Inactive' }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-[#77736E] alerto-card">
                    <i data-lucide="home" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                    <p class="font-bold">No response bases registered.</p>
                    <p class="text-xs mt-1">Click "Add Response Base" to register deployment staging posts.</p>
                </div>
            @endforelse
        </div>

        {{-- Add Base Modal --}}
        <div x-show="addBaseModalOpen" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/65 backdrop-blur-xs"
             @keydown.escape.window="addBaseModalOpen = false">
            <div class="w-full max-w-lg alerto-card shadow-xl p-6" @click.away="addBaseModalOpen = false">
                <div class="flex items-center justify-between pb-3 border-b border-[#E6E3DE] dark:border-white/10 mb-4">
                    <h3 class="text-base font-bold">Add Response Base</h3>
                    <button type="button" @click="addBaseModalOpen = false" class="text-[#77736E] hover:text-[#17191C] dark:hover:text-white">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.personnel.bases.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold mb-1">Base / Station Name <span class="text-[#C83F43]">*</span></label>
                        <input type="text" name="base_name" required placeholder="e.g., Hinoba-an Central Rescue HQ"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold mb-1">Office / Station <span class="text-[#C83F43]">*</span></label>
                            <select name="office_id" required class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                                @foreach($offices as $office)
                                    <option value="{{ $office->id }}">{{ $office->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold mb-1">Barangay <span class="text-[#C83F43]">*</span></label>
                            <select name="barangay_id" required class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                                @foreach($barangays as $bg)
                                    <option value="{{ $bg->id }}">{{ $bg->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Street Address</label>
                        <input type="text" name="address" placeholder="e.g., National Highway, Poblacion 1"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold mb-1">Latitude <span class="text-[#C83F43]">*</span></label>
                            <input type="number" step="0.00000001" name="latitude" required value="9.4975"
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-bold mb-1">Longitude <span class="text-[#C83F43]">*</span></label>
                            <input type="number" step="0.00000001" name="longitude" required value="122.5850"
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Contact Phone</label>
                        <input type="text" name="contact_phone" placeholder="e.g., 0917-000-0000"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#E6E3DE] dark:border-white/10">
                        <button type="button" @click="addBaseModalOpen = false" class="px-4 py-2 text-xs font-bold text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10 rounded-xl">
                            Cancel
                        </button>
                        <button type="submit" class="alerto-btn-press px-5 py-2 text-xs font-bold bg-[#E87524] text-white hover:bg-[#B94F12] rounded-xl shadow-xs">
                            Register Facility
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-layouts.admin>
