@props(['active' => 'overview', 'pendingDepositCount' => 0, 'pendingWithdrawalCount' => 0])

{{-- Every section is its own real, server-rendered route now - Overview,
     Deposit requests, and Withdrawal requests were already real pages;
     Wallet adjustment, Simulations, Referral program, and Activity logs
     used to be JS-tabbed panels sharing one page (app/Modules/Admin/Views/dashboard.blade.php)
     but that read as unfinished/non-standard next to the real pages, so
     they moved to their own routes/views too. Nav is now a plain list of
     links, no [data-panel] JS-toggle branching. --}}
@php
    $navItems = [
        ['route' => 'admin.dashboard', 'key' => 'overview', 'icon' => 'fa-chart-line', 'label' => 'Overview'],
        ['route' => 'admin.deposits', 'key' => 'deposits', 'icon' => 'fa-money-bill-transfer', 'label' => 'Deposit requests', 'count' => $pendingDepositCount],
        ['route' => 'admin.withdrawals', 'key' => 'withdrawals', 'icon' => 'fa-money-bill-transfer fa-flip-horizontal', 'label' => 'Withdrawal requests', 'count' => $pendingWithdrawalCount],
        ['route' => 'admin.payment-gateway', 'key' => 'payment-gateway', 'icon' => 'fa-qrcode', 'label' => 'Payment gateway'],
        ['route' => 'admin.wallet-tools', 'key' => 'wallet', 'icon' => 'fa-wallet', 'label' => 'Wallet adjustment'],
        ['route' => 'admin.simulations', 'key' => 'simulations', 'icon' => 'fa-bolt', 'label' => 'Simulations'],
        ['route' => 'admin.settings', 'key' => 'settings', 'icon' => 'fa-sliders', 'label' => 'Referral program'],
        ['route' => 'admin.logs', 'key' => 'logs', 'icon' => 'fa-list', 'label' => 'Activity logs'],
        ['route' => 'admin.push-notification', 'key' => 'push-notification', 'icon' => 'fa-paper-plane', 'label' => 'Push notification'],
        ['route' => 'admin.plans', 'key' => 'plans', 'icon' => 'fa-layer-group', 'label' => 'Investment plans'],
    ];
@endphp

{{-- No fixed h-screen here on purpose: a flex row's default align-items:
     stretch already makes this match the row's real height (i.e. main's
     content height, whatever that is) - h-screen instead capped it at
     exactly one viewport, so on any page taller than that, scrolling past
     the first screen ran past the sidebar's own box and left it behind,
     showing empty page background where the white sidebar should still be. --}}
<aside class="hidden md:flex md:flex-col w-60 shrink-0 border-r border-[#E5E9EB] bg-white px-4 py-6 sticky top-0 self-stretch">
    <div class="flex items-center gap-2.5 px-2 mb-8">
        <div class="w-8 h-8 bg-brand rounded-lg flex items-center justify-center shrink-0">
            <i class="fa-solid fa-piggy-bank text-white text-[13px]"></i>
        </div>
        <span class="font-poppins font-extrabold text-[15.5px] tracking-tight text-[#0F172A]">GullakPe <span class="font-semibold text-[#64748B]">Ops</span></span>
    </div>

    <nav class="flex flex-col gap-1">
        @foreach ($navItems as $item)
            <a href="{{ route($item['route']) }}" class="ops-nav-item {{ $active === $item['key'] ? 'is-active' : '' }} flex items-center gap-3 h-10 px-3 rounded-lg text-left transition-colors">
                <i class="fa-solid {{ $item['icon'] }} w-4 text-center text-[14px]"></i>
                <span class="text-[13.5px] font-semibold">{{ $item['label'] }}</span>
                @if (($item['count'] ?? 0) > 0)
                    <span class="ml-auto bg-[#DC2626] text-white text-[10px] font-bold h-[18px] min-w-[18px] px-1 rounded-full flex items-center justify-center">{{ $item['count'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <form method="POST" action="{{ route('admin.logout') }}" class="mt-auto">
        @csrf
        <button type="submit" class="w-full flex items-center gap-3 h-10 px-3 rounded-lg text-left text-[13.5px] font-semibold text-[#64748B] hover:bg-[#F1F5F9] hover:text-[#0F172A] transition-colors">
            <i class="fa-solid fa-arrow-right-from-bracket w-4 text-center text-[14px]"></i>
            Sign out
        </button>
    </form>
</aside>

{{-- Mobile top bar (sidebar is md+ only) --}}
<header class="md:hidden sticky top-0 z-10 w-full bg-white/95 backdrop-blur border-b border-[#E5E9EB]">
    <div class="px-5 h-16 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 bg-brand rounded-lg flex items-center justify-center shrink-0">
                <i class="fa-solid fa-piggy-bank text-white text-[13px]"></i>
            </div>
            <span class="font-poppins font-extrabold text-[15.5px] tracking-tight text-[#0F172A]">GullakPe <span class="font-semibold text-[#64748B]">Ops</span></span>
        </div>
        <div class="flex items-center gap-3">
            <x-admin-notification-bell id="admin-notif-bell-mobile" />
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="text-[13px] font-semibold text-[#64748B] hover:text-[#0F172A] transition-colors">Sign out</button>
            </form>
        </div>
    </div>
    <div class="flex gap-1.5 px-4 pb-3 overflow-x-auto">
        @foreach ($navItems as $item)
            <a href="{{ route($item['route']) }}" class="ops-nav-item {{ $active === $item['key'] ? 'is-active' : '' }} shrink-0 h-8 px-3 rounded-full text-[12.5px] font-semibold transition-colors">
                {{ $item['label'] }} @if (($item['count'] ?? 0) > 0)<span class="ml-1 bg-[#DC2626] text-white text-[10px] font-bold px-1.5 rounded-full">{{ $item['count'] }}</span>@endif
            </a>
        @endforeach
    </div>
</header>
