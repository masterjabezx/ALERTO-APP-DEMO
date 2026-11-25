<div
    x-data="toastManager()"
    x-on:show-toast.window="addToast($event.detail)"
    class="fixed top-5 right-5 z-[1100] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-4 sm:px-0"
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
            class="alerto-card pointer-events-auto p-4 rounded-2xl bg-white border shadow-[0_12px_28px_-4px_rgba(16,18,20,0.2)] flex items-start gap-3"
            :class="{
                'border-[#27845B]/40': toast.type === 'success',
                'border-[#C83F43]/40': toast.type === 'error',
                'border-[#D99024]/40': toast.type === 'warning',
                'border-[#E87524]/40': toast.type === 'info',
            }"
        >
            <div
                class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                :class="{
                    'bg-[#27845B]/12 text-[#27845B]': toast.type === 'success',
                    'bg-[#C83F43]/12 text-[#C83F43]': toast.type === 'error',
                    'bg-[#D99024]/15 text-[#9a630d]': toast.type === 'warning',
                    'bg-[#FFF0E4] text-[#B94F12]': toast.type === 'info',
                }"
            >
                <i data-lucide="bell" class="w-4 h-4"></i>
            </div>

            <div class="flex-1 min-w-0 pt-0.5">
                <h4 class="text-xs font-extrabold text-[#17191C]" x-text="toast.title"></h4>
                <p class="text-xs text-[#454A50] mt-0.5 leading-relaxed" x-text="toast.message"></p>
            </div>

            <button
                type="button"
                x-on:click="removeToast(toast.id)"
                aria-label="Dismiss notification"
                class="text-[#77736E] hover:text-[#17191C] p-0.5 transition-colors cursor-pointer"
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
                    const id = Date.now() + Math.floor(Math.random() * 1000);
                    const toast = {
                        id: id,
                        title: detail.title || 'Notification',
                        message: detail.message || '',
                        type: detail.type || 'info',
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
