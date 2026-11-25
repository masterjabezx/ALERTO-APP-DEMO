<x-layouts.personnel :title="'Field Communications — Internal Messenger'">
<div class="space-y-4 pb-16 md:pb-6" x-data="personnelMessenger({{ json_encode([
    'activeConversationId' => $activeConversation?->id,
    'currentUserId' => $user->id,
]) }})">
    {{-- Header Banner --}}
    <div class="alerto-card flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-3xl">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-[#17191C] text-white border border-white/10 flex items-center justify-center font-black shadow-xs shrink-0">
                <i data-lucide="message-square" class="w-5 h-5 text-[#E87524]"></i>
            </div>
            <div>
                <h1 class="text-base sm:text-lg font-black text-[var(--text-primary)] tracking-tight flex items-center gap-2">
                    Field Dispatch Messenger
                    <span class="px-2 py-0.5 rounded-full bg-[#27845B]/15 text-[#1B6644] dark:text-[#5BD19A] text-[10px] font-bold border border-[#27845B]/30">
                        ONLINE
                    </span>
                </h1>
                <p class="text-xs text-[var(--text-secondary)]">Direct encrypted line with LDRRMO Central Operations &amp; Team Leads.</p>
            </div>
        </div>

        {{-- Direct to Admin button if not already messaging admin --}}
        @php
            $adminUser = \App\Models\User::where('account_type', 'admin')->first();
        @endphp
        @if($adminUser)
            <a href="{{ route('personnel.messages.direct', $adminUser->id) }}"
               class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-[#17191C] hover:bg-[#292D32] text-white text-xs font-bold rounded-2xl shadow-xs transition-colors shrink-0 border border-white/10">
                <i data-lucide="shield" class="w-4 h-4 text-[#E87524]"></i>
                Message Admin EOC
            </a>
        @endif
    </div>

    {{-- Main Container --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        {{-- Channels List --}}
        <div class="lg:col-span-4 alerto-card rounded-3xl overflow-hidden flex flex-col h-[400px] lg:h-[650px]">
            <div class="p-3 border-b border-[var(--border-subtle)] bg-[var(--surface-panel)]">
                <form method="GET" action="{{ route('personnel.messages') }}" class="relative">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-2.5 text-[var(--text-muted)]"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search chats or incidents..."
                           class="w-full pl-9 pr-3 py-1.5 bg-[var(--surface-card)] border border-[var(--border-subtle)] rounded-xl text-xs text-[var(--text-primary)] placeholder:text-[var(--text-muted)] focus-visible:ring-2 focus-visible:ring-[#E87524] outline-hidden">
                </form>
            </div>

            <div class="flex-1 overflow-y-auto divide-y divide-[var(--border-subtle)] alerto-scrollbar">
                @forelse($conversations as $conv)
                    @php
                        $isActive = $activeConversation && $activeConversation->id === $conv->id;
                        $unread = $conv->unreadCountFor($user->id);
                        $otherParticipant = $conv->type === 'direct'
                            ? $conv->participants->firstWhere('user_id', '!=', $user->id)?->user
                            : null;
                    @endphp
                    <a href="{{ route('personnel.messages', ['conversation_id' => $conv->id]) }}"
                       class="block p-3.5 transition-colors hover:bg-[var(--surface-hover)]/60 {{ $isActive ? 'bg-[#E87524]/12 border-l-4 border-[#E87524]' : '' }}">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl shrink-0 flex items-center justify-center font-bold text-xs {{ $conv->type === 'incident' ? 'bg-[#C83F43]/15 text-[#C83F43] dark:text-[#F27D80]' : 'bg-[#17191C] text-[#F29A56]' }} relative">
                                @if($conv->type === 'incident')
                                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                                @else
                                    {{ strtoupper(substr($otherParticipant?->name ?? 'EOC', 0, 2)) }}
                                @endif
                                @if($unread > 0)
                                    <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-[#C83F43] text-white text-[9px] font-bold flex items-center justify-center border-2 border-[var(--surface-card)]">
                                        {{ $unread }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-0.5">
                                    <h4 class="text-xs font-bold text-[var(--text-primary)] truncate">
                                        {{ $conv->type === 'direct' ? ($otherParticipant?->name ?? 'Admin EOC') : ($conv->title ?? 'Incident Channel') }}
                                    </h4>
                                    <span class="text-[9px] text-[var(--text-muted)] font-mono">
                                        {{ $conv->latestMessage?->created_at?->diffForHumans(short: true) ?? $conv->updated_at->diffForHumans(short: true) }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-[var(--text-secondary)] truncate">
                                    {{ $conv->latestMessage?->body ?? ($conv->latestMessage ? '[' . ucfirst($conv->latestMessage->message_type) . ']' : 'No messages yet') }}
                                </p>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center text-[var(--text-muted)]">
                        <i data-lucide="message-square-off" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                        <p class="text-xs">No active chats.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Active Chat Area --}}
        <div class="lg:col-span-8 alerto-card rounded-3xl overflow-hidden flex flex-col h-[550px] lg:h-[650px]">
            @if($activeConversation)
                {{-- Chat Top Bar --}}
                <div class="p-3.5 px-4 bg-[var(--surface-panel)] border-b border-[var(--border-subtle)] flex items-center justify-between shrink-0">
                    <div>
                        <h3 class="text-xs sm:text-sm font-black text-[var(--text-primary)]">
                            {{ $activeConversation->title ?? ($activeConversation->participants->firstWhere('user_id', '!=', $user->id)?->user?->name ?? 'Direct Chat') }}
                        </h3>
                        <p class="text-[10px] text-[var(--text-muted)]">
                            {{ $activeConversation->participants->count() }} active participants
                        </p>
                    </div>
                    @if($activeConversation->incident)
                        <span class="px-2.5 py-1 rounded-xl bg-[#C83F43]/15 text-[#C83F43] dark:text-[#F27D80] font-bold text-[10px] font-mono">
                            #{{ $activeConversation->incident->reference_number }}
                        </span>
                    @endif
                </div>

                {{-- Messages Feed --}}
                <div id="personnel-messages-container" class="flex-1 p-4 overflow-y-auto space-y-3 bg-[var(--surface-bg)]/50 alerto-scrollbar">
                    @forelse($messages as $msg)
                        @php
                            $isMe = $msg->sender_id === $user->id;
                        @endphp
                        <div class="flex items-start gap-2.5 {{ $isMe ? 'flex-row-reverse' : '' }}">
                            <div class="w-7 h-7 rounded-xl shrink-0 flex items-center justify-center font-bold text-xs text-white {{ $isMe ? 'bg-[#E87524]' : 'bg-[#17191C]' }}">
                                {{ strtoupper(substr($msg->sender?->name ?? 'U', 0, 1)) }}
                            </div>

                            <div class="max-w-[80%] space-y-1">
                                <div class="flex items-center gap-1.5 text-[10px] text-[var(--text-muted)] {{ $isMe ? 'justify-end' : '' }}">
                                    <span class="font-bold text-[var(--text-secondary)]">{{ $msg->sender?->name }}</span>
                                    <span>·</span>
                                    <span class="font-mono">{{ $msg->created_at->format('H:i') }}</span>
                                </div>

                                <div class="p-3 rounded-2xl shadow-xs text-xs {{ $isMe ? 'bg-[#17191C] text-[#F5F3EF] rounded-tr-none border border-white/10' : 'bg-[var(--surface-card)] border border-[var(--border-subtle)] text-[var(--text-primary)] rounded-tl-none' }}">
                                    @if($msg->body)
                                        <p class="whitespace-pre-wrap leading-relaxed">{{ $msg->body }}</p>
                                    @endif

                                    @if($msg->attachments->isNotEmpty())
                                        <div class="mt-2 space-y-2">
                                            @foreach($msg->attachments as $att)
                                                @php $pGeo = $att->geoMetadata; @endphp
                                                <div class="rounded-xl overflow-hidden border {{ $isMe ? 'border-white/15 bg-white/5' : 'border-[var(--border-subtle)] bg-[var(--surface-panel)]' }} p-2">
                                                    @if(str_starts_with($att->mime_type, 'image/'))
                                                        <img src="{{ route('messenger.attachments.download', $att->id) }}" alt="{{ $att->file_name }}" class="max-h-48 rounded-lg object-cover w-full mb-1.5">
                                                    @elseif(str_starts_with($att->mime_type, 'video/'))
                                                        <video controls class="max-h-48 rounded-lg w-full mb-1.5 bg-black">
                                                            <source src="{{ route('messenger.attachments.download', $att->id) }}" type="{{ $att->mime_type }}">
                                                        </video>
                                                    @endif
                                                    <div class="flex items-center justify-between gap-2 text-[10px]">
                                                        <span class="truncate font-medium">{{ $att->file_name }}</span>
                                                        <a href="{{ route('messenger.attachments.download', $att->id) }}" class="px-2 py-0.5 rounded bg-[#E87524] hover:bg-[#D16419] text-white font-bold shrink-0">Download</a>
                                                    </div>
                                                    @if($pGeo && $pGeo->latitude)
                                                        <div class="mt-1 pt-1 border-t {{ $isMe ? 'border-white/10' : 'border-[var(--border-subtle)]' }} text-[9px] text-[#27845B] dark:text-[#5BD19A] font-bold flex items-center justify-between">
                                                            <span>GPS: {{ number_format((float)$pGeo->latitude, 5) }}, {{ number_format((float)$pGeo->longitude, 5) }}</span>
                                                            <a href="{{ route('personnel.map') }}?lat={{ $pGeo->latitude }}&lng={{ $pGeo->longitude }}" class="underline">Map</a>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 text-[var(--text-muted)]">
                            <i data-lucide="message-square" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                            <p class="text-xs">No messages yet. Send a field report or update to begin.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Personnel Field Composer --}}
                <div class="p-3 border-t border-[var(--border-subtle)] bg-[var(--surface-card)] shrink-0">
                    <form method="POST" action="{{ route('personnel.messages.send', $activeConversation->id) }}" enctype="multipart/form-data" class="space-y-2">
                        @csrf
                        <div x-show="hasGps" x-cloak class="p-1.5 rounded-xl bg-[#27845B]/12 border border-[#27845B]/30 flex items-center justify-between text-xs text-[#1B6644] dark:text-[#5BD19A]">
                            <span class="font-bold flex items-center gap-1 text-[11px]">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-[#27845B] dark:text-[#5BD19A] animate-pulse"></i>
                                Field GPS Attached: <span x-text="lat + ', ' + lng" class="font-mono"></span>
                            </span>
                            <button type="button" @click="clearGps()" class="text-[var(--text-muted)] hover:text-[var(--text-primary)] cursor-pointer">
                                <i data-lucide="x" class="w-3 h-3"></i>
                            </button>
                        </div>

                        <input type="hidden" name="latitude" :value="lat">
                        <input type="hidden" name="longitude" :value="lng">
                        <input type="hidden" name="accuracy" :value="accuracy">
                        <input type="hidden" name="gps_source" :value="gpsSource">

                        @if($activeConversation->incident_id)
                            <input type="hidden" name="incident_id" value="{{ $activeConversation->incident_id }}">
                        @endif

                        <div class="flex items-end gap-2">
                            <div class="flex-1 bg-[var(--surface-panel)] rounded-2xl border border-[var(--border-subtle)] p-2 focus-within:ring-2 focus-within:ring-[#E87524] transition-all">
                                <textarea name="body" rows="2" placeholder="Send field report or message..."
                                          class="w-full bg-transparent resize-none border-none outline-hidden text-xs text-[var(--text-primary)] placeholder:text-[var(--text-muted)]"></textarea>

                                <div x-show="fileName" x-cloak class="flex items-center justify-between px-2 py-1 bg-[var(--surface-card)] rounded-lg border border-[var(--border-subtle)] my-1 text-xs text-[var(--text-secondary)]">
                                    <span class="truncate font-mono text-[11px]" x-text="fileName"></span>
                                    <button type="button" @click="clearFile()" class="text-[var(--text-muted)] hover:text-[var(--text-primary)] cursor-pointer">
                                        <i data-lucide="x" class="w-3 h-3"></i>
                                    </button>
                                </div>

                                <div class="flex items-center justify-between pt-1 border-t border-[var(--border-subtle)]">
                                    <div class="flex items-center gap-2">
                                        <label class="p-1.5 text-[var(--text-secondary)] hover:text-[var(--text-primary)] hover:bg-[var(--surface-hover)] rounded-xl cursor-pointer transition-colors flex items-center gap-1 text-[11px] font-bold">
                                            <i data-lucide="camera" class="w-4 h-4 text-[#E87524]"></i>
                                            <span>Photo / Video</span>
                                            <input type="file" name="attachment" id="personnel-file-input" class="hidden" @change="handleFileChange($event)"
                                                   accept="image/*,video/*,application/pdf" capture="environment">
                                        </label>

                                        <button type="button" @click="requestGps()"
                                                :class="hasGps ? 'text-[#1B6644] dark:text-[#5BD19A] bg-[#27845B]/15 font-bold' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)] hover:bg-[var(--surface-hover)] font-semibold'"
                                                class="p-1.5 rounded-xl transition-colors flex items-center gap-1 text-[11px] cursor-pointer">
                                            <i data-lucide="crosshair" class="w-4 h-4 text-[#27845B] dark:text-[#5BD19A]"></i>
                                            <span>GPS</span>
                                        </button>
                                    </div>
                                    <span class="text-[10px] text-[var(--text-muted)] font-mono">Field Mobile</span>
                                </div>
                            </div>

                            <button type="submit"
                                    class="h-10 px-4 rounded-xl bg-[#E87524] hover:bg-[#D16419] text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-xs transition-colors shrink-0 cursor-pointer">
                                <span>Send</span>
                                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="flex-1 flex items-center justify-center bg-[var(--surface-panel)]/40">
                    <p class="text-xs text-[var(--text-muted)]">Select a conversation or tap "Message Admin EOC".</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function personnelMessenger(config) {
    return {
        activeConversationId: config.activeConversationId,
        currentUserId: config.currentUserId,
        hasGps: false,
        lat: null,
        lng: null,
        accuracy: null,
        gpsSource: 'none',
        fileName: null,

        init() {
            this.scrollToBottom();
            if (this.activeConversationId && window.Echo) {
                window.Echo.private(`conversation.${this.activeConversationId}`)
                    .listen('.InternalMessageSent', (e) => {
                        if (e.sender && e.sender.id !== this.currentUserId) {
                            window.location.reload();
                        }
                    });
            }
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = document.getElementById('personnel-messages-container');
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        requestGps() {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported on this device.');
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.lat = pos.coords.latitude.toFixed(6);
                    this.lng = pos.coords.longitude.toFixed(6);
                    this.accuracy = Math.round(pos.coords.accuracy);
                    this.gpsSource = 'device_live_upload';
                    this.hasGps = true;
                },
                (err) => alert('GPS Error: ' + err.message),
                { enableHighAccuracy: true, timeout: 10000 }
            );
        },

        clearGps() {
            this.hasGps = false;
            this.lat = null;
            this.lng = null;
            this.accuracy = null;
            this.gpsSource = 'none';
        },

        handleFileChange(e) {
            if (e.target.files && e.target.files[0]) {
                this.fileName = e.target.files[0].name;
            }
        },

        clearFile() {
            const el = document.getElementById('personnel-file-input');
            if (el) el.value = '';
            this.fileName = null;
        }
    };
}
</script>
@endpush
</x-layouts.personnel>
