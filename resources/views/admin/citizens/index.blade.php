<x-layouts.admin title="Citizen Accounts Directory — DIAL DAPH">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight">Registered Citizen Accounts</h1>
                <p class="text-xs text-slate-400 mt-1">Manage verified/unverified Citizen portal accounts across Hinoba-an.</p>
            </div>
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search name or phone..." class="px-3 py-2 rounded-xl border text-xs">
                <button type="submit" class="px-4 py-2 rounded-xl bg-[#3187D8] text-white text-xs font-bold">Filter</button>
            </form>
        </div>

        <div class="eoc-card overflow-hidden">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-white/10 text-[10px] uppercase text-slate-400">
                    <tr>
                        <th class="p-4">Citizen</th>
                        <th class="p-4">Phone &amp; Verification</th>
                        <th class="p-4">Primary Barangay</th>
                        <th class="p-4">Reports</th>
                        <th class="p-4 text-right">Status Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @foreach($citizens as $cit)
                        <tr>
                            <td class="p-4 font-bold text-white">{{ $cit->name }}<span class="block text-[10px] text-slate-400">{{ $cit->email ?? 'No email' }}</span></td>
                            <td class="p-4 font-mono">
                                {{ $cit->phone_number }}
                                <span class="ml-1 px-1.5 py-0.5 rounded text-[9px] font-bold {{ $cit->is_phone_verified ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300' }}">
                                    {{ $cit->is_phone_verified ? 'Verified' : 'Unverified' }}
                                </span>
                            </td>
                            <td class="p-4">{{ $cit->citizenAddresses->first()?->barangay?->name ?? 'Unassigned' }}</td>
                            <td class="p-4 font-mono font-bold">{{ $cit->reported_incidents_count }}</td>
                            <td class="p-4 text-right">
                                <form method="POST" action="{{ route('admin.citizens.toggle-status', $cit->id) }}">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-xl font-bold text-xs cursor-pointer {{ $cit->is_active ? 'bg-rose-600/80 text-white' : 'bg-emerald-600 text-white' }}">
                                        {{ $cit->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4 border-t border-white/10">{{ $citizens->links() }}</div>
        </div>
    </div>
</x-layouts.admin>

