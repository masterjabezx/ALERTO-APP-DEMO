@props([
    'hotline' => '0917-123-4567',
    'hotlineTitle' => 'DIAL DAPH COMMAND CENTER'
])

<div x-data="{
        drawerOpen: false,
        pendingItems: [],
        conflicts: [],
        lastSyncStr: '',
        async loadDetails() {
            const uid = {{ auth()->id() ?? 'null' }};
            if (!uid || !window.AlertoPwa) return;
            const drafts = await window.AlertoPwa.storage.getDraftIncidents(uid);
            const updates = await window.AlertoPwa.storage.getQueuedMilestones(uid);
            this.pendingItems = [...drafts.filter(d => d.status !== 'submitted'), ...updates.filter(u => u.status !== 'synced')];
            this.conflicts = updates.filter(u => u.status === 'conflict');
        },
        async triggerSync() {
            const uid = {{ auth()->id() ?? 'null' }};
            if (uid && window.AlertoPwa) {
                await window.AlertoPwa.sync.syncAll(uid);
                await this.loadDetails();
            }
        }
    }" 
    x-init="
        loadDetails();
        window.addEventListener('alerto:network-status', () => loadDetails());
        window.addEventListener('alerto:sync-complete', () => loadDetails());
    "
    class="relative z-50">

    {{-- 1. Status Banner / Pill across layouts --}}
    <div x-show="!$store.pwa.isOnline || $store.pwa.status === 'syncing' || $store.pwa.status === 'sync_failed' || $store.pwa.pendingCount > 0"
         x-cloak
         class="w-full text-xs font-semibold transition-all duration-300 shadow-sm"
         :class="{
             'bg-rose-900/95 text-rose-100 border-b border-rose-700': $store.pwa.status === 'offline',
             'bg-amber-900/95 text-amber-100 border-b border-amber-700': $store.pwa.status === 'reconnecting',
             'bg-blue-900/95 text-blue-100 border-b border-blue-700': $store.pwa.status === 'syncing',
             'bg-red-950 text-red-200 border-b border-red-800': $store.pwa.status === 'sync_failed',
             'bg-slate-800/95 text-slate-200 border-b border-slate-700': $store.pwa.isOnline && $store.pwa.pendingCount > 0
         }">
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-2 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2.5">
                {{-- Status Pulse Dot --}}
                <span class="relative flex h-2.5 w-2.5 flex-shrink-0">
                    <template x-if="$store.pwa.status === 'syncing' || $store.pwa.status === 'reconnecting'">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                              :class="$store.pwa.status === 'syncing' ? 'bg-blue-400' : 'bg-amber-400'"></span>
                    </template>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5"
                          :class="{
                              'bg-rose-500': $store.pwa.status === 'offline',
                              'bg-amber-400': $store.pwa.status === 'reconnecting',
                              'bg-blue-400': $store.pwa.status === 'syncing',
                              'bg-red-400': $store.pwa.status === 'sync_failed',
                              'bg-emerald-400': $store.pwa.isOnline
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
                {{-- Quick Hotline Link when Offline --}}
                <template x-if="!$store.pwa.isOnline">
                    <a href="tel:{{ preg_replace('/[^0-9]/', '', $hotline) }}" 
                       class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-[#DB4545] hover:bg-red-600 text-white shadow-xs transition-colors">
                        <i data-lucide="phone-call" class="w-3.5 h-3.5"></i>
                        <span class="hidden sm:inline">Call Hotline</span>
                        <span class="sm:hidden">Hotline</span>
                    </a>
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
             class="relative w-full max-w-md bg-slate-900 border-l border-slate-800 text-slate-100 flex flex-col h-full shadow-2xl z-10">

            {{-- Drawer Header --}}
            <div class="p-5 border-b border-slate-800 flex items-center justify-between bg-slate-950">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-[#173957] flex items-center justify-center text-[#34B9D4]">
                        <i data-lucide="refresh-cw" class="w-4 h-4" :class="$store.pwa.status === 'syncing' ? 'animate-spin' : ''"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm text-white">Offline Synchronization</h3>
                        <p class="text-[11px] text-slate-400">Hinoba-an Emergency Queue</p>
                    </div>
                </div>
                <button type="button" @click="drawerOpen = false" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
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

                {{-- Emergency Hotline --}}
                <div class="p-3.5 rounded-xl bg-slate-800/80 border border-slate-700 flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-rose-400">Emergency Dispatch</div>
                        <div class="text-xs font-bold text-white">{{ $hotlineTitle }}</div>
                    </div>
                    <a href="tel:{{ preg_replace('/[^0-9]/', '', $hotline) }}" 
                       class="px-3 py-1.5 rounded-lg text-xs font-bold bg-[#DB4545] text-white hover:bg-red-600 transition-colors">
                        {{ $hotline }}
                    </a>
                </div>

                {{-- Pending Items Section --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Local Queue (<span x-text="pendingItems.length"></span>)</h4>
                        <template x-if="$store.pwa.isOnline && pendingItems.length > 0">
                            <button type="button" 
                                    @click="triggerSync()"
                                    :disabled="$store.pwa.status === 'syncing'"
                                    class="text-xs text-[#3187D8] hover:text-blue-400 font-bold">
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
            <div class="p-4 border-t border-slate-800 bg-slate-950 flex items-center justify-between">
                <span class="text-[10px] text-slate-500">Hinoba-an LDRRMO PWA</span>
                <button type="button" 
                        @click="triggerSync()"
                        :disabled="!$store.pwa.isOnline || $store.pwa.status === 'syncing'"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-[#3187D8] hover:bg-blue-600 disabled:opacity-50 text-white shadow-xs transition-all">
                    <span x-text="$store.pwa.status === 'syncing' ? 'Syncing...' : 'Sync All'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

