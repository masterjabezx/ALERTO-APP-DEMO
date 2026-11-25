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
        <label for="{{ $id }}" class="block text-xs font-bold uppercase tracking-wider text-[#454A50] mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-[#C83F43] font-bold">*</span>
            @endif
        </label>
    @endif

    <div class="relative rounded-xl">
        <input
            :type="show ? 'text' : 'password'"
            name="{{ $name }}"
            id="{{ $id }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge([
                'class' => 'w-full min-h-[44px] py-2.5 pl-3.5 pr-11 text-sm rounded-xl border bg-white text-[#17191C] placeholder-[#77736E]/70 transition-colors duration-150 focus:outline-none focus:ring-2 ' .
                ($hasError
                    ? 'border-[#C83F43] focus:border-[#C83F43] focus:ring-[#C83F43]/20 text-[#C83F43]'
                    : 'border-[#E6E3DE] focus:border-[#E87524] focus:ring-[#E87524]/20')
            ]) }}
        />

        <button
            type="button"
            x-on:click="show = !show"
            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[#77736E] hover:text-[#17191C] focus:outline-none cursor-pointer"
            :aria-label="show ? 'Hide password' : 'Show password'"
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
        <p class="mt-1.5 text-xs text-[#C83F43] font-semibold flex items-center gap-1">
            <span>{{ $errorMessage }}</span>
        </p>
    @endif
</div>
