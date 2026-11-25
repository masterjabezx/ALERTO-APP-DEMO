@props([
    'variant' => 'personnel', // personnel, office, admin, super_admin
    'portalTitle',
    'subtitle',
    'welcomeHeading' => 'Sign in to continue',
    'welcomeDescription' => 'Enter your verified municipal credentials.',
    'action',
    'loginLabel' => 'Email or Phone Number',
    'loginPlaceholder' => 'email@hinobaan.gov.ph or 09XXXXXXXXX',
    'submitLabel' => 'Sign In',
    'submittingLabel' => 'Authenticating...',
    'accessNotice' => 'Authorized municipal access only.',
    'securityBanner' => null,
    'showForgot' => true,
])

@php
    $isLightRight = ($variant === 'office');
    $leftConfig = match($variant) {
        'office' => [
            'bg' => 'bg-[#17191C] text-[#F5F3EF]',
            'pill' => 'bg-[#E87524]/15 border-[#E87524]/40 text-[#F29A56]',
            'accentText' => 'text-[#F29A56]',
            'rightBg' => 'bg-[#F5F3EF] text-[#17191C]',
            'cardBg' => 'bg-white border-[#E6E3DE] text-[#17191C] shadow-[0_14px_40px_-10px_rgba(16,18,20,0.12)]',
            'labelClass' => 'text-[#454A50]',
            'inputClass' => 'bg-[#FAF8F5] border-[#E6E3DE] text-[#17191C] placeholder-[#77736E]/60 focus:border-[#E87524] focus:ring-[#E87524]/20',
            'mutedText' => 'text-[#77736E]',
            'linkClass' => 'text-[#B94F12] hover:text-[#E87524]',
            'btnClass' => 'bg-[#E87524] hover:bg-[#B94F12] text-white shadow-[#E87524]/25',
            'backClass' => 'text-[#77736E] hover:text-[#17191C]',
        ],
        'admin' => [
            'bg' => 'bg-[#101214] text-[#F5F3EF]',
            'pill' => 'bg-[#C83F43]/15 border-[#C83F43]/45 text-[#FDA4AF]',
            'accentText' => 'text-[#E87524]',
            'rightBg' => 'bg-[#17191C] text-[#F5F3EF]',
            'cardBg' => 'bg-[#292D32]/90 border-white/10 text-[#F5F3EF] shadow-[0_24px_48px_-12px_rgba(0,0,0,0.55)]',
            'labelClass' => 'text-[#D4D0C9]',
            'inputClass' => 'bg-[#101214] border-white/15 text-[#F5F3EF] placeholder-[#77736E] focus:border-[#E87524] focus:ring-[#E87524]/25',
            'mutedText' => 'text-[#9D9993]',
            'linkClass' => 'text-[#F29A56] hover:text-[#E87524]',
            'btnClass' => 'bg-[#E87524] hover:bg-[#B94F12] text-white shadow-[#E87524]/25',
            'backClass' => 'text-[#9D9993] hover:text-white',
        ],
        'super_admin' => [
            'bg' => 'bg-[#101214] text-[#F5F3EF]',
            'pill' => 'bg-[#E87524]/18 border-[#E87524]/45 text-[#F29A56]',
            'accentText' => 'text-[#F29A56]',
            'rightBg' => 'bg-[#17191C] text-[#F5F3EF]',
            'cardBg' => 'bg-[#292D32]/85 border-[#E87524]/25 text-[#F5F3EF] shadow-[0_24px_48px_-12px_rgba(0,0,0,0.55)]',
            'labelClass' => 'text-[#D4D0C9]',
            'inputClass' => 'bg-[#101214] border-white/15 text-[#F5F3EF] placeholder-[#77736E] focus:border-[#E87524] focus:ring-[#E87524]/25',
            'mutedText' => 'text-[#9D9993]',
            'linkClass' => 'text-[#F29A56] hover:text-[#E87524]',
            'btnClass' => 'bg-[#E87524] hover:bg-[#B94F12] text-white shadow-[#E87524]/25',
            'backClass' => 'text-[#9D9993] hover:text-white',
        ],
        default => [
            'bg' => 'bg-[#17191C] text-[#F5F3EF]',
            'pill' => 'bg-[#E87524]/15 border-[#E87524]/40 text-[#F29A56]',
            'accentText' => 'text-[#F29A56]',
            'rightBg' => 'bg-[#101214] text-[#F5F3EF]',
            'cardBg' => 'bg-[#17191C] border-white/10 text-[#F5F3EF] shadow-[0_24px_48px_-12px_rgba(0,0,0,0.55)]',
            'labelClass' => 'text-[#D4D0C9]',
            'inputClass' => 'bg-[#292D32] border-white/15 text-[#F5F3EF] placeholder-[#9D9993] focus:border-[#E87524] focus:ring-[#E87524]/25',
            'mutedText' => 'text-[#9D9993]',
            'linkClass' => 'text-[#F29A56] hover:text-[#E87524]',
            'btnClass' => 'bg-[#E87524] hover:bg-[#B94F12] text-white shadow-[#E87524]/25',
            'backClass' => 'text-[#9D9993] hover:text-white',
        ],
    };
