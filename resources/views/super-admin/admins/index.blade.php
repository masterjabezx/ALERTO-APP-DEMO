<x-layouts.super-admin :title="'Admin Accounts & Permissions — LDRRMO Super Admin'">
    @php
        $exclusiveNames = ['system:manage_admins', 'system:manage_permissions', 'system:backups', 'incident:super_override'];
        $grantablePermissions = $permissions->reject(fn ($p) => in_array($p->name, $exclusiveNames, true));
    @endphp

    <div class="space-y-6">
        <x-portal.page-header
            eyebrow="Super Admin Governance"
            title="DIAL DAPH Admin Accounts &amp; Permissions"
            subtitle="Only LDRRMO Super Admin may provision Command Center Admin accounts, issue single-use setup invitations, or assign operational permissions."
            :breadcrumbs="[['label' => 'Executive Dashboard', 'href' => route('super-admin.dashboard')], ['label' => 'Admin Accounts']]"
        />

        @if(session('generated_setup_url'))
            <div class="eoc-card p-5 border border-[#E87524]/60 bg-[#E87524]/10 space-y-2 text-xs" x-data="{ copied: false }">
                <div class="flex items-center justify-between gap-3">
                    <span class="font-black uppercase tracking-wider text-[#E87524]">Single-Use Admin Setup Link (Copy &amp; Share Securely Now)</span>
                    <button type="button"
                            @click="navigator.clipboard?.writeText(@js(session('generated_setup_url'))); copied = true"
                            class="px-3.5 py-1.5 rounded-lg bg-[#E87524] hover:bg-[#B94F12] text-white font-bold cursor-pointer btn-fixed">
                        <span x-text="copied ? 'Copied!' : 'Copy URL'"></span>
                    </button>
                </div>
                <p class="font-mono break-all bg-black/25 p-3 rounded-xl border border-white/10">{{ session('generated_setup_url') }}</p>
            </div>
        @endif

        {{-- Provisioning Forms --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 text-xs">
            <form method="POST" action="{{ route('super-admin.admins.store') }}" class="eoc-card p-6 border-t-2 border-t-[#E87524] space-y-4">
                @csrf
                <h2 class="text-sm font-black uppercase tracking-wider text-[#E87524]">Create DIAL DAPH Admin Account</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[#9D9993] font-bold mb-1">Full Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full min-h-[40px] px-3 py-2 rounded-xl border">
                    </div>
                    <div>
                        <label class="block text-[#9D9993] font-bold mb-1">Phone Number</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number') }}" required placeholder="09XXXXXXXXX" class="w-full min-h-[40px] px-3 py-2 rounded-xl border font-mono">
                    </div>
                    <div>
                        <label class="block text-[#9D9993] font-bold mb-1">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="w-full min-h-[40px] px-3 py-2 rounded-xl border">
                    </div>
                    <div>
                        <label class="block text-[#9D9993] font-bold mb-1">Initial Password (min 10 chars)</label>
                        <input type="password" name="password" minlength="10" required class="w-full min-h-[40px] px-3 py-2 rounded-xl border">
                    </div>
                </div>

                <div>
                    <label class="block text-[#9D9993] font-bold mb-2">Command Center Permissions</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-44 overflow-y-auto alerto-scrollbar p-3 rounded-xl bg-black/20 border border-white/10">
                        @foreach($grantablePermissions as $perm)
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="permission_ids[]" value="{{ $perm->id }}" class="rounded text-[#E87524] focus:ring-[#E87524]">
                                <span class="truncate" title="{{ $perm->name }}">{{ $perm->display_name ?: $perm->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <label class="inline-flex items-center gap-2 font-semibold cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded text-[#E87524] focus:ring-[#E87524]">
                    Activate account immediately
                </label>

                <button type="submit" class="w-full py-3 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white font-black cursor-pointer transition-colors btn-fixed">
                    Create DIAL DAPH Admin
                </button>
            </form>

            <form method="POST" action="{{ route('super-admin.admins.invite') }}" class="eoc-card p-6 border-t-2 border-t-[#397BA8] space-y-4">
                @csrf
                <h2 class="text-sm font-black uppercase tracking-wider text-[#397BA8]">Generate Single-Use Admin Setup Link</h2>
                <p class="text-[11px] text-[#9D9993]">Creates a cryptographic token link so the invited operator sets their own password privately.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[#9D9993] font-bold mb-1">Invitee Name</label>
                        <input type="text" name="name" required class="w-full min-h-[40px] px-3 py-2 rounded-xl border">
                    </div>
                    <div>
                        <label class="block text-[#9D9993] font-bold mb-1">Invitee Email</label>
                        <input type="email" name="email" required class="w-full min-h-[40px] px-3 py-2 rounded-xl border">
                    </div>
                    <div>
                        <label class="block text-[#9D9993] font-bold mb-1">Phone Number (Optional)</label>
                        <input type="text" name="phone_number" class="w-full min-h-[40px] px-3 py-2 rounded-xl border font-mono">
                    </div>
                    <div>
                        <label class="block text-[#9D9993] font-bold mb-1">Link Validity (Hours)</label>
                        <input type="number" name="expires_hours" value="48" min="1" max="168" class="w-full min-h-[40px] px-3 py-2 rounded-xl border font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-[#9D9993] font-bold mb-2">Pre-Assigned Permissions</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-40 overflow-y-auto alerto-scrollbar p-3 rounded-xl bg-black/20 border border-white/10">
                        @foreach($grantablePermissions as $perm)
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="{{ $perm->name }}" class="rounded text-[#E87524] focus:ring-[#E87524]">
                                <span class="truncate">{{ $perm->display_name ?: $perm->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="w-full py-3 rounded-xl bg-[#292D32] hover:bg-[#E87524] border border-white/15 text-white font-black cursor-pointer transition-colors btn-fixed">
                    Generate Setup Invitation Link
                </button>
            </form>
        </div>

        {{-- Directory --}}
        <div class="eoc-card overflow-hidden text-xs">
            <div class="p-4 border-b border-white/10">
                <h2 class="text-sm font-black uppercase tracking-wider">Command Center &amp; Super Admin Directory ({{ $admins->count() }})</h2>
            </div>

            <div class="divide-y divide-white/10">
                @foreach($admins as $adm)
                    <div class="p-5 space-y-3" x-data="{ editing: false }">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-black text-sm">{{ $adm->name }}</span>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase {{ $adm->isSuperAdmin() ? 'bg-[#E87524] text-white badge-fixed' : 'bg-[#397BA8]/20 text-[#397BA8]' }}">
                                        {{ $adm->isSuperAdmin() ? 'LDRRMO SUPER ADMIN' : 'DIAL DAPH ADMIN' }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $adm->is_active ? 'bg-[#27845B]/20 text-[#27845B]' : 'bg-[#C83F43]/20 text-[#C83F43]' }}">
                                        {{ $adm->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                <p class="font-mono text-[#9D9993] mt-1">{{ $adm->email }} · {{ $adm->phone_number }}</p>
                                @if(!$adm->isSuperAdmin() && $adm->permissions->isNotEmpty())
                                    <p class="text-[11px] text-[#9D9993] mt-1">
                                        Permissions: {{ $adm->permissions->pluck('name')->implode(', ') }}
                                    </p>
                                @endif
                            </div>

                            @if(!$adm->isSuperAdmin() || (int) $adm->id === (int) auth()->id())
                                <button type="button" @click="editing = !editing"
                                        class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-[#E87524] hover:text-white font-bold cursor-pointer self-start transition-colors">
                                    <span x-text="editing ? 'Close Editor' : 'Edit Account & Permissions'"></span>
                                </button>
                            @endif
                        </div>

                        <form x-show="editing" x-cloak method="POST" action="{{ route('super-admin.admins.update', $adm->id) }}"
                              class="p-4 rounded-xl bg-black/20 border border-white/10 space-y-3">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="block text-[#9D9993] mb-1">Name</label>
                                    <input type="text" name="name" value="{{ $adm->name }}" required class="w-full px-3 py-2 rounded-xl border">
                                </div>
                                <div>
                                    <label class="block text-[#9D9993] mb-1">Email</label>
                                    <input type="email" name="email" value="{{ $adm->email }}" required class="w-full px-3 py-2 rounded-xl border">
                                </div>
                                <div>
                                    <label class="block text-[#9D9993] mb-1">Phone</label>
                                    <input type="text" name="phone_number" value="{{ $adm->phone_number }}" required class="w-full px-3 py-2 rounded-xl border font-mono">
                                </div>
                                <div>
                                    <label class="block text-[#9D9993] mb-1">Reset Password (Optional)</label>
                                    <input type="password" name="password" minlength="10" placeholder="Leave blank to keep" class="w-full px-3 py-2 rounded-xl border">
                                </div>
                            </div>

                            @if(!$adm->isSuperAdmin())
                                @php $heldIds = $adm->permissions->pluck('id')->all(); @endphp
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 p-3 rounded-xl bg-black/20 border border-white/10 max-h-40 overflow-y-auto alerto-scrollbar">
                                    @foreach($grantablePermissions as $perm)
                                        <label class="inline-flex items-center gap-2">
                                            <input type="checkbox" name="permission_ids[]" value="{{ $perm->id }}" {{ in_array($perm->id, $heldIds, true) ? 'checked' : '' }} class="rounded text-[#E87524]">
                                            <span class="truncate">{{ $perm->display_name ?: $perm->name }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                <label class="inline-flex items-center gap-2 font-semibold">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ $adm->is_active ? 'checked' : '' }} class="rounded text-[#E87524]">
                                    Account Active
                                </label>
                            @endif

                            <div class="flex justify-end">
                                <button type="submit" class="px-4 py-2 rounded-xl bg-[#27845B] hover:bg-[#1e6a49] text-white font-bold cursor-pointer btn-fixed">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Recent Setup Invitations --}}
        <div class="eoc-card overflow-hidden text-xs">
            <div class="p-4 border-b border-white/10">
                <h2 class="text-sm font-black uppercase tracking-wider">Recent Admin Setup Invitations</h2>
            </div>
            <div class="overflow-x-auto alerto-scrollbar">
                <table class="w-full text-left">
                    <thead class="border-b border-white/10 text-[10px] uppercase text-[#9D9993]">
                        <tr>
                            <th class="p-4">Invitee</th>
                            <th class="p-4">Invited By</th>
                            <th class="p-4">Expires At</th>
                            <th class="p-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($invitations as $inv)
                            <tr>
                                <td class="p-4">
                                    <span class="font-bold block">{{ $inv->name }}</span>
                                    <span class="font-mono text-[#9D9993]">{{ $inv->email }}</span>
                                </td>
                                <td class="p-4">{{ $inv->inviter?->name ?? 'System' }}</td>
                                <td class="p-4 font-mono text-[#9D9993]">{{ $inv->expires_at?->format('Y-m-d H:i') }}</td>
                                <td class="p-4">
                                    @if($inv->accepted_at)
                                        <span class="px-2.5 py-0.5 rounded-full bg-[#27845B]/20 text-[#27845B] font-bold uppercase text-[10px]">Accepted {{ $inv->accepted_at->diffForHumans() }}</span>
                                    @elseif($inv->isValid())
                                        <span class="px-2.5 py-0.5 rounded-full bg-[#E87524]/20 text-[#E87524] font-bold uppercase text-[10px]">Pending Use</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full bg-[#C83F43]/20 text-[#C83F43] font-bold uppercase text-[10px]">Expired</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-8 text-center text-[#9D9993]">No admin invitations generated yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.super-admin>
