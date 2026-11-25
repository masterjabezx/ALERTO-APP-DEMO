@extends('layouts.auth')

@section('title', 'DIAL DAPH Command Center Login — ALERTO APP')

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center bg-slate-950 px-4 py-10"
     style="font-family: 'Plus Jakarta Sans', sans-serif;"
     x-data="{ submitting: false, showPass: false }">

    {{-- Background --}}
    <div class="fixed inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)] bg-[size:4rem_4rem] opacity-20"></div>
        <div class="absolute top-0 right-1/3 w-80 h-80 bg-rose-500/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-1/3 w-80 h-80 bg-red-500/5 rounded-full blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-md">

        {{-- Back --}}
        <a href="{{ route('home') }}"
           class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-300 mb-8 transition-colors group">
            <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to ALERTO Homepage
        </a>

        {{-- Card --}}
        <div class="bg-slate-900/90 border border-rose-900/30 rounded-2xl p-8 shadow-2xl shadow-rose-950/50 backdrop-blur-sm">

            {{-- Security Banner --}}
            <div class="flex items-center gap-2 mb-6 p-3 rounded-xl bg-rose-950/40 border border-rose-900/40">
                <svg class="w-4 h-4 text-rose-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <p class="text-xs text-rose-400 font-medium">Restricted Access — DIAL DAPH Command Center Operators Only</p>
            </div>

            {{-- Header --}}
            <div class="text-center mb-8">
                <img src="/images/branding/alerto-logo-web-256.png" alt="ALERTO APP" class="w-16 h-16 rounded-2xl object-contain mx-auto mb-4 drop-shadow-lg">
                <h1 class="text-2xl font-bold text-white">DIAL DAPH Command Center</h1>
                <p class="mt-1 text-sm text-slate-400">Emergency Receiving, Call Verification & Dispatch Portal</p>
                <div class="mt-3 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-500/10 border border-rose-500/20 text-xs font-medium text-rose-400">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                    </svg>
                    Provisioned by LDRRMO Super Admin
                </div>
            </div>

            {{-- Flash Messages --}}
            @if(session('error'))
                <div class="mb-5 flex items-start gap-3 p-3.5 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
                    <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 flex items-start gap-3 p-3.5 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
                    <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Login Form --}}
            <form method="POST" action="{{ route('admin.login') }}" x-on:submit="submitting = true">
                @csrf

                {{-- Login field --}}
                <div class="mb-5">
                    <label for="login" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Admin Email or Phone Number
                    </label>
                    <input
                        id="login"
                        name="login"
                        type="text"
                        value="{{ old('login') }}"
                        autocomplete="username"
                        required
                        placeholder="operator@hinobaan.gov.ph"
                        class="w-full px-4 py-3 rounded-xl bg-slate-800/70 border @error('login') border-red-500/60 @else border-rose-900/40 @enderror text-white placeholder-slate-600 text-sm focus:outline-none focus:border-rose-500/60 focus:ring-2 focus:ring-rose-500/20 transition-all"
                    />
                </div>

                {{-- Password --}}
                <div class="mb-5">
                    <label for="password" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Password
                    </label>
                    <div class="relative">
                        <input
                            id="password"
                            name="password"
                            :type="showPassword ? 'text' : 'password'"
                            autocomplete="current-password"
                            required
                            placeholder="••••••••"
                            class="w-full px-4 py-3 pr-12 rounded-xl bg-slate-800/70 border @error('password') border-red-500/60 @else border-rose-900/40 @enderror text-white placeholder-slate-600 text-sm focus:outline-none focus:border-rose-500/60 focus:ring-2 focus:ring-rose-500/20 transition-all"
                        />
                        <button type="button"
                                x-on:click="showPassword = !showPassword"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors p-1"
                                tabindex="-1">
                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Remember + Forgot --}}
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-600 bg-slate-800 text-rose-500 focus:ring-rose-500/30 cursor-pointer">
                        <span class="text-sm text-slate-400 group-hover:text-slate-300 transition-colors">Remember me</span>
                    </label>
                    <a href="#" class="text-sm text-rose-400 hover:text-rose-300 transition-colors font-medium">
                        Forgot password?
                    </a>
                </div>

                {{-- Submit --}}
                <button type="submit"
                        :disabled="submitting"
                        class="w-full flex items-center justify-center gap-2 py-3 px-6 rounded-xl font-semibold text-sm transition-all duration-200
                               bg-rose-700 hover:bg-rose-600 text-white shadow-lg shadow-rose-900/40
                               disabled:opacity-70 disabled:cursor-not-allowed">
                    <svg x-show="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="submitting ? 'Verifying Credentials...' : 'Access Control Center'"></span>
                </button>
            </form>

            {{-- Security footer --}}
            <div class="mt-6 pt-5 border-t border-slate-800">
                <p class="text-center text-xs text-slate-600">
                    All admin access is logged and audited.<br>
                    Unauthorized access attempts are recorded.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    function adminLoginForm() {
        return {
            showPassword: false,
            submitting: false,
        };
    }
</script>
@endsection

