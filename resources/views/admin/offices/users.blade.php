<x-layouts.admin :title="'Office Accounts — ALERTO EOC'">
    <div class="space-y-6" x-data="{ createUserModalOpen: false, editUserModalOpen: false, activeUser: null, resetModalOpen: false }">

        {{-- Breadcrumb & Top Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <x-portal.breadcrumbs :items="[
                    ['label' => 'Office Agencies', 'url' => route('admin.offices.index')],
                    ['label' => $office->name]
                ]" />
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight mt-2">
                    Office Accounts: {{ $office->name }}
                </h1>
                <p class="text-xs sm:text-sm text-[#77736E] mt-0.5">
                    Authorized Office Admins and Office Staff assigned to {{ $office->code }}.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button"
                        @click="createUserModalOpen = true"
                        class="alerto-btn-press inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white font-bold text-xs shadow-md transition-all">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    <span>Create Office Account</span>
                </button>
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="p-4 rounded-xl bg-[#27845B]/15 border border-[#27845B]/30 text-[#27845B] text-xs font-medium flex items-center gap-3">
                <i data-lucide="check-circle" class="w-4 h-4 text-[#27845B] shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="p-4 rounded-xl bg-[#C83F43]/15 border border-[#C83F43]/30 text-[#C83F43] text-xs font-medium">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Office Users Table --}}
        <div class="alerto-card overflow-hidden">
            <div class="p-4 bg-[#F5F3EF] dark:bg-white/5 border-b border-[#E6E3DE] dark:border-white/10 flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider">Authorized Personnel &amp; Staff</h3>
                <span class="text-xs font-bold text-[#77736E]">{{ $office->officeUsers->count() }} Registered User(s)</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3EF]/70 dark:bg-white/5 border-b border-[#E6E3DE] dark:border-white/10 text-[#77736E] uppercase text-[10px] tracking-wider font-bold">
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
                    <tbody class="divide-y divide-[#E6E3DE] dark:divide-white/10">
                        @forelse($office->officeUsers as $ou)
                            @php
                                $u = $ou->user;
                                $presence = $u?->userPresence?->status ?? 'offline';
                            @endphp
                            <tr class="hover:bg-[#FFF0E4]/40 dark:hover:bg-white/5 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="font-bold">{{ $u?->name ?? 'Unknown' }}</div>
                                    <div class="text-[11px] text-[#77736E]">{{ $u?->email }} · {{ $u?->phone_number }}</div>
                                </td>

                                <td class="py-3 px-4">
                                    @if($ou->office_level === 'office_admin')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56]">
                                            <i data-lucide="shield-check" class="w-3 h-3"></i>
                                            Office Admin
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#397BA8]/15 text-[#397BA8]">
                                            Office Staff
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $ou->can_review_incidents ? 'bg-[#27845B]/15 text-[#27845B]' : 'bg-black/5 dark:bg-white/10 text-[#77736E]' }}">
                                        {{ $ou->can_review_incidents ? 'Authorized' : 'Restricted' }}
                                    </span>
                                </td>

                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $ou->can_dispatch_units ? 'bg-[#27845B]/15 text-[#27845B]' : 'bg-black/5 dark:bg-white/10 text-[#77736E]' }}">
                                        {{ $ou->can_dispatch_units ? 'Authorized' : 'Restricted' }}
                                    </span>
                                </td>

                                <td class="py-3 px-4">
                                    @if($presence === 'online')
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-[#27845B]/15 text-[#27845B]">
                                            <span class="w-2 h-2 rounded-full bg-[#27845B] animate-pulse"></span> Online
                                        </span>
                                    @elseif($presence === 'connection_lost')
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-[#C83F43]/15 text-[#C83F43]">
                                            <span class="w-2 h-2 rounded-full bg-[#C83F43]"></span> Lost
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-black/5 dark:bg-white/10 text-[#77736E]">
                                            <span class="w-2 h-2 rounded-full bg-[#77736E]"></span> Offline
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3 px-4 text-center">
                                    <form method="POST" action="{{ route('admin.offices.users.toggle-status', [$office->id, $u->id]) }}" onsubmit="return confirm('Toggle status for this user?');">
                                        @csrf
                                        <button type="submit" class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $u?->is_active ? 'bg-[#27845B]/15 text-[#27845B]' : 'bg-[#C83F43]/15 text-[#C83F43]' }}">
                                            {{ $u?->is_active ? 'Active' : 'Disabled' }}
                                        </button>
                                    </form>
                                </td>

                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button"
                                                @click="activeUser = {{ json_encode(['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'phone_number' => $u->phone_number, 'office_level' => $ou->office_level, 'can_review_incidents' => $ou->can_review_incidents, 'can_dispatch_units' => $ou->can_dispatch_units]) }}; editUserModalOpen = true"
                                                class="p-1.5 rounded-lg text-[#77736E] hover:text-[#E87524] hover:bg-[#FFF0E4] dark:hover:bg-white/10" title="Edit Permissions">
                                            <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                        </button>

                                        <button type="button"
                                                @click="activeUser = {{ json_encode(['id' => $u->id, 'name' => $u->name]) }}; resetModalOpen = true"
                                                class="p-1.5 rounded-lg text-[#77736E] hover:text-[#D99024] hover:bg-[#D99024]/15" title="Reset Password">
                                            <i data-lucide="key" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-[#77736E]">
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
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/65 backdrop-blur-xs"
             @keydown.escape.window="createUserModalOpen = false">
            <div class="w-full max-w-md alerto-card shadow-xl p-6" @click.away="createUserModalOpen = false">
                <div class="flex items-center justify-between pb-3 border-b border-[#E6E3DE] dark:border-white/10 mb-4">
                    <h3 class="text-base font-bold">Create Office Account</h3>
                    <button type="button" @click="createUserModalOpen = false" class="text-[#77736E] hover:text-[#17191C] dark:hover:text-white">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.offices.users.store', $office->id) }}" class="space-y-3.5">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold mb-1">Full Name <span class="text-[#C83F43]">*</span></label>
                        <input type="text" name="name" required placeholder="e.g., Captain Maria Santos"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Official Email Address <span class="text-[#C83F43]">*</span></label>
                        <input type="email" name="email" required placeholder="e.g., m.santos@hinobaan.gov.ph"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Mobile / Direct Phone <span class="text-[#C83F43]">*</span></label>
                        <input type="text" name="phone_number" required placeholder="09171234567"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Temporary Password (min 8 chars) <span class="text-[#C83F43]">*</span></label>
                        <input type="password" name="password" required minlength="8"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Office Level / Role <span class="text-[#C83F43]">*</span></label>
                        <select name="office_level" required class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                            <option value="office_admin">Office Admin (Commander / Reviewer)</option>
                            <option value="office_staff" selected>Office Staff (Operations / Desk Officer)</option>
                        </select>
                    </div>

                    <div class="space-y-2 pt-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="can_review_incidents" value="1" checked
                                   class="w-4 h-4 rounded text-[#E87524] border-[#E6E3DE] focus:ring-[#E87524]">
                            <span class="text-xs">Grant Incident Review &amp; Acceptance Authority</span>
                        </label>

                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="can_dispatch_units" value="1"
                                   class="w-4 h-4 rounded text-[#E87524] border-[#E6E3DE] focus:ring-[#E87524]">
                            <span class="text-xs">Grant Team Dispatch Authorization</span>
                        </label>

                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="can_manage_accounts" value="1"
                                   class="w-4 h-4 rounded text-[#E87524] border-[#E6E3DE] focus:ring-[#E87524]">
                            <span class="text-xs">Grant Office Staff Account Creation Permission</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#E6E3DE] dark:border-white/10">
                        <button type="button" @click="createUserModalOpen = false" class="px-4 py-2 text-xs font-bold text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10 rounded-xl">
                            Cancel
                        </button>
                        <button type="submit" class="alerto-btn-press px-5 py-2 text-xs font-bold bg-[#E87524] text-white hover:bg-[#B94F12] rounded-xl shadow-xs">
                            Create Account
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Edit Office User Permissions Modal --}}
        <div x-show="editUserModalOpen" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/65 backdrop-blur-xs"
             @keydown.escape.window="editUserModalOpen = false">
            <div class="w-full max-w-md alerto-card shadow-xl p-6" @click.away="editUserModalOpen = false">
                <div class="flex items-center justify-between pb-3 border-b border-[#E6E3DE] dark:border-white/10 mb-4">
                    <h3 class="text-base font-bold">Update User: <span x-text="activeUser?.name"></span></h3>
                    <button type="button" @click="editUserModalOpen = false" class="text-[#77736E] hover:text-[#17191C] dark:hover:text-white">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form :action="`{{ url('admin/offices/' . $office->id . '/users') }}/${activeUser?.id}`" method="POST" class="space-y-3.5">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold mb-1">Full Name</label>
                        <input type="text" name="name" :value="activeUser?.name" required
                               class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Email</label>
                        <input type="email" name="email" :value="activeUser?.email" required
                               class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Phone Number</label>
                        <input type="text" name="phone_number" :value="activeUser?.phone_number" required
                               class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Office Level</label>
                        <select name="office_level" class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                            <option value="office_admin" :selected="activeUser?.office_level === 'office_admin'">Office Admin</option>
                            <option value="office_staff" :selected="activeUser?.office_level === 'office_staff'">Office Staff</option>
                        </select>
                    </div>

                    <div class="space-y-2 pt-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="can_review_incidents" value="1" :checked="activeUser?.can_review_incidents"
                                   class="w-4 h-4 rounded text-[#E87524] border-[#E6E3DE] focus:ring-[#E87524]">
                            <span class="text-xs">Can Review Incidents</span>
                        </label>

                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="can_dispatch_units" value="1" :checked="activeUser?.can_dispatch_units"
                                   class="w-4 h-4 rounded text-[#E87524] border-[#E6E3DE] focus:ring-[#E87524]">
                            <span class="text-xs">Can Dispatch Units</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#E6E3DE] dark:border-white/10">
                        <button type="button" @click="editUserModalOpen = false" class="px-4 py-2 text-xs font-bold text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10 rounded-xl">
                            Cancel
                        </button>
                        <button type="submit" class="alerto-btn-press px-5 py-2 text-xs font-bold bg-[#E87524] text-white hover:bg-[#B94F12] rounded-xl shadow-xs">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Reset Password Modal --}}
        <div x-show="resetModalOpen" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/65 backdrop-blur-xs"
             @keydown.escape.window="resetModalOpen = false">
            <div class="w-full max-w-md alerto-card shadow-xl p-6" @click.away="resetModalOpen = false">
                <h3 class="text-base font-bold mb-1">Reset Password</h3>
                <p class="text-xs text-[#77736E] mb-4">Set a secure password for <span class="font-bold" x-text="activeUser?.name"></span>.</p>

                <form :action="`{{ url('admin/offices/' . $office->id . '/users') }}/${activeUser?.id}/reset-password`" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold mb-1">New Password (min 8 chars)</label>
                        <input type="password" name="password" required minlength="8"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="resetModalOpen = false" class="px-4 py-2 text-xs font-bold text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10 rounded-xl">
                            Cancel
                        </button>
                        <button type="submit" class="alerto-btn-press px-4 py-2 text-xs font-bold bg-[#E87524] text-white hover:bg-[#B94F12] rounded-xl shadow-xs">
                            Confirm Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-layouts.admin>
