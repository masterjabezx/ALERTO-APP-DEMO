{{-- ALERTO APP PWA Install Banner --}}
<div x-show="$store.pwa.canInstall && !$store.pwa.isStandalone"
     x-cloak
     class="sticky top-0 z-40 bg-gradient-to-r from-[#0B1726] via-[#173957] to-[#0B1726] text-white border-b border-blue-500/30 px-4 py-2.5 shadow-md">
    <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-[#3187D8] text-white flex items-center justify-center shadow-md flex-shrink-0">
                <i data-lucide="download" class="w-4 h-4"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-extrabold text-xs tracking-tight">INSTALL ALERTO APP</span>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-300 bg-emerald-950/80 px-1.5 py-0.5 rounded-sm border border-emerald-700/50">Fast Access</span>
                </div>
                <p class="text-[11px] text-slate-300">
                    <template x-if="!$store.pwa.isIos">
                        <span>Install on your device for fast access and offline emergency reporting.</span>
                    </template>
                    <template x-if="$store.pwa.isIos">
                        <span>iOS: Tap <span class="font-bold text-white">Share</span> then select <span class="font-bold text-white">'Add to Home Screen'</span>.</span>
                    </template>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <template x-if="!$store.pwa.isIos">
                <button type="button"
                        @click="$store.pwa.promptInstall()"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-[#3187D8] hover:bg-blue-600 text-white shadow-xs transition-colors">
                    Install Now
                </button>
            </template>
            <button type="button"
                    @click="$store.pwa.dismissInstall()"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors"
                    title="Dismiss">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
</div>

