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
    $hasError = $errors->has($name) || !empty($error);
    $errorMessage = $error ?? ($name ? $errors->first($name) : null);
@endphp

<div class="w-full">
    @if($label)
        <label for="{{ $id }}" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-[#DB4545] font-bold">*</span>
            @endif
        </label>
    @endif

    <div class="relative rounded-xl shadow-xs">
        @if($icon)
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
            </div>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $id }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge([
                'class' => 'w-full py-2.5 px-3.5 text-sm rounded-xl border bg-white text-slate-900 placeholder-slate-400 transition-colors duration-150 focus:outline-none focus:ring-2 ' .
                ($icon ? 'pl-10 ' : '') .
                ($hasError 
                    ? 'border-[#DB4545] focus:border-[#DB4545] focus:ring-[#DB4545]/20 text-[#DB4545]' 
                    : 'border-slate-300 focus:border-[#3187D8] focus:ring-[#3187D8]/20')
            ]) }}
        />

        @if($hasError)
            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-[#DB4545]">
                <i data-lucide="alert-circle" class="w-4 h-4"></i>
            </div>
        @endif
    </div>

    @if($errorMessage)
        <p class="mt-1.5 text-xs text-[#DB4545] font-medium flex items-center gap-1">
            <span>{{ $errorMessage }}</span>
        </p>
    @elseif($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>

