@props([
    'label' => null,
    'name' => null,
    'options' => [],
    'placeholder' => 'Select an option',
    'selected' => null,
    'required' => false,
    'error' => null,
])

@php
    $id = $attributes->get('id', $name ?? 'select-' . uniqid());
    $hasError = ($name && $errors->has($name)) || !empty($error);
    $errorMessage = $error ?? ($name ? $errors->first($name) : null);
    $currentVal = $name ? old($name, $selected) : $selected;
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
        <select
            @if($name) name="{{ $name }}" @endif
            id="{{ $id }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge([
                'class' => 'w-full min-h-[44px] py-2.5 pl-3.5 pr-10 text-sm rounded-xl border bg-white text-[#17191C] transition-colors duration-150 focus:outline-none focus:ring-2 appearance-none cursor-pointer ' .
                ($hasError
                    ? 'border-[#C83F43] focus:border-[#C83F43] focus:ring-[#C83F43]/20'
                    : 'border-[#E6E3DE] focus:border-[#E87524] focus:ring-[#E87524]/20')
            ]) }}
        >
            @if($placeholder)
                <option value="" disabled {{ is_null($currentVal) || $currentVal === '' ? 'selected' : '' }}>
                    {{ $placeholder }}
                </option>
            @endif

            @foreach($options as $val => $optLabel)
                <option value="{{ $val }}" {{ (string) $currentVal === (string) $val ? 'selected' : '' }}>
                    {{ $optLabel }}
                </option>
            @endforeach
            {{ $slot }}
        </select>

        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-[#77736E]">
            <i data-lucide="chevron-down" class="w-4 h-4"></i>
        </div>
    </div>

    @if($errorMessage)
        <p class="mt-1.5 text-xs text-[#C83F43] font-semibold">{{ $errorMessage }}</p>
    @endif
</div>
