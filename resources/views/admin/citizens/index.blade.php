<x-layouts.admin title="Citizen Accounts Directory — DIAL DAPH">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <x-portal.page-header
                badge="Citizen Directory · DIAL DAPH"
                title="Registered Citizen Accounts"
                subtitle="Manage verified and unverified Citizen portal accounts across Hinoba-an."
            />
            <form method="GET" class="flex gap-2 shrink-0">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search name or phone..."
                       class="px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] text-xs focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                <button type="submit" class="alerto-btn-press px-4 py-2 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white text-xs font-bold">
                    Filter
                </button>
            </form>
        </div>

        <div class="alerto-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F5F3EF] dark:bg-white/5 border-b border-[#E6E3DE] dark:border-white/10 text-[10px] uppercase tracking-wider text-[#77736E] font-bold">
                        <tr>
                            <th class="p-4">Citizen</th>
                            <th class="p-4">Phone &amp; Verification</th>
                            <th class="p-4">Primary Barangay</th>
                            <th class="p-4">Reports</th>
                            <th class="p-4 text-right">Status Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E6E3DE] dark:divide-white/10">
                        @foreach($citizens as $cit)
                            <tr class="hover:bg-[#FFF0E4]/40 dark:hover:bg-white/5 transition-colors">
                                <td class="p-4 font-bold">
                                    {{ $cit->name }}
                                    <span class="block text-[10px] font-normal text-[#77736E]">{{ $cit->email ?? 'No email' }}</span>
                                </td>
                                <td class="p-4 font-mono">
                                    {{ $cit->phone_number }}
                                    <span class="ml-1 px-1.5 py-0.5 rounded text-[9px] font-bold {{ $cit->is_phone_verified ? 'bg-[#27845B]/15 text-[#27845B]' : 'bg-[#D99024]/15 text-[#B94F12] dark:text-[#F29A56]' }}">
                                        {{ $cit->is_phone_verified ? 'Verified' : 'Unverified' }}
                                    </span>
                                </td>
                                <td class="p-4">{{ $cit->citizenAddresses->first()?->barangay?->name ?? 'Unassigned' }}</td>
                                <td class="p-4 font-mono font-bold">{{ $cit->reported_incidents_count }}</td>
                                <td class="p-4 text-right">
                                    <form method="POST" action="{{ route('admin.citizens.toggle-status', $cit->id) }}">
                                        @csrf
                                        <button type="submit" class="alerto-btn-press px-3 py-1.5 rounded-xl font-bold text-xs cursor-pointer {{ $cit->is_active ? 'bg-[#C83F43] hover:bg-[#A63034] text-white' : 'bg-[#27845B] hover:bg-[#1f6b49] text-white' }}">
                                            {{ $cit->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-[#E6E3DE] dark:border-white/10">{{ $citizens->links() }}</div>
        </div>
    </div>
</x-layouts.admin>
