<x-layouts.office :title="'Internal Messenger — Agency Coordination'">
<div class="h-[calc(100vh-8.5rem)] flex flex-col bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden"
     x-data="officeMessenger({{ json_encode([
        'activeConversationId' => $activeConversation?->id,
        'currentUserId' => $user->id,
     ]) }})">

    {{-- Top Action Header --}}
    <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#173957] text-white flex items-center justify-center font-bold text-xs shadow-xs">
                <i data-lucide="message-square" class="w-5 h-5 text-[#3187D8]"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-sm sm:text-base font-black text-slate-900 tracking-tight">
                        EOC Secure Agency Line
                    </h1>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">
                        {{ $office?->code ?? 'AGENCY' }}
                    </span>
                </div>
                <p class="text-xs text-slate-500">Official dispatch line between {{ $office?->name ?? 'Agency' }} and Central Command EOC.</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($adminUser && (! $activeConversation || ! $activeConversation->participants->contains('user_id', $adminUser->id)))
                <a href="{{ route('office.messages.direct', $adminUser->id) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#173957] hover:bg-[#122e46] text-white rounded-xl text-xs font-bold transition-colors shadow-xs">
                    <i data-lucide="shield" class="w-4 h-4 text-[#3187D8]"></i>
                    Direct Line to Admin EOC
                </a>
            @endif
        </div>
    </div>

    {{-- Main Container (2 Panels) --}}
    <div class="flex-1 flex overflow-hidden">
        {{-- Channels Sidebar --}}
        <div class="w-72 sm:w-80 border-r border-slate-200 flex flex-col bg-slate-50/50 shrink-0">
            {{-- Search & Direct Options --}}
            <div class="p-3 border-b border-slate-200 bg-white">
                <form method="GET" action="{{ route('office.messages') }}" class="relative">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-2.5 text-slate-400"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search chats..."
                           class="w-full pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-[#3187D8] outline-none">
                </form>
            </div>

            {{-- Conversation Items --}}
            <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
                @forelse($conversations as $conv)
                    @php
                        $isActive = $activeConversation && $activeConversation->id === $conv->id;
                        $unread = $conv->unreadCountFor($user->id);
                        $otherParticipant = $conv->type === 'direct'
                            ? $conv->participants->firstWhere('user_id', '!=', $user->id)?->user
                            : null;
                    @endphp
                    <a href="{{ route('office.messages', ['conversation_id' => $conv->id]) }}"
                       class="block p-3 transition-colors hover:bg-slate-100/70 {{ $isActive ? 'bg-white border-l-4 border-[#3187D8] shadow-xs' : '' }}">
                        <div class="flex items-start gap-2.5">
                            <div class="relative shrink-0">
                                @if($conv->type === 'incident')
                                    <div class="w-9 h-9 rounded-xl bg-red-100 text-[#DB4545] flex items-center justify-center font-bold">
                                        <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                                    </div>
                                @else
                                    <div class="w-9 h-9 rounded-xl bg-[#173957] text-white flex items-center justify-center font-bold text-xs">
                                        {{ strtoupper(substr($otherParticipant?->name ?? 'EOC', 0, 2)) }}
                                    </div>
                                @endif
                                @if($unread > 0)
                                    <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-[#DB4545] text-white text-[9px] font-bold flex items-center justify-center border-2 border-white">
                                        {{ $unread }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-1 mb-0.5">
                                    <h4 class="text-xs font-bold text-slate-900 truncate">
                                        {{ $conv->type === 'direct' ? ($otherParticipant?->name ?? 'Direct Chat') : ($conv->title ?? 'Incident Channel') }}
                                    </h4>
                                    <span class="text-[9px] text-slate-400 font-mono shrink-0">
                                        {{ $conv->latestMessage?->created_at?->diffForHumans(short: true) ?? $conv->updated_at->diffForHumans(short: true) }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 truncate">
                                    {{ $conv->latestMessage?->body ?? ($conv->latestMessage ? '[' . ucfirst($conv->latestMessage->message_type) . ']' : 'No messages yet') }}
                                </p>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-6 text-center text-slate-400">
                        <i data-lucide="message-square-off" class="w-6 h-6 mx-auto mb-1 opacity-50"></i>
                        <p class="text-xs">No active conversations</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Active Chat Feed --}}
        @if($activeConversation)
            <div class="flex-1 flex flex-col bg-white overflow-hidden">
                {{-- Active Chat Bar --}}
                <div class="p-3 px-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50 shrink-0">
                    <div>
                        <h3 class="text-xs sm:text-sm font-black text-slate-900 tracking-tight">
                            {{ $activeConversation->title ?? ($activeConversation->participants->firstWhere('user_id', '!=', $user->id)?->user?->name ?? 'Conversation') }}
                        </h3>
                        <p class="text-[11px] text-slate-500">
                            {{ $activeConversation->participants->count() }} participants ·
                            <span class="capitalize">{{ $activeConversation->type }}</span>
                        </p>
                    </div>
                    @if($activeConversation->incident)
                        <a href="{{ route('office.incidents.show', $activeConversation->incident->reference_number) }}"
                           class="px-2.5 py-1 rounded-xl bg-red-50 text-[#DB4545] border border-red-200 hover:bg-red-100 text-[10px] font-bold transition-colors inline-flex items-center gap-1">
                            <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                            Incident #{{ $activeConversation->incident->reference_number }}
                        </a>
                    @endif
                </div>

                {{-- Messages Area --}}
                <div id="office-messages-container" class="flex-1 p-4 overflow-y-auto space-y-3 bg-slate-50/30">
                    @forelse($messages as $msg)
                        @php
                            $isMe = $msg->sender_id === $user->id;
                            $geo = $msg->attachments->first()?->geoMetadata;
                        @endphp
                        <div class="flex items-start gap-2.5 {{ $isMe ? 'flex-row-reverse' : '' }}">
                            <div class="w-7 h-7 rounded-xl shrink-0 flex items-center justify-center font-bold text-xs text-white {{ $isMe ? 'bg-[#3187D8]' : 'bg-[#173957]' }}">
                                {{ strtoupper(substr($msg->sender?->name ?? 'U', 0, 1)) }}
                            </div>

                            <div class="max-w-[75%] space-y-1">
                                <div class="flex items-center gap-1.5 text-[10px] text-slate-400 {{ $isMe ? 'justify-end' : '' }}">
                                    <span class="font-bold text-slate-700">{{ $msg->sender?->name }}</span>
                                    <span>·</span>
                                    <span class="font-mono">{{ $msg->created_at->format('H:i') }}</span>
                                </div>

                                <div class="p-3 rounded-2xl shadow-xs text-xs {{ $isMe ? 'bg-[#173957] text-white rounded-tr-none' : 'bg-white border border-slate-200 text-slate-800 rounded-tl-none' }}">
                                    @if($msg->body)
                                        <p class="whitespace-pre-wrap leading-relaxed">{{ $msg->body }}</p>
                                    @endif

                                    @if($msg->attachments->isNotEmpty())
                                        <div class="mt-2 space-y-2">
                                            @foreach($msg->attachments as $att)
                                                @php $mGeo = $att->geoMetadata; @endphp
                                                <div class="rounded-xl overflow-hidden border {{ $isMe ? 'border-white/20 bg-white/10' : 'border-slate-200 bg-slate-50' }} p-2">
                                                    @if(str_starts_with($att->mime_type, 'image/'))
                                                        <img src="{{ route('messenger.attachments.download', $att->id) }}" alt="{{ $att->file_name }}" class="max-h-48 rounded-lg object-cover w-full mb-1.5">
                                                    @elseif(str_starts_with($att->mime_type, 'video/'))
                                                        <video controls class="max-h-48 rounded-lg w-full mb-1.5 bg-black">
                                                            <source src="{{ route('messenger.attachments.download', $att->id) }}" type="{{ $att->mime_type }}">
                                                        </video>
                                                    @endif
                                                    <div class="flex items-center justify-between gap-2 text-[10px]">
                                                        <span class="truncate font-medium">{{ $att->file_name }}</span>
                                                        <a href="{{ route('messenger.attachments.download', $att->id) }}" class="px-2 py-0.5 rounded bg-[#3187D8] text-white font-bold shrink-0">Download</a>
                                                    </div>
                                                    @if($mGeo && $mGeo->latitude)
                                                        <div class="mt-1 pt-1 border-t {{ $isMe ? 'border-white/10' : 'border-slate-200' }} text-[9px] text-emerald-600 font-bold flex items-center justify-between">
                                                            <span>GPS: {{ number_format((float)$mGeo->latitude, 5) }}, {{ number_format((float)$mGeo->longitude, 5) }}</span>
                                                            <span class="uppercase">({{ $mGeo->gps_source }})</span>
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
                        <div class="text-center py-12 text-slate-400">
                            <i data-lucide="message-square" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                            <p class="text-xs">No messages yet. Send a message to start coordination.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Composer --}}
                <div class="p-3 border-t border-slate-200 bg-white shrink-0">
                    <form method="POST" action="{{ route('office.messages.send', $activeConversation->id) }}" enctype="multipart/form-data" class="space-y-2">
                        @csrf
                        <div x-show="hasGps" x-cloak class="p-1.5 rounded-lg bg-emerald-50 border border-emerald-200 flex items-center justify-between text-xs text-emerald-800">
                            <span class="font-bold flex items-center gap-1 text-[11px]">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-600"></i>
                                GPS Attached: <span x-text="lat + ', ' + lng" class="font-mono"></span>
                            </span>
                            <button type="button" @click="clearGps()" class="text-slate-400 hover:text-slate-600">
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
                            <div class="flex-1 bg-slate-50 rounded-2xl border border-slate-200 p-2 focus-within:ring-2 focus-within:ring-[#3187D8] transition-all">
                                <textarea name="body" rows="2" placeholder="Write message to Central EOC..."
                                          class="w-full bg-transparent resize-none border-none outline-none text-xs text-slate-800 placeholder-slate-400"></textarea>

                                <div x-show="fileName" x-cloak class="flex items-center justify-between px-2 py-1 bg-white rounded-lg border border-slate-200 my-1 text-xs text-slate-600">
                                    <span class="truncate font-mono text-[11px]" x-text="fileName"></span>
                                    <button type="button" @click="clearFile()" class="text-slate-400 hover:text-slate-600">
                                        <i data-lucide="x" class="w-3 h-3"></i>
                                    </button>
                                </div>

                                <div class="flex items-center justify-between pt-1 border-t border-slate-200/60">
                                    <div class="flex items-center gap-1.5">
                                        <label class="p-1 text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 rounded-lg cursor-pointer transition-colors" title="Attach file">
                                            <i data-lucide="paperclip" class="w-4 h-4"></i>
                                            <input type="file" name="attachment" id="office-file-input" class="hidden" @change="handleFileChange($event)"
                                                   accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,application/pdf,.doc,.docx">
                                        </label>

                                        <button type="button" @click="requestGps()"
                                                :class="hasGps ? 'text-emerald-600 bg-emerald-100' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-200/60'"
                                                class="p-1 rounded-lg transition-colors flex items-center gap-1 text-[11px] font-bold">
                                            <i data-lucide="crosshair" class="w-4 h-4"></i>
                                            <span>GPS</span>
                                        </button>

                                        @if($activeConversation->incident)
                                            <label class="flex items-center gap-1 text-[11px] text-slate-600 ml-2 cursor-pointer">
                                                <input type="checkbox" name="inherit_incident_location" value="1" class="rounded text-[#3187D8]">
                                                <span>Inherit Incident GPS</span>
                                            </label>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-mono">Secure Agency Terminal</span>
                                </div>
                            </div>

                            <button type="submit"
                                    class="h-10 px-4 rounded-xl bg-[#173957] hover:bg-[#122e46] text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-xs transition-colors shrink-0">
                                <span>Send</span>
                                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @else
            <div class="flex-1 flex items-center justify-center bg-slate-50">
                <div class="text-center p-8">
                    <p class="text-xs text-slate-400">Select a conversation or initiate contact with Central EOC.</p>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function officeMessenger(config) {
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
                const el = document.getElementById('office-messages-container');
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        requestGps() {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported.');
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
            const el = document.getElementById('office-file-input');
            if (el) el.value = '';
            this.fileName = null;
        }
    };
}
</script>
@endpush
</x-layouts.office>
