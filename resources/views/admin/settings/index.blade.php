<x-layouts.admin :title="'System Settings — ALERTO EOC'">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#3187D8]/15 text-[#34B9D4] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="settings" class="w-3.5 h-3.5"></i>
                    Core Platform Governance &amp; System Parameters
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight">System Settings</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">
                    Global environment settings, upload quotas, backup history, and connected subsystem status.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.media.index') }}" class="px-4 py-2 rounded-xl bg-[#173957] text-white text-xs font-bold">
                    Media Policy Settings &rarr;
                </a>
                <a href="{{ route('admin.communications.fb.settings') }}" class="px-4 py-2 rounded-xl border border-slate-400/30 text-xs font-bold">
                    Meta API Settings &rarr;
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-2xl bg-[#19865B]/15 border border-[#19865B]/40 text-[#6AE0B0] text-xs font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 text-xs">
            <form method="POST" action="{{ route('admin.settings.update') }}" class="lg:col-span-5 eoc-card p-6 space-y-4">
                @csrf
                <h2 class="text-sm font-black uppercase tracking-wider border-b border-slate-400/15 pb-3">Global Upload Thresholds</h2>

                <div>
                    <label class="block font-bold uppercase text-slate-400 mb-1">Legacy Max Upload Size (MB)</label>
                    <input type="number" name="max_upload_size_mb" value="{{ \App\Models\SystemSetting::get('max_upload_size_mb', 25) }}" min="1" max="500" required class="w-full px-3 py-2 rounded-xl border font-mono">
                </div>

                <div>
                    <label class="block font-bold uppercase text-slate-400 mb-1">Max Attachments per Submission</label>
                    <input type="number" name="max_upload_count" value="{{ \App\Models\SystemSetting::get('max_upload_count', 5) }}" min="1" max="20" required class="w-full px-3 py-2 rounded-xl border font-mono">
                </div>

                <button type="submit" :disabled="!$store.pwa.isOnline" class="w-full py-2.5 rounded-xl bg-[#3187D8] hover:bg-[#2770b6] disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold">
                    Save System Thresholds
                </button>
            </form>

            <div class="lg:col-span-7 eoc-card p-6 space-y-4">
                <h2 class="text-sm font-black uppercase tracking-wider border-b border-slate-400/15 pb-3">Active Key-Value Registry &amp; Backups</h2>
                <div class="max-h-64 overflow-y-auto alerto-scrollbar divide-y divide-slate-400/15">
                    @foreach($all_settings as $setting)
                        <div class="py-2 flex items-center justify-between gap-3">
                            <span class="font-mono font-bold text-[#34B9D4]">{{ $setting->key }}</span>
                            <span class="font-mono text-slate-400 truncate max-w-xs">{{ $setting->value }}</span>
                        </div>
                    @endforeach
                </div>

                <h3 class="text-xs font-bold uppercase text-slate-400 pt-2">System Backup Records</h3>
                @forelse($backups as $bkp)
                    <div class="p-2.5 rounded-xl border border-slate-400/15 flex items-center justify-between">
                        <span class="font-mono font-bold">{{ $bkp->backup_name }}</span>
                        <span class="px-2 py-0.5 rounded bg-[#19865B]/20 text-[#6AE0B0] font-mono text-[10px] uppercase">{{ $bkp->status }}</span>
                    </div>
                @empty
                    <p class="text-slate-400 italic">No automated backup snapshot records currently stored.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.admin>

