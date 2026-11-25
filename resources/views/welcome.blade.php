<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="ALERTO APP — Municipality of Hinoba-an Citizen Emergency Reporting & Response Platform">
    <title>Citizen Portal — ALERTO Hinoba-an</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <x-pwa-meta />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-[#101214] text-[#F5F3EF] antialiased selection:bg-[#E87524] selection:text-white"
      style="font-family: 'Plus Jakarta Sans', sans-serif;">

<div class="fixed inset-0 pointer-events-none" aria-hidden="true">
    <div class="absolute inset-0 alerto-grid-canvas opacity-35"></div>
    <div class="absolute top-0 left-1/4 w-96 h-96 bg-[#E87524]/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-[#C83F43]/10 rounded-full blur-3xl"></div>
</div>

<div class="relative min-h-screen flex flex-col justify-between">
    {{-- Top Public Header (Strictly Citizen-Only Gateway) --}}
    <header class="w-full border-b border-white/10 bg-[#17191C]/90 backdrop-blur-md sticky top-0 z-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <img src="/images/branding/alerto-logo-web-128.png" alt="ALERTO APP" class="w-11 h-11 rounded-2xl object-contain shrink-0 drop-shadow-md">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xl font-black tracking-tight text-white">ALERTO</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#F29A56] bg-[#E87524]/15 px-2 py-0.5 rounded-sm border border-[#E87524]/35">Citizen Portal</span>
                    </div>
                    <span class="block text-[11px] font-medium text-[#9D9993]">Municipality of Hinoba-an · LDRRMO</span>
                </div>
            </div>

            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#292D32] border border-white/10 text-xs font-semibold text-[#D4D0C9]">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#27845B] opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-[#27845B]"></span>
                </span>
                <span class="hidden sm:inline">DIAL DAPH 24/7 Response Active</span>
                <span class="sm:hidden">24/7 Active</span>
            </div>
        </div>
    </header>

    {{-- Main Content: Citizen Sign In & Emergency Access --}}
    <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 py-8 sm:py-10 flex items-center">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center w-full">

            {{-- Column 1: Citizen Sign In Form (7 cols) --}}
            <div class="lg:col-span-7" x-data="{ showPassword: false, submitting: false, showForgotModal: false }">
                <div class="bg-[#17191C] border border-white/10 rounded-[24px] p-6 sm:p-8 shadow-2xl">
                    <div class="mb-5">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#F29A56] bg-[#E87524]/15 px-2.5 py-1 rounded-full border border-[#E87524]/35">
                            Citizen Sign In
                        </span>
                        <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight mt-2.5">
                            Sign in to your account
                        </h1>
                        <p class="text-xs text-[#9D9993] mt-1">
                            Use your registered mobile phone number or email to continue.
                        </p>
                    </div>

                    @if(session('error'))
                        <div class="mb-4 p-3 rounded-xl bg-[#C83F43]/15 border border-[#C83F43]/35 text-[#FDA4AF] text-xs font-medium flex items-center gap-3">
                            <svg class="w-4 h-4 text-[#C83F43] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-4 p-3 rounded-xl bg-[#C83F43]/15 border border-[#C83F43]/35 text-[#FDA4AF] text-xs font-medium space-y-1">
                            @foreach($errors->all() as $err)
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#C83F43] shrink-0"></span>
                                    <span>{{ $err }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('citizen.login.post') }}" method="POST" @submit="submitting = true" class="space-y-4">
                        @csrf

                        <div>
                            <label for="citizen_login" class="block text-xs font-bold uppercase tracking-wider text-[#D4D0C9] mb-1.5">
                                Phone number or email *
                            </label>
                            <input type="text" name="login" id="citizen_login" value="{{ old('login') }}" required autofocus
                                   placeholder="09xx xxx xxxx or name@email.com"
                                   class="w-full min-h-[46px] px-4 py-2.5 rounded-xl bg-[#101214] border border-white/15 text-white placeholder-[#77736E] text-sm focus:border-[#E87524] focus:ring-2 focus:ring-[#E87524]/25 focus:outline-none transition-all">
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="citizen_password" class="block text-xs font-bold uppercase tracking-wider text-[#D4D0C9]">
                                    Password *
                                </label>
                                <button type="button" @click="showForgotModal = true"
                                        class="text-xs font-bold text-[#F29A56] hover:text-[#E87524] transition-colors cursor-pointer">
                                    Forgot password?
                                </button>
                            </div>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'" name="password" id="citizen_password" required
                                       placeholder="Enter your password"
                                       class="w-full min-h-[46px] px-4 py-2.5 pr-11 rounded-xl bg-[#101214] border border-white/15 text-white placeholder-[#77736E] text-sm focus:border-[#E87524] focus:ring-2 focus:ring-[#E87524]/25 focus:outline-none transition-all">
                                <button type="button" @click="showPassword = !showPassword"
                                        aria-label="Toggle password visibility"
                                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[#9D9993] hover:text-white transition-colors cursor-pointer">
                                    <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="showPassword" style="display:none;" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-xs">
                            <label class="flex items-center gap-2 text-[#D4D0C9] cursor-pointer select-none">
                                <input type="checkbox" name="remember" value="1" class="rounded bg-[#101214] border-white/20 text-[#E87524] focus:ring-[#E87524]">
                                <span>Remember me</span>
                            </label>
                        </div>

                        <button type="submit" :disabled="submitting"
                                class="w-full min-h-[48px] py-3 rounded-xl bg-[#E87524] hover:bg-[#B94F12] disabled:opacity-50 text-white font-black text-sm shadow-lg shadow-[#E87524]/25 transition-all flex items-center justify-center gap-2 cursor-pointer">
                            <svg x-show="submitting" style="display:none;" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="submitting ? 'Signing in...' : 'Sign in'"></span>
                        </button>
                    </form>

                    <div class="mt-5 pt-4 border-t border-white/10 text-center text-xs text-[#9D9993]">
                        New to ALERTO?
                        <a href="{{ route('citizen.register') }}"
                           class="inline-flex items-center gap-1 px-2 py-1 text-[#F29A56] hover:text-[#E87524] font-bold transition-all">
                            <span>Create an account</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>

                {{-- Forgot Password Info Modal --}}
                <div x-show="showForgotModal" style="display:none;" x-cloak
                     class="fixed inset-0 z-50 bg-[#101214]/80 backdrop-blur-xs flex items-center justify-center p-4">
                    <div class="w-full max-w-md bg-[#17191C] border border-white/15 rounded-2xl p-6 shadow-2xl text-white space-y-4"
                         @click.away="showForgotModal = false">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-white">Password Recovery</h3>
                            <button type="button" @click="showForgotModal = false" class="text-[#9D9993] hover:text-white text-lg font-bold cursor-pointer">&times;</button>
                        </div>
                        <p class="text-xs text-[#D4D0C9] leading-relaxed">
                            For security and identity protection in the municipality, citizen password resets can be requested by calling the <strong>DIAL DAPH Command Center</strong> or visiting the LDRRMO Headquarters with valid identification.
                        </p>
                        <div class="p-3 rounded-xl bg-[#101214] border border-white/10 text-xs font-mono text-[#F29A56]">
                            Hotline: 0917-DIAL-DAPH / (034) Hinoba-an LDRRMO
                        </div>
                        <div class="flex justify-end">
                            <button type="button" @click="showForgotModal = false"
                                    class="px-4 py-2 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-xs font-bold text-white cursor-pointer">
                                Got It
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Column 2: Immediate Public Emergency Access (5 cols) --}}
            <div class="lg:col-span-5 space-y-4">
                <div class="rounded-[24px] border border-[#C83F43]/45 bg-[#17191C] p-6 shadow-2xl space-y-3.5">
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="text-lg font-black text-white">Need help now?</h2>
                        <span class="text-[10px] font-bold uppercase tracking-wide text-[#FDA4AF] bg-[#C83F43]/15 border border-[#C83F43]/35 px-2.5 py-1 rounded-full whitespace-nowrap">No login needed</span>
                    </div>
                    <p class="text-xs text-[#D4D0C9]">
                        Submit your first emergency report immediately or transmit a life-threatening distress SOS.
                    </p>

                    <div class="grid grid-cols-1 gap-2.5">
                        <a href="{{ route('guest.report') }}"
                           class="w-full min-h-[46px] px-4 py-3 rounded-xl bg-[#27845B] hover:bg-[#1e6a49] text-white font-extrabold text-xs sm:text-sm text-center shadow-lg transition-all cursor-pointer flex items-center justify-center">
                            Submit Emergency Report (No Login)
                        </a>

                        <a href="{{ route('guest.sos') }}"
                           class="w-full min-h-[46px] px-4 py-3 rounded-xl bg-[#C83F43] hover:bg-[#a93034] text-white font-black text-xs sm:text-sm text-center shadow-lg shadow-[#C83F43]/30 border border-white/15 transition-all cursor-pointer flex items-center justify-center">
                            TRIGGER EMERGENCY SOS
                        </a>
                    </div>
                </div>

                <div class="rounded-[24px] bg-[#17191C] border border-white/10 p-4 sm:p-5 shadow-xl space-y-2.5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-extrabold text-[#F5F3EF]">Emergency Hotlines</span>
                        <span class="text-[10px] text-[#9D9993]">Tap to call</span>
                    </div>
                    <x-dual-hotlines :hotlines="$hotlines" compact />
                </div>
            </div>
        </div>
    </main>

    <footer class="hidden sm:block w-full border-t border-white/10 bg-[#17191C]/90 py-3 px-4 text-center text-[10px] text-[#9D9993]">
        &copy; {{ date('Y') }} ALERTO APP · Municipality of Hinoba-an LDRRMO · DIAL DAPH Command Center
    </footer>
</div>

</body>
</html>
