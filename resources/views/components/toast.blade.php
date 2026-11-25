<div
    x-data="toastManager()"
    x-on:show-toast.window="addToast($event.detail)"
    class="fixed top-5 right-5 z-50 flex flex-col gap-2.5 max-w-sm w-full pointer-events-none"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-[-12px] scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="pointer-events-auto p-4 rounded-2xl bg-white border shadow-[0_10px_28px_-4px_rgba(23,57,87,0.16)] flex items-start gap-3"
            :class="{
                'border-[#19865B]/30': toast.type === 'success',
                'border-[#DB4545]/30': toast.type === 'error',
                'border-[#F0A82E]/30': toast.type === 'warning',
                'border-[#3187D8]/30': toast.type === 'info',
            }"
        >
            <div 
                class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                :class="{
                    'bg-[#19865B]/10 text-[#19865B]': toast.type === 'success',
                    'bg-[#DB4545]/10 text-[#DB4545]': toast.type === 'error',
                    'bg-[#F0A82E]/15 text-[#b3740e]': toast.type === 'warning',
                    'bg-[#3187D8]/10 text-[#3187D8]': toast.type === 'info',
                }"
            >
                <i data-lucide="bell" class="w-4 h-4"></i>
            </div>

            <div class="flex-1 min-w-0 pt-0.5">
                <h4 class="text-xs font-bold text-slate-900" x-text="toast.title"></h4>
                <p class="text-xs text-slate-600 mt-0.5 leading-relaxed" x-text="toast.message"></p>
            </div>

            <button
                type="button"
                x-on:click="removeToast(toast.id)"
                class="text-slate-400 hover:text-slate-600 p-0.5 transition-colors"
            >
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </template>
</div>

@once
<script>
    if (typeof window.toastManager === 'undefined') {
        window.toastManager = function() {
            return {
                toasts: [],
                addToast(detail) {
                    const id = Date.now();
                    const toast = {
                        id: id,
                        title: detail.title || 'Notification',
                        message: detail.message || '',
                        type: detail.type || 'info', // success, error, warning, info
                        visible: true
                    };
                    this.toasts.push(toast);
                    setTimeout(() => window.refreshIcons ? window.refreshIcons() : null, 50);

                    setTimeout(() => {
                        this.removeToast(id);
                    }, detail.duration || 4500);
                },
                removeToast(id) {
                    const idx = this.toasts.findIndex(t => t.id === id);
                    if (idx !== -1) {
                        this.toasts[idx].visible = false;
                        setTimeout(() => {
                            this.toasts = this.toasts.filter(t => t.id !== id);
                        }, 300);
                    }
                }
            };
        };
    }
</script>
@endonce

