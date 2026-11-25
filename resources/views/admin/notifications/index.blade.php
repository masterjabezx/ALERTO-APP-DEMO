<x-layouts.admin :title="'Command Notifications — ALERTO EOC'">
    <div class="space-y-6">
        <x-portal.page-header
            :badge="'EOC Notification Archive · ' . $unreadCount . ' Unread'"
            title="Admin Notification Center"
        >
            <x-slot:actions>
                <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="alerto-btn-press px-4 py-2 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white text-xs font-bold">
                        Mark All Notifications Read
                    </button>
                </form>
            </x-slot:actions>
        </x-portal.page-header>

        <div class="space-y-3">
            @forelse($notifications as $notif)
                <div class="alerto-card p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 {{ $notif->is_read ? 'opacity-75' : 'border-[#E87524]/50' }}">
                    <div class="space-y-1 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] font-mono text-[10px] font-bold uppercase">
                                {{ str_replace('_', ' ', $notif->type) }}
                            </span>
                            <span class="text-[#77736E] font-mono text-[11px]">{{ $notif->created_at?->format('M d, Y H:i') }}</span>
                        </div>
                        <h3 class="text-sm font-bold">{{ $notif->title }}</h3>
                        <p class="text-[#77736E]">{{ $notif->body }}</p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0 text-xs">
                        @if($notif->action_url)
                            <a href="{{ $notif->action_url }}" class="px-3 py-1.5 rounded-lg bg-[#17191C] hover:bg-[#292D32] text-white font-bold">Open</a>
                        @endif
                        @if(! $notif->is_read)
                            <form method="POST" action="{{ route('admin.notifications.read', $notif->id) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#27845B] hover:bg-[#1f6b49] text-white font-bold">Mark Read</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="alerto-card p-10 text-center text-xs text-[#77736E]">
                    No notifications recorded for your Admin account yet.
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="alerto-card p-4">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
