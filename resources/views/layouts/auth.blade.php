<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ALERTO APP' }} — Emergency Response & Coordination Platform</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <x-pwa-meta />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .font-mono-tactical {
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>
<body class="h-full antialiased text-slate-800 {{ $bodyClass ?? 'bg-slate-950 text-slate-100' }}">

    <!-- Flash Notifications -->
    <div class="fixed top-4 right-4 z-50 max-w-md w-full space-y-2 pointer-events-none px-4 sm:px-0">
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="pointer-events-auto bg-emerald-900/90 border border-emerald-500/50 text-emerald-100 p-4 rounded-xl shadow-2xl backdrop-blur-md flex items-start space-x-3 transition-all duration-300">
                <svg class="w-6 h-6 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="flex-1 text-sm font-medium">{{ session('success') }}</div>
                <button @click="show = false" class="text-emerald-300 hover:text-white">&times;</button>
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div x-data="{ show: true }" x-show="show" class="pointer-events-auto bg-rose-950/90 border border-rose-500/50 text-rose-100 p-4 rounded-xl shadow-2xl backdrop-blur-md flex items-start space-x-3 transition-all duration-300">
                <svg class="w-6 h-6 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div class="flex-1 text-sm font-medium">
                    @if(session('error'))
                        <div>{{ session('error') }}</div>
                    @endif
                    @if($errors->any())
                        <ul class="list-disc list-inside space-y-1 mt-1 text-xs text-rose-200">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <button @click="show = false" class="text-rose-300 hover:text-white">&times;</button>
            </div>
        @endif
    </div>

    {{ $slot ?? '' }}
    @yield('content')

    @stack('scripts')
</body>
</html>

