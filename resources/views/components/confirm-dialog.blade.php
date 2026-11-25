@props([
    'name',
    'title' => 'Confirm Action',
    'message' => 'Are you sure you want to proceed with this operation?',
    'confirmText' => 'Confirm',
    'cancelText' => 'Cancel',
    'variant' => 'emergency',
    'action' => '#',
    'method' => 'POST',
])

<x-modal :name="$name" maxWidth="sm">
    <div class="text-center sm:text-left">
        <div class="flex items-center gap-3.5 mb-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 {{ $variant === 'emergency' ? 'bg-[#C83F43]/12 text-[#C83F43]' : ($variant === 'warning' ? 'bg-[#D99024]/15 text-[#9a630d]' : 'bg-[#FFF0E4] text-[#B94F12]') }}">
                <i data-lucide="{{ $variant === 'emergency' ? 'alert-triangle' : ($variant === 'warning' ? 'alert-circle' : 'help-circle') }}" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="text-lg font-extrabold text-[#17191C]">{{ $title }}</h3>
                <p class="text-xs text-[#77736E] mt-0.5">Please confirm this operational action.</p>
            </div>
        </div>

        <p class="text-sm text-[#454A50] leading-relaxed">{{ $message }}</p>
    </div>

    <x-slot:footer>
        <button
            type="button"
            x-on:click="show = false"
            class="px-4 py-2 text-xs sm:text-sm font-bold text-[#77736E] hover:text-[#17191C] transition-colors cursor-pointer"
        >
            {{ $cancelText }}
        </button>

        <form method="{{ $method === 'GET' ? 'GET' : 'POST' }}" action="{{ $action }}">
            @csrf
            @if(!in_array($method, ['GET', 'POST']))
                @method($method)
            @endif
            <x-button :variant="$variant" type="submit" size="sm">
                {{ $confirmText }}
            </x-button>
        </form>
    </x-slot:footer>
</x-modal>
