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
<body class="min-h-full bg-[#0B1726] text-white antialiased selection:bg-[#3187D8] selection:text-white"
      style="font-family: 'Plus Jakarta Sans', sans-serif;">

<div class="fixed inset-0 pointer-events-none" aria-hidden="true">
    <div class="absolute inset-0 bg-[linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)] bg-[size:4rem_4rem] opacity-20"></div>
    <div class="absolute top-0 left-1/4 w-96 h-96 bg-[#173957]/30 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-[#DB4545]/10 rounded-full blur-3xl"></div>
</div>

<div class="relative min-h-screen flex flex-col justify-between">
    {{-- Top Public Header --}}
    <header class="w-full border-b border-slate-800/80 bg-[#0B1726]/80 backdrop-blur-md sticky top-0 z-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="/images/branding/alerto-logo-web-128.png" alt="ALERTO APP" class="w-11 h-11 rounded-2xl object-contain flex-shrink-0 drop-shadow-md">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xl font-black tracking-tight text-white">ALERTO</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-300 bg-emerald-950/80 px-2 py-0.5 rounded-sm border border-emerald-500/30">Citizen Portal</span>
                    </div>
                    <span class="block text-[11px] font-medium text-slate-400">Municipality of Hinoba-an · LDRRMO</span>
                </div>
            </div>

            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-900 border border-slate-700/60 text-xs font-semibold text-slate-300">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="hidden sm:inline">DIAL DAPH 24/7 Response Active</span>
                <span class="sm:hidden">24/7 Active</span>
            </div>
        </div>
    </header>

    {{-- Main Content: Two Columns on Desktop (Sign In & Emergency Access) --}}
    <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 py-8 sm:py-12">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            {{-- Column 1: Citizen Login & Registration Form (7 cols) --}}
            <div class="lg:col-span-7 space-y-6" x-data="{ showPassword: false, submitting: false, showForgotModal: false }">
                <div class="bg-slate-900/90 border border-slate-800 rounded-[24px] p-6 sm:p-8 shadow-2xl backdrop-blur-md">
                    <div class="mb-6">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#3187D8] bg-[#3187D8]/10 px-2.5 py-1 rounded-full border border-[#3187D8]/20">
                            Citizen Sign In
                        </span>
                        <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight mt-3">
                            Access Your Citizen Account
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-400 mt-1">
                            Sign in with your registered mobile phone number or email to view your emergency records and follow up on incidents.
                        </p>
                    </div>

                    {{-- Flash Errors --}}
                    @if(session('error'))
                        <div class="mb-5 p-4 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs font-medium flex items-center gap-3">
                            <svg class="w-4 h-4 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-5 p-4 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs font-medium space-y-1">
                            @foreach($errors->all() as $err)
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400 shrink-0"></span>
                                    <span>{{ $err }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('citizen.login.post') }}" method="POST" @submit="submitting = true" class="space-y-4">
                        @csrf

                        {{-- Mobile Number or Email --}}
                        <div>
                            <label for="citizen_login" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                                Mobile Number or Email Address *
                            </label>
                            <input type="text" name="login" id="citizen_login" value="{{ old('login') }}" required autofocus
                                   placeholder="e.g., 09171234567 or citizen@example.com"
                                   class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-700 text-white placeholder-slate-500 text-sm focus:border-[#3187D8] focus:ring-1 focus:ring-[#3187D8] focus:outline-none transition-all">
                        </div>

                        {{-- Password --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="citizen_password" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                    Password *
                                </label>
                                <button type="button" @click="showForgotModal = true"
                                        class="text-xs font-semibold text-[#3187D8] hover:text-blue-300 transition-colors cursor-pointer">
                                    Forgot password?
                                </button>
                            </div>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'" name="password" id="citizen_password" required
                                       placeholder="Enter your account password"
                                       class="w-full px-4 py-3 pr-11 rounded-xl bg-slate-950 border border-slate-700 text-white placeholder-slate-500 text-sm focus:border-[#3187D8] focus:ring-1 focus:ring-[#3187D8] focus:outline-none transition-all">
                                <button type="button" @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-white transition-colors cursor-pointer"
                                        tabindex="-1" title="Toggle password visibility">
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

                        {{-- Remember Me --}}
                        <div class="flex items-center justify-between text-xs pt-1">
                            <label class="flex items-center gap-2 text-slate-300 cursor-pointer select-none">
                                <input type="checkbox" name="remember" value="1" class="rounded bg-slate-950 border-slate-700 text-[#3187D8] focus:ring-[#3187D8]">
                                <span>Keep me signed in on this device</span>
                            </label>
                        </div>

                        {{-- Submit Button --}}
                        <button type="submit" :disabled="submitting"
                                class="w-full py-4 rounded-xl bg-[#173957] hover:bg-[#1f4b72] disabled:opacity-50 text-white font-extrabold text-sm shadow-lg shadow-[#173957]/30 border border-[#3187D8]/30 transition-all flex items-center justify-center gap-2 cursor-pointer mt-2">
                            <svg x-show="submitting" style="display:none;" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="submitting ? 'Authenticating...' : 'Sign In to Citizen Portal'"></span>
                        </button>
                    </form>

                    {{-- Registration Link --}}
                    <div class="mt-6 pt-6 border-t border-slate-800 text-center">
                        <p class="text-xs text-slate-400">
                            Don't have an ALERTO Citizen Account?
                        </p>
                        <a href="{{ route('citizen.register') }}"
                           class="inline-flex items-center gap-1.5 mt-2 px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold transition-all border border-slate-700">
                            <span>Register as a New Citizen</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>

                {{-- Forgot Password Info Modal --}}
                <div x-show="showForgotModal" style="display:none;" x-cloak
                     class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4">
                    <div class="w-full max-w-md bg-slate-900 border border-slate-700 rounded-2xl p-6 shadow-2xl text-white space-y-4"
                         @click.away="showForgotModal = false">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-white">Password Recovery</h3>
                            <button type="button" @click="showForgotModal = false" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            For security and identity protection in the municipality, citizen password resets can be requested by calling the <strong>DIAL DAPH Command Center</strong> or visiting the LDRRMO Headquarters with your valid identification.
                        </p>
                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-emerald-400">
                            Hotline: 0917-DIAL-DAPH / (034) Hinoba-an LDRRMO
                        </div>
                        <div class="flex justify-end">
                            <button type="button" @click="showForgotModal = false"
                                    class="px-4 py-2 rounded-xl bg-[#173957] hover:bg-[#1f4b72] text-xs font-bold text-white cursor-pointer">
                                Got It
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Column 2: Fast Public Emergency Access (5 cols) --}}
            <div class="lg:col-span-5 space-y-6">

                {{-- Fast Emergency Report & SOS Card (No Login Required) --}}
                <div class="rounded-[24px] border border-rose-500/40 bg-slate-900/90 p-6 sm:p-7 shadow-2xl space-y-5">
                    <div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-500/15 border border-rose-400/30 text-rose-300 text-xs font-bold uppercase tracking-wider mb-2">
                            Emergency? Act Now
                        </span>
                        <h2 class="text-xl sm:text-2xl font-black text-white">
                            No Login Required for Urgent Emergencies
                        </h2>
                        <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                            You do not need to log in to report an emergency. Submit an immediate guest report or press SOS — DIAL DAPH Command Center will call and dispatch responders immediately.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3">
                        <a href="{{ route('guest.report') }}"
                           class="w-full py-3.5 px-4 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-sm text-center shadow-lg shadow-emerald-500/20 transition-all cursor-pointer">
                            Submit Emergency Report (No Login)
                        </a>

                        <a href="{{ route('guest.sos') }}"
                           class="w-full py-3.5 px-4 rounded-xl bg-[#DB4545] hover:bg-red-500 text-white font-black text-sm text-center shadow-lg shadow-red-600/30 border border-red-400/40 transition-all cursor-pointer">
                            TRIGGER EMERGENCY SOS
                        </a>
                    </div>
                </div>

                {{-- Dual Hotlines Section --}}
                <div class="rounded-[24px] bg-slate-900/90 border border-slate-800 p-6 shadow-xl space-y-3">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-slate-300 block">
                        Direct Emergency Call Lines
                    </span>
                    <p class="text-xs text-slate-400">
                        Tap either hotline below to connect immediately via direct voice call:
                    </p>
                    <x-dual-hotlines :hotlines="$hotlines" />
                </div>
            </div>
        </div>
    </main>

    {{-- Simple, Clean Public Footer --}}
    <footer class="w-full border-t border-slate-800/80 bg-[#0B1726]/80 py-5 px-4 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} ALERTO APP · Municipality of Hinoba-an LDRRMO · DIAL DAPH Command Center
    </footer>
</div>

</body>
</html>
