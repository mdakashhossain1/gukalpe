<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'GullakPe')</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('assets/favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- Same as layouts/simple, minus its shared centered-logo header - this
         layout is for full-bleed hero auth screens (see Auth::phone) that
         build their own hero/back-button/logo inline instead of reusing
         that generic chrome. --}}
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans min-h-screen" data-lang-base="{{ asset('lang') }}">
    @yield('content')

    <x-i18n-engine />

    {{-- Shared across every phone/OTP/MPIN screen: swaps a form's submit
         button(s) to a spinner + status label the moment it's submitted,
         whether that's a manual click or pin-input.blade.php's autoSubmit.
         Real full-page POSTs - the loading state just needs to survive
         until the next page renders, so there's no reset-on-failure path
         to handle (a validation-failure redirect reloads the DOM fresh). --}}
    <script>
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            // Buttons nested inside the <form> (e.g. Resend OTP) plus buttons
            // elsewhere on the page wired via form="<id>" (every bottom-action-bar
            // submit button in this layout uses that pattern instead of nesting).
            var buttons = Array.prototype.filter.call(document.querySelectorAll('button[type="submit"]'), function (btn) {
                return form.contains(btn) || (form.id && btn.getAttribute('form') === form.id);
            });
            buttons.forEach(function (btn) {
                if (btn.disabled) return;
                btn.disabled = true;
                btn.classList.add('auth-btn-loading');
                var label = btn.getAttribute('data-loading-text') || 'Please wait...';
                btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> ' + label;
            });
        });
    </script>
</body>
</html>
