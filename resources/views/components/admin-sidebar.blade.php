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
        ['route' => 'admin.users', 'key' => 'users', 'icon' => 'fa-users', 'label' => 'Users'],
        ['route' => 'admin.deposits', 'key' => 'deposits', 'icon' => 'fa-money-bill-transfer', 'label' => 'Deposit requests', 'count' => $pendingDepositCount],
        ['route' => 'admin.withdrawals', 'key' => 'withdrawals', 'icon' => 'fa-money-bill-transfer fa-flip-horizontal', 'label' => 'Withdrawal requests', 'count' => $pendingWithdrawalCount],
        ['route' => 'admin.payment-gateway', 'key' => 'payment-gateway', 'icon' => 'fa-qrcode', 'label' => 'Payment gateway'],
        ['route' => 'admin.simulations', 'key' => 'simulations', 'icon' => 'fa-bolt', 'label' => 'Simulations'],
        ['route' => 'admin.settings', 'key' => 'settings', 'icon' => 'fa-sliders', 'label' => 'Referral program'],
        ['route' => 'admin.logs', 'key' => 'logs', 'icon' => 'fa-list', 'label' => 'Activity logs'],
        ['route' => 'admin.push-notification', 'key' => 'push-notification', 'icon' => 'fa-paper-plane', 'label' => 'Push notification'],
        ['route' => 'admin.plans', 'key' => 'plans', 'icon' => 'fa-layer-group', 'label' => 'Investment plans'],
    ];
@endphp

{{-- Mobile top bar (md:hidden) - hamburger opens the SAME <aside> below as
     a slide-in drawer instead of the old "different mobile nav" approach
     (a separate horizontal pill-link row). One real sidebar, two
     presentations: an in-flow column on desktop, a fixed off-canvas panel
     on mobile toggled by the button here. `sticky` (not `fixed`) is
     deliberate - every page wrapper is `flex flex-col md:flex-row`, so on
     mobile this bar is a normal in-flow column item stacked above <main>,
     not a row sibling next to it (that row/column mismatch - header and
     main forced side-by-side in the default row direction - was the actual
     bug behind "sidebar not responsive": with the old plain `flex` wrapper,
     hiding the desktop <aside> on mobile left this header and <main> as two
     items in the same row instead of stacked). --}}
<header class="md:hidden sticky top-0 z-30 w-full bg-white/95 backdrop-blur border-b border-[#E5E9EB]">
    <div class="px-4 h-16 flex items-center justify-between gap-2">
        <div class="flex items-center gap-2 min-w-0">
            <button type="button" id="admin-sidebar-open" class="w-10 h-10 -ml-1 shrink-0 rounded-lg flex items-center justify-center text-[#334155] hover:bg-[#F1F5F9] transition-colors" aria-label="Open menu" aria-expanded="false" aria-controls="admin-sidebar">
                <i class="fa-solid fa-bars text-[16px]"></i>
            </button>
            <div class="flex items-center gap-2 min-w-0">
                <div class="w-8 h-8 bg-brand rounded-lg flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-piggy-bank text-white text-[13px]"></i>
                </div>
                <span class="font-poppins font-extrabold text-[15px] tracking-tight text-[#0F172A] truncate">GullakPe <span class="font-semibold text-[#64748B]">Ops</span></span>
            </div>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <x-admin-notification-bell id="admin-notif-bell-mobile" />
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="text-[13px] font-semibold text-[#64748B] hover:text-[#0F172A] transition-colors">Sign out</button>
            </form>
        </div>
    </div>
</header>

{{-- Backdrop - mobile only. Dims the page and closes the drawer on click,
     same as the X button or Escape. Starts hidden; JS below toggles it. --}}
<div id="admin-sidebar-backdrop" class="hidden md:hidden fixed inset-0 bg-slate-900/50 z-40" aria-hidden="true"></div>

{{-- The one real sidebar. Mobile: fixed off-canvas panel, translated out of
     view by default and slid in via JS (`fixed` also takes it out of the
     page's flex flow entirely, so it never competes for row/column space).
     Desktop (md:): back to the original in-flow, sticky column - `md:static`
     isn't enough on its own since sticky is what let it match the row's
     real height per the note below, so `md:sticky` + the transform reset
     (`md:translate-x-0`) restores the exact pre-drawer desktop behavior. --}}
<aside id="admin-sidebar"
    class="fixed md:sticky inset-y-0 left-0 md:inset-y-auto top-0 md:top-0 z-50 md:z-auto w-72 md:w-60 shrink-0 border-r border-[#E5E9EB] bg-white px-4 py-6 flex flex-col self-stretch -translate-x-full md:translate-x-0 transition-transform duration-200 ease-out">
    <div class="flex items-center justify-between gap-2.5 px-2 mb-8">
        <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-8 h-8 bg-brand rounded-lg flex items-center justify-center shrink-0">
                <i class="fa-solid fa-piggy-bank text-white text-[13px]"></i>
            </div>
            <span class="font-poppins font-extrabold text-[15.5px] tracking-tight text-[#0F172A] truncate">GullakPe <span class="font-semibold text-[#64748B]">Ops</span></span>
        </div>
        <button type="button" id="admin-sidebar-close" class="md:hidden shrink-0 w-9 h-9 rounded-lg flex items-center justify-center text-[#64748B] hover:bg-[#F1F5F9] transition-colors" aria-label="Close menu">
            <i class="fa-solid fa-xmark text-[15px]"></i>
        </button>
    </div>

    <nav class="flex flex-col gap-1 overflow-y-auto">
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

<script>
(function () {
    var sidebar = document.getElementById('admin-sidebar');
    var backdrop = document.getElementById('admin-sidebar-backdrop');
    var openBtn = document.getElementById('admin-sidebar-open');
    var closeBtn = document.getElementById('admin-sidebar-close');
    if (!sidebar || !backdrop || !openBtn) return;

    // Guard against this script running twice - admin-notification-bell is
    // rendered once per breakpoint inside this component, but the sidebar
    // itself (and this script) is only ever included once per page, so this
    // is just cheap insurance, not a known duplication.
    if (openBtn.dataset.drawerBound) return;
    openBtn.dataset.drawerBound = '1';

    function openDrawer() {
        sidebar.classList.remove('-translate-x-full');
        backdrop.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        openBtn.setAttribute('aria-expanded', 'true');
    }

    function closeDrawer() {
        sidebar.classList.add('-translate-x-full');
        backdrop.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        openBtn.setAttribute('aria-expanded', 'false');
    }

    openBtn.addEventListener('click', openDrawer);
    closeBtn && closeBtn.addEventListener('click', closeDrawer);
    backdrop.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeDrawer();
    });
})();
</script>
