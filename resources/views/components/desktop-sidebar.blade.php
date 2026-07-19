{{-- Desktop-only nav (hidden below md). Mobile keeps the bottom tab bar.
     Real <a href> links + request()->routeIs() for active state now - see
     bottom-nav.blade.php for the same conversion and why. --}}
<aside id="desktop-sidebar" class="hidden md:flex md:flex-col fixed top-0 left-0 h-screen w-[248px] bg-white border-r border-slate-200/70 px-4 py-6 z-[100]">

    <div class="flex items-center gap-2.5 px-2 mb-8">
        <div class="w-9 h-9 bg-brand rounded-xl flex items-center justify-center shadow-sm shrink-0">
            <i class="fa-solid fa-piggy-bank text-white text-[16px]"></i>
        </div>
        <span class="font-poppins font-extrabold text-[17px] tracking-tight text-[#0F172A]">Gullak<span class="text-brand">Pe</span></span>
    </div>

    <nav class="flex flex-col gap-1">
        <a href="{{ route('home') }}" data-tab="home"
            class="sidebar-nav-item {{ request()->routeIs('home') ? 'is-active' : '' }} group flex items-center gap-3 h-11 px-3 rounded-xl transition-colors outline-none focus-visible:ring-2 focus-visible:ring-brand/40">
            <i class="fa-solid fa-house text-[16px] w-5 text-center {{ request()->routeIs('home') ? 'text-[#0A5C66]' : 'text-slate-400' }} transition-colors"></i>
            <span class="text-[14px] {{ request()->routeIs('home') ? 'font-bold text-[#0A5C66]' : 'font-medium text-slate-400' }} group-hover:text-[#0A5C66] font-poppins transition-colors">Home</span>
        </a>

        <a href="{{ route('explore') }}" data-tab="explore"
            class="sidebar-nav-item {{ request()->routeIs('explore') ? 'is-active' : '' }} group flex items-center gap-3 h-11 px-3 rounded-xl transition-colors outline-none focus-visible:ring-2 focus-visible:ring-brand/40">
            <i class="fa-solid fa-compass text-[16px] w-5 text-center {{ request()->routeIs('explore') ? 'text-[#0A5C66]' : 'text-slate-400' }} group-hover:text-[#0A5C66] transition-colors"></i>
            <span class="text-[14px] {{ request()->routeIs('explore') ? 'font-bold text-[#0A5C66]' : 'font-medium text-slate-400' }} group-hover:text-[#0A5C66] font-poppins transition-colors">Explore</span>
        </a>

        <a href="{{ route('portfolio') }}" data-tab="portfolio"
            class="sidebar-nav-item {{ request()->routeIs('portfolio') ? 'is-active' : '' }} group flex items-center gap-3 h-11 px-3 rounded-xl transition-colors outline-none focus-visible:ring-2 focus-visible:ring-brand/40">
            <i class="fa-solid fa-chart-pie text-[16px] w-5 text-center {{ request()->routeIs('portfolio') ? 'text-[#0A5C66]' : 'text-slate-400' }} group-hover:text-[#0A5C66] transition-colors"></i>
            <span class="text-[14px] {{ request()->routeIs('portfolio') ? 'font-bold text-[#0A5C66]' : 'font-medium text-slate-400' }} group-hover:text-[#0A5C66] font-poppins transition-colors">Portfolio</span>
        </a>

        <a href="{{ route('rewards') }}" data-tab="rewards"
            class="sidebar-nav-item {{ request()->routeIs('rewards') ? 'is-active' : '' }} group flex items-center gap-3 h-11 px-3 rounded-xl transition-colors outline-none focus-visible:ring-2 focus-visible:ring-brand/40">
            <i class="fa-solid fa-gift text-[16px] w-5 text-center {{ request()->routeIs('rewards') ? 'text-[#0A5C66]' : 'text-slate-400' }} group-hover:text-[#0A5C66] transition-colors"></i>
            <span class="text-[14px] {{ request()->routeIs('rewards') ? 'font-bold text-[#0A5C66]' : 'font-medium text-slate-400' }} group-hover:text-[#0A5C66] font-poppins transition-colors">Rewards</span>
            <span id="sidebar-rewards-badge" class="hidden ml-auto bg-[#FF3B30] text-white text-[10px] font-bold h-4.5 min-w-[18px] px-1 rounded-full items-center justify-center">0</span>
        </a>

        <a href="{{ route('profile') }}" data-tab="profile"
            class="sidebar-nav-item {{ request()->routeIs('profile') ? 'is-active' : '' }} group flex items-center gap-3 h-11 px-3 rounded-xl transition-colors outline-none focus-visible:ring-2 focus-visible:ring-brand/40">
            <i class="fa-solid fa-user text-[16px] w-5 text-center {{ request()->routeIs('profile') ? 'text-[#0A5C66]' : 'text-slate-400' }} group-hover:text-[#0A5C66] transition-colors"></i>
            <span class="text-[14px] {{ request()->routeIs('profile') ? 'font-bold text-[#0A5C66]' : 'font-medium text-slate-400' }} group-hover:text-[#0A5C66] font-poppins transition-colors">Profile</span>
        </a>

    </nav>

    <a href="{{ route('login') }}" id="sidebar-login-btn"
        class="mt-auto h-11 rounded-xl bg-brand text-white font-semibold text-[13.5px] hover:bg-brand-light transition-colors flex items-center justify-center">
        Log in
    </a>
</aside>
