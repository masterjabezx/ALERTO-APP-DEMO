@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'placeholder' => '',
    'value' => null,
    'icon' => null,
    'hint' => null,
    'required' => false,
    'error' => null,
])

@php
    $id = $attributes->get('id', $name ?? 'input-' . uniqid());
    $hasError = ($name && $errors->has($name)) || !empty($error);
    $errorMessage = $error ?? ($name ? $errors->first($name) : null);
@endphp

<div class="w-full">
    @if($label)
        <label for="{{ $id }}" class="block text-xs font-bold uppercase tracking-wider text-[#454A50] mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-[#C83F43] font-bold">*</span>
            @endif
        </label>
    @endif

    <div class="relative rounded-xl">
        @if($icon)
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#77736E]">
                <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
            </div>
        @endif

        <input
            type="{{ $type }}"
            @if($name) name="{{ $name }}" @endif
            id="{{ $id }}"
            value="{{ $name ? old($name, $value) : $value }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge([
                'class' => 'w-full min-h-[44px] py-2.5 px-3.5 text-sm rounded-xl border bg-white text-[#17191C] placeholder-[#77736E]/70 transition-colors duration-150 focus:outline-none focus:ring-2 ' .
                ($icon ? 'pl-10 ' : '') .
                ($hasError
                    ? 'border-[#C83F43] focus:border-[#C83F43] focus:ring-[#C83F43]/20 text-[#C83F43]'
                    : 'border-[#E6E3DE] focus:border-[#E87524] focus:ring-[#E87524]/20')
            ]) }}
        />

        @if($hasError)
            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-[#C83F43]">
                <i data-lucide="alert-circle" class="w-4 h-4"></i>
            </div>
        @endif
    </div>

    @if($errorMessage)
        <p class="mt-1.5 text-xs text-[#C83F43] font-semibold flex items-center gap-1">
            <span>{{ $errorMessage }}</span>
        </p>
    @elseif($hint)
        <p class="mt-1 text-xs text-[#77736E]">{{ $hint }}</p>
    @endif
</div>
