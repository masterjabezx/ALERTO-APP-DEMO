<x-layouts.admin :title="'Facebook Messenger Settings — Communications Command'">
<div class="space-y-6 max-w-5xl">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.communications.fb.inbox') }}" class="hover:text-[#1877F2] font-medium">Facebook Messenger</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-slate-800 font-bold">Integration Settings</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 flex items-center gap-2.5">
                <i data-lucide="sliders" class="w-7 h-7 text-[#1877F2]"></i>
                Meta Platform &amp; Webhook Configuration
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Manage the official Hinoba-an LDRRMO Facebook Page connection, webhook endpoints, and automated intake policies.
            </p>
        </div>

        <a href="{{ route('admin.communications.fb.inbox') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#1877F2] hover:bg-[#1565cb] text-white rounded-2xl text-xs font-bold transition-colors shadow-xs shrink-0">
            <i data-lucide="message-circle" class="w-4 h-4"></i>
            Open Messenger Inbox
        </a>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-semibold flex items-center gap-3">
            <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- System Health Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 rounded-3xl bg-white border border-slate-200 shadow-xs space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Connection Status</span>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full {{ $setting->is_active ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
                <span class="text-base font-black text-slate-900">{{ $setting->is_active ? 'Active & Connected' : 'Paused' }}</span>
            </div>
            <p class="text-[10px] text-slate-400">Meta Graph API {{ $graphVersion }}</p>
        </div>

        <div class="p-4 rounded-3xl bg-white border border-slate-200 shadow-xs space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Webhook Status</span>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full {{ $setting->webhook_verified ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                <span class="text-base font-black text-slate-900">{{ $setting->webhook_verified ? 'Verified' : 'Pending Verification' }}</span>
            </div>
            <p class="text-[10px] text-slate-400">
                Last event: {{ $setting->last_webhook_at ? $setting->last_webhook_at->diffForHumans() : 'None yet' }}
            </p>
        </div>

        <div class="p-4 rounded-3xl bg-white border border-slate-200 shadow-xs space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Conversations</span>
            <div class="text-2xl font-black text-slate-900">{{ number_format($totalConversations) }}</div>
            <p class="text-[10px] text-slate-400">{{ number_format($totalMessages) }} total messages processed</p>
        </div>

        <div class="p-4 rounded-3xl bg-white border border-slate-200 shadow-xs space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Emergency Intakes</span>
            <div class="text-2xl font-black text-[#1877F2]">{{ number_format($totalIntakes) }}</div>
            <p class="text-[10px] text-slate-400">Logged via Assistant</p>
        </div>
    </div>

    {{-- Webhook Configuration Box --}}
    <div class="p-6 rounded-3xl bg-white border border-slate-200 shadow-xs space-y-4">
        <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
            <div class="w-9 h-9 rounded-xl bg-blue-50 text-[#1877F2] flex items-center justify-center font-bold">
                <i data-lucide="webhook" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="text-sm font-black text-slate-900">Meta Webhook Endpoint Configuration</h3>
                <p class="text-xs text-slate-500">Provide this callback URL and verification token in your Meta App Dashboard under Messenger &gt; Webhooks.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Callback URL (HTTPS Required by Meta)</label>
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="{{ $webhookUrl }}"
                           class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-800 outline-none select-all">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Verify Token</label>
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="{{ $setting->verify_token }}"
                           class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-800 outline-none select-all">
                </div>
            </div>
        </div>

        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600 flex items-start gap-2">
            <i data-lucide="info" class="w-4 h-4 text-[#1877F2] shrink-0 mt-0.5"></i>
            <div>
                <span class="font-bold">Required Meta Webhook Field Subscriptions:</span>
                <span class="font-mono text-[11px] block mt-0.5 text-slate-700">messages, messaging_postbacks, messaging_optins</span>
            </div>
        </div>
    </div>

    {{-- Page & Credentials Form --}}
    <div class="p-6 rounded-3xl bg-white border border-slate-200 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-black text-slate-900">Facebook Page &amp; Meta App Credentials</h3>
                <p class="text-xs text-slate-500">Sensitive tokens and application secrets are protected on the server and never exposed in plain text.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.communications.fb.settings.update') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Official Facebook Page Name</label>
                    <input type="text" name="page_name" value="{{ old('page_name', $setting->page_name) }}" required
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs outline-none focus:ring-2 focus:ring-[#1877F2]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Facebook Page ID</label>
                    <input type="text" name="page_id" value="{{ old('page_id', $setting->page_id) }}" required
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono outline-none focus:ring-2 focus:ring-[#1877F2]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Meta App ID</label>
                    <input type="text" name="app_id" value="{{ old('app_id', $appId) }}"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono outline-none focus:ring-2 focus:ring-[#1877F2]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Webhook Verify Token</label>
                    <input type="text" name="verify_token" value="{{ old('verify_token', $setting->verify_token) }}"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono outline-none focus:ring-2 focus:ring-[#1877F2]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Meta App Secret (Masked)</label>
                    <input type="text" readonly value="{{ $maskedAppSecret }}"
                           class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-xl text-xs font-mono text-slate-500 cursor-not-allowed">
                    <span class="text-[10px] text-slate-400 mt-1 block">Set in your .env as META_APP_SECRET</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Page Access Token (Masked)</label>
                    <input type="text" readonly value="{{ $maskedPageToken }}"
                           class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-xl text-xs font-mono text-slate-500 cursor-not-allowed">
                    <span class="text-[10px] text-slate-400 mt-1 block">Set in your .env as META_PAGE_ACCESS_TOKEN</span>
                </div>
            </div>

            <div class="pt-2 flex items-center justify-between border-t border-slate-100">
                <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-700">
                    <input type="checkbox" name="is_active" value="1" {{ $setting->is_active ? 'checked' : '' }}
                           class="rounded text-[#1877F2] focus:ring-[#1877F2]">
                    <span>Enable Automated Facebook Messenger Webhook &amp; Assistant</span>
                </label>

                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-[#173957] hover:bg-[#122e46] text-white text-xs font-bold shadow-xs transition-colors">
                    Save Integration Settings
                </button>
            </div>
        </form>
    </div>
</div>
</x-layouts.admin>

