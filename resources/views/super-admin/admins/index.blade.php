<x-layouts.super-admin :title="'Admin Accounts & Permissions — LDRRMO Super Admin'">
    @php
        $exclusiveNames = ['system:manage_admins', 'system:manage_permissions', 'system:backups', 'incident:super_override'];
        $grantablePermissions = $permissions->reject(fn ($p) => in_array($p->name, $exclusiveNames, true));
    @endphp

    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-white">DIAL DAPH Admin Accounts &amp; Permissions</h1>
            <p class="text-xs text-slate-400 mt-1">
                Only LDRRMO Super Admin may provision Command Center Admin accounts, issue cryptographic single-use setup invitations, or assign operational permissions.
            </p>
        </div>

        @if(session('generated_setup_url'))
            <div class="p-5 rounded-2xl bg-purple-950/60 border border-purple-400/60 space-y-2 text-xs" x-data="{ copied: false }">
                <div class="flex items-center justify-between gap-3">
                    <span class="font-black uppercase tracking-wider text-purple-200">Single-Use Admin Setup Link (Copy &amp; Share Securely Now)</span>
                    <button type="button"
                            @click="navigator.clipboard?.writeText(@js(session('generated_setup_url'))); copied = true"
                            class="px-3 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-white font-bold cursor-pointer">
                        <span x-text="copied ? 'Copied!' : 'Copy URL'"></span>
                    </button>
                </div>
                <p class="font-mono text-cyan-300 break-all bg-black/30 p-3 rounded-xl">{{ session('generated_setup_url') }}</p>
            </div>
        @endif

        {{-- Provisioning Forms --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 text-xs">
            {{-- Direct Creation --}}
            <form method="POST" action="{{ route('super-admin.admins.store') }}" class="p-6 rounded-2xl bg-[#0B1726] border border-purple-500/30 space-y-4">
                @csrf
                <h2 class="text-sm font-black uppercase tracking-wider text-purple-300">Create DIAL DAPH Admin Account</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-400 font-bold mb-1">Full Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                    </div>
                    <div>
                        <label class="block text-slate-400 font-bold mb-1">Phone Number</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number') }}" required placeholder="09XXXXXXXXX" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white font-mono">
                    </div>
                    <div>
                        <label class="block text-slate-400 font-bold mb-1">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                    </div>
                    <div>
                        <label class="block text-slate-400 font-bold mb-1">Initial Password (min 10 chars)</label>
                        <input type="password" name="password" minlength="10" required class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-slate-400 font-bold mb-2">Command Center Permissions</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-44 overflow-y-auto alerto-scrollbar p-3 rounded-xl bg-black/25 border border-white/10">
                        @foreach($grantablePermissions as $perm)
                            <label class="inline-flex items-center gap-2 text-slate-200 cursor-pointer">
                                <input type="checkbox" name="permission_ids[]" value="{{ $perm->id }}" class="rounded">
                                <span class="truncate" title="{{ $perm->name }}">{{ $perm->display_name ?: $perm->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <label class="inline-flex items-center gap-2 text-slate-200 font-semibold">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded">
                    Activate account immediately
                </label>

                <button type="submit" class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-black cursor-pointer">
                    Create DIAL DAPH Admin
                </button>
            </form>

            {{-- One-Time Invitation Generator --}}
            <form method="POST" action="{{ route('super-admin.admins.invite') }}" class="p-6 rounded-2xl bg-[#0B1726] border border-indigo-500/30 space-y-4">
                @csrf
                <h2 class="text-sm font-black uppercase tracking-wider text-indigo-300">Generate Single-Use Admin Setup Link</h2>
                <p class="text-[11px] text-slate-400">Creates a cryptographic token link so the invited operator sets their own password privately.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-400 font-bold mb-1">Invitee Name</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                    </div>
                    <div>
                        <label class="block text-slate-400 font-bold mb-1">Invitee Email</label>
                        <input type="email" name="email" required class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                    </div>
                    <div>
                        <label class="block text-slate-400 font-bold mb-1">Phone Number (Optional)</label>
                        <input type="text" name="phone_number" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white font-mono">
                    </div>
                    <div>
                        <label class="block text-slate-400 font-bold mb-1">Link Validity (Hours)</label>
                        <input type="number" name="expires_hours" value="48" min="1" max="168" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-slate-400 font-bold mb-2">Pre-Assigned Permissions</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-40 overflow-y-auto alerto-scrollbar p-3 rounded-xl bg-black/25 border border-white/10">
                        @foreach($grantablePermissions as $perm)
                            <label class="inline-flex items-center gap-2 text-slate-200 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="{{ $perm->name }}" class="rounded">
                                <span class="truncate">{{ $perm->display_name ?: $perm->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-black cursor-pointer">
                    Generate Setup Invitation Link
                </button>
            </form>
        </div>

        {{-- Existing Admin & Super Admin Accounts --}}
        <div class="rounded-2xl bg-[#0B1726] border border-white/10 overflow-hidden text-xs">
            <div class="p-4 border-b border-white/10">
                <h2 class="text-sm font-black uppercase tracking-wider text-white">Command Center &amp; Super Admin Directory ({{ $admins->count() }})</h2>
            </div>

            <div class="divide-y divide-white/10">
                @foreach($admins as $adm)
                    <div class="p-5 space-y-3" x-data="{ editing: false }">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-black text-sm text-white">{{ $adm->name }}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase {{ $adm->isSuperAdmin() ? 'bg-purple-600 text-white' : 'bg-cyan-500/20 text-cyan-300' }}">
                                        {{ $adm->isSuperAdmin() ? 'LDRRMO SUPER ADMIN' : 'DIAL DAPH ADMIN' }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $adm->is_active ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300' }}">
                                        {{ $adm->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                <p class="font-mono text-slate-400 mt-1">{{ $adm->email }} · {{ $adm->phone_number }}</p>
                                @if(!$adm->isSuperAdmin() && $adm->permissions->isNotEmpty())
                                    <p class="text-[11px] text-slate-300 mt-1">
                                        Permissions: {{ $adm->permissions->pluck('name')->implode(', ') }}
                                    </p>
                                @endif
                            </div>

                            @if(!$adm->isSuperAdmin() || (int) $adm->id === (int) auth()->id())
                                <button type="button" @click="editing = !editing"
                                        class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold cursor-pointer self-start">
                                    <span x-text="editing ? 'Close Editor' : 'Edit Account & Permissions'"></span>
                                </button>
                            @endif
                        </div>

                        <form x-show="editing" x-cloak method="POST" action="{{ route('super-admin.admins.update', $adm->id) }}"
                              class="p-4 rounded-xl bg-black/30 border border-white/10 space-y-3">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="block text-slate-400 mb-1">Name</label>
                                    <input type="text" name="name" value="{{ $adm->name }}" required class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                                </div>
                                <div>
                                    <label class="block text-slate-400 mb-1">Email</label>
                                    <input type="email" name="email" value="{{ $adm->email }}" required class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                                </div>
                                <div>
                                    <label class="block text-slate-400 mb-1">Phone</label>
                                    <input type="text" name="phone_number" value="{{ $adm->phone_number }}" required class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white font-mono">
                                </div>
                                <div>
                                    <label class="block text-slate-400 mb-1">Reset Password (Optional)</label>
                                    <input type="password" name="password" minlength="10" placeholder="Leave blank to keep" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                                </div>
                            </div>

                            @if(!$adm->isSuperAdmin())
                                @php $heldIds = $adm->permissions->pluck('id')->all(); @endphp
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 p-3 rounded-xl bg-slate-900/70 border border-white/10 max-h-40 overflow-y-auto alerto-scrollbar">
                                    @foreach($grantablePermissions as $perm)
                                        <label class="inline-flex items-center gap-2 text-slate-200">
                                            <input type="checkbox" name="permission_ids[]" value="{{ $perm->id }}" {{ in_array($perm->id, $heldIds, true) ? 'checked' : '' }} class="rounded">
                                            <span class="truncate">{{ $perm->display_name ?: $perm->name }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                <label class="inline-flex items-center gap-2 text-slate-200 font-semibold">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ $adm->is_active ? 'checked' : '' }} class="rounded">
                                    Account Active
                                </label>
                            @endif

                            <div class="flex justify-end">
                                <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold cursor-pointer">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Recent Setup Invitations --}}
        <div class="rounded-2xl bg-[#0B1726] border border-white/10 overflow-hidden text-xs">
            <div class="p-4 border-b border-white/10">
                <h2 class="text-sm font-black uppercase tracking-wider text-slate-300">Recent Admin Setup Invitations</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="border-b border-white/10 text-[10px] uppercase text-slate-400">
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
                                    <span class="font-bold text-white block">{{ $inv->name }}</span>
                                    <span class="font-mono text-slate-400">{{ $inv->email }}</span>
                                </td>
                                <td class="p-4 text-slate-300">{{ $inv->inviter?->name ?? 'System' }}</td>
                                <td class="p-4 font-mono text-slate-400">{{ $inv->expires_at?->format('Y-m-d H:i') }}</td>
                                <td class="p-4">
                                    @if($inv->accepted_at)
                                        <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-bold uppercase text-[10px]">Accepted {{ $inv->accepted_at->diffForHumans() }}</span>
                                    @elseif($inv->isValid())
                                        <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 font-bold uppercase text-[10px]">Pending Use</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 font-bold uppercase text-[10px]">Expired</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-6 text-center text-slate-400">No admin invitations generated yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.super-admin>
