@props([
    'title' => 'Action Required',
    'message' => 'An unexpected issue prevented this operation from completing.',
    'icon' => 'alert-octagon',
])

<div role="alert" {{ $attributes->merge(['class' => 'rounded-2xl border border-[#C83F43]/35 bg-[#C83F43]/10 p-4 text-xs text-[#C83F43] flex items-start gap-3']) }}>
    <div class="w-8 h-8 rounded-xl bg-[#C83F43]/15 text-[#C83F43] flex items-center justify-center shrink-0">
        <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
    </div>
    <div class="min-w-0 flex-1">
        <h3 class="font-bold text-sm text-[#17191C]">{{ $title }}</h3>
        <p class="mt-0.5 text-xs leading-relaxed">{{ $message }}</p>
        @if($slot->isNotEmpty())
            <div class="mt-2.5">{{ $slot }}</div>
        @endif
    </div>
</div>

