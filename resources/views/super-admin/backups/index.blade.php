<x-layouts.super-admin :title="'Backup & Restore — LDRRMO Super Admin'">
    <div class="space-y-6 text-xs">
        <x-portal.page-header
            eyebrow="Disaster Recovery"
            title="System Backup &amp; Non-Destructive Restore"
            subtitle="Create SHA-256 verified JSON database snapshots of core ALERTO APP operational tables and restore verified archives safely."
            :breadcrumbs="[['label' => 'Executive Dashboard', 'href' => route('super-admin.dashboard')], ['label' => 'Database Backups']]"
        />

        {{-- Create Snapshot Form --}}
        <form method="POST" action="{{ route('super-admin.backups.store') }}" class="eoc-card p-6 border-t-2 border-t-[#E87524] flex flex-col sm:flex-row items-stretch sm:items-end gap-4">
            @csrf
            <div class="flex-1">
                <label class="block text-[#9D9993] font-bold mb-1.5 uppercase tracking-wider text-[11px]">Backup Snapshot Notes (Optional)</label>
                <input type="text" name="notes" placeholder="e.g. Pre-maintenance full operational snapshot" class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border">
            </div>
            <button type="submit" class="min-h-[44px] px-6 py-2.5 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white font-black cursor-pointer transition-colors btn-fixed">
                Create Database Backup Now
            </button>
        </form>

        {{-- Backup Archive Table --}}
        <div class="eoc-card overflow-hidden">
            <div class="overflow-x-auto alerto-scrollbar">
                <table class="w-full text-left">
                    <thead class="border-b border-white/10 text-[10px] uppercase text-[#9D9993] bg-black/15">
                        <tr>
                            <th class="p-4">Backup Snapshot</th>
                            <th class="p-4">Size &amp; SHA-256</th>
                            <th class="p-4">Created By</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($backups as $bkp)
                            <tr>
                                <td class="p-4">
                                    <span class="font-mono font-bold block">{{ $bkp->backup_name }}</span>
                                    <span class="text-[11px] text-[#9D9993]">{{ $bkp->notes ?: 'No notes provided' }} · {{ $bkp->created_at?->format('Y-m-d H:i:s') }}</span>
                                </td>
                                <td class="p-4 font-mono">
                                    <span class="text-[#E87524] block">{{ number_format((float) ($bkp->file_size_bytes ?? 0) / 1024, 1) }} KB</span>
                                    <span class="text-[10px] text-[#9D9993]">{{ \Illuminate\Support\Str::limit((string) $bkp->checksum_sha256, 20) }}</span>
                                </td>
                                <td class="p-4">{{ $bkp->creator?->name ?? 'System' }}</td>
                                <td class="p-4">
                                    <span class="px-2.5 py-0.5 rounded-full bg-[#27845B]/20 text-[#27845B] font-mono text-[10px] uppercase font-bold">{{ $bkp->status }}</span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('super-admin.backups.download', $bkp->id) }}"
                                           class="px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 font-bold">
                                            Download
                                        </a>
                                        <form method="POST" action="{{ route('super-admin.backups.restore', $bkp->id) }}"
                                              onsubmit="return confirm('Restore verified snapshot {{ $bkp->backup_name }}? Existing matching records will be updated non-destructively.');">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#D99024] hover:bg-[#b5761a] text-white font-bold cursor-pointer btn-fixed">
                                                Restore
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-[#9D9993]">No system backup snapshots have been created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($backups->hasPages())
                <div class="p-4 border-t border-white/10">{{ $backups->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.super-admin>
