<x-layouts.admin :title="'Activity & Audit Logs — ALERTO EOC'">
    <div class="space-y-6">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#3187D8]/15 text-[#34B9D4] text-[11px] font-bold uppercase tracking-wider mb-1">
                <i data-lucide="history" class="w-3.5 h-3.5"></i>
                Immutable Forensic Trail · Security Governance
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Activity &amp; Audit Logs</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                System-wide chronological trace of administrative mutations, incident verifications, media integrity events, and policy changes.
            </p>
        </div>

        <div class="eoc-card p-4">
            <form method="GET" action="{{ route('admin.audit.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search event type, model, actor..." class="px-3 py-2 rounded-xl border">
                <input type="text" name="event_type" value="{{ $filters['event_type'] ?? '' }}" placeholder="Filter event prefix (e.g. media:, incident:)..." class="px-3 py-2 rounded-xl border">
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="px-3 py-2 rounded-xl border">
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 rounded-xl bg-[#3187D8] text-white font-bold">Search Logs</button>
                    <a href="{{ route('admin.audit.index') }}" class="px-3 py-2 rounded-xl border border-slate-400/30 font-bold">Reset</a>
                </div>
            </form>
        </div>

        <div class="eoc-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-400/20 text-[10px] font-bold uppercase tracking-wider text-slate-400 bg-black/15">
                            <th class="py-3 px-4">Timestamp</th>
                            <th class="py-3 px-4">Event Type</th>
                            <th class="py-3 px-4">Actor</th>
                            <th class="py-3 px-4">Target Entity</th>
                            <th class="py-3 px-4">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-400/15">
                        @forelse($auditLogs as $log)
                            <tr>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-400 whitespace-nowrap">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded bg-[#173957] text-[#34B9D4] font-mono font-bold text-[10px]">
                                        {{ $log->event_type }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-semibold">{{ $log->user?->name ?? 'System / Webhook' }}</td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-400">
                                    {{ class_basename((string) $log->auditable_type) }} #{{ $log->auditable_id }}
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-400">{{ $log->ip_address ?? '127.0.0.1' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400">No audit log events recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($auditLogs->hasPages())
                <div class="p-4 border-t border-slate-400/15">
                    {{ $auditLogs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>

