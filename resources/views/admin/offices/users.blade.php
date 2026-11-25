<x-layouts.admin>
    <div class="space-y-6" x-data="{ createUserModalOpen: false, editUserModalOpen: false, activeUser: null, resetModalOpen: false }">

        {{-- Breadcrumb & Top Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                    <a href="{{ route('admin.offices.index') }}" class="hover:text-[#173957] font-medium">Office Agencies</a>
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    <span class="text-slate-800 font-bold">{{ $office->name }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                    Office Accounts: {{ $office->name }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                    Authorized Office Admins and Office Staff assigned to {{ $office->code }}.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" 
                        @click="createUserModalOpen = true" 
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#173957] hover:bg-[#1f4b72] text-white font-bold text-xs shadow-md transition-all">
                    <i data-lucide="user-plus" class="w-4 h-4 text-[#3187D8]"></i>
                    <span>Create Office Account</span>
                </button>
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-3">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Office Users Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Authorized Personnel &amp; Staff</h3>
                <span class="text-xs font-bold text-slate-500">{{ $office->officeUsers->count() }} Registered User(s)</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50/50 border-b border-slate-200 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                        <tr>
                            <th class="py-3 px-4">User &amp; Contact</th>
                            <th class="py-3 px-4">Permission Level</th>
                            <th class="py-3 px-4 text-center">Review Incidents</th>
                            <th class="py-3 px-4 text-center">Dispatch Units</th>
                            <th class="py-3 px-4">Presence Status</th>
                            <th class="py-3 px-4 text-center">Account</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($office->officeUsers as $ou)
                            @php
                                $u = $ou->user;
                                $presence = $u?->userPresence?->status ?? 'offline';
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ $u?->name ?? 'Unknown' }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $u?->email }} · {{ $u?->phone_number }}</div>
                                </td>

                                <td class="py-3 px-4">
                                    @if($ou->office_level === 'office_admin')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                            <i data-lucide="shield-check" class="w-3 h-3"></i>
                                            Office Admin
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                            Office Staff
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $ou->can_review_incidents ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $ou->can_review_incidents ? 'Authorized' : 'Restricted' }}
                                    </span>
                                </td>

                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $ou->can_dispatch_units ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $ou->can_dispatch_units ? 'Authorized' : 'Restricted' }}
                                    </span>
                                </td>

                                <td class="py-3 px-4">
                                    @if($presence === 'online')
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Online
                                        </span>
                                    @elseif($presence === 'connection_lost')
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700">
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span> Lost
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-50 text-slate-500">
                                            <span class="w-2 h-2 rounded-full bg-slate-300"></span> Offline
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3 px-4 text-center">
                                    <form method="POST" action="{{ route('admin.offices.users.toggle-status', [$office->id, $u->id]) }}" onsubmit="return confirm('Toggle status for this user?');">
                                        @csrf
                                        <button type="submit" class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $u?->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                            {{ $u?->is_active ? 'Active' : 'Disabled' }}
                                        </button>
                                    </form>
                                </td>

                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button" 
                                                @click="activeUser = {{ json_encode(['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'phone_number' => $u->phone_number, 'office_level' => $ou->office_level, 'can_review_incidents' => $ou->can_review_incidents, 'can_dispatch_units' => $ou->can_dispatch_units]) }}; editUserModalOpen = true"
                                                class="p-1 rounded text-slate-500 hover:text-slate-800 hover:bg-slate-100" title="Edit Permissions">
                                            <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                        </button>

                                        <button type="button" 
                                                @click="activeUser = {{ json_encode(['id' => $u->id, 'name' => $u->name]) }}; resetModalOpen = true"
                                                class="p-1 rounded text-slate-500 hover:text-amber-700 hover:bg-amber-50" title="Reset Password">
                                            <i data-lucide="key" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    No accounts configured for this office.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Create Office User Modal --}}
        <div x-show="createUserModalOpen" x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
             @keydown.escape.window="createUserModalOpen = false">
            <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-6 border border-slate-200" @click.away="createUserModalOpen = false">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="text-base font-bold text-slate-900">Create Office Account</h3>
                    <button type="button" @click="createUserModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.offices.users.store', $office->id) }}" class="space-y-3.5">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Full Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required placeholder="e.g., Captain Maria Santos" 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Official Email Address <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" required placeholder="e.g., m.santos@hinobaan.gov.ph" 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Mobile / Direct Phone <span class="text-rose-500">*</span></label>
                        <input type="text" name="phone_number" required placeholder="09171234567" 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Temporary Password (min 8 chars) <span class="text-rose-500">*</span></label>
                        <input type="password" name="password" required minlength="8" 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Office Level / Role <span class="text-rose-500">*</span></label>
                        <select name="office_level" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                            <option value="office_admin">Office Admin (Commander / Reviewer)</option>
                            <option value="office_staff" selected>Office Staff (Operations / Desk Officer)</option>
                        </select>
                    </div>

                    <div class="space-y-2 pt-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="can_review_incidents" value="1" checked 
                                   class="w-4 h-4 rounded text-[#173957] border-slate-300">
                            <span class="text-xs text-slate-800">Grant Incident Review &amp; Acceptance Authority</span>
                        </label>

                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="can_dispatch_units" value="1" 
                                   class="w-4 h-4 rounded text-[#173957] border-slate-300">
                            <span class="text-xs text-slate-800">Grant Team Dispatch Authorization</span>
                        </label>

                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="can_manage_accounts" value="1"
                                   class="w-4 h-4 rounded text-[#173957] border-slate-300">
                            <span class="text-xs text-slate-800">Grant Office Staff Account Creation Permission</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="createUserModalOpen = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-[#173957] text-white hover:bg-[#1f4b72] rounded-xl shadow-xs">
                            Create Account
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Edit Office User Permissions Modal --}}
        <div x-show="editUserModalOpen" x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
             @keydown.escape.window="editUserModalOpen = false">
            <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-6 border border-slate-200" @click.away="editUserModalOpen = false">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="text-base font-bold text-slate-900">Update User: <span x-text="activeUser?.name"></span></h3>
                    <button type="button" @click="editUserModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form :action="`{{ url('admin/offices/' . $office->id . '/users') }}/${activeUser?.id}`" method="POST" class="space-y-3.5">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Full Name</label>
                        <input type="text" name="name" :value="activeUser?.name" required 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" :value="activeUser?.email" required 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Phone Number</label>
                        <input type="text" name="phone_number" :value="activeUser?.phone_number" required 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Office Level</label>
                        <select name="office_level" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200">
                            <option value="office_admin" :selected="activeUser?.office_level === 'office_admin'">Office Admin</option>
                            <option value="office_staff" :selected="activeUser?.office_level === 'office_staff'">Office Staff</option>
                        </select>
                    </div>

                    <div class="space-y-2 pt-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="can_review_incidents" value="1" :checked="activeUser?.can_review_incidents" 
                                   class="w-4 h-4 rounded text-[#173957] border-slate-300">
                            <span class="text-xs text-slate-800">Can Review Incidents</span>
                        </label>

                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="can_dispatch_units" value="1" :checked="activeUser?.can_dispatch_units" 
                                   class="w-4 h-4 rounded text-[#173957] border-slate-300">
                            <span class="text-xs text-slate-800">Can Dispatch Units</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="editUserModalOpen = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-[#173957] text-white hover:bg-[#1f4b72] rounded-xl shadow-xs">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Reset Password Modal --}}
        <div x-show="resetModalOpen" x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
             @keydown.escape.window="resetModalOpen = false">
            <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-6 border border-slate-200" @click.away="resetModalOpen = false">
                <h3 class="text-base font-bold text-slate-900 mb-1">Reset Password</h3>
                <p class="text-xs text-slate-500 mb-4">Set a secure password for <span class="font-bold" x-text="activeUser?.name"></span>.</p>

                <form :action="`{{ url('admin/offices/' . $office->id . '/users') }}/${activeUser?.id}/reset-password`" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">New Password (min 8 chars)</label>
                        <input type="password" name="password" required minlength="8" 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="resetModalOpen = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold bg-[#173957] text-white hover:bg-[#1f4b72] rounded-xl shadow-xs">
                            Confirm Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-layouts.admin>
