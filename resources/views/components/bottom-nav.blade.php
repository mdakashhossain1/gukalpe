    {{-- Real navigation now (route()-based <a> links + request()->routeIs()
         for active state) - this used to be entirely JS-driven
         (onclick="window.newSwitchTab(...)" swapping which tab-content div
         was hidden), which stopped working once the app's client-side JS
         was removed. Each item now navigates to its own real page. --}}
    <div id="bottom-nav" class="fixed bottom-0 left-0 w-full flex justify-around items-end px-4 z-[100] overflow-visible md:hidden">

        <!-- Separate background layer to fix backdrop-filter clipping bug on Safari/Mobile -->
        <div class="absolute inset-0 nav-glass -z-10 rounded-t-[24px] shadow-[0_-5px_30px_rgba(10,92,102,0.08)] pointer-events-none"></div>

        <a href="{{ route('home') }}" id="nav-home" data-tab="home" class="relative flex flex-col items-center justify-center w-14 h-[60px] gap-1.5 active:scale-95 transition-transform cursor-pointer group outline-none focus-visible:ring-2 focus-visible:ring-[#0A5C66]/40 focus-visible:ring-offset-2 rounded-2xl">
            <i class="fa-solid fa-house text-[22px] {{ request()->routeIs('home') ? 'text-[#0A5C66]' : 'text-slate-400' }} group-hover:-translate-y-1 transition-transform duration-300"></i>
            <span class="text-[10px] font-bold {{ request()->routeIs('home') ? 'text-[#0A5C66]' : 'text-slate-400' }} font-poppins leading-none">Home</span>
            <div class="nav-active-glow {{ request()->routeIs('home') ? '' : 'opacity-0' }}"></div>
        </a>

        <a href="{{ route('portfolio') }}" id="nav-portfolio" data-tab="portfolio" class="relative flex flex-col items-center justify-center w-14 h-[60px] gap-1.5 active:scale-95 transition-transform cursor-pointer group outline-none focus-visible:ring-2 focus-visible:ring-[#0A5C66]/40 focus-visible:ring-offset-2 rounded-2xl">
            <i class="fa-solid fa-chart-pie text-[22px] {{ request()->routeIs('portfolio') ? 'text-[#0A5C66]' : 'text-slate-400' }} group-hover:text-[#0A5C66] group-hover:-translate-y-1 transition-all duration-300"></i>
            <span class="text-[10px] {{ request()->routeIs('portfolio') ? 'font-bold text-[#0A5C66]' : 'font-medium text-slate-400' }} group-hover:text-[#0A5C66] leading-none">Portfolio</span>
            <div class="nav-active-glow {{ request()->routeIs('portfolio') ? '' : 'opacity-0' }}"></div>
        </a>

        <!-- EXPLORE BUTTON WRAPPER - visually fixed -->
        <a href="{{ route('explore') }}" id="nav-explore-wrapper" class="relative flex flex-col items-center justify-center w-14 h-[60px] cursor-pointer group" style="overflow: visible;">

            <!-- Floating Circular Button - resized to match other buttons better -->
            <div class="absolute -top-[14px] w-12 h-12 rounded-full flex items-center justify-center z-[110] transition-all duration-300 active:scale-95 group-active:scale-95 shadow-[0_4px_12px_rgba(10,92,102,0.2)] cursor-pointer">
                <!-- Mint Green Glow Effect -->
                <div id="explore-glow" class="absolute inset-0 bg-[#3FEA8A] rounded-full blur-[8px] {{ request()->routeIs('explore') ? 'opacity-60' : 'opacity-0' }} transition-opacity duration-300 pointer-events-none"></div>

                <!-- Main Button Body -->
                <div id="nav-explore" class="relative w-full h-full bg-gradient-to-b from-[#0A5C66] to-[#063B42] rounded-full flex items-center justify-center pointer-events-none overflow-hidden transition-all duration-300 outline-none focus-visible:ring-2 focus-visible:ring-[#3FEA8A] focus-visible:ring-offset-2">
                    <i id="explore-icon" class="fa-solid fa-compass text-[20px] transition-colors duration-300 text-white/90 group-hover:text-white"></i>
                </div>
            </div>

            <div class="w-full h-[24px]"></div> <!-- Spacer for icon height -->
            <span id="explore-text" class="text-[10px] font-black {{ request()->routeIs('explore') ? 'text-[#0A5C66]' : 'text-slate-500' }} group-hover:text-[#0A5C66] font-poppins z-10 leading-none mt-1 transition-colors duration-300">Explore</span>
        </a>

        <a href="{{ route('rewards') }}" id="nav-rewards" data-tab="rewards" class="relative flex flex-col items-center justify-center w-14 h-[60px] gap-1.5 active:scale-95 transition-transform cursor-pointer group outline-none focus-visible:ring-2 focus-visible:ring-[#0A5C66]/40 focus-visible:ring-offset-2 rounded-2xl">
            <div class="relative flex items-center justify-center">
                <i class="fa-solid fa-gift text-[22px] {{ request()->routeIs('rewards') ? 'text-[#0A5C66]' : 'text-slate-400' }} group-hover:text-[#0A5C66] group-hover:-translate-y-1 transition-all duration-300"></i>
                <span id="rewards-notification-badge" class="absolute -top-2 -right-2.5 bg-[#FF3B30] text-white font-bold text-[10px] font-poppins h-5 min-w-[20px] px-1 rounded-full border-2 border-white shadow-[0_2px_5px_rgba(0,0,0,0.15)] flex items-center justify-center pointer-events-none z-50 hidden">0</span>
            </div>
            <span class="text-[10px] {{ request()->routeIs('rewards') ? 'font-bold text-[#0A5C66]' : 'font-medium text-slate-400' }} group-hover:text-[#0A5C66] leading-none">Rewards</span>
            <div class="nav-active-glow {{ request()->routeIs('rewards') ? '' : 'opacity-0' }}"></div>
        </a>

        <a href="{{ route('profile') }}" id="nav-profile" data-tab="profile" class="relative flex flex-col items-center justify-center w-14 h-[60px] gap-1.5 active:scale-95 transition-transform cursor-pointer group outline-none focus-visible:ring-2 focus-visible:ring-[#0A5C66]/40 focus-visible:ring-offset-2 rounded-2xl">
            <i class="fa-solid fa-user text-[22px] {{ request()->routeIs('profile') ? 'text-[#0A5C66]' : 'text-slate-400' }} group-hover:text-[#0A5C66] group-hover:-translate-y-1 transition-all duration-300"></i>
            <span class="text-[10px] {{ request()->routeIs('profile') ? 'font-bold text-[#0A5C66]' : 'font-medium text-slate-400' }} group-hover:text-[#0A5C66] leading-none">Profile</span>
            <div class="nav-active-glow {{ request()->routeIs('profile') ? '' : 'opacity-0' }}"></div>
        </a>

    </div>
