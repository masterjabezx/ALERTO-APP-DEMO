@props([
    'label' => 'Password',
    'name' => 'password',
    'placeholder' => '••••••••',
    'required' => false,
    'error' => null,
])

@php
    $id = $attributes->get('id', $name);
    $hasError = $errors->has($name) || !empty($error);
    $errorMessage = $error ?? ($name ? $errors->first($name) : null);
@endphp

<div class="w-full" x-data="{ show: false }">
    @if($label)
        <label for="{{ $id }}" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-[#DB4545] font-bold">*</span>
            @endif
        </label>
    @endif

    <div class="relative rounded-xl shadow-xs">
        <input
            :type="show ? 'text' : 'password'"
            name="{{ $name }}"
            id="{{ $id }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge([
                'class' => 'w-full py-2.5 pl-3.5 pr-11 text-sm rounded-xl border bg-white text-slate-900 placeholder-slate-400 transition-colors duration-150 focus:outline-none focus:ring-2 ' .
                ($hasError 
                    ? 'border-[#DB4545] focus:border-[#DB4545] focus:ring-[#DB4545]/20 text-[#DB4545]' 
                    : 'border-slate-300 focus:border-[#3187D8] focus:ring-[#3187D8]/20')
            ]) }}
        />

        <button 
            type="button" 
            x-on:click="show = !show" 
            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
            tabindex="-1"
            :title="show ? 'Hide password' : 'Show password'"
        >
            <span x-show="!show">
                <i data-lucide="eye" class="w-4 h-4"></i>
            </span>
            <span x-show="show" x-cloak>
                <i data-lucide="eye-off" class="w-4 h-4"></i>
            </span>
        </button>
    </div>

    @if($errorMessage)
        <p class="mt-1.5 text-xs text-[#DB4545] font-medium flex items-center gap-1">
            <span>{{ $errorMessage }}</span>
        </p>
    @endif
</div>

