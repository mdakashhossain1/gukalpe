<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Ops Console') · GullakPe</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- CSS-only entry - every admin page's behavior now lives in an inline
         <script> at the bottom of its own Blade file/component, not a
         shared bundled JS file. --}}
    @vite(['resources/css/app.css'])
</head>
<body class="admin-body bg-[#F4F6F7] font-sans text-[#0F172A] antialiased">
    <x-toast />
    @yield('content')
</body>
</html>
