<x-layouts.admin>
    <div class="space-y-6 max-w-4xl">

        {{-- Breadcrumb / Top Bar --}}
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-2">
                <a href="{{ route('admin.personnel.index') }}" class="hover:text-[#173957] font-medium">Personnel Directory</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-slate-800 font-bold">Edit Operative #{{ $profile->badge_number }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                Edit Personnel Profile
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Update account details, organizational department, assigned response teams, and territorial coverage.
            </p>
        </div>

        @if($errors->any())
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium">
                <div class="font-bold mb-1 flex items-center gap-2">
                    <i data-lucide="alert-octagon" class="w-4 h-4 text-rose-600"></i>
                    Please resolve the following errors:
                </div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.personnel.update', $profile->id) }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- 1. Account & Identity --}}
            <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                    <div class="w-8 h-8 rounded-lg bg-[#173957]/10 text-[#173957] flex items-center justify-center font-bold text-sm">1</div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">User Account &amp; Identity</h2>
                        <p class="text-xs text-slate-500">Contact information and authentication settings</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Full Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $profile->user?->name) }}" required 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Official Email Address <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $profile->user?->email) }}" required 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Mobile / Dispatch Radio Phone <span class="text-rose-500">*</span></label>
                        <input type="text" name="phone_number" value="{{ old('phone_number', $profile->user?->phone_number) }}" required 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">New Password (leave blank to keep current)</label>
                        <input type="password" name="password" minlength="8" 
                               placeholder="Optional new password" 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                    </div>
                </div>
            </div>

            {{-- 2. Operational Profile & Agency --}}
            <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                    <div class="w-8 h-8 rounded-lg bg-[#173957]/10 text-[#173957] flex items-center justify-center font-bold text-sm">2</div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Operational Profile &amp; Department</h2>
                        <p class="text-xs text-slate-500">Badge identifier, rank designation, and specialized skills</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Assigned Office / Station <span class="text-rose-500">*</span></label>
                        <select name="office_id" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                            @foreach($offices as $office)
                                <option value="{{ $office->id }}" {{ old('office_id', $profile->office_id) == $office->id ? 'selected' : '' }}>
                                    {{ $office->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Badge / Serial Number <span class="text-rose-500">*</span></label>
                        <input type="text" name="badge_number" value="{{ old('badge_number', $profile->badge_number) }}" required 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Username (Radio Handle)</label>
                        <input type="text" name="username" value="{{ old('username', $profile->username) }}" 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Rank / Designation <span class="text-rose-500">*</span></label>
                        <input type="text" name="rank_designation" value="{{ old('rank_designation', $profile->rank_designation) }}" required 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Specialization</label>
                        <input type="text" name="specialization" value="{{ old('specialization', $profile->specialization) }}" 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                    </div>

                    <div class="flex items-center pt-5">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_team_leader" value="1" 
                                   {{ old('is_team_leader', $profile->is_team_leader) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded text-[#173957] border-slate-300 focus:ring-[#3187D8]">
                            <span class="text-xs font-bold text-slate-800">Assign as Team Leader</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- 3. Team Assignment & Authorized Incident Categories --}}
            <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                    <div class="w-8 h-8 rounded-lg bg-[#173957]/10 text-[#173957] flex items-center justify-center font-bold text-sm">3</div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Teams &amp; Category Authorization</h2>
                        <p class="text-xs text-slate-500">Response units and types of emergency incidents this operative is certified to handle</p>
                    </div>
                </div>

                @php
                    $currentTeamIds = $profile->teams->pluck('id')->toArray();
                    $currentCategories = is_array($profile->authorized_categories) ? $profile->authorized_categories : [];
                    $currentBarangayIds = $profile->jurisdictions->pluck('id')->toArray();
                @endphp

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Assign to Response Teams</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                        @forelse($teams as $team)
                            <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 hover:border-[#3187D8]/50 hover:bg-blue-50/20 cursor-pointer transition-colors">
                                <input type="checkbox" name="team_ids[]" value="{{ $team->id }}" 
                                       {{ in_array($team->id, old('team_ids', $currentTeamIds)) ? 'checked' : '' }}
                                       class="w-4 h-4 rounded text-[#173957] border-slate-300 focus:ring-[#3187D8]">
                                <div>
                                    <div class="text-xs font-bold text-slate-900">{{ $team->team_name }}</div>
                                    <div class="text-[10px] text-slate-500 font-mono">Call Sign: {{ $team->call_sign }}</div>
                                </div>
                            </label>
                        @empty
                            <p class="text-xs text-slate-400 italic">No response teams found.</p>
                        @endforelse
                    </div>
                </div>

                <div class="pt-2">
                    <label class="block text-xs font-bold text-slate-700 mb-2">Authorized Emergency Categories</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                        @foreach($categories as $category)
                            <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 hover:border-[#3187D8]/50 cursor-pointer text-xs">
                                <input type="checkbox" name="authorized_categories[]" value="{{ $category->slug }}" 
                                       {{ in_array($category->slug, old('authorized_categories', $currentCategories)) ? 'checked' : '' }}
                                       class="w-3.5 h-3.5 rounded text-[#173957] border-slate-300 focus:ring-[#3187D8]">
                                <span class="font-medium text-slate-700 truncate">{{ $category->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- 4. Territorial Jurisdictions (Hinoba-an 13 Barangays) --}}
            <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                    <div class="w-8 h-8 rounded-lg bg-[#173957]/10 text-[#173957] flex items-center justify-center font-bold text-sm">4</div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Territorial Jurisdictions</h2>
                        <p class="text-xs text-slate-500">Assigned Hinoba-an barangays for primary or secondary dispatch</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5">
                    @foreach($barangays as $bg)
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 hover:border-[#3187D8]/50 hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" name="barangay_ids[]" value="{{ $bg->id }}" 
                                   {{ in_array($bg->id, old('barangay_ids', $currentBarangayIds)) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded text-[#173957] border-slate-300 focus:ring-[#3187D8]">
                            <span class="text-xs font-medium text-slate-800">{{ $bg->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.personnel.index') }}" 
                   class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-2.5 rounded-xl text-xs font-bold bg-[#173957] hover:bg-[#1f4b72] text-white shadow-md transition-all flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Save Profile Changes</span>
                </button>
            </div>
        </form>

    </div>
</x-layouts.admin>
