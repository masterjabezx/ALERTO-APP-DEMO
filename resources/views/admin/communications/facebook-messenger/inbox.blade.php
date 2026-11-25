<x-layouts.admin :title="'Facebook Messenger — Communications Command'">
<div class="min-h-[680px] lg:h-[calc(100vh-5.5rem)] flex flex-col -m-4 sm:-m-6 lg:-m-8" x-data="fbMessengerInbox({{ json_encode([
    'activeConversationId' => $activeConversation?->id,
    'routes' => [
        'reply' => $activeConversation ? route('admin.communications.fb.reply', $activeConversation->id) : '',
    ]
]) }})">
    {{-- Top Action Header --}}
    <div class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-[#1877F2] text-white flex items-center justify-center font-black shadow-xs">
                <i data-lucide="message-circle" class="w-5 h-5 text-white"></i>
            </div>
            <div>
                <h1 class="text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                    Facebook Messenger Emergency Intake
                    <span class="px-2 py-0.5 rounded-full bg-blue-100 text-[#1877F2] text-[10px] font-bold uppercase tracking-wider flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#1877F2] animate-pulse"></span>
                        Meta Official Page Integration
                    </span>
                </h1>
                <p class="text-xs text-slate-500">Public emergency inquiries, automated reporting assistant, and incident conversion.</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.communications.fb.settings') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                <i data-lucide="sliders" class="w-4 h-4 text-slate-500"></i>
                Integration Settings
            </a>
        </div>
    </div>

    {{-- Main 3-Panel Layout --}}
    <div class="flex-1 flex flex-col lg:flex-row overflow-y-auto lg:overflow-hidden bg-slate-100">
        {{-- PANEL 1: CONVERSATIONS LIST (Width: 320px on lg+) --}}
        <div class="w-full lg:w-80 xl:w-96 max-h-72 lg:max-h-none bg-white border-b lg:border-b-0 lg:border-r border-slate-200 flex flex-col shrink-0">
            {{-- Search & Filter --}}
            <div class="p-3 border-b border-slate-200 space-y-2.5">
                <form method="GET" action="{{ route('admin.communications.fb.inbox') }}" class="relative">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-2.5 text-slate-400"></i>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Search sender, PSID, phone, message..."
                           class="w-full pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-[#1877F2] outline-none">
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
                           class="px-2.5 py-1 rounded-lg text-[11px] font-bold whitespace-nowrap transition-colors {{ $statusFilter === $key ? 'bg-[#1877F2] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Conversation Items --}}
            <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
                @forelse($conversations as $conv)
                    @php
                        $isActive = $activeConversation && $activeConversation->id === $conv->id;
                        $intake = $conv->intakeRecord;
                    @endphp
                    <a href="{{ route('admin.communications.fb.inbox', ['conversation_id' => $conv->id, 'status' => $statusFilter, 'search' => $search]) }}"
                       class="block p-3.5 transition-colors hover:bg-slate-50 {{ $isActive ? 'bg-[#1877F2]/10 border-l-4 border-[#1877F2]' : '' }}">
                        <div class="flex items-start gap-3">
                            <div class="relative shrink-0">
                                <div class="w-10 h-10 rounded-2xl bg-[#1877F2]/15 text-[#1877F2] flex items-center justify-center font-black text-xs">
                                    {{ strtoupper(substr($conv->fullName(), 0, 2)) }}
                                </div>
                                @if($conv->unread_count > 0)
                                    <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-[#DB4545] text-white text-[9px] font-bold flex items-center justify-center border-2 border-white">
                                        {{ $conv->unread_count }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-0.5">
                                    <h4 class="text-xs font-bold text-slate-900 truncate">
                                        {{ $conv->fullName() }}
                                    </h4>
                                    <span class="text-[9px] text-slate-400 font-mono shrink-0">
                                        {{ $conv->last_interaction_at?->diffForHumans(short: true) }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-1.5 mb-1">
                                    @if($conv->is_possible_incident)
                                        <span class="px-1.5 py-0.2 rounded bg-red-100 text-[#DB4545] text-[9px] font-black uppercase">
                                            POSSIBLE EMERGENCY
                                        </span>
                                    @endif
                                    @if($intake)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold uppercase
                                            {{ $intake->status === 'converted' ? 'bg-emerald-100 text-emerald-800' : ($intake->status === 'ready_for_review' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800') }}">
                                            {{ str_replace('_', ' ', $intake->status) }}
                                        </span>
                                    @endif
                                </div>

                                <p class="text-xs text-slate-500 truncate">
                                    {{ $conv->latest_message_text ?? 'No message text' }}
                                </p>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center text-slate-400">
                        <i data-lucide="message-square-off" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                        <p class="text-xs font-bold">No Messenger conversations found</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- PANEL 2: MESSENGER CHAT (Flex-1) --}}
        @if($activeConversation)
            <div class="flex-1 min-h-[420px] lg:min-h-0 flex flex-col bg-white overflow-hidden">
                {{-- Header --}}
                <div class="p-3.5 px-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-2xl bg-[#1877F2] text-white flex items-center justify-center font-bold text-xs">
                            <i data-lucide="message-circle" class="w-5 h-5 text-white"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-black text-slate-900 tracking-tight">
                                    {{ $activeConversation->fullName() }}
                                </h3>
                                <span class="font-mono text-[10px] text-slate-400">PSID: {{ $activeConversation->fb_user_psid }}</span>
                            </div>
                            <p class="text-[11px] text-slate-500">
                                Last active {{ $activeConversation->last_interaction_at?->diffForHumans() }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($activeConversation->intakeRecord?->incident)
                            <a href="{{ route('admin.incidents.show', $activeConversation->intakeRecord->incident->reference_number) }}"
                               class="px-2.5 py-1 rounded-xl bg-emerald-100 hover:bg-emerald-200 text-emerald-800 text-xs font-bold transition-colors inline-flex items-center gap-1">
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                View Incident #{{ $activeConversation->intakeRecord->incident->reference_number }}
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Messages Stream --}}
                <div id="fb-messages-container" class="flex-1 p-4 overflow-y-auto space-y-3.5 bg-slate-50/30">
                    <div class="text-center my-1">
                        <span class="px-3 py-1 rounded-full bg-slate-200/70 text-slate-500 text-[10px] font-mono font-bold">
                            Official Facebook Page Communication Line · Graph API v22.0
                        </span>
                    </div>

                    @forelse($messages as $msg)
                        @php
                            $isOutgoing = $msg->direction === 'outgoing';
                        @endphp
                        <div class="flex items-start gap-2.5 {{ $isOutgoing ? 'flex-row-reverse' : '' }}">
                            <div class="w-7 h-7 rounded-xl shrink-0 flex items-center justify-center font-bold text-xs text-white {{ $isOutgoing ? 'bg-[#173957]' : 'bg-[#1877F2]' }}">
                                {{ $isOutgoing ? 'EOC' : 'FB' }}
                            </div>

                            <div class="max-w-[75%] space-y-1">
                                <div class="flex items-center gap-1.5 text-[10px] text-slate-400 {{ $isOutgoing ? 'justify-end' : '' }}">
                                    <span class="font-bold text-slate-700">{{ $isOutgoing ? 'Admin Dispatcher' : $activeConversation->fullName() }}</span>
                                    <span>·</span>
                                    <span class="font-mono">{{ $msg->sent_at?->format('H:i') }}</span>
                                    @if($isOutgoing)
                                        <span class="px-1 py-0.2 rounded text-[9px] uppercase font-bold
                                            {{ $msg->delivery_status === 'sent' ? 'bg-emerald-100 text-emerald-800' : ($msg->delivery_status === 'failed' ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600') }}">
                                            {{ $msg->delivery_status }}
                                        </span>
                                    @endif
                                </div>

                                <div class="eoc-fb-bubble p-3.5 rounded-2xl shadow-xs text-xs {{ $isOutgoing ? 'bg-[#173957] text-white rounded-tr-none' : 'bg-white border border-slate-200 text-slate-800 rounded-tl-none' }}">
                                    @if($msg->message_text)
                                        <p class="whitespace-pre-wrap leading-relaxed">{{ $msg->message_text }}</p>
                                    @endif

                                    @if($msg->attachments->isNotEmpty())
                                        <div class="mt-2 space-y-2">
                                            @foreach($msg->attachments as $att)
                                                @php $media = $att->media; @endphp
                                                <div class="rounded-xl overflow-hidden border {{ $isOutgoing ? 'border-white/20 bg-white/10' : 'border-slate-200 bg-slate-50' }} p-2">
                                                    @if($att->attachment_type === 'image' && $media)
                                                        <a href="{{ route('messenger.attachments.download', $media->id) }}" target="_blank" class="block mb-1.5">
                                                            <img src="{{ route('messenger.attachments.download', $media->id) }}" alt="Evidence" class="max-h-48 rounded-lg object-cover w-full">
                                                        </a>
                                                    @elseif($att->attachment_type === 'location' && isset($att->payload['coordinates']))
                                                        <div class="p-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-900 text-[11px] font-bold">
                                                            <div class="flex items-center gap-1 mb-1">
                                                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-600"></i>
                                                                <span>Shared Location Coordinates</span>
                                                            </div>
                                                            <div class="font-mono text-[10px]">
                                                                {{ $att->payload['coordinates']['lat'] }}, {{ $att->payload['coordinates']['long'] }}
                                                            </div>
                                                            <a href="{{ route('admin.map') }}?lat={{ $att->payload['coordinates']['lat'] }}&lng={{ $att->payload['coordinates']['long'] }}&zoom=17"
                                                               class="mt-1.5 inline-flex items-center gap-1 text-[10px] text-emerald-700 underline font-semibold">
                                                                Open in Live Operations Map
                                                            </a>
                                                        </div>
                                                    @endif

                                                    @if($media)
                                                        <div class="flex items-center justify-between gap-2 text-[10px] mt-1">
                                                            <span class="truncate font-medium">{{ $media->file_name }}</span>
                                                            <a href="{{ route('messenger.attachments.download', $media->id) }}" class="px-2 py-0.5 rounded bg-[#3187D8] text-white font-bold shrink-0">Download</a>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if($msg->error_message)
                                        <div class="mt-2 p-1.5 rounded-lg bg-rose-500/20 text-rose-200 text-[10px] font-mono">
                                            Meta Error: {{ $msg->error_message }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 text-slate-400">
                            <i data-lucide="message-square" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                            <p class="text-xs">No messages yet in this conversation.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Admin Reply Composer --}}
                <div class="p-3.5 border-t border-slate-200 bg-white shrink-0">
                    <form method="POST" action="{{ route('admin.communications.fb.reply', $activeConversation->id) }}" class="space-y-2">
                        @csrf
                        {{-- Quick reply templates --}}
                        <div class="flex gap-1.5 overflow-x-auto pb-1 alerto-scrollbar">
                            <button type="button" @click="insertTemplate('Please provide your exact street or nearest landmark.')"
                                    class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-[10px] font-bold text-slate-600 whitespace-nowrap transition-colors">
                                Ask Landmark
                            </button>
                            <button type="button" @click="insertTemplate('Emergency response team is mobilizing to your reported location.')"
                                    class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-[10px] font-bold text-slate-600 whitespace-nowrap transition-colors">
                                Units Mobilizing
                            </button>
                            <button type="button" @click="insertTemplate('Please keep your mobile line open for dispatcher verification callback.')"
                                    class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-[10px] font-bold text-slate-600 whitespace-nowrap transition-colors">
                                Keep Line Open
                            </button>
                        </div>

                        <div class="flex items-end gap-2">
                            <div class="flex-1 bg-slate-50 rounded-2xl border border-slate-200 p-2 focus-within:ring-2 focus-within:ring-[#1877F2] transition-all">
                                <textarea name="message_text" id="fb-reply-input" rows="2" required placeholder="Type reply through official Facebook Page..."
                                          class="w-full bg-transparent resize-none border-none outline-none text-xs text-slate-800 placeholder-slate-400"></textarea>
                                <div class="flex items-center justify-between pt-1 border-t border-slate-200/60 text-[10px] text-slate-400">
                                    <span>Sent via Meta Messenger Platform · Tagged CONFIRMED_EVENT_UPDATE</span>
                                    <span>Max 2,000 chars</span>
                                </div>
                            </div>

                            <button type="submit"
                                    class="h-10 px-4 rounded-xl bg-[#1877F2] hover:bg-[#1565cb] text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-xs transition-colors shrink-0">
                                <span>Reply</span>
                                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- PANEL 3: INCIDENT INTAKE & CONVERSION PANEL (Width: 320px on lg+) --}}
            <div class="w-full lg:w-80 bg-white border-t lg:border-t-0 lg:border-l border-slate-200 flex flex-col shrink-0 overflow-y-auto">
                <div class="p-4 border-b border-slate-200 bg-slate-50/50">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-500">Incident Intake Details</h3>
                        @php $intake = $activeConversation->intakeRecord; @endphp
                        @if($intake)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                                {{ $intake->status === 'converted' ? 'bg-emerald-100 text-emerald-800' : ($intake->status === 'ready_for_review' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800') }}">
                                {{ str_replace('_', ' ', $intake->status) }}
                            </span>
                        @endif
                    </div>
                    <p class="text-[11px] text-slate-500">Structured data collected by the automated assistant.</p>
                </div>

                @if($intake)
                    <div class="p-4 space-y-4">
                        {{-- Category --}}
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Incident Category</span>
                            <div class="font-bold text-xs text-slate-900 flex items-center gap-1.5">
                                <i data-lucide="tag" class="w-3.5 h-3.5 text-[#3187D8]"></i>
                                {{ $intake->category?->name ?? 'Not yet categorized' }}
                            </div>
                        </div>

                        {{-- Description --}}
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Description</span>
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 whitespace-pre-wrap">
                                {{ $intake->collected_description ?? 'No detailed description provided yet.' }}
                            </div>
                        </div>

                        {{-- Contact Phone --}}
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Contact Phone</span>
                            @if($intake->collected_phone)
                                <div class="flex items-center justify-between p-2 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-mono font-bold text-emerald-900">
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="phone" class="w-3.5 h-3.5 text-emerald-600"></i>
                                        {{ $intake->collected_phone }}
                                    </span>
                                    <span class="px-1.5 py-0.2 rounded bg-emerald-200 text-emerald-800 text-[9px] uppercase">Format Valid</span>
                                </div>
                            @else
                                <div class="p-2 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800">
                                    Awaiting contact phone number
                                </div>
                            @endif
                        </div>

                        {{-- Location & Barangay --}}
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Location / Barangay</span>
                            <div class="space-y-1 text-xs text-slate-800">
                                <div class="font-bold flex items-center gap-1">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
                                    {{ $intake->barangay?->name ?? 'Barangay not specified' }}
                                </div>
                                @if($intake->location_details)
                                    <p class="text-[11px] text-slate-600 pl-4">{{ $intake->location_details }}</p>
                                @endif
                                @if($intake->collected_latitude && $intake->collected_longitude)
                                    <div class="font-mono text-[10px] text-emerald-700 pl-4 pt-1 flex items-center justify-between">
                                        <span>GPS: {{ $intake->collected_latitude }}, {{ $intake->collected_longitude }}</span>
                                        <a href="{{ route('admin.map') }}?lat={{ $intake->collected_latitude }}&lng={{ $intake->collected_longitude }}&zoom=17"
                                           class="underline font-bold text-emerald-800">Map</a>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Actions: Convert to Incident --}}
                        <div class="pt-4 border-t border-slate-200 space-y-2">
                            @if($intake->status !== 'converted')
                                <button type="button" @click="showConvertModal = true"
                                        class="w-full py-2.5 rounded-xl bg-[#173957] hover:bg-[#122e46] text-white font-bold text-xs flex items-center justify-center gap-2 shadow-xs transition-colors">
                                    <i data-lucide="check-square" class="w-4 h-4 text-[#3187D8]"></i>
                                    Convert to Formal Incident
                                </button>

                                <form method="POST" action="{{ route('admin.communications.fb.intakes.reject', $intake->id) }}">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Are you sure you want to reject this intake report?')"
                                            class="w-full py-2 rounded-xl border border-rose-200 text-rose-700 hover:bg-rose-50 font-bold text-xs transition-colors">
                                        Reject Report
                                    </button>
                                </form>
                            @else
                                <div class="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-900 space-y-1 text-center">
                                    <div class="font-bold flex items-center justify-center gap-1">
                                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                                        Converted to Incident
                                    </div>
                                    <a href="{{ route('admin.incidents.show', $intake->incident->reference_number) }}"
                                       class="font-mono font-black text-[#173957] underline block">
                                        #{{ $intake->incident->reference_number }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="p-8 text-center text-slate-400">
                        <p class="text-xs">No active emergency intake record on this conversation.</p>
                    </div>
                @endif
            </div>

            {{-- Convert Intake Modal --}}
            @if($intake)
                <div x-show="showConvertModal" x-cloak
                     class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200"
                         @click.outside="showConvertModal = false">
                        <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                            <div>
                                <h3 class="text-sm font-black text-slate-900">Convert Messenger Intake to Incident</h3>
                                <p class="text-[11px] text-slate-500">Will enter the official Pending Review queue with source: facebook_messenger</p>
                            </div>
                            <button type="button" @click="showConvertModal = false" class="text-slate-400 hover:text-slate-600">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('admin.communications.fb.intakes.convert', $intake->id) }}" class="p-5 space-y-3.5">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Incident Category</label>
                                <select name="category_id" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ $intake->collected_category_id === $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Hinoba-an Barangay</label>
                                <select name="barangay_id" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                    @foreach($barangays as $brgy)
                                        <option value="{{ $brgy->id }}" {{ $intake->collected_barangay_id === $brgy->id ? 'selected' : '' }}>
                                            {{ $brgy->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Caller Contact Phone</label>
                                <input type="text" name="phone_number" value="{{ $intake->collected_phone ?? '09170000000' }}" required
                                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono font-bold">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Initial Priority</label>
                                <select name="priority" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                    <option value="critical">🔴 Critical</option>
                                    <option value="high" selected>🟠 High</option>
                                    <option value="medium">🔵 Medium</option>
                                    <option value="low">⚪ Low</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Incident Description</label>
                                <textarea name="description" rows="3" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">{{ $intake->collected_description ?? 'Emergency reported via Facebook Messenger.' }}</textarea>
                            </div>

                            <input type="hidden" name="latitude" value="{{ $intake->collected_latitude }}">
                            <input type="hidden" name="longitude" value="{{ $intake->collected_longitude }}">

                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                                <button type="button" @click="showConvertModal = false"
                                        class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-100">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="px-4 py-2 rounded-xl bg-[#173957] hover:bg-[#122e46] text-white text-xs font-bold shadow-xs">
                                    Confirm Conversion
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        @else
            <div class="flex-1 flex items-center justify-center bg-slate-50">
                <div class="text-center p-8 max-w-sm">
                    <div class="w-16 h-16 rounded-3xl bg-[#1877F2]/10 text-[#1877F2] flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="message-circle" class="w-8 h-8 text-[#1877F2]"></i>
                    </div>
                    <h3 class="text-base font-black text-slate-900 mb-1">Select a Facebook Conversation</h3>
                    <p class="text-xs text-slate-500">Pick an incoming chat from the left panel to review citizen messages and convert emergency intakes.</p>
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

