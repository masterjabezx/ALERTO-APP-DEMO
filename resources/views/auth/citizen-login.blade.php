@extends('layouts.auth', ['bodyClass' => 'bg-[#0B1726] text-slate-100 flex items-center justify-center min-h-screen p-4'])

@section('title', 'Citizen Portal Login — ALERTO APP')

@section('content')
<div class="w-full max-w-md my-8" x-data="{ showPassword: false, loading: false, showForgotModal: false }">
    {{-- Top Back link & Portal Tag --}}
    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('home') }}" class="inline-flex items-center text-xs font-semibold text-slate-400 hover:text-white transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to ALERTO Homepage
        </a>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
            Citizen Portal
        </span>
    </div>

    {{-- Citizen Login Card --}}
    <div class="bg-slate-900/90 border border-slate-800 rounded-[24px] p-6 sm:p-8 shadow-2xl backdrop-blur-xl">
        <div class="text-center mb-6">
            <img src="/images/branding/alerto-logo-web-256.png" alt="ALERTO APP" class="w-16 h-16 rounded-2xl object-contain mx-auto mb-4 drop-shadow-lg">
            <h1 class="text-2xl font-black text-white tracking-tight">Citizen Sign In</h1>
            <p class="text-xs text-slate-400 mt-1">Municipality of Hinoba-an Emergency System</p>
        </div>

        {{-- Flash Error --}}
        @if(session('error'))
            <div class="mb-4 p-3 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs font-medium">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-3 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs font-medium space-y-1">
                @foreach($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('citizen.login.post') }}" method="POST" @submit="loading = true" class="space-y-4">
            @csrf

            {{-- Login Identifier (Mobile Phone or Email) --}}
            <div>
                <label for="login" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                    Mobile Number or Email *
                </label>
                <div class="relative">
                    <input type="text" name="login" id="login" value="{{ old('login') }}" required autofocus
                           placeholder="e.g. 09171234567 or citizen@example.com"
                           class="w-full px-4 py-3 bg-slate-950 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-[#3187D8] focus:ring-1 focus:ring-[#3187D8] transition-all text-sm">
                </div>
            </div>

            {{-- Password Input with Show/Hide --}}
            <div>
                <div class="flex justify-between items-center mb-1.5">
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-300">Password *</label>
                    <button type="button" @click="showForgotModal = true" class="text-xs font-semibold text-[#3187D8] hover:text-blue-300 transition-colors cursor-pointer">
                        Forgot password?
                    </button>
                </div>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'" name="password" id="password" required placeholder="Enter password"
                           class="w-full px-4 py-3 pr-11 bg-slate-950 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-[#3187D8] focus:ring-1 focus:ring-[#3187D8] transition-all text-sm">
                    <button type="button" @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white focus:outline-none cursor-pointer"
                            tabindex="-1">
                        <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        <svg x-show="showPassword" style="display:none;" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                    </button>
                </div>
            </div>

            {{-- Remember Me --}}
            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center space-x-2 text-slate-300 cursor-pointer select-none">
                    <input type="checkbox" name="remember" value="1" class="rounded bg-slate-950 border-slate-700 text-[#3187D8] focus:ring-[#3187D8]">
                    <span>Keep me signed in</span>
                </label>
            </div>

            {{-- Submit Button --}}
            <button type="submit" :disabled="loading"
                    class="w-full py-3.5 px-4 bg-[#173957] hover:bg-[#1f4b72] text-white font-black rounded-xl shadow-lg shadow-[#173957]/30 border border-[#3187D8]/30 transition-all flex items-center justify-center space-x-2 disabled:opacity-50 cursor-pointer">
                <span x-show="!loading">Sign In to Citizen Portal</span>
                <span x-show="loading" style="display: none;" class="flex items-center space-x-2">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <span>Authenticating...</span>
                </span>
            </button>
        </form>

        {{-- Citizen Register Link --}}
        <div class="mt-6 pt-5 border-t border-slate-800 text-center text-xs text-slate-400">
            Don't have an ALERTO citizen account?
            <a href="{{ route('citizen.register') }}" class="font-bold text-emerald-400 hover:text-emerald-300 ml-1 transition-colors">
                Register as a Citizen &rarr;
            </a>
        </div>
    </div>

    {{-- Forgot Password Modal --}}
    <div x-show="showForgotModal" style="display:none;" x-cloak
         class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="w-full max-w-sm bg-slate-900 border border-slate-700 rounded-2xl p-6 shadow-2xl text-white space-y-4"
             @click.away="showForgotModal = false">
            <h3 class="text-sm font-bold text-white">Citizen Password Assistance</h3>
            <p class="text-xs text-slate-300 leading-relaxed">
                If you lost access to your registered account, please call the <strong>DIAL DAPH Command Center</strong> or visit the Hinoba-an LDRRMO office for credential verification.
            </p>
            <div class="flex justify-end">
                <button type="button" @click="showForgotModal = false"
                        class="px-4 py-2 rounded-xl bg-[#173957] hover:bg-[#1f4b72] text-xs font-bold text-white cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
