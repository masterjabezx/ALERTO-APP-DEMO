@props([
    'hotline' => '0917-123-4567',
    'hotlineTitle' => 'DIAL DAPH COMMAND CENTER'
])

<div x-data="{
        drawerOpen: false,
        wasOnline: window.AlertoPwa?.network?.isOnline() ?? navigator.onLine,
        pendingItems: [],
        conflicts: [],
        lastSyncStr: '',
        async loadDetails() {
            const uid = {{ auth()->id() ?? 'null' }};
            let sosItems = [];
            try {
                const rawSos = JSON.parse(localStorage.getItem('alerto_pending_sos') || '[]');
                if (Array.isArray(rawSos)) {
                    sosItems = rawSos.map((s, idx) => ({
                        id: s.idempotency_key || ('sos_' + idx),
                        type: 'sos_alert',
                        status: 'pending',
                        title: 'Emergency SOS Alert (' + (s.sender_name || 'Distress') + ')'
                    }));
                }
            } catch (err) {
                sosItems = [];
            }

            if (!uid || !window.AlertoPwa) {
                this.pendingItems = sosItems;
                this.conflicts = [];
                await window.AlertoPwa?.sync?.refreshPendingCount(uid);
                return;
            }
            const drafts = await window.AlertoPwa.storage.getDraftIncidents(uid);
            const updates = await window.AlertoPwa.storage.getQueuedMilestones(uid);
            this.pendingItems = [...sosItems, ...drafts.filter(d => d.status !== 'submitted'), ...updates.filter(u => u.status !== 'synced')];
            this.conflicts = updates.filter(u => u.status === 'conflict');
        },
        async triggerSync() {
            const uid = {{ auth()->id() ?? 'null' }};
            if (window.AlertoPwa) {
                await window.AlertoPwa.sync.syncAll(uid);
                await this.loadDetails();
            }
        }
    }" 
    x-init="
        loadDetails();
        window.addEventListener('alerto:network-status', (e) => {
            loadDetails();
            const isOnlineNow = Boolean(e?.detail?.isOnline);
            if (!wasOnline && isOnlineNow && window.AlertoPwa?.sync) {
                window.AlertoPwa.sync.syncAll({{ auth()->id() ?? 'null' }}).then(() => loadDetails());
            }
            wasOnline = isOnlineNow;
        });
        window.addEventListener('alerto:sync-complete', () => loadDetails());
    "
    class="relative z-50">

    {{-- 1. Status Banner / Pill across layouts --}}
    <div x-show="!$store.pwa.isOnline || $store.pwa.status === 'syncing' || $store.pwa.status === 'sync_failed' || $store.pwa.pendingCount > 0"
         x-cloak
         class="w-full text-xs font-semibold transition-all duration-300 shadow-sm"
         :class="{
             'bg-[#C83F43] text-white border-b border-[#C83F43]': $store.pwa.status === 'offline' || $store.pwa.status === 'sync_failed',
             'bg-[#D99024] text-[#101214] border-b border-[#B94F12]': $store.pwa.status === 'reconnecting',
             'bg-[#E87524] text-white border-b border-[#B94F12]': $store.pwa.status === 'syncing',
             'bg-[#17191C] text-[#F5F3EF] border-b border-[#292D32]': $store.pwa.isOnline && $store.pwa.pendingCount > 0 && $store.pwa.status !== 'syncing'
         }">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-2 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2.5">
                {{-- Status Pulse Dot --}}
                <span class="relative flex h-2.5 w-2.5 shrink-0">
                    <template x-if="$store.pwa.status === 'syncing' || $store.pwa.status === 'reconnecting'">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                              :class="$store.pwa.status === 'syncing' ? 'bg-white' : 'bg-[#101214]'"></span>
                    </template>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5"
                          :class="{
                              'bg-white': $store.pwa.status === 'offline' || $store.pwa.status === 'sync_failed' || $store.pwa.status === 'syncing',
                              'bg-[#101214]': $store.pwa.status === 'reconnecting',
                              'bg-[#27845B]': $store.pwa.isOnline && $store.pwa.status !== 'syncing'
                          }"></span>
                </span>

                {{-- Status Message --}}
                <div class="flex items-center gap-2">
                    <span class="font-bold uppercase tracking-wider text-[11px]" x-text="$store.pwa.status.replace('_', ' ')"></span>
                    <span class="text-slate-300 hidden sm:inline">&bull;</span>
                    <span class="text-[11px] sm:text-xs" x-show="$store.pwa.status === 'offline'">
                        Local Mode Active. Changes are saved on this device.
                    </span>
                    <span class="text-[11px] sm:text-xs" x-show="$store.pwa.status === 'reconnecting'">
                        Restoring connection to Hinoba-an Command Center...
                    </span>
                    <span class="text-[11px] sm:text-xs" x-show="$store.pwa.status === 'syncing'">
                        Uploading pending reports and response updates...
                    </span>
                    <span class="text-[11px] sm:text-xs" x-show="$store.pwa.status === 'sync_failed'">
                        Sync failed or conflict detected. Review pending items.
                    </span>
                    <span class="text-[11px] sm:text-xs" x-show="$store.pwa.isOnline && $store.pwa.pendingCount > 0 && $store.pwa.status !== 'syncing'">
                        <span x-text="$store.pwa.pendingCount"></span> item(s) waiting to sync.
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                {{-- Real hotline calls are disabled in the demo --}}
                <template x-if="!$store.pwa.isOnline">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-300/10 text-amber-200 border border-amber-300/30">
                        No real calls in demo
                    </span>
                </template>

                {{-- Sync Drawer Button --}}
                <button type="button" 
                        @click="drawerOpen = true; loadDetails()"
                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white/10 hover:bg-white/20 text-white transition-colors">
                    Queue (<span x-text="$store.pwa.pendingCount"></span>)
                </button>
            </div>
        </div>
    </div>

    {{-- 2. Offline Sync Queue Drawer / Modal --}}
    <div x-show="drawerOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-hidden flex justify-end"
         role="dialog" 
         aria-modal="true">
        
        {{-- Backdrop --}}
        <div x-show="drawerOpen" 
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="drawerOpen = false"
             class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs"></div>

        {{-- Panel --}}
        <div x-show="drawerOpen"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="relative w-full max-w-md bg-[#17191C] border-l border-white/10 text-[#F5F3EF] flex flex-col h-full shadow-2xl z-10">

            {{-- Drawer Header --}}
            <div class="p-5 border-b border-white/10 flex items-center justify-between bg-[#101214]">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-[#E87524]/15 border border-[#E87524]/30 flex items-center justify-center text-[#F29A56]">
                        <i data-lucide="refresh-cw" class="w-4 h-4" :class="$store.pwa.status === 'syncing' ? 'animate-spin' : ''"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm text-white">Offline Synchronization</h3>
                        <p class="text-[11px] text-[#9D9993]">Hinoba-an Emergency Queue</p>
                    </div>
                </div>
                <button type="button" @click="drawerOpen = false" aria-label="Close queue drawer" class="p-1.5 rounded-lg text-[#9D9993] hover:text-white hover:bg-white/10 cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            {{-- Drawer Body --}}
            <div class="flex-1 overflow-y-auto p-5 space-y-4">
                {{-- Connection Banner --}}
                <div class="p-4 rounded-xl border"
                     :class="{
                         'bg-emerald-950/40 border-emerald-800/60 text-emerald-300': $store.pwa.isOnline,
                         'bg-rose-950/40 border-rose-800/60 text-rose-300': !$store.pwa.isOnline
                     }">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold uppercase tracking-wider" x-text="$store.pwa.isOnline ? 'Command Center Online' : 'Command Center Offline'"></span>
                        <span class="text-[10px] text-slate-400" x-show="$store.pwa.lastConnected">
                            Last online: <span x-text="new Date($store.pwa.lastConnected).toLocaleTimeString()"></span>
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-300 mt-1" x-show="!$store.pwa.isOnline">
                        Unsubmitted incident drafts and responder milestones are stored safely in local browser storage.
                    </p>
                </div>

                {{-- Demo-safe emergency contact notice --}}
                <div class="p-3.5 rounded-xl bg-amber-300/10 border border-amber-300/30">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-amber-200">Testing Environment</div>
                        <div class="text-xs font-bold text-amber-100">Emergency calls are disabled in this demo.</div>
                    </div>
                </div>

                {{-- Pending Items Section --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Local Queue (<span x-text="pendingItems.length"></span>)</h4>
                        <template x-if="$store.pwa.isOnline && pendingItems.length > 0">
                            <button type="button" 
                                    @click="triggerSync()"
                                    :disabled="$store.pwa.status === 'syncing'"
                                    class="text-xs text-[#F29A56] hover:text-[#E87524] font-bold cursor-pointer">
                                Sync Now
                            </button>
                        </template>
                    </div>

                    <template x-if="pendingItems.length === 0">
                        <div class="p-6 text-center rounded-xl bg-slate-950/50 border border-slate-800/80 text-slate-400">
                            <i data-lucide="check-circle" class="w-8 h-8 mx-auto text-emerald-400 mb-2"></i>
                            <p class="text-xs font-bold text-slate-300">All reports and milestones synchronized</p>
                            <p class="text-[10px] text-slate-500 mt-0.5">No pending items stored on this device.</p>
                        </div>
                    </template>

                    <div class="space-y-2.5">
                        <template x-for="item in pendingItems" :key="item.client_uuid">
                            <div class="p-3.5 rounded-xl bg-slate-800/90 border border-slate-700">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-white" x-text="item.category_name || item.milestone || 'Report Draft'"></span>
                                            <span class="px-1.5 py-0.5 rounded-xs text-[9px] font-bold uppercase tracking-wider"
                                                  :class="{
                                                      'bg-amber-950 text-amber-300 border border-amber-800': item.status === 'pending',
                                                      'bg-blue-950 text-blue-300 border border-blue-800': item.status === 'syncing',
                                                      'bg-rose-950 text-rose-300 border border-rose-800': item.status === 'failed',
                                                      'bg-purple-950 text-purple-300 border border-purple-800': item.status === 'conflict'
                                                  }"
                                                  x-text="item.status === 'pending' ? 'Waiting to Submit' : item.status"></span>
                                        </div>
                                        <p class="text-[11px] text-slate-400 mt-1 line-clamp-2" x-text="item.description || item.notes || item.barangay_name || 'Queued response update'"></p>
                                    </div>
                                </div>
                                <template x-if="item.last_error">
                                    <div class="mt-2 text-[10px] text-rose-300 bg-rose-950/60 p-2 rounded-lg border border-rose-900/60" x-text="item.last_error"></div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Drawer Footer --}}
            <div class="p-4 border-t border-white/10 bg-[#101214] flex items-center justify-between">
                <span class="text-[10px] text-[#9D9993]">Hinoba-an LDRRMO PWA</span>
                <button type="button"
                        @click="triggerSync()"
                        :disabled="!$store.pwa.isOnline || $store.pwa.status === 'syncing'"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-[#E87524] hover:bg-[#B94F12] disabled:opacity-50 text-white shadow-xs transition-all cursor-pointer">
                    <span x-text="$store.pwa.status === 'syncing' ? 'Syncing...' : 'Sync All'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

