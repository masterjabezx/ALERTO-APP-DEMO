<x-layouts.office :title="'Office Personnel & Staff Roster — ' . $office->name">
    <div class="space-y-6">
        <div class="alerto-card p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-black">{{ $office->name }} — Unit Roster Management</h1>
                <p class="text-xs text-[#77736E] mt-1">
                    Accounts created here are automatically scoped strictly to <strong>{{ $office->name }}</strong> ({{ $office->code }}).
                </p>
            </div>
            <a href="{{ route('office.dashboard') }}" class="text-xs font-bold text-[#B94F12] dark:text-[#F29A56] hover:underline">&larr; Back to Office Dashboard</a>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-[#27845B]/15 border border-[#27845B]/30 text-[#27845B] text-xs font-bold">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-xl bg-[#C83F43]/15 border border-[#C83F43]/30 text-[#C83F43] text-xs space-y-1">
                @foreach($errors->all() as $err)
                    <p>&bull; {{ $err }}</p>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Create Personnel Account Form --}}
            <div class="alerto-card p-6 space-y-4">
                <h2 class="text-sm font-extrabold uppercase tracking-wider">Add Responder Personnel</h2>
                <form action="{{ route('office.personnel.store') }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold mb-1">Full Name *</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] text-xs focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold mb-1">Phone Number (09XXXXXXXXX) *</label>
                        <input type="text" name="phone_number" required placeholder="09171234567" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] text-xs focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold mb-1">Email (Optional)</label>
                        <input type="email" name="email" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] text-xs focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold mb-1">Badge # *</label>
                            <input type="text" name="badge_number" required class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] text-xs focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold mb-1">Rank / Title *</label>
                            <input type="text" name="rank_designation" required placeholder="EMT / Officer" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] text-xs focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold mb-1">Temporary Password *</label>
                        <input type="password" name="password" required minlength="8" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] text-xs focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                    </div>
                    <button type="submit" class="alerto-btn-press w-full py-2.5 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white text-xs font-bold cursor-pointer">
                        Create {{ $office->code }} Personnel Account
                    </button>
                </form>

                @if($officeUser?->office_level === 'office_admin' && $officeUser?->can_manage_accounts)
                    <div class="pt-4 border-t border-[#E6E3DE] dark:border-white/10 space-y-3">
                        <h3 class="text-xs font-extrabold uppercase tracking-wider">Add Office Staff (Permitted)</h3>
                        <form action="{{ route('office.staff.store') }}" method="POST" class="space-y-2.5">
                            @csrf
                            <input type="hidden" name="office_level" value="office_staff">
                            <input type="text" name="name" required placeholder="Staff Name" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] text-xs">
                            <input type="email" name="email" required placeholder="staff@office.gov" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] text-xs">
                            <input type="text" name="phone_number" required placeholder="09171234568" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] text-xs">
                            <input type="password" name="password" required minlength="8" placeholder="Password (min 8)" class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] text-xs">
                            <button type="submit" class="alerto-btn-press w-full py-2.5 rounded-xl bg-[#17191C] hover:bg-[#292D32] text-[#F29A56] border border-[#E87524]/40 text-xs font-bold cursor-pointer">
                                Provision {{ $office->code }} Staff
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            {{-- Existing Personnel Table --}}
            <div class="lg:col-span-2 alerto-card p-6 space-y-4">
                <h2 class="text-sm font-extrabold uppercase tracking-wider">Assigned Personnel ({{ $personnel->total() }})</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-[#F5F3EF] dark:bg-white/5 text-[#77736E] uppercase text-[10px]">
                            <tr>
                                <th class="p-3">Responder</th>
                                <th class="p-3">Badge / Rank</th>
                                <th class="p-3">Phone</th>
                                <th class="p-3">Availability</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E6E3DE] dark:divide-white/10">
                            @forelse($personnel as $p)
                                <tr class="hover:bg-[#FFF0E4]/40 dark:hover:bg-white/5 transition-colors">
                                    <td class="p-3 font-bold">{{ $p->user?->name }}</td>
                                    <td class="p-3 font-mono">#{{ $p->badge_number }} · {{ $p->rank_designation }}</td>
                                    <td class="p-3 font-mono">{{ $p->user?->phone_number }}</td>
                                    <td class="p-3 uppercase font-bold">{{ str_replace('_', ' ', $p->availability?->operational_status ?? 'off_duty') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="p-4 text-center text-[#77736E]">No responders assigned yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $personnel->links() }}
            </div>
        </div>
    </div>
</x-layouts.office>
