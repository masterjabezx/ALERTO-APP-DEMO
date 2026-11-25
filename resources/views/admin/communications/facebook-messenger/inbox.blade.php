<x-layouts.admin :title="'Facebook Messenger — Communications Command'">
<div class="min-h-[680px] lg:h-[calc(100vh-5.5rem)] flex flex-col -m-4 sm:-m-6 lg:-m-8" x-data="fbMessengerInbox({{ json_encode([
    'activeConversationId' => $activeConversation?->id,
    'routes' => [
        'reply' => $activeConversation ? route('admin.communications.fb.reply', $activeConversation->id) : '',
    ]
]) }})">
    {{-- Top Action Header --}}
    <div class="bg-white dark:bg-[#17191C] border-b border-[#E6E3DE] dark:border-white/10 px-6 py-3 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-[#1877F2] text-white flex items-center justify-center font-black shadow-xs">
                <i data-lucide="message-circle" class="w-5 h-5 text-white"></i>
            </div>
            <div>
                <h1 class="text-lg font-black tracking-tight flex items-center gap-2">
                    Facebook Messenger Emergency Intake
                    <span class="px-2 py-0.5 rounded-full bg-[#397BA8]/15 text-[#397BA8] text-[10px] font-bold uppercase tracking-wider flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#397BA8] animate-pulse"></span>
                        Meta Official Page Integration
                    </span>
                </h1>
                <p class="text-xs text-[#77736E]">Public emergency inquiries, automated reporting assistant, and incident conversion.</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.communications.fb.settings') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-[#F5F3EF] dark:bg-[#292D32] hover:bg-[#E6E3DE] dark:hover:bg-white/10 border border-[#E6E3DE] dark:border-white/15 rounded-xl text-xs font-bold transition-colors">
                <i data-lucide="sliders" class="w-4 h-4 text-[#E87524]"></i>
                Integration Settings
            </a>
        </div>
    </div>

    {{-- Main 3-Panel Layout --}}
    <div class="flex-1 flex flex-col lg:flex-row overflow-y-auto lg:overflow-hidden bg-[#F5F3EF] dark:bg-[#101214]">
        {{-- PANEL 1: CONVERSATIONS LIST (Width: 320px on lg+) --}}
        <div class="w-full lg:w-80 xl:w-96 max-h-72 lg:max-h-none bg-white dark:bg-[#17191C] border-b lg:border-b-0 lg:border-r border-[#E6E3DE] dark:border-white/10 flex flex-col shrink-0">
            {{-- Search & Filter --}}
            <div class="p-3 border-b border-[#E6E3DE] dark:border-white/10 space-y-2.5">
                <form method="GET" action="{{ route('admin.communications.fb.inbox') }}" class="relative">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-2.5 text-[#77736E]"></i>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Search sender, PSID, phone, message..."
                           class="w-full pl-9 pr-3 py-1.5 bg-[#F5F3EF] dark:bg-[#101214] border border-[#E6E3DE] dark:border-white/15 rounded-xl text-xs focus:ring-2 focus:ring-[#E87524] outline-hidden">
                </form>

                <div class="flex gap-1 overflow-x-auto pb-1 alerto-scrollbar">
                    @php
                        $filters = [
                            'all' => 'All',
                            'unread' => 'Unread',
                            'possible_incident' => 'Emergencies',
                            'ready_for_review' => 'Ready for Review',
                            'incomplete' => 'Incomplete',
                            'converted' => 'Converted',
                        ];
                    @endphp
                    @foreach($filters as $key => $label)
                        <a href="{{ route('admin.communications.fb.inbox', ['status' => $key, 'search' => $search]) }}"
                           class="px-2.5 py-1 rounded-lg text-[11px] font-bold whitespace-nowrap transition-colors {{ $statusFilter === $key ? 'bg-[#E87524] text-white shadow-xs' : 'bg-[#F5F3EF] dark:bg-white/10 text-[#77736E] hover:bg-[#E6E3DE] dark:hover:bg-white/20' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Conversation Items --}}
            <div class="flex-1 overflow-y-auto divide-y divide-[#E6E3DE] dark:divide-white/10">
                @forelse($conversations as $conv)
                    @php
                        $isActive = $activeConversation && $activeConversation->id === $conv->id;
                        $intake = $conv->intakeRecord;
                    @endphp
                    <a href="{{ route('admin.communications.fb.inbox', ['conversation_id' => $conv->id, 'status' => $statusFilter, 'search' => $search]) }}"
                       class="block p-3.5 transition-colors hover:bg-[#FFF0E4]/40 dark:hover:bg-white/5 {{ $isActive ? 'bg-[#E87524]/15 border-l-4 border-[#E87524]' : '' }}">
                        <div class="flex items-start gap-3">
                            <div class="relative shrink-0">
                                <div class="w-10 h-10 rounded-2xl bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] flex items-center justify-center font-black text-xs">
                                    {{ strtoupper(substr($conv->fullName(), 0, 2)) }}
                                </div>
                                @if($conv->unread_count > 0)
                                    <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-[#C83F43] text-white text-[9px] font-bold flex items-center justify-center border-2 border-white dark:border-[#17191C]">
                                        {{ $conv->unread_count }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-0.5">
                                    <h4 class="text-xs font-bold truncate">
                                        {{ $conv->fullName() }}
                                    </h4>
                                    <span class="text-[9px] text-[#77736E] font-mono shrink-0">
                                        {{ $conv->last_interaction_at?->diffForHumans(short: true) }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-1.5 mb-1">
                                    @if($conv->is_possible_incident)
                                        <span class="px-1.5 py-0.2 rounded bg-[#C83F43]/15 text-[#C83F43] text-[9px] font-black uppercase">
                                            POSSIBLE EMERGENCY
                                        </span>
                                    @endif
                                    @if($intake)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold uppercase
                                            {{ $intake->status === 'converted' ? 'bg-[#27845B]/15 text-[#27845B]' : ($intake->status === 'ready_for_review' ? 'bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56]' : 'bg-[#D99024]/15 text-[#D99024]') }}">
                                            {{ str_replace('_', ' ', $intake->status) }}
                                        </span>
                                    @endif
                                </div>

                                <p class="text-xs text-[#77736E] truncate">
                                    {{ $conv->latest_message_text ?? 'No message text' }}
                                </p>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center text-[#77736E]">
                        <i data-lucide="message-square-off" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                        <p class="text-xs font-bold">No Messenger conversations found</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- PANEL 2: MESSENGER CHAT (Flex-1) --}}
        @if($activeConversation)
            <div class="flex-1 min-h-[420px] lg:min-h-0 flex flex-col bg-white dark:bg-[#17191C] overflow-hidden">
                {{-- Header --}}
                <div class="p-3.5 px-4 border-b border-[#E6E3DE] dark:border-white/10 flex items-center justify-between bg-[#F5F3EF] dark:bg-[#101214] shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-2xl bg-[#1877F2] text-white flex items-center justify-center font-bold text-xs">
                            <i data-lucide="message-circle" class="w-5 h-5 text-white"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-black tracking-tight">
                                    {{ $activeConversation->fullName() }}
                                </h3>
                                <span class="font-mono text-[10px] text-[#77736E]">PSID: {{ $activeConversation->fb_user_psid }}</span>
                            </div>
                            <p class="text-[11px] text-[#77736E]">
                                Last active {{ $activeConversation->last_interaction_at?->diffForHumans() }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($activeConversation->intakeRecord?->incident)
                            <a href="{{ route('admin.incidents.show', $activeConversation->intakeRecord->incident->reference_number) }}"
                               class="px-2.5 py-1 rounded-xl bg-[#27845B]/15 hover:bg-[#27845B]/25 text-[#27845B] text-xs font-bold transition-colors inline-flex items-center gap-1">
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                View Incident #{{ $activeConversation->intakeRecord->incident->reference_number }}
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Messages Stream --}}
                <div id="fb-messages-container" class="flex-1 p-4 overflow-y-auto space-y-3.5 bg-[#F5F3EF]/50 dark:bg-[#101214]/40">
                    <div class="text-center my-1">
                        <span class="px-3 py-1 rounded-full bg-[#E6E3DE]/80 dark:bg-white/10 text-[#77736E] dark:text-[#CFCBC4] text-[10px] font-mono font-bold">
                            Official Facebook Page Communication Line · Graph API v22.0
                        </span>
                    </div>

                    @forelse($messages as $msg)
                        @php
                            $isOutgoing = $msg->direction === 'outgoing';
                        @endphp
                        <div class="flex items-start gap-2.5 {{ $isOutgoing ? 'flex-row-reverse' : '' }}">
                            <div class="w-7 h-7 rounded-xl shrink-0 flex items-center justify-center font-bold text-xs text-white {{ $isOutgoing ? 'bg-[#E87524]' : 'bg-[#292D32]' }}">
                                {{ $isOutgoing ? 'EOC' : 'FB' }}
                            </div>

                            <div class="max-w-[75%] space-y-1">
                                <div class="flex items-center gap-1.5 text-[10px] text-[#77736E] {{ $isOutgoing ? 'justify-end' : '' }}">
                                    <span class="font-bold">{{ $isOutgoing ? 'Admin Dispatcher' : $activeConversation->fullName() }}</span>
                                    <span>·</span>
                                    <span class="font-mono">{{ $msg->sent_at?->format('H:i') }}</span>
                                    @if($isOutgoing)
                                        <span class="px-1 py-0.2 rounded text-[9px] uppercase font-bold
                                            {{ $msg->delivery_status === 'sent' ? 'bg-[#27845B]/15 text-[#27845B]' : ($msg->delivery_status === 'failed' ? 'bg-[#C83F43]/15 text-[#C83F43]' : 'bg-black/5 dark:bg-white/10 text-[#77736E]') }}">
                                            {{ $msg->delivery_status }}
                                        </span>
                                    @endif
                                </div>

                                <div class="eoc-fb-bubble p-3.5 rounded-2xl shadow-xs text-xs {{ $isOutgoing ? 'bg-[#17191C] text-[#F5F3EF] border border-[#E87524]/40 rounded-tr-none' : 'bg-white dark:bg-[#292D32] border border-[#E6E3DE] dark:border-white/10 rounded-tl-none' }}">
                                    @if($msg->message_text)
                                        <p class="whitespace-pre-wrap leading-relaxed">{{ $msg->message_text }}</p>
                                    @endif

                                    @if($msg->attachments->isNotEmpty())
                                        <div class="mt-2 space-y-2">
                                            @foreach($msg->attachments as $att)
                                                @php $media = $att->media; @endphp
                                                <div class="rounded-xl overflow-hidden border {{ $isOutgoing ? 'border-white/15 bg-white/5' : 'border-[#E6E3DE] dark:border-white/15 bg-[#F5F3EF] dark:bg-black/20' }} p-2">
                                                    @if($att->attachment_type === 'image' && $media)
                                                        <a href="{{ route('messenger.attachments.download', $media->id) }}" target="_blank" class="block mb-1.5">
                                                            <img src="{{ route('messenger.attachments.download', $media->id) }}" alt="Evidence" class="max-h-48 rounded-lg object-cover w-full">
                                                        </a>
                                                    @elseif($att->attachment_type === 'location' && isset($att->payload['coordinates']))
                                                        <div class="p-2 rounded-lg bg-[#27845B]/15 border border-[#27845B]/30 text-[#27845B] text-[11px] font-bold">
                                                            <div class="flex items-center gap-1 mb-1">
                                                                <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                                                <span>Shared Location Coordinates</span>
                                                            </div>
                                                            <div class="font-mono text-[10px]">
                                                                {{ $att->payload['coordinates']['lat'] }}, {{ $att->payload['coordinates']['long'] }}
                                                            </div>
                                                            <a href="{{ route('admin.map') }}?lat={{ $att->payload['coordinates']['lat'] }}&lng={{ $att->payload['coordinates']['long'] }}&zoom=17"
                                                               class="mt-1.5 inline-flex items-center gap-1 text-[10px] underline font-semibold">
                                                                Open in Live Operations Map
                                                            </a>
                                                        </div>
                                                    @endif

                                                    @if($media)
                                                        <div class="flex items-center justify-between gap-2 text-[10px] mt-1">
                                                            <span class="truncate font-medium">{{ $media->file_name }}</span>
                                                            <a href="{{ route('messenger.attachments.download', $media->id) }}" class="px-2 py-0.5 rounded bg-[#E87524] text-white font-bold shrink-0">Download</a>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if($msg->error_message)
                                        <div class="mt-2 p-1.5 rounded-lg bg-[#C83F43]/20 text-[#FCA5A5] text-[10px] font-mono">
                                            Meta Error: {{ $msg->error_message }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 text-[#77736E]">
                            <i data-lucide="message-square" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                            <p class="text-xs">No messages yet in this conversation.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Admin Reply Composer --}}
                <div class="p-3.5 border-t border-[#E6E3DE] dark:border-white/10 bg-white dark:bg-[#17191C] shrink-0">
                    <form method="POST" action="{{ route('admin.communications.fb.reply', $activeConversation->id) }}" class="space-y-2">
                        @csrf
                        {{-- Quick reply templates --}}
                        <div class="flex gap-1.5 overflow-x-auto pb-1 alerto-scrollbar">
                            <button type="button" @click="insertTemplate('Please provide your exact street or nearest landmark.')"
                                    class="px-2 py-1 rounded-lg bg-[#F5F3EF] dark:bg-white/10 hover:bg-[#FFF0E4] dark:hover:bg-white/20 text-[10px] font-bold whitespace-nowrap transition-colors">
                                Ask Landmark
                            </button>
                            <button type="button" @click="insertTemplate('Emergency response team is mobilizing to your reported location.')"
                                    class="px-2 py-1 rounded-lg bg-[#F5F3EF] dark:bg-white/10 hover:bg-[#FFF0E4] dark:hover:bg-white/20 text-[10px] font-bold whitespace-nowrap transition-colors">
                                Units Mobilizing
                            </button>
                            <button type="button" @click="insertTemplate('Please keep your mobile line open for dispatcher verification callback.')"
                                    class="px-2 py-1 rounded-lg bg-[#F5F3EF] dark:bg-white/10 hover:bg-[#FFF0E4] dark:hover:bg-white/20 text-[10px] font-bold whitespace-nowrap transition-colors">
                                Keep Line Open
                            </button>
                        </div>

                        <div class="flex items-end gap-2">
                            <div class="flex-1 bg-[#F5F3EF] dark:bg-[#101214] rounded-2xl border border-[#E6E3DE] dark:border-white/15 p-2 focus-within:ring-2 focus-within:ring-[#E87524] transition-all">
                                <textarea name="message_text" id="fb-reply-input" rows="2" required placeholder="Type reply through official Facebook Page..."
                                          class="w-full bg-transparent resize-none border-none outline-none text-xs placeholder-[#77736E]"></textarea>
                                <div class="flex items-center justify-between pt-1 border-t border-[#E6E3DE] dark:border-white/10 text-[10px] text-[#77736E]">
                                    <span>Sent via Meta Messenger Platform · Tagged CONFIRMED_EVENT_UPDATE</span>
                                    <span>Max 2,000 chars</span>
                                </div>
                            </div>

                            <button type="submit"
                                    class="alerto-btn-press h-10 px-4 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-xs transition-colors shrink-0">
                                <span>Reply</span>
                                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- PANEL 3: INCIDENT INTAKE & CONVERSION PANEL (Width: 320px on lg+) --}}
            <div class="w-full lg:w-80 bg-white dark:bg-[#17191C] border-t lg:border-t-0 lg:border-l border-[#E6E3DE] dark:border-white/10 flex flex-col shrink-0 overflow-y-auto">
                <div class="p-4 border-b border-[#E6E3DE] dark:border-white/10 bg-[#F5F3EF] dark:bg-[#101214]">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-xs font-black uppercase tracking-wider text-[#77736E]">Incident Intake Details</h3>
                        @php $intake = $activeConversation->intakeRecord; @endphp
                        @if($intake)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                                {{ $intake->status === 'converted' ? 'bg-[#27845B]/15 text-[#27845B]' : ($intake->status === 'ready_for_review' ? 'bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56]' : 'bg-[#D99024]/15 text-[#D99024]') }}">
                                {{ str_replace('_', ' ', $intake->status) }}
                            </span>
                        @endif
                    </div>
                    <p class="text-[11px] text-[#77736E]">Structured data collected by the automated assistant.</p>
                </div>

                @if($intake)
                    <div class="p-4 space-y-4">
                        {{-- Category --}}
                        <div>
                            <span class="text-[10px] font-bold uppercase text-[#77736E] block mb-1">Incident Category</span>
                            <div class="font-bold text-xs flex items-center gap-1.5">
                                <i data-lucide="tag" class="w-3.5 h-3.5 text-[#E87524]"></i>
                                {{ $intake->category?->name ?? 'Not yet categorized' }}
                            </div>
                        </div>

                        {{-- Description --}}
                        <div>
                            <span class="text-[10px] font-bold uppercase text-[#77736E] block mb-1">Description</span>
                            <div class="p-2.5 rounded-xl bg-[#F5F3EF] dark:bg-white/5 border border-[#E6E3DE] dark:border-white/10 text-xs whitespace-pre-wrap">
                                {{ $intake->collected_description ?? 'No detailed description provided yet.' }}
                            </div>
                        </div>

                        {{-- Contact Phone --}}
                        <div>
                            <span class="text-[10px] font-bold uppercase text-[#77736E] block mb-1">Contact Phone</span>
                            @if($intake->collected_phone)
                                <div class="flex items-center justify-between p-2 rounded-xl bg-[#27845B]/15 border border-[#27845B]/30 text-xs font-mono font-bold text-[#27845B]">
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                                        {{ $intake->collected_phone }}
                                    </span>
                                    <span class="px-1.5 py-0.2 rounded bg-[#27845B]/20 text-[#27845B] text-[9px] uppercase">Format Valid</span>
                                </div>
                            @else
                                <div class="p-2 rounded-xl bg-[#D99024]/15 border border-[#D99024]/30 text-xs text-[#B94F12] dark:text-[#F29A56]">
                                    Awaiting contact phone number
                                </div>
                            @endif
                        </div>

                        {{-- Location & Barangay --}}
                        <div>
                            <span class="text-[10px] font-bold uppercase text-[#77736E] block mb-1">Location / Barangay</span>
                            <div class="space-y-1 text-xs">
                                <div class="font-bold flex items-center gap-1">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-[#E87524]"></i>
                                    {{ $intake->barangay?->name ?? 'Barangay not specified' }}
                                </div>
                                @if($intake->location_details)
                                    <p class="text-[11px] text-[#77736E] pl-4">{{ $intake->location_details }}</p>
                                @endif
                                @if($intake->collected_latitude && $intake->collected_longitude)
                                    <div class="font-mono text-[10px] text-[#27845B] pl-4 pt-1 flex items-center justify-between">
                                        <span>GPS: {{ $intake->collected_latitude }}, {{ $intake->collected_longitude }}</span>
                                        <a href="{{ route('admin.map') }}?lat={{ $intake->collected_latitude }}&lng={{ $intake->collected_longitude }}&zoom=17"
                                           class="underline font-bold">Map</a>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Actions: Convert to Incident --}}
                        <div class="pt-4 border-t border-[#E6E3DE] dark:border-white/10 space-y-2">
                            @if($intake->status !== 'converted')
                                <button type="button" @click="showConvertModal = true"
                                        class="alerto-btn-press w-full py-2.5 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white font-bold text-xs flex items-center justify-center gap-2 shadow-xs transition-colors">
                                    <i data-lucide="check-square" class="w-4 h-4"></i>
                                    Convert to Formal Incident
                                </button>

                                <form method="POST" action="{{ route('admin.communications.fb.intakes.reject', $intake->id) }}">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Are you sure you want to reject this intake report?')"
                                            class="w-full py-2 rounded-xl border border-[#C83F43]/35 text-[#C83F43] hover:bg-[#C83F43]/10 font-bold text-xs transition-colors">
                                        Reject Report
                                    </button>
                                </form>
                            @else
                                <div class="p-3 rounded-2xl bg-[#27845B]/15 border border-[#27845B]/30 text-xs text-[#27845B] space-y-1 text-center">
                                    <div class="font-bold flex items-center justify-center gap-1">
                                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                                        Converted to Incident
                                    </div>
                                    <a href="{{ route('admin.incidents.show', $intake->incident->reference_number) }}"
                                       class="font-mono font-black underline block">
                                        #{{ $intake->incident->reference_number }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="p-8 text-center text-[#77736E]">
                        <p class="text-xs">No active emergency intake record on this conversation.</p>
                    </div>
                @endif
            </div>

            {{-- Convert Intake Modal --}}
            @if($intake)
                <div x-show="showConvertModal" x-cloak
                     class="fixed inset-0 z-50 bg-black/65 backdrop-blur-xs flex items-center justify-center p-4">
                    <div class="alerto-card shadow-2xl max-w-lg w-full overflow-hidden"
                         @click.outside="showConvertModal = false">
                        <div class="p-5 border-b border-[#E6E3DE] dark:border-white/10 flex items-center justify-between bg-[#F5F3EF] dark:bg-[#101214]">
                            <div>
                                <h3 class="text-sm font-black">Convert Messenger Intake to Incident</h3>
                                <p class="text-[11px] text-[#77736E]">Will enter the official Pending Review queue with source: facebook_messenger</p>
                            </div>
                            <button type="button" @click="showConvertModal = false" class="text-[#77736E] hover:text-[#17191C] dark:hover:text-white">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('admin.communications.fb.intakes.convert', $intake->id) }}" class="p-5 space-y-3.5">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold mb-1">Incident Category</label>
                                <select name="category_id" required class="w-full px-3 py-2 bg-white dark:bg-[#17191C] border border-[#E6E3DE] dark:border-white/15 rounded-xl text-xs font-bold">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ $intake->collected_category_id === $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold mb-1">Hinoba-an Barangay</label>
                                <select name="barangay_id" required class="w-full px-3 py-2 bg-white dark:bg-[#17191C] border border-[#E6E3DE] dark:border-white/15 rounded-xl text-xs font-bold">
                                    @foreach($barangays as $brgy)
                                        <option value="{{ $brgy->id }}" {{ $intake->collected_barangay_id === $brgy->id ? 'selected' : '' }}>
                                            {{ $brgy->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold mb-1">Caller Contact Phone</label>
                                <input type="text" name="phone_number" value="{{ $intake->collected_phone ?? '09170000000' }}" required
                                       class="w-full px-3 py-2 bg-white dark:bg-[#17191C] border border-[#E6E3DE] dark:border-white/15 rounded-xl text-xs font-mono font-bold">
                            </div>

                            <div>
                                <label class="block text-xs font-bold mb-1">Initial Priority</label>
                                <select name="priority" required class="w-full px-3 py-2 bg-white dark:bg-[#17191C] border border-[#E6E3DE] dark:border-white/15 rounded-xl text-xs font-bold">
                                    <option value="critical">Critical</option>
                                    <option value="high" selected>High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold mb-1">Incident Description</label>
                                <textarea name="description" rows="3" required class="w-full px-3 py-2 bg-white dark:bg-[#17191C] border border-[#E6E3DE] dark:border-white/15 rounded-xl text-xs">{{ $intake->collected_description ?? 'Emergency reported via Facebook Messenger.' }}</textarea>
                            </div>

                            <input type="hidden" name="latitude" value="{{ $intake->collected_latitude }}">
                            <input type="hidden" name="longitude" value="{{ $intake->collected_longitude }}">

                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#E6E3DE] dark:border-white/10">
                                <button type="button" @click="showConvertModal = false"
                                        class="px-4 py-2 rounded-xl border border-[#E6E3DE] dark:border-white/15 text-xs font-bold hover:bg-[#F5F3EF] dark:hover:bg-white/10">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="alerto-btn-press px-4 py-2 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white text-xs font-bold shadow-xs">
                                    Confirm Conversion
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        @else
            <div class="flex-1 flex items-center justify-center bg-[#F5F3EF] dark:bg-[#101214]/40">
                <div class="text-center p-8 max-w-sm">
                    <div class="w-16 h-16 rounded-3xl bg-[#1877F2]/15 text-[#1877F2] flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="message-circle" class="w-8 h-8 text-[#1877F2]"></i>
                    </div>
                    <h3 class="text-base font-black mb-1">Select a Facebook Conversation</h3>
                    <p class="text-xs text-[#77736E]">Pick an incoming chat from the left panel to review citizen messages and convert emergency intakes.</p>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function fbMessengerInbox(config) {
    return {
        activeConversationId: config.activeConversationId,
        showConvertModal: false,

        init() {
            this.scrollToBottom();
            if (window.Echo) {
                window.Echo.private('admin.facebook-messenger')
                    .listen('.FbMessageReceived', (e) => {
                        console.log('Incoming FB Message:', e);
                        if (e.conversation_id === this.activeConversationId) {
                            window.location.reload();
                        }
                    });
            }
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = document.getElementById('fb-messages-container');
                if (el) {
                    el.scrollTop = el.scrollHeight;
                    const lastBubble = el.querySelector('.eoc-fb-bubble:last-of-type');
                    if (lastBubble && window.AlertoEoc?.messenger) {
                        window.AlertoEoc.messenger.animateBubble(lastBubble);
                    }
                }
            });
        },

        insertTemplate(text) {
            const input = document.getElementById('fb-reply-input');
            if (input) {
                input.value = text;
                input.focus();
            }
        }
    };
}
</script>
@endpush
</x-layouts.admin>