@endphp

<div class="min-h-screen grid grid-cols-1 lg:grid-cols-12"
     x-data="{ showPassword: false, submitting: false, showForgotInfo: false }">

    {{-- LEFT PANEL: Hinoba-an Municipal Branding --}}
    <div class="lg:col-span-6 relative overflow-hidden flex flex-col justify-between p-8 sm:p-12 lg:p-16 border-b lg:border-b-0 lg:border-r border-white/10 {{ $leftConfig['bg'] }}">
        {{-- Subtle Animated Background Canvas & Grid --}}
        <canvas id="alerto-ambient-particles" class="absolute inset-0 w-full h-full pointer-events-none opacity-65" aria-hidden="true"></canvas>
        <div class="absolute inset-0 alerto-grid-canvas opacity-35 pointer-events-none" aria-hidden="true"></div>
        <div class="absolute -bottom-32 -left-32 w-96 h-96 rounded-full bg-[#E87524]/10 blur-3xl pointer-events-none" aria-hidden="true"></div>
        @if($variant === 'admin')
            <div class="absolute top-12 right-12 w-72 h-72 rounded-full bg-[#C83F43]/10 blur-3xl pointer-events-none" aria-hidden="true"></div>
        @endif

        {{-- Top Official Brand Lockup --}}
        <div class="relative z-10 flex items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3.5 group">
                <img src="/images/branding/alerto-logo-web-256.png" alt="ALERTO APP" class="w-12 h-12 rounded-2xl object-contain shrink-0 drop-shadow-lg">
                <div>
                    <span class="block text-lg font-black tracking-tight text-white">ALERTO APP</span>
                    <span class="block text-[11px] font-semibold text-[#D4D0C9]">Municipality of Hinoba-an · LDRRMO</span>
                </div>
            </a>

            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border text-[10px] font-mono font-bold uppercase tracking-wider {{ $leftConfig['pill'] }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $variant === 'admin' ? 'bg-[#C83F43]' : 'bg-[#E87524]' }}"></span>
                Operational Access
            </span>
        </div>

        {{-- Center Portal Identity --}}
        <div class="relative z-10 my-10 lg:my-0 space-y-5 max-w-xl" x-animate-entrance>
            <div class="inline-flex items-center gap-2 text-xs font-mono font-bold uppercase tracking-widest {{ $leftConfig['accentText'] }}">
                <i data-lucide="shield-check" class="w-4 h-4"></i>
                <span>Municipality of Hinoba-an Emergency Platform</span>
            </div>

            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight text-white leading-[1.08]">
                {{ $portalTitle }}
            </h1>

            <p class="text-base sm:text-lg font-medium text-[#D4D0C9] leading-relaxed">
                {{ $subtitle }}
            </p>

            {{-- Subtle Location & Emergency Vector Graphic --}}
            <div class="pt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                <div class="p-3.5 rounded-2xl bg-white/5 border border-white/10 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#E87524]/15 border border-[#E87524]/30 text-[#F29A56] flex items-center justify-center shrink-0">
                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <span class="block font-bold text-white">9°35'37.0"N 122°27'15.1"E</span>
                        <span class="text-[11px] text-[#9D9993]">Hinoba-an Municipal Grid</span>
                    </div>
                </div>
                <div class="p-3.5 rounded-2xl bg-white/5 border border-white/10 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#27845B]/15 border border-[#27845B]/35 text-[#6AE0B0] flex items-center justify-center shrink-0">
                        <i data-lucide="radio" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <span class="block font-bold text-white">24/7 Operations Ready</span>
                        <span class="text-[11px] text-[#9D9993]">Verified Telemetry &amp; Dispatch</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom Footer Identity --}}
        <div class="relative z-10 flex flex-wrap items-center justify-between gap-3 text-[11px] text-[#9D9993] pt-6 border-t border-white/10">
            <span>Hinoba-an Local Disaster Risk Reduction &amp; Management Office</span>
            <span class="font-mono uppercase tracking-wider text-[#F29A56]">ALERTO EOC v2</span>
        </div>
    </div>

    {{-- RIGHT PANEL: Authentication Form --}}
    <div class="lg:col-span-6 flex flex-col justify-center items-center p-6 sm:p-10 lg:p-14 {{ $leftConfig['rightBg'] }}">
        <div class="w-full max-w-md space-y-6">
            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-xs font-bold transition-colors {{ $leftConfig['backClass'] }}">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Back to ALERTO Homepage</span>
                </a>
                <span class="text-[11px] font-mono uppercase tracking-wider {{ $leftConfig['mutedText'] }}">HTTPS Session</span>
            </div>

            <div class="rounded-[24px] border p-7 sm:p-9 {{ $leftConfig['cardBg'] }}">
                @if($securityBanner)
                    <div class="mb-6 flex items-center gap-2.5 p-3 rounded-xl bg-[#C83F43]/12 border border-[#C83F43]/35 text-xs font-semibold text-[#FDA4AF]">
                        <i data-lucide="shield-alert" class="w-4 h-4 text-[#C83F43] shrink-0"></i>
                        <span>{{ $securityBanner }}</span>
                    </div>
                @endif

                <div class="mb-6">
                    <h2 class="text-xl sm:text-2xl font-black tracking-tight">{{ $welcomeHeading }}</h2>
                    <p class="text-xs mt-1 {{ $leftConfig['mutedText'] }}">{{ $welcomeDescription }}</p>
                </div>

                {{-- Session / Validation Errors --}}
                @if(session('error'))
                    <div role="alert" class="mb-5 flex items-start gap-2.5 p-3.5 rounded-xl bg-[#C83F43]/12 border border-[#C83F43]/35 text-[#C83F43] text-xs font-semibold">
                        <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 mt-0.5"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div role="alert" class="mb-5 flex items-start gap-2.5 p-3.5 rounded-xl bg-[#C83F43]/12 border border-[#C83F43]/35 text-[#C83F43] text-xs font-semibold">
                        <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 mt-0.5"></i>
                        <div class="space-y-0.5">
                            @foreach($errors->all() as $err)
                                <p>{{ $err }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ $action }}" x-on:submit="submitting = true" class="space-y-5">
                    @csrf

                    <div>
                        <label for="login" class="block text-xs font-bold uppercase tracking-wider mb-1.5 {{ $leftConfig['labelClass'] }}">
                            {{ $loginLabel }}
                        </label>
                        <input
                            id="login"
                            name="login"
                            type="text"
                            value="{{ old('login') }}"
                            autocomplete="username"
                            required
                            autofocus
                            placeholder="{{ $loginPlaceholder }}"
                            class="w-full min-h-[46px] px-4 py-2.5 rounded-xl border text-sm transition-all focus:outline-none focus:ring-2 {{ $leftConfig['inputClass'] }} @error('login') border-[#C83F43] @enderror"
                        />
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider mb-1.5 {{ $leftConfig['labelClass'] }}">
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
                                class="w-full min-h-[46px] px-4 py-2.5 pr-12 rounded-xl border text-sm transition-all focus:outline-none focus:ring-2 {{ $leftConfig['inputClass'] }} @error('password') border-[#C83F43] @enderror"
                            />
                            <button type="button"
                                    x-on:click="showPassword = !showPassword"
                                    aria-label="Toggle password visibility"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 rounded-lg {{ $leftConfig['mutedText'] }} hover:text-[#E87524] transition-colors cursor-pointer">
                                <span x-show="!showPassword"><i data-lucide="eye" class="w-4 h-4"></i></span>
                                <span x-show="showPassword" x-cloak><i data-lucide="eye-off" class="w-4 h-4"></i></span>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 text-xs">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-[#77736E] text-[#E87524] focus:ring-[#E87524]/30 cursor-pointer">
                            <span class="{{ $leftConfig['mutedText'] }} font-medium">Remember me</span>
                        </label>

                        @if($showForgot)
                            <button type="button" @click="showForgotInfo = !showForgotInfo" class="font-bold transition-colors cursor-pointer {{ $leftConfig['linkClass'] }}">
                                Forgot password?
                            </button>
                        @endif
                    </div>

                    <div x-show="showForgotInfo" x-cloak class="p-3 rounded-xl border border-[#E87524]/35 bg-[#E87524]/10 text-xs {{ $isLightRight ? 'text-[#292D32]' : 'text-[#F5F3EF]' }}">
                        Operational account resets are managed exclusively by your authorized Office Admin or LDRRMO Super Admin for audit security.
                    </div>

                    <button type="submit"
                            :disabled="submitting"
                            class="w-full min-h-[48px] flex items-center justify-center gap-2 py-3 px-6 rounded-xl font-black text-sm shadow-lg alerto-btn-press cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed {{ $leftConfig['btnClass'] }}">
                        <svg x-show="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" x-cloak>
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="submitting ? @js($submittingLabel) : @js($submitLabel)"></span>
                    </button>
                </form>

                <div class="mt-6 pt-5 border-t {{ $isLightRight ? 'border-[#E6E3DE]' : 'border-white/10' }} text-center text-[11px] {{ $leftConfig['mutedText'] }}">
                    {{ $accessNotice }}
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.AlertoEoc?.dashboard) {
            window.AlertoEoc.dashboard.init(document);
        }
    });
</script>
@endpush

