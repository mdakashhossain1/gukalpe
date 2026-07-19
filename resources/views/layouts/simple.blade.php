<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'GullakPe')</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- CSS-only entry - deliberately no resources/js/app.js here. Pages
         using this layout are plain server-rendered Laravel forms/pages
         (see app/Modules/Deposits), not part of the client-side SPA shell,
         so none of the SPA's JS (navigation, global loader, etc.) is loaded
         or needed. i18n is the one exception (<x-i18n-engine /> below): the
         Auth flow (phone/OTP/MPIN) is real guest-facing UI and needs the
         same Hindi/English toggle as the rest of the app, so it can't be
         skipped just because this layout is otherwise JS-light. --}}
    @vite(['resources/css/app.css'])
</head>
<body class="bg-bg font-sans min-h-screen" data-lang-base="{{ asset('lang') }}">
    <x-toast />
    <div class="min-h-screen flex flex-col">
        <header class="w-full flex items-center justify-between py-4 px-5 bg-white border-b border-slate-100 shadow-[0_1px_2px_rgba(15,23,42,0.03)] sticky top-0 z-10">
            <div class="flex items-center gap-3 min-w-0">
                @hasSection('backRoute')
                    <a href="@yield('backRoute')" aria-label="Back"
                        class="w-9 h-9 rounded-full bg-slate-50 border border-slate-200/80 flex items-center justify-center text-slate-500 shrink-0 transition-all active:scale-95 hover:border-[#0A5C66]/25 hover:text-[#0A5C66]">
                        <i class="fa-solid fa-arrow-left text-[13px]"></i>
                    </a>
                @endif
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 p-1">
                        <img src="{{ asset('assets/logo.png') }}" alt="GullakPe" class="w-full h-full object-contain">
                    </div>
                    <span class="font-poppins font-extrabold text-[18px] tracking-tight text-[#0F172A] truncate">Gullak<span class="text-brand">Pe</span></span>
                </a>
            </div>
            <button type="button" onclick="window.toggleLanguage && window.toggleLanguage()" aria-label="Switch language"
                class="relative w-9 h-9 rounded-full bg-slate-50 border border-slate-200/80 flex items-center justify-center text-slate-500 transition-all active:scale-95 hover:border-[#0A5C66]/25 hover:text-[#0A5C66]">
                <i class="bi bi-translate text-[15px]"></i>
                <span data-current-lang class="absolute -bottom-1 -right-1 bg-[#0A5C66] text-white text-[8px] font-black px-1 rounded-full leading-tight border-2 border-white">EN</span>
            </button>
        </header>

        <main class="flex-1 w-full max-w-[480px] mx-auto px-5 pb-12">
            @yield('content')
        </main>
    </div>

    <x-i18n-engine />
</body>
</html>
