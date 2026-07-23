<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Aapki Digital Gullak, Safe Aur Secure!">
    <title>GullakPe: Secure & Trusted Savings and Goal App in India</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    @vite(['resources/css/app.css'])
</head>
<body class="bg-bg font-sans" data-lang-base="{{ asset('lang') }}">

    <x-system-overlays />

    <x-toast />

    <!-- Desktop sidebar nav (md+ only; mobile uses the bottom tab bar) -->
    <x-desktop-sidebar />

    <!-- Main Content Area -->
    {{-- md:pl- (padding), not md:ml- (margin): w-full is 100% width, so a
         margin on top of that pushes the box 248px past the viewport edge
         and triggers horizontal scroll. Padding is included inside the
         100% width (box-sizing: border-box), so it can't overflow. --}}
    <main class="w-full min-h-screen relative md:pl-[248px] overflow-x-hidden">
        @yield('content')
    </main>

    <div id="google_translate_element"></div>

    <!-- Language Selector Modal -->
    <x-language-modal />

    <!-- Shared Popups & Overlays -->
    <x-popups />

    <!-- Trust Builder / Growth Plan purchase-flow popups (pure CSS,
         driven by session flash set in PlanPurchaseController) -->
    <x-unlock-required-popup />
    <x-insufficient-balance-popup />
    <x-purchase-success-popup />

    {{-- Bottom Navigation Bar: skipped on plan-details, which renders its own
         fixed bottom invest CTA bar in the same screen region. Both are
         `fixed bottom-0`; stacking them competes for the same space, so the
         page-level CTA takes over instead of layering on top of the tab bar. --}}
    @unless(request()->routeIs('plan-details'))
        <x-bottom-nav />
    @endunless

    <x-i18n-engine />

</body>
</html>
