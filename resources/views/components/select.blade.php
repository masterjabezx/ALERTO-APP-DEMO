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
    $hasError = $errors->has($name) || !empty($error);
    $errorMessage = $error ?? ($name ? $errors->first($name) : null);
    $currentVal = old($name, $selected);
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
        <select
            name="{{ $name }}"
            id="{{ $id }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge([
                'class' => 'w-full py-2.5 pl-3.5 pr-10 text-sm rounded-xl border bg-white text-slate-900 transition-colors duration-150 focus:outline-none focus:ring-2 appearance-none cursor-pointer ' .
                ($hasError 
                    ? 'border-[#DB4545] focus:border-[#DB4545] focus:ring-[#DB4545]/20' 
                    : 'border-slate-300 focus:border-[#3187D8] focus:ring-[#3187D8]/20')
            ]) }}
        >
            @if($placeholder)
                <option value="" disabled {{ is_null($currentVal) || $currentVal === '' ? 'selected' : '' }}>
                    {{ $placeholder }}
                </option>
            @endif

            @foreach($options as $val => $optLabel)
                <option value="{{ $val }}" {{ (string)$currentVal === (string)$val ? 'selected' : '' }}>
                    {{ $optLabel }}
                </option>
            @endforeach
            {{ $slot }}
        </select>

        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="chevron-down" class="w-4 h-4"></i>
        </div>
    </div>

    @if($errorMessage)
        <p class="mt-1.5 text-xs text-[#DB4545] font-medium">{{ $errorMessage }}</p>
    @endif
</div>

