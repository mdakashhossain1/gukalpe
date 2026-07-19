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

    {{-- Same as layouts/simple, minus its shared centered-logo header - this
         layout is for full-bleed hero auth screens (see Auth::phone) that
         build their own hero/back-button/logo inline instead of reusing
         that generic chrome. --}}
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans min-h-screen" data-lang-base="{{ asset('lang') }}">
    <x-toast />

    @yield('content')

    <x-i18n-engine />
</body>
</html>
