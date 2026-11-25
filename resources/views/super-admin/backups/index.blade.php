<x-layouts.super-admin :title="'Backup & Restore — LDRRMO Super Admin'">
    <div class="space-y-6 text-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-white">System Backup &amp; Non-Destructive Restore</h1>
                <p class="text-xs text-slate-400 mt-1">
                    Create SHA-256 verified JSON database snapshots of core ALERTO APP operational tables and restore verified archives safely.
                </p>
            </div>
        </div>

        {{-- Create Snapshot Form --}}
        <form method="POST" action="{{ route('super-admin.backups.store') }}" class="p-6 rounded-2xl bg-[#0B1726] border border-purple-500/30 flex flex-col sm:flex-row items-stretch sm:items-end gap-4">
            @csrf
            <div class="flex-1">
                <label class="block text-slate-400 font-bold mb-1">Backup Snapshot Notes (Optional)</label>
                <input type="text" name="notes" placeholder="e.g. Pre-maintenance full operational snapshot" class="w-full px-3 py-2.5 rounded-xl bg-slate-900 border border-white/15 text-white">
            </div>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-black cursor-pointer">
                Create Database Backup Now
            </button>
        </form>

        {{-- Backup Archive Table --}}
        <div class="rounded-2xl bg-[#0B1726] border border-white/10 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="border-b border-white/10 text-[10px] uppercase text-slate-400 bg-black/20">
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
                            <tr class="hover:bg-white/5">
                                <td class="p-4">
                                    <span class="font-mono font-bold text-white block">{{ $bkp->backup_name }}</span>
                                    <span class="text-[11px] text-slate-400">{{ $bkp->notes ?: 'No notes provided' }} · {{ $bkp->created_at?->format('Y-m-d H:i:s') }}</span>
                                </td>
                                <td class="p-4 font-mono">
                                    <span class="text-cyan-300 block">{{ number_format((float) ($bkp->file_size_bytes ?? 0) / 1024, 1) }} KB</span>
                                    <span class="text-[10px] text-slate-500">{{ \Illuminate\Support\Str::limit((string) $bkp->checksum_sha256, 20) }}</span>
                                </td>
                                <td class="p-4 text-slate-300">{{ $bkp->creator?->name ?? 'System' }}</td>
                                <td class="p-4">
                                    <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-mono text-[10px] uppercase font-bold">{{ $bkp->status }}</span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('super-admin.backups.download', $bkp->id) }}"
                                           class="px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white font-bold">
                                            Download
                                        </a>
                                        <form method="POST" action="{{ route('super-admin.backups.restore', $bkp->id) }}"
                                              onsubmit="return confirm('Restore verified snapshot {{ $bkp->backup_name }}? Existing matching records will be updated non-destructively.');">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white font-bold cursor-pointer">
                                                Restore
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-slate-400">No system backup snapshots have been created yet.</td>
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

