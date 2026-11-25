@props([
    'label' => null,
    'name' => null,
    'placeholder' => '',
    'value' => null,
    'rows' => 3,
    'hint' => null,
    'required' => false,
    'error' => null,
])

@php
    $id = $attributes->get('id', $name ?? 'textarea-' . uniqid());
    $hasError = ($name && $errors->has($name)) || !empty($error);
    $errorMessage = $error ?? ($name ? $errors->first($name) : null);
    $content = $name ? old($name, $value) : $value;
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

    <textarea
        @if($name) name="{{ $name }}" @endif
        id="{{ $id }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge([
            'class' => 'w-full py-2.5 px-3.5 text-sm rounded-xl border bg-white text-[#17191C] placeholder-[#77736E]/70 transition-colors duration-150 focus:outline-none focus:ring-2 ' .
            ($hasError
                ? 'border-[#C83F43] focus:border-[#C83F43] focus:ring-[#C83F43]/20 text-[#C83F43]'
                : 'border-[#E6E3DE] focus:border-[#E87524] focus:ring-[#E87524]/20')
        ]) }}
    >{{ $content }}</textarea>

    @if($errorMessage)
        <p class="mt-1.5 text-xs text-[#C83F43] font-semibold">{{ $errorMessage }}</p>
    @elseif($hint)
        <p class="mt-1 text-xs text-[#77736E]">{{ $hint }}</p>
    @endif
</div>

