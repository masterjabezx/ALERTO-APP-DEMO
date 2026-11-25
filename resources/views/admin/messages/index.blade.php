<x-layouts.admin :title="'Internal Messenger — Emergency Operations Command'">
<div class="min-h-[680px] lg:h-[calc(100vh-5.5rem)] flex flex-col -m-4 sm:-m-6 lg:-m-8" x-data="adminMessenger({{ json_encode([
    'activeConversationId' => $activeConversation?->id,
    'currentUserId' => $user->id,
    'routes' => [
        'send' => $activeConversation ? route('admin.messages.send', $activeConversation->id) : '',
        'read' => $activeConversation ? route('admin.messages.read', $activeConversation->id) : '',
    ]
]) }})">
    {{-- Top Action Header --}}
    <div class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#173957] text-white flex items-center justify-center font-black shadow-xs">
                <i data-lucide="messages-square" class="w-5 h-5 text-[#3187D8]"></i>
            </div>
            <div>
                <h1 class="text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                    Internal Messenger &amp; Field Coordination
                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase tracking-wider">
                        EOC Secure Line
                    </span>
                </h1>
                <p class="text-xs text-slate-500">Real-time dispatch messaging between LDRRMO Admin, field personnel, and agency offices.</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="button" @click="showNewGroupModal = true"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#173957] hover:bg-[#122e46] text-white rounded-xl text-xs font-bold shadow-xs transition-colors">
                <i data-lucide="users-plus" class="w-4 h-4"></i>
                New Group Channel
            </button>
        </div>
    </div>

    {{-- Main 3-Panel Layout --}}
    <div class="flex-1 flex flex-col lg:flex-row overflow-y-auto lg:overflow-hidden bg-slate-100">
        {{-- PANEL 1: CONVERSATIONS LIST (Width: 320px on lg+) --}}
        <div class="w-full lg:w-80 xl:w-96 max-h-72 lg:max-h-none bg-white border-b lg:border-b-0 lg:border-r border-slate-200 flex flex-col shrink-0">
            {{-- Search Bar --}}
            <div class="p-3 border-b border-slate-200">
                <form method="GET" action="{{ route('admin.messages.index') }}" class="relative">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-2.5 text-slate-400"></i>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Search conversations, personnel, incident #..."
                           class="w-full pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-[#3187D8] focus:border-transparent outline-none">
                </form>

                {{-- Filter Tabs --}}
                <div class="flex gap-1 mt-2.5">
                    <button type="button" @click="activeFilter = 'all'"
                            :class="activeFilter === 'all' ? 'bg-[#173957] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-colors">
                        All ({{ $conversations->count() }})
                    </button>
                    <button type="button" @click="activeFilter = 'incident'"
                            :class="activeFilter === 'incident' ? 'bg-[#173957] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-colors">
                        Incidents
                    </button>
                    <button type="button" @click="activeFilter = 'group'"
                            :class="activeFilter === 'group' ? 'bg-[#173957] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-colors">
                        Groups
                    </button>
                    <button type="button" @click="activeFilter = 'direct'"
                            :class="activeFilter === 'direct' ? 'bg-[#173957] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-colors">
                        Direct
                    </button>
                </div>
            </div>

            {{-- Conversation Items Feed --}}
            <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
                @forelse($conversations as $conv)
                    @php
                        $isActive = $activeConversation && $activeConversation->id === $conv->id;
                        $unreadCount = $conv->unreadCountFor($user->id);
                        $otherParticipant = $conv->type === 'direct' 
                            ? $conv->participants->firstWhere('user_id', '!=', $user->id)?->user 
                            : null;
                    @endphp
                    <a href="{{ route('admin.messages.index', ['conversation_id' => $conv->id]) }}"
                       x-show="matchesFilter('{{ $conv->type }}')"
                       class="block p-3.5 transition-colors hover:bg-slate-50 {{ $isActive ? 'bg-[#3187D8]/10 border-l-4 border-[#3187D8]' : '' }}">
                        <div class="flex items-start gap-3">
                            <div class="relative shrink-0">
                                @if($conv->type === 'incident')
                                    <div class="w-10 h-10 rounded-xl bg-[#DB4545]/10 text-[#DB4545] flex items-center justify-center font-bold">
                                        <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                                    </div>
                                @elseif($conv->type === 'group')
                                    <div class="w-10 h-10 rounded-xl bg-[#173957] text-white flex items-center justify-center font-bold text-xs">
                                        <i data-lucide="users" class="w-5 h-5 text-[#3187D8]"></i>
                                    </div>
                                @else
                                    <div class="w-10 h-10 rounded-xl bg-[#3187D8]/10 text-[#3187D8] flex items-center justify-center font-bold text-xs">
                                        {{ strtoupper(substr($otherParticipant?->name ?? 'U', 0, 2)) }}
                                    </div>
                                @endif
                                @if($unreadCount > 0)
                                    <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-[#DB4545] text-white text-[9px] font-bold flex items-center justify-center border-2 border-white">
                                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-1 mb-0.5">
                                    <h4 class="text-xs font-bold text-slate-900 truncate">
                                        @if($conv->type === 'direct')
                                            {{ $otherParticipant?->name ?? 'Direct Chat' }}
                                        @else
                                            {{ $conv->title ?? 'Untitled Channel' }}
                                        @endif
                                    </h4>
                                    <span class="text-[10px] text-slate-400 font-mono shrink-0">
                                        {{ $conv->latestMessage?->created_at?->diffForHumans(short: true) ?? $conv->updated_at->diffForHumans(short: true) }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-1.5 mb-1">
                                    @if($conv->type === 'incident')
                                        <span class="px-1.5 py-0.2 rounded bg-[#DB4545]/15 text-[#DB4545] text-[9px] font-bold">
                                            INCIDENT
                                        </span>
                                    @elseif($conv->type === 'group')
                                        <span class="px-1.5 py-0.2 rounded bg-slate-200 text-slate-700 text-[9px] font-bold">
                                            GROUP
                                        </span>
                                    @else
                                        <span class="px-1.5 py-0.2 rounded bg-[#3187D8]/15 text-[#3187D8] text-[9px] font-bold uppercase">
                                            {{ $otherParticipant?->account_type ?? 'DIRECT' }}
                                        </span>
                                    @endif
                                    @if($conv->incident)
                                        <span class="text-[10px] text-slate-500 truncate font-mono">
                                            #{{ $conv->incident->reference_number }}
                                        </span>
                                    @endif
                                </div>

                                <p class="text-xs text-slate-500 truncate">
                                    @if($conv->latestMessage)
                                        <span class="font-medium text-slate-700">{{ $conv->latestMessage->sender_id === $user->id ? 'You: ' : ($conv->latestMessage->sender?->name . ': ') }}</span>
                                        {{ $conv->latestMessage->body ?? ('[' . ucfirst($conv->latestMessage->message_type) . ' attachment]') }}
                                    @else
                                        <span class="italic text-slate-400">No messages yet</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center text-slate-400">
                        <i data-lucide="message-square-off" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                        <p class="text-xs font-semibold">No conversations found</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- PANEL 2: ACTIVE CHAT AREA (Flex-1) --}}
        @if($activeConversation)
            <div class="flex-1 min-h-[420px] lg:min-h-0 flex flex-col bg-white overflow-hidden">
                {{-- Active Chat Header --}}
                <div class="p-3.5 border-b border-slate-200 flex items-center justify-between bg-slate-50/50 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-[#173957] text-white flex items-center justify-center font-bold text-xs">
                            @if($activeConversation->type === 'incident')
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-[#DB4545]"></i>
                            @elseif($activeConversation->type === 'group')
                                <i data-lucide="users" class="w-4 h-4 text-[#3187D8]"></i>
                            @else
                                <i data-lucide="user" class="w-4 h-4 text-emerald-400"></i>
                            @endif
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-black text-slate-900 tracking-tight">
                                    {{ $activeConversation->title ?? ($activeConversation->participants->firstWhere('user_id', '!=', $user->id)?->user?->name ?? 'Conversation') }}
                                </h3>
                                @if($activeConversation->incident)
                                    <a href="{{ route('admin.incidents.show', $activeConversation->incident->reference_number) }}"
                                       class="px-2 py-0.5 rounded-full bg-red-100 hover:bg-red-200 text-[#DB4545] text-[10px] font-bold transition-colors inline-flex items-center gap-1">
                                        <i data-lucide="external-link" class="w-3 h-3"></i>
                                        #{{ $activeConversation->incident->reference_number }}
                                    </a>
                                @endif
                            </div>
                            <div class="text-[11px] text-slate-500 flex items-center gap-2">
                                <span>{{ $activeConversation->participants->count() }} participants</span>
                                <span>·</span>
                                <span class="capitalize">{{ $activeConversation->type }} Channel</span>
                                @if($activeConversation->is_archived)
                                    <span class="px-1.5 py-0.2 rounded bg-amber-100 text-amber-800 text-[9px] font-bold">ARCHIVED</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('admin.messages.archive', $activeConversation->id) }}">
                            @csrf
                            <input type="hidden" name="archive" value="{{ $activeConversation->is_archived ? 0 : 1 }}">
                            <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 transition-colors">
                                {{ $activeConversation->is_archived ? 'Unarchive' : 'Archive' }}
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Messages Feed --}}
                <div id="messages-container" class="flex-1 p-4 overflow-y-auto space-y-4 bg-slate-50/40">
                    <div class="text-center my-2">
                        <span class="px-3 py-1 rounded-full bg-slate-200/60 text-slate-500 text-[10px] font-mono font-bold">
                            End-to-End Logged EOC Incident &amp; Operations Channel
                        </span>
                    </div>

                    @forelse($messages as $msg)
                        @php
                            $isMe = $msg->sender_id === $user->id;
                            $senderProfile = $msg->sender?->personnelProfile;
                            $senderOffice = $msg->sender?->officeUsers?->first()?->office;
                        @endphp
                        <div class="flex items-start gap-2.5 {{ $isMe ? 'flex-row-reverse' : '' }}">
                            {{-- Avatar --}}
                            <div class="w-8 h-8 rounded-xl shrink-0 flex items-center justify-center font-bold text-xs text-white {{ $isMe ? 'bg-[#3187D8]' : 'bg-[#173957]' }}">
                                {{ strtoupper(substr($msg->sender?->name ?? 'U', 0, 1)) }}
                            </div>

                            <div class="max-w-[75%] space-y-1">
                                {{-- Sender Meta --}}
                                <div class="flex items-center gap-1.5 text-[10px] text-slate-500 {{ $isMe ? 'justify-end' : '' }}">
                                    <span class="font-bold text-slate-700">{{ $msg->sender?->name }}</span>
                                    @if($senderProfile?->badge_number)
                                        <span class="px-1 py-0.2 rounded bg-slate-100 font-mono text-slate-600 text-[9px]">Badge #{{ $senderProfile->badge_number }}</span>
                                    @elseif($senderOffice)
                                        <span class="px-1 py-0.2 rounded bg-slate-100 font-medium text-slate-600 text-[9px]">{{ $senderOffice->name }}</span>
                                    @endif
                                    <span>·</span>
                                    <span class="font-mono">{{ $msg->created_at->format('H:i') }}</span>
                                </div>

                                {{-- Reply Context if applicable --}}
                                @if($msg->replyTo)
                                    <div class="px-3 py-1.5 rounded-lg bg-slate-100/80 border-l-2 border-[#3187D8] text-[11px] text-slate-600 mb-1">
                                        <span class="font-bold text-slate-700">{{ $msg->replyTo->sender?->name }}:</span>
                                        {{ Str::limit($msg->replyTo->body ?? '[Attachment]', 60) }}
                                    </div>
                                @endif

                                {{-- Bubble Content --}}
                                <div class="eoc-msg-bubble p-3.5 rounded-2xl shadow-xs text-xs {{ $isMe ? 'bg-[#173957] text-white rounded-tr-none' : 'bg-white border border-slate-200 text-slate-800 rounded-tl-none' }}">
                                    @if($msg->body)
                                        <p class="whitespace-pre-wrap leading-relaxed">{{ $msg->body }}</p>
                                    @endif

                                    {{-- Attachments --}}
                                    @if($msg->attachments->isNotEmpty())
                                        <div class="mt-2.5 space-y-2">
                                            @foreach($msg->attachments as $media)
                                                @php $geo = $media->geoMetadata; @endphp
                                                <div class="rounded-xl overflow-hidden border {{ $isMe ? 'border-white/20 bg-white/10' : 'border-slate-200 bg-slate-50' }} p-2">
                                                    @if(str_starts_with($media->mime_type, 'image/'))
                                                        <a href="{{ route('messenger.attachments.download', $media->id) }}" target="_blank" class="block mb-2 overflow-hidden rounded-lg">
                                                            <img src="{{ route('messenger.attachments.download', $media->id) }}" alt="{{ $media->file_name }}" class="max-h-60 rounded-lg object-cover w-full hover:scale-102 transition-transform">
                                                        </a>
                                                    @elseif(str_starts_with($media->mime_type, 'video/'))
                                                        <video controls class="max-h-60 rounded-lg w-full mb-2 bg-black">
                                                            <source src="{{ route('messenger.attachments.download', $media->id) }}" type="{{ $media->mime_type }}">
                                                            Your browser does not support video playback.
                                                        </video>
                                                    @endif

                                                    <div class="flex items-center justify-between gap-2 text-[11px]">
                                                        <div class="flex items-center gap-1.5 truncate">
                                                            <i data-lucide="file" class="w-3.5 h-3.5 shrink-0"></i>
                                                            <span class="truncate font-medium">{{ $media->file_name }}</span>
                                                            <span class="opacity-60 text-[10px]">({{ number_format($media->file_size_bytes / 1024, 1) }} KB)</span>
                                                        </div>
                                                        <a href="{{ route('messenger.attachments.download', $media->id) }}"
                                                           class="px-2 py-0.5 rounded bg-[#3187D8] text-white text-[10px] font-bold hover:bg-[#256bb0] shrink-0 transition-colors">
                                                            Download
                                                        </a>
                                                    </div>

                                                    {{-- Geo-Tag Metadata Badge & Map Link --}}
                                                    @if($geo && $geo->latitude && $geo->longitude)
                                                        <div class="mt-2 pt-2 border-t {{ $isMe ? 'border-white/15' : 'border-slate-200' }} flex items-center justify-between gap-2 text-[10px]">
                                                            <div class="flex items-center gap-1 text-emerald-600 font-bold">
                                                                <i data-lucide="map-pin" class="w-3 h-3 text-emerald-500"></i>
                                                                <span>GPS: {{ number_format((float)$geo->latitude, 6) }}, {{ number_format((float)$geo->longitude, 6) }}</span>
                                                                <span class="px-1 py-0.2 rounded bg-emerald-100 text-emerald-800 text-[8px] font-mono">
                                                                    {{ strtoupper(str_replace('_', ' ', $geo->gps_source)) }}
                                                                </span>
                                                            </div>
                                                            <a href="{{ route('admin.map') }}?lat={{ $geo->latitude }}&lng={{ $geo->longitude }}&zoom=17"
                                                               class="px-2 py-0.5 rounded bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[9px] inline-flex items-center gap-1 transition-colors">
                                                                <i data-lucide="map" class="w-2.5 h-2.5"></i>
                                                                View on Map
                                                            </a>
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
                            <p class="text-xs">No messages yet. Send a message to begin dispatch coordination.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Input Composer --}}
                <div class="p-3.5 border-t border-slate-200 bg-white shrink-0">
                    <form method="POST" action="{{ route('admin.messages.send', $activeConversation->id) }}" enctype="multipart/form-data" class="space-y-2">
                        @csrf
                        {{-- Location Telemetry Banner if GPS enabled --}}
                        <div x-show="hasGps" x-cloak class="p-2 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-between text-xs text-emerald-800">
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="map-pin" class="w-4 h-4 text-emerald-600 animate-bounce"></i>
                                <span class="font-bold">Live GPS Telemetry Attached:</span>
                                <span x-text="lat + ', ' + lng" class="font-mono text-[11px]"></span>
                                <span class="text-[10px] text-emerald-600">(±<span x-text="accuracy"></span>m)</span>
                            </div>
                            <button type="button" @click="clearGps()" class="text-slate-400 hover:text-slate-600">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>

                        {{-- Hidden GPS fields --}}
                        <input type="hidden" name="latitude" :value="lat">
                        <input type="hidden" name="longitude" :value="lng">
                        <input type="hidden" name="accuracy" :value="accuracy">
                        <input type="hidden" name="gps_source" :value="gpsSource">

                        @if($activeConversation->incident_id)
                            <input type="hidden" name="incident_id" value="{{ $activeConversation->incident_id }}">
                        @endif

                        <div class="flex items-end gap-2">
                            <div class="flex-1 bg-slate-50 rounded-2xl border border-slate-200 p-2 focus-within:ring-2 focus-within:ring-[#3187D8] focus-within:border-transparent transition-all">
                                <textarea name="body" rows="2" placeholder="Type your dispatch or coordination message..."
                                          class="w-full bg-transparent resize-none border-none outline-none text-xs text-slate-800 placeholder-slate-400"></textarea>

                                {{-- Attachment file preview --}}
                                <div x-show="fileName" x-cloak class="flex items-center justify-between px-2 py-1 bg-white rounded-lg border border-slate-200 mt-1.5 text-xs text-slate-600">
                                    <span class="truncate font-mono text-[11px]" x-text="fileName"></span>
                                    <button type="button" @click="clearFile()" class="text-slate-400 hover:text-slate-600">
                                        <i data-lucide="x" class="w-3 h-3"></i>
                                    </button>
                                </div>

                                {{-- Action Tools --}}
                                <div class="flex items-center justify-between pt-1 border-t border-slate-200/60 mt-1">
                                    <div class="flex items-center gap-1">
                                        {{-- File upload --}}
                                        <label class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 rounded-lg cursor-pointer transition-colors" title="Attach file or photo">
                                            <i data-lucide="paperclip" class="w-4 h-4"></i>
                                            <input type="file" name="attachment" id="file-input" class="hidden" @change="handleFileChange($event)"
                                                   accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,application/pdf,.doc,.docx">
                                        </label>

                                        {{-- GPS Toggle --}}
                                        <button type="button" @click="requestGps()"
                                                :class="hasGps ? 'text-emerald-600 bg-emerald-100/60' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-200/60'"
                                                class="p-1.5 rounded-lg transition-colors flex items-center gap-1 text-[11px] font-bold"
                                                title="Include current device GPS location">
                                            <i data-lucide="crosshair" class="w-4 h-4"></i>
                                            <span class="hidden sm:inline">GPS</span>
                                        </button>

                                        @if($activeConversation->incident)
                                            <label class="flex items-center gap-1 text-[11px] text-slate-600 ml-2 cursor-pointer font-medium">
                                                <input type="checkbox" name="inherit_incident_location" value="1" class="rounded border-slate-300 text-[#3187D8] focus:ring-[#3187D8]">
                                                <span>Use Incident GPS</span>
                                            </label>
                                        @endif
                                    </div>

                                    <span class="text-[10px] text-slate-400 font-mono">Press Send or Enter</span>
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

            {{-- PANEL 3: DETAILS & CONTEXT PANEL (Width: 300px on lg+) --}}
            <div class="w-full lg:w-72 bg-white border-t lg:border-t-0 lg:border-l border-slate-200 flex flex-col shrink-0 overflow-y-auto">
                <div class="p-4 border-b border-slate-200">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-2">Channel Context</h3>
                    @if($activeConversation->incident)
                        @php $inc = $activeConversation->incident; @endphp
                        <div class="p-3 bg-red-50/60 border border-red-200/80 rounded-2xl space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="px-2 py-0.5 rounded-full bg-red-100 text-[#DB4545] font-black text-[10px] font-mono">
                                    #{{ $inc->reference_number }}
                                </span>
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold text-[9px] uppercase">
                                    {{ $inc->status }}
                                </span>
                            </div>
                            <div class="text-xs font-bold text-slate-900">{{ $inc->category?->name ?? 'Emergency Incident' }}</div>
                            <p class="text-[11px] text-slate-600 line-clamp-2">{{ $inc->description }}</p>
                            <div class="text-[10px] text-slate-500 flex items-center gap-1">
                                <i data-lucide="map-pin" class="w-3 h-3 text-slate-400 shrink-0"></i>
                                <span class="truncate">{{ $inc->landmark_or_address ?? $inc->barangay?->name }}</span>
                            </div>
                            <div class="pt-2 border-t border-red-200/60 flex gap-2">
                                <a href="{{ route('admin.incidents.show', $inc->reference_number) }}"
                                   class="flex-1 py-1 text-center rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-[10px] font-bold text-slate-700 transition-colors">
                                    Full Details
                                </a>
                                <a href="{{ route('admin.map') }}?lat={{ $inc->latitude }}&lng={{ $inc->longitude }}&zoom=17"
                                   class="flex-1 py-1 text-center rounded-lg bg-[#173957] hover:bg-[#122e46] text-[10px] font-bold text-white transition-colors">
                                    Live Map
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="p-3 bg-slate-50 rounded-2xl text-xs text-slate-600">
                            General administrative or team coordination channel.
                        </div>
                    @endif
                </div>

                {{-- Participants Roster --}}
                <div class="p-4 border-b border-slate-200">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-2">
                        Participants ({{ $activeConversation->participants->count() }})
                    </h3>
                    <div class="space-y-2">
                        @foreach($activeConversation->participants as $p)
                            @php
                                $pUser = $p->user;
                                $pBadge = $pUser?->personnelProfile?->badge_number;
                                $pOffice = $pUser?->officeUsers?->first()?->office?->name;
                            @endphp
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2 truncate">
                                    <div class="w-6 h-6 rounded-lg bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-[10px] shrink-0">
                                        {{ strtoupper(substr($pUser?->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div class="truncate">
                                        <div class="font-bold text-slate-800 truncate">{{ $pUser?->name }}</div>
                                        <div class="text-[10px] text-slate-400 capitalize">{{ $pUser?->account_type }} {{ $pBadge ? "· Badge #{$pBadge}" : ($pOffice ? "· {$pOffice}" : '') }}</div>
                                    </div>
                                </div>
                                @if($p->is_admin)
                                    <span class="px-1.5 py-0.2 rounded bg-amber-100 text-amber-800 font-bold text-[9px]">LEAD</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Shared Media Gallery --}}
                <div class="p-4">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-2">Shared Files</h3>
                    @php
                        $mediaItems = $messages->flatMap->attachments;
                    @endphp
                    @if($mediaItems->isNotEmpty())
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($mediaItems->take(9) as $m)
                                <a href="{{ route('messenger.attachments.download', $m->id) }}" target="_blank"
                                   class="aspect-square rounded-xl overflow-hidden bg-slate-100 border border-slate-200 relative group flex items-center justify-center hover:opacity-90 transition-opacity">
                                    @if(str_starts_with($m->mime_type, 'image/'))
                                        <img src="{{ route('messenger.attachments.download', $m->id) }}" alt="{{ $m->file_name }}" class="w-full h-full object-cover">
                                    @else
                                        <i data-lucide="file-text" class="w-6 h-6 text-slate-400"></i>
                                    @endif
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white transition-opacity">
                                        <i data-lucide="download" class="w-4 h-4"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-slate-400 italic">No files shared yet.</p>
                    @endif
                </div>
            </div>
        @else
            <div class="flex-1 flex items-center justify-center bg-slate-50">
                <div class="text-center p-8 max-w-sm">
                    <div class="w-16 h-16 rounded-3xl bg-[#173957]/10 text-[#173957] flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="messages-square" class="w-8 h-8 text-[#3187D8]"></i>
                    </div>
                    <h3 class="text-base font-black text-slate-900 mb-1">Select a Conversation</h3>
                    <p class="text-xs text-slate-500 mb-4">Choose a channel from the left panel or initiate a new group or direct communication line.</p>
                    <button type="button" @click="showNewGroupModal = true"
                            class="px-4 py-2 bg-[#173957] hover:bg-[#122e46] text-white rounded-xl text-xs font-bold shadow-xs transition-colors">
                        Create Group Channel
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- New Group Modal --}}
    <div x-show="showNewGroupModal" x-cloak
         class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200"
             @click.outside="showNewGroupModal = false">
            <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-[#173957] text-white flex items-center justify-center font-bold">
                        <i data-lucide="users-plus" class="w-4 h-4 text-[#3187D8]"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900">New Group Channel</h3>
                        <p class="text-[11px] text-slate-500">Coordinate emergency personnel and office liaisons</p>
                    </div>
                </div>
                <button type="button" @click="showNewGroupModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.messages.group') }}" class="p-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Group Channel Name</label>
                    <input type="text" name="title" required placeholder="e.g. Typhoon Rapid Response Coordination"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-[#3187D8] focus:border-transparent outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Select Participants</label>
                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 border border-slate-200 rounded-xl p-1 bg-slate-50/50">
                        @foreach($availableContacts as $contact)
                            <label class="flex items-center gap-2.5 p-2 hover:bg-white rounded-lg cursor-pointer transition-colors text-xs">
                                <input type="checkbox" name="participant_ids[]" value="{{ $contact->id }}"
                                       class="rounded border-slate-300 text-[#3187D8] focus:ring-[#3187D8]">
                                <div class="flex-1 truncate">
                                    <span class="font-bold text-slate-800">{{ $contact->name }}</span>
                                    <span class="text-[10px] text-slate-400 block capitalize">
                                        {{ $contact->account_type }} · {{ $contact->personnelProfile?->badge_number ? 'Badge #' . $contact->personnelProfile->badge_number : ($contact->offices?->first()?->name ?? 'Command') }}
                                    </span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showNewGroupModal = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl bg-[#173957] hover:bg-[#122e46] text-white text-xs font-bold shadow-xs transition-colors">
                        Create Channel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function adminMessenger(config) {
    return {
        activeConversationId: config.activeConversationId,
        currentUserId: config.currentUserId,
        activeFilter: 'all',
        showNewGroupModal: false,
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
                        this.handleIncomingMessage(e);
                    })
                    .listen('.MessageRead', (e) => {
                        console.log('Message read event:', e);
                    });
            }
        },

        matchesFilter(type) {
            if (this.activeFilter === 'all') return true;
            return this.activeFilter === type;
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = document.getElementById('messages-container');
                if (el) {
                    el.scrollTop = el.scrollHeight;
                    const lastBubble = el.querySelector('.eoc-msg-bubble:last-of-type');
                    if (lastBubble && window.AlertoEoc?.messenger) {
                        window.AlertoEoc.messenger.animateBubble(lastBubble);
                    }
                }
            });
        },

        handleIncomingMessage(e) {
            if (e.sender && e.sender.id !== this.currentUserId) {
                // If on same active conversation, reload page or dynamically append
                window.location.reload();
            }
        },

        requestGps() {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser.');
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
                (err) => {
                    alert('Could not retrieve GPS coordinates: ' + err.message);
                },
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

        handleFileChange(event) {
            const files = event.target.files;
            if (files && files[0]) {
                this.fileName = files[0].name;
            }
        },

        clearFile() {
            const input = document.getElementById('file-input');
            if (input) input.value = '';
            this.fileName = null;
        }
    };
}
</script>
@endpush
</x-layouts.admin>

