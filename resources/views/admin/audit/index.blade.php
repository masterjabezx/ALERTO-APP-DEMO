<x-layouts.admin :title="'Activity & Audit Logs — ALERTO EOC'">
    <div class="space-y-6">
        <x-portal.page-header
            badge="Immutable Forensic Trail · Security Governance"
            title="Activity &amp; Audit Logs"
            subtitle="System-wide chronological trace of administrative mutations, incident verifications, media integrity events, and policy changes."
        />

        <div class="alerto-card p-4">
            <form method="GET" action="{{ route('admin.audit.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search event type, model, actor..." class="px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                <input type="text" name="event_type" value="{{ $filters['event_type'] ?? '' }}" placeholder="Filter event prefix (e.g. media:, incident:)..." class="px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                <div class="flex gap-2">
                    <button type="submit" class="alerto-btn-press flex-1 px-4 py-2 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white font-bold">Search Logs</button>
                    <a href="{{ route('admin.audit.index') }}" class="px-3 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 font-bold hover:bg-[#F5F3EF] dark:hover:bg-white/10">Reset</a>
                </div>
            </form>
        </div>

        <div class="alerto-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-[#E6E3DE] dark:border-white/10 text-[10px] font-bold uppercase tracking-wider text-[#77736E] bg-[#F5F3EF] dark:bg-white/5">
                            <th class="py-3 px-4">Timestamp</th>
                            <th class="py-3 px-4">Event Type</th>
                            <th class="py-3 px-4">Actor</th>
                            <th class="py-3 px-4">Target Entity</th>
                            <th class="py-3 px-4">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E6E3DE] dark:divide-white/10">
                        @forelse($auditLogs as $log)
                            <tr class="hover:bg-[#FFF0E4]/40 dark:hover:bg-white/5 transition-colors">
                                <td class="py-3 px-4 font-mono text-[11px] text-[#77736E] whitespace-nowrap">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] font-mono font-bold text-[10px]">
                                        {{ $log->event_type }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-semibold">{{ $log->user?->name ?? 'System / Webhook' }}</td>
                                <td class="py-3 px-4 font-mono text-[11px] text-[#77736E]">
                                    {{ class_basename((string) $log->auditable_type) }} #{{ $log->auditable_id }}
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-[#77736E]">{{ $log->ip_address ?? '127.0.0.1' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-[#77736E]">No audit log events recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($auditLogs->hasPages())
                <div class="p-4 border-t border-[#E6E3DE] dark:border-white/10">
                    {{ $auditLogs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
