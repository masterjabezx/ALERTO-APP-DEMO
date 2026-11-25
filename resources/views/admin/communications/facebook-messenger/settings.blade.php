<x-layouts.admin :title="'Facebook Messenger Settings — Communications Command'">
<div class="space-y-6 max-w-5xl">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <x-portal.breadcrumbs :items="[
                ['label' => 'Facebook Messenger', 'url' => route('admin.communications.fb.inbox')],
                ['label' => 'Integration Settings']
            ]" />
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight flex items-center gap-2.5 mt-2">
                <i data-lucide="sliders" class="w-7 h-7 text-[#E87524]"></i>
                Meta Platform &amp; Webhook Configuration
            </h1>
            <p class="text-xs sm:text-sm text-[#77736E] mt-1">
                Manage the official Hinoba-an LDRRMO Facebook Page connection, webhook endpoints, and automated intake policies.
            </p>
        </div>

        <a href="{{ route('admin.communications.fb.inbox') }}"
           class="alerto-btn-press inline-flex items-center gap-1.5 px-4 py-2 bg-[#E87524] hover:bg-[#B94F12] text-white rounded-2xl text-xs font-bold transition-colors shadow-xs shrink-0">
            <i data-lucide="message-circle" class="w-4 h-4"></i>
            Open Messenger Inbox
        </a>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-[#27845B]/15 border border-[#27845B]/35 text-[#27845B] text-xs font-semibold flex items-center gap-3">
            <i data-lucide="check-circle" class="w-5 h-5 text-[#27845B] shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- System Health Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="alerto-card p-4 space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-[#77736E]">Connection Status</span>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full {{ $setting->is_active ? 'bg-[#27845B] animate-pulse' : 'bg-[#77736E]' }}"></span>
                <span class="text-base font-black">{{ $setting->is_active ? 'Active & Connected' : 'Paused' }}</span>
            </div>
            <p class="text-[10px] text-[#77736E]">Meta Graph API {{ $graphVersion }}</p>
        </div>

        <div class="alerto-card p-4 space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-[#77736E]">Webhook Status</span>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full {{ $setting->webhook_verified ? 'bg-[#27845B]' : 'bg-[#D99024]' }}"></span>
                <span class="text-base font-black">{{ $setting->webhook_verified ? 'Verified' : 'Pending Verification' }}</span>
            </div>
            <p class="text-[10px] text-[#77736E]">
                Last event: {{ $setting->last_webhook_at ? $setting->last_webhook_at->diffForHumans() : 'None yet' }}
            </p>
        </div>

        <div class="alerto-card p-4 space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-[#77736E]">Total Conversations</span>
            <div class="text-2xl font-black font-mono">{{ number_format($totalConversations) }}</div>
            <p class="text-[10px] text-[#77736E]">{{ number_format($totalMessages) }} total messages processed</p>
        </div>

        <div class="alerto-card p-4 space-y-1 border-[#E87524]/30">
            <span class="text-[10px] font-bold uppercase tracking-wider text-[#77736E]">Emergency Intakes</span>
            <div class="text-2xl font-black font-mono text-[#E87524]">{{ number_format($totalIntakes) }}</div>
            <p class="text-[10px] text-[#77736E]">Logged via Assistant</p>
        </div>
    </div>

    {{-- Webhook Configuration Box --}}
    <div class="alerto-card p-6 space-y-4">
        <div class="flex items-center gap-3 pb-3 border-b border-[#E6E3DE] dark:border-white/10">
            <div class="w-9 h-9 rounded-xl bg-[#E87524]/15 text-[#E87524] flex items-center justify-center font-bold">
                <i data-lucide="webhook" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="text-sm font-black">Meta Webhook Endpoint Configuration</h3>
                <p class="text-xs text-[#77736E]">Provide this callback URL and verification token in your Meta App Dashboard under Messenger &gt; Webhooks.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold mb-1">Callback URL (HTTPS Required by Meta)</label>
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="{{ $webhookUrl }}"
                           class="flex-1 px-3 py-2 bg-[#F5F3EF] dark:bg-white/5 border border-[#E6E3DE] dark:border-white/15 rounded-xl text-xs font-mono outline-hidden select-all">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold mb-1">Verify Token</label>
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="{{ $setting->verify_token }}"
                           class="flex-1 px-3 py-2 bg-[#F5F3EF] dark:bg-white/5 border border-[#E6E3DE] dark:border-white/15 rounded-xl text-xs font-mono outline-hidden select-all">
                </div>
            </div>
        </div>

        <div class="p-3 rounded-2xl bg-[#F5F3EF] dark:bg-white/5 border border-[#E6E3DE] dark:border-white/10 text-xs flex items-start gap-2">
            <i data-lucide="info" class="w-4 h-4 text-[#E87524] shrink-0 mt-0.5"></i>
            <div>
                <span class="font-bold">Required Meta Webhook Field Subscriptions:</span>
                <span class="font-mono text-[11px] block mt-0.5 text-[#77736E]">messages, messaging_postbacks, messaging_optins</span>
            </div>
        </div>
    </div>

    {{-- Page & Credentials Form --}}
    <div class="alerto-card p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-[#E6E3DE] dark:border-white/10">
            <div>
                <h3 class="text-sm font-black">Facebook Page &amp; Meta App Credentials</h3>
                <p class="text-xs text-[#77736E]">Sensitive tokens and application secrets are protected on the server and never exposed in plain text.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.communications.fb.settings.update') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold mb-1">Official Facebook Page Name</label>
                    <input type="text" name="page_name" value="{{ old('page_name', $setting->page_name) }}" required
                           class="w-full px-3 py-2 bg-white dark:bg-[#17191C] border border-[#E6E3DE] dark:border-white/15 rounded-xl text-xs outline-hidden focus:ring-2 focus:ring-[#E87524]">
                </div>

                <div>
                    <label class="block text-xs font-bold mb-1">Facebook Page ID</label>
                    <input type="text" name="page_id" value="{{ old('page_id', $setting->page_id) }}" required
                           class="w-full px-3 py-2 bg-white dark:bg-[#17191C] border border-[#E6E3DE] dark:border-white/15 rounded-xl text-xs font-mono outline-hidden focus:ring-2 focus:ring-[#E87524]">
                </div>

                <div>
                    <label class="block text-xs font-bold mb-1">Meta App ID</label>
                    <input type="text" name="app_id" value="{{ old('app_id', $appId) }}"
                           class="w-full px-3 py-2 bg-white dark:bg-[#17191C] border border-[#E6E3DE] dark:border-white/15 rounded-xl text-xs font-mono outline-hidden focus:ring-2 focus:ring-[#E87524]">
                </div>

                <div>
                    <label class="block text-xs font-bold mb-1">Webhook Verify Token</label>
                    <input type="text" name="verify_token" value="{{ old('verify_token', $setting->verify_token) }}"
                           class="w-full px-3 py-2 bg-white dark:bg-[#17191C] border border-[#E6E3DE] dark:border-white/15 rounded-xl text-xs font-mono outline-hidden focus:ring-2 focus:ring-[#E87524]">
                </div>

                <div>
                    <label class="block text-xs font-bold mb-1">Meta App Secret (Masked)</label>
                    <input type="text" readonly value="{{ $maskedAppSecret }}"
                           class="w-full px-3 py-2 bg-[#F5F3EF] dark:bg-white/5 border border-[#E6E3DE] dark:border-white/15 rounded-xl text-xs font-mono text-[#77736E] cursor-not-allowed">
                    <span class="text-[10px] text-[#77736E] mt-1 block">Set in your .env as META_APP_SECRET</span>
                </div>

                <div>
                    <label class="block text-xs font-bold mb-1">Page Access Token (Masked)</label>
                    <input type="text" readonly value="{{ $maskedPageToken }}"
                           class="w-full px-3 py-2 bg-[#F5F3EF] dark:bg-white/5 border border-[#E6E3DE] dark:border-white/15 rounded-xl text-xs font-mono text-[#77736E] cursor-not-allowed">
                    <span class="text-[10px] text-[#77736E] mt-1 block">Set in your .env as META_PAGE_ACCESS_TOKEN</span>
                </div>
            </div>

            <div class="pt-2 flex items-center justify-between border-t border-[#E6E3DE] dark:border-white/10">
                <label class="flex items-center gap-2 cursor-pointer text-xs font-bold">
                    <input type="checkbox" name="is_active" value="1" {{ $setting->is_active ? 'checked' : '' }}
                           class="rounded text-[#E87524] focus:ring-[#E87524]">
                    <span>Enable Automated Facebook Messenger Webhook &amp; Assistant</span>
                </label>

                <button type="submit"
                        class="alerto-btn-press px-5 py-2.5 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white text-xs font-bold shadow-xs transition-colors">
                    Save Integration Settings
                </button>
            </div>
        </form>
    </div>
</div>
</x-layouts.admin>
