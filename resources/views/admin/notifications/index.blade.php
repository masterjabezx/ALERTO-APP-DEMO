<x-layouts.admin :title="'Command Notifications — ALERTO EOC'">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-[#3187D8]/15 text-[#34B9D4] text-[11px] font-bold uppercase tracking-wider mb-1">
                    <i data-lucide="bell-ring" class="w-3.5 h-3.5"></i>
                    EOC Notification Archive · {{ $unreadCount }} Unread
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Admin Notification Center</h1>
            </div>

            <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-xl bg-[#3187D8] hover:bg-[#2770b6] text-white text-xs font-bold">
                    Mark All Notifications Read
                </button>
            </form>
        </div>

        <div class="space-y-3">
            @forelse($notifications as $notif)
                <div class="eoc-card p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 {{ $notif->is_read ? 'opacity-70' : 'border-[#3187D8]/50' }}">
                    <div class="space-y-1 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded bg-[#173957] text-[#34B9D4] font-mono text-[10px] font-bold uppercase">
                                {{ str_replace('_', ' ', $notif->type) }}
                            </span>
                            <span class="text-slate-400 font-mono text-[11px]">{{ $notif->created_at?->format('M d, Y H:i') }}</span>
                        </div>
                        <h3 class="text-sm font-bold">{{ $notif->title }}</h3>
                        <p class="text-slate-400">{{ $notif->body }}</p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0 text-xs">
                        @if($notif->action_url)
                            <a href="{{ $notif->action_url }}" class="px-3 py-1.5 rounded-lg bg-[#173957] text-white font-bold">Open</a>
                        @endif
                        @if(! $notif->is_read)
                            <form method="POST" action="{{ route('admin.notifications.read', $notif->id) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#19865B] text-white font-bold">Mark Read</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="eoc-card p-10 text-center text-xs text-slate-400">
                    No notifications recorded for your Admin account yet.
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="eoc-card p-4">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>

