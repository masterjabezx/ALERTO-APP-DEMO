{{-- ALERTO APP PWA Install Banner --}}
<div x-show="$store.pwa.canInstall && !$store.pwa.isStandalone"
     x-cloak
     class="sticky top-0 z-40 bg-[#17191C] text-[#F5F3EF] border-b border-[#E87524]/40 px-4 py-2.5 shadow-md no-print">
    <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <img src="/images/branding/alerto-logo-web-128.png" alt="ALERTO APP" class="w-10 h-10 rounded-xl object-contain shrink-0">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-extrabold text-xs tracking-tight text-white">INSTALL ALERTO APP</span>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#F29A56] bg-[#E87524]/15 px-1.5 py-0.5 rounded-sm border border-[#E87524]/40">Fast Access</span>
                </div>
                <p class="text-[11px] text-[#D4D0C9]">
                    <template x-if="!$store.pwa.isIos">
                        <span>Install on your device for offline emergency reporting &amp; real-time alerts.</span>
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
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-[#E87524] hover:bg-[#B94F12] text-white shadow-xs transition-colors cursor-pointer">
                    Install Now
                </button>
            </template>
            <button type="button"
                    @click="$store.pwa.dismissInstall()"
                    aria-label="Dismiss install banner"
                    class="p-1.5 rounded-lg text-[#9D9993] hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
                    title="Dismiss">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
</div>
