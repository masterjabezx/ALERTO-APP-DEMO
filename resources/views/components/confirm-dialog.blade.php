@props([
    'name',
    'title' => 'Confirm Action',
    'message' => 'Are you sure you want to proceed with this operation?',
    'confirmText' => 'Confirm',
    'cancelText' => 'Cancel',
    'variant' => 'emergency', // emergency, warning, primary
    'action' => '#',
    'method' => 'POST',
])

<x-modal :name="$name" maxWidth="sm">
    <div class="text-center sm:text-left">
        <div class="flex items-center gap-3.5 mb-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0 {{ $variant === 'emergency' ? 'bg-[#DB4545]/10 text-[#DB4545]' : ($variant === 'warning' ? 'bg-[#F0A82E]/15 text-[#b3740e]' : 'bg-[#173957]/10 text-[#173957]') }}">
                <i data-lucide="{{ $variant === 'emergency' ? 'alert-triangle' : ($variant === 'warning' ? 'alert-circle' : 'help-circle') }}" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-900">{{ $title }}</h3>
                <p class="text-xs text-slate-500 mt-0.5">Please confirm your decision.</p>
            </div>
        </div>

        <p class="text-sm text-slate-600 leading-relaxed">{{ $message }}</p>
    </div>

    <x-slot:footer>
        <button
            type="button"
            x-on:click="show = false"
            class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 transition-colors"
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

