@extends('layouts.auth', ['bodyClass' => 'bg-[#101214] text-[#F5F3EF] flex items-center justify-center min-h-screen p-4'])

@section('title', 'Citizen Portal Login — ALERTO APP')

@section('content')
<div class="w-full max-w-md my-8" x-data="{ showPassword: false, loading: false, showForgotModal: false }">
    {{-- Top Back link & Citizen-Only Badge --}}
    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('home') }}" class="inline-flex items-center text-xs font-semibold text-[#9D9993] hover:text-white transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to ALERTO Homepage
        </a>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-[#E87524]/15 text-[#F29A56] border border-[#E87524]/35">
            Citizen Portal
        </span>
    </div>

    {{-- Citizen Login Card --}}
    <div class="bg-[#17191C] border border-white/10 rounded-[24px] p-6 sm:p-8 shadow-2xl">
        <div class="text-center mb-6">
            <img src="/images/branding/alerto-logo-web-256.png" alt="ALERTO APP" class="w-16 h-16 rounded-2xl object-contain mx-auto mb-4 drop-shadow-lg">
            <h1 class="text-2xl font-black text-white tracking-tight">Citizen Sign In</h1>
            <p class="text-xs text-[#9D9993] mt-1">Municipality of Hinoba-an · LDRRMO</p>
        </div>

        @if(session('error'))
            <div class="mb-4 p-3 rounded-xl bg-[#C83F43]/15 border border-[#C83F43]/35 text-[#FDA4AF] text-xs font-medium">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-3 rounded-xl bg-[#C83F43]/15 border border-[#C83F43]/35 text-[#FDA4AF] text-xs font-medium space-y-1">
                @foreach($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('citizen.login.post') }}" method="POST" @submit="loading = true" class="space-y-4">
            @csrf

            <div>
                <label for="login" class="block text-xs font-bold uppercase tracking-wider text-[#D4D0C9] mb-1.5">
                    Mobile Number or Email *
                </label>
                <input type="text" name="login" id="login" value="{{ old('login') }}" required autofocus
                       placeholder="Phone number or email"
                       class="w-full min-h-[44px] px-3.5 py-2.5 bg-[#101214] border border-white/15 rounded-xl text-white placeholder-[#77736E] focus:outline-none focus:border-[#E87524] focus:ring-2 focus:ring-[#E87524]/25 transition-all text-sm">
            </div>

            <div>
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-[#D4D0C9] mb-1.5">Password *</label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'" name="password" id="password" required autocomplete="current-password" placeholder="Password"
                           class="w-full min-h-[44px] px-3.5 py-2.5 pr-11 bg-[#101214] border border-white/15 rounded-xl text-white placeholder-[#77736E] focus:outline-none focus:border-[#E87524] focus:ring-2 focus:ring-[#E87524]/25 transition-all text-sm">
                    <button type="button" @click="showPassword = !showPassword"
                            :aria-label="showPassword ? 'Hide password' : 'Show password'"
                            :aria-pressed="showPassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#9D9993] hover:text-white focus:outline-none cursor-pointer">
                        <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        <svg x-show="showPassword" style="display:none;" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 text-[#D4D0C9] cursor-pointer select-none">
                    <input type="checkbox" name="remember" value="1" class="rounded bg-[#101214] border-white/20 text-[#E87524] focus:ring-[#E87524]">
                    <span>Remember me</span>
                </label>
                <button type="button" @click="showForgotModal = true" class="text-xs font-bold text-[#F29A56] hover:text-[#E87524] transition-colors cursor-pointer">
                    Forgot password?
                </button>
            </div>

            <button type="submit" :disabled="loading"
                    class="w-full min-h-[46px] py-3 px-4 bg-[#E87524] hover:bg-[#B94F12] text-white font-extrabold rounded-xl transition-colors flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer shadow-lg shadow-[#E87524]/20">
                <span x-show="!loading">Sign in</span>
                <span x-show="loading" style="display: none;" class="flex items-center space-x-2">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <span>Signing in...</span>
                </span>
            </button>
        </form>

        <div class="mt-4 pt-4 border-t border-white/10 text-center text-xs text-[#9D9993]">
            New to ALERTO?
            <a href="{{ route('citizen.register') }}" class="font-bold text-[#F29A56] hover:text-[#E87524] ml-1 transition-colors">
                Create an account
            </a>
        </div>
    </div>

    {{-- Forgot Password Modal --}}
    <div x-show="showForgotModal" style="display:none;" x-cloak
         class="fixed inset-0 z-50 bg-[#101214]/80 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="w-full max-w-sm bg-[#17191C] border border-white/15 rounded-2xl p-5 shadow-2xl text-white space-y-3"
             @click.away="showForgotModal = false">
            <h3 class="text-sm font-bold text-white">Need help signing in?</h3>
            <p class="text-xs text-[#D4D0C9] leading-relaxed">
                Call DIAL DAPH or visit the Hinoba-an LDRRMO office to reset your password.
            </p>
            <div class="flex justify-end">
                <button type="button" @click="showForgotModal = false"
                        class="px-4 py-2 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-xs font-bold text-white cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
