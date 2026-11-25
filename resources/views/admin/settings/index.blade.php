<x-layouts.admin :title="'System Settings — ALERTO EOC'">
    <div class="space-y-6">
        <x-portal.page-header
            badge="Core Platform Governance &amp; System Parameters"
            title="System Settings"
            subtitle="Global environment settings, upload quotas, backup history, and connected subsystem status."
        >
            <x-slot:actions>
                <a href="{{ route('admin.media.index') }}" class="alerto-btn-press px-4 py-2 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white text-xs font-bold">
                    Media Policy Settings &rarr;
                </a>
                <a href="{{ route('admin.communications.fb.settings') }}" class="px-4 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#292D32] hover:bg-[#F5F3EF] dark:hover:bg-white/10 text-xs font-bold">
                    Meta API Settings &rarr;
                </a>
            </x-slot:actions>
        </x-portal.page-header>

        @if(session('success'))
            <div class="p-4 rounded-2xl bg-[#27845B]/15 border border-[#27845B]/35 text-[#27845B] text-xs font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 text-xs">
            <form method="POST" action="{{ route('admin.settings.update') }}" class="lg:col-span-5 alerto-card p-6 space-y-4">
                @csrf
                <h2 class="text-sm font-black uppercase tracking-wider border-b border-[#E6E3DE] dark:border-white/10 pb-3">Global Upload Thresholds</h2>

                <div>
                    <label class="block font-bold uppercase text-[#77736E] mb-1">Legacy Max Upload Size (MB)</label>
                    <input type="number" name="max_upload_size_mb" value="{{ \App\Models\SystemSetting::get('max_upload_size_mb', 25) }}" min="1" max="500" required class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] font-mono focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                </div>

                <div>
                    <label class="block font-bold uppercase text-[#77736E] mb-1">Max Attachments per Submission</label>
                    <input type="number" name="max_upload_count" value="{{ \App\Models\SystemSetting::get('max_upload_count', 5) }}" min="1" max="20" required class="w-full px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] font-mono focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                </div>

                <button type="submit" :disabled="!$store.pwa.isOnline" class="alerto-btn-press w-full py-2.5 rounded-xl bg-[#E87524] hover:bg-[#B94F12] disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold">
                    Save System Thresholds
                </button>
            </form>

            <div class="lg:col-span-7 alerto-card p-6 space-y-4">
                <h2 class="text-sm font-black uppercase tracking-wider border-b border-[#E6E3DE] dark:border-white/10 pb-3">Active Key-Value Registry &amp; Backups</h2>
                <div class="max-h-64 overflow-y-auto alerto-scrollbar divide-y divide-[#E6E3DE] dark:divide-white/10">
                    @foreach($all_settings as $setting)
                        <div class="py-2 flex items-center justify-between gap-3">
                            <span class="font-mono font-bold text-[#B94F12] dark:text-[#F29A56]">{{ $setting->key }}</span>
                            <span class="font-mono text-[#77736E] truncate max-w-xs">{{ $setting->value }}</span>
                        </div>
                    @endforeach
                </div>

                <h3 class="text-xs font-bold uppercase text-[#77736E] pt-2">System Backup Records</h3>
                @forelse($backups as $bkp)
                    <div class="p-2.5 rounded-xl border border-[#E6E3DE] dark:border-white/10 bg-[#F5F3EF] dark:bg-white/5 flex items-center justify-between">
                        <span class="font-mono font-bold">{{ $bkp->backup_name }}</span>
                        <span class="px-2 py-0.5 rounded bg-[#27845B]/15 text-[#27845B] font-mono text-[10px] uppercase">{{ $bkp->status }}</span>
                    </div>
                @empty
                    <p class="text-[#77736E] italic">No automated backup snapshot records currently stored.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.admin>
