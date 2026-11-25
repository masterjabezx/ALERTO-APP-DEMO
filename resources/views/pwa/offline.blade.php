<!DOCTYPE html>
<html lang="en" class="h-full bg-[#17191C]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Offline Mode — ALERTO Hinoba-an</title>
    <x-pwa-meta />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
    @vite(['resources/css/app.css'])
</head>
<body class="h-full flex flex-col justify-between bg-[#17191C] text-[#F5F3EF] p-6 selection:bg-[#E87524] selection:text-white">

    <div class="max-w-md w-full mx-auto my-auto text-center flex flex-col items-center">
        <!-- Official ALERTO Brand Beacon Badge -->
        <div class="relative mb-6">
            <div class="w-24 h-24 rounded-3xl bg-[#101214] border border-[#E87524]/35 p-2 flex items-center justify-center shadow-2xl">
                <img src="/images/branding/alerto-logo-web-256.png" alt="ALERTO APP" class="w-full h-full object-contain">
            </div>
            <span class="absolute -top-1 -right-1 flex h-4 w-4">
                <span class="relative inline-flex rounded-full h-4 w-4 bg-[#C83F43] border-2 border-[#17191C]"></span>
            </span>
        </div>

        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-[#C83F43]/15 text-[#F27D80] border border-[#C83F43]/35 mb-3">
            Local Emergency Mode
        </span>

        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mb-2">
            Connection Lost
        </h1>

        <p class="text-[#D1CEC9] text-sm mb-6 leading-relaxed">
            ALERTO is running in offline mode. Any incident reports or responder updates on this device remain safely saved and will be automatically synchronized when your connection returns.
        </p>

        <!-- Emergency Hotline Callout (CRITICAL) -->
        <div class="w-full bg-[#292D32] border border-[#E87524]/30 rounded-2xl p-5 mb-6 text-left shadow-lg">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-[#C83F43] flex items-center justify-center shrink-0 text-white shadow-md">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-[#F27D80]">Life-Threatening Emergency?</h2>
                    <p class="text-xs text-[#D1CEC9] mt-0.5">Call Hinoba-an LDRRMO Dispatch directly via phone.</p>
                    <a href="tel:{{ preg_replace('/[^0-9]/', '', $hotline) }}" 
                       class="mt-3 inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-xl text-sm font-bold bg-[#C83F43] hover:bg-[#A83135] text-white shadow-md transition-all active:scale-[0.98]">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        <span>{{ $hotlineTitle }}: {{ $hotline }}</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Reconnect Actions -->
        <div class="w-full flex flex-col sm:flex-row gap-3">
            <button onclick="window.location.reload()" 
                    class="flex-1 py-3 px-4 rounded-xl text-xs font-bold uppercase tracking-wider bg-[#E87524] hover:bg-[#D16419] text-white transition-all shadow-md cursor-pointer">
                Try Reconnecting
            </button>
            <button onclick="window.history.back()" 
                    class="py-3 px-4 rounded-xl text-xs font-bold uppercase tracking-wider bg-[#292D32] hover:bg-[#363B42] text-[#F5F3EF] border border-white/15 transition-all cursor-pointer">
                Go Back
            </button>
        </div>
    </div>

    <!-- Footer info -->
    <footer class="text-center text-[11px] text-[#A39E98] py-4">
        Hinoba-an Local Disaster Risk Reduction and Management Office &bull; ALERTO APP PWA
    </footer>
</body>
</html>
