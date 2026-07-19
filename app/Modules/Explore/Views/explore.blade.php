@extends('layouts.app')

@section('content')
    <div id="tab-explore" class="flex min-h-[100dvh] flex-col flex-1 bg-[#F8FAFC] pb-28 pt-safe overflow-y-auto custom-scrollbar w-full animate-fade-in-up">

        {{-- Pure-CSS toggles (no JS left in the main app to drive a JS-opened
             modal/sheet) - a label wraps each trigger icon, and the panel
             below reacts to the matching checkbox via peer-checked. Both
             checkboxes and both panels are direct children of this same
             #tab-explore div so the CSS sibling selector actually reaches
             them regardless of where each is nested visually. --}}
        <input type="checkbox" id="explore-search-toggle" class="peer/search hidden">
        <input type="checkbox" id="explore-filter-toggle" class="peer/filter hidden">

        <!-- STICKY PREMIUM HEADER -->
        <div class="w-full px-4 py-3.5 bg-white/80 backdrop-blur-md sticky top-0 z-50 border-b border-slate-200/40 shadow-[0_4px_20px_rgba(10,92,102,0.02)] flex flex-col gap-3.5 transition-all duration-300">
            <div class="flex items-center justify-between">
                <!-- LEFT: Circular back button -->
                <div class="flex-1 flex justify-start">
                    <a href="{{ route('home') }}" class="w-10 h-10 rounded-full bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-[#1a153a] hover:text-[#0A5C66] border border-slate-200/50 hover:scale-105 active:scale-95 transition-all btn-ripple shadow-sm">
                        <i class="bi bi-chevron-left text-[16px]"></i>
                    </a>
                </div>

                <!-- CENTER: Title & Subtitle -->
                <div class="flex-auto text-center flex flex-col items-center">
                    <h1 class="font-extrabold text-[#0A5C66] text-[18px] tracking-tight font-poppins leading-tight">Explore Goals</h1>
                    <span class="text-[11px] font-semibold text-slate-400 tracking-wide font-poppins mt-0.5">Build your future goals</span>
                </div>

                <!-- RIGHT: Search & Filter buttons -->
                <div class="flex-1 flex items-center justify-end gap-2">
                    <label for="explore-search-toggle" class="w-10 h-10 rounded-full bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-500 hover:text-[#0A5C66] border border-slate-200/50 hover:scale-105 active:scale-95 transition-all shadow-sm cursor-pointer">
                        <i class="bi bi-search text-[14px]"></i>
                    </label>
                    <label for="explore-filter-toggle" class="relative w-10 h-10 rounded-full bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-500 hover:text-[#0A5C66] border border-slate-200/50 hover:scale-105 active:scale-95 transition-all shadow-sm cursor-pointer">
                        <i class="bi bi-sliders text-[14px]"></i>
                        @if ($hasActiveFilters)
                            <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-[#3CCF91] border border-white"></span>
                        @endif
                    </label>
                </div>
            </div>

            <!-- Filter Chips - generated from the real Plan catalog's
                 distinct badges (App\Modules\Explore\Controllers\ExploreController),
                 not a hardcoded list, so a badge an admin adds/removes on
                 the Plans page changes what's on offer here automatically. -->
            <div class="w-full flex justify-start items-center gap-2.5 overflow-x-auto hide-scrollbar whitespace-nowrap pb-1.5 -mx-4 px-4">
                @php $isAllActive = $selectedBadge === null; @endphp
                <a href="{{ route('explore') }}"
                    class="relative px-5 h-11 shrink-0 rounded-full text-[12px] sm:text-[13px] font-black flex items-center gap-1.5 active:scale-95 transition-all duration-300 btn-ripple
                        {{ $isAllActive
                            ? 'bg-gradient-to-r from-[#0A5C66] to-[#0E7481] text-white shadow-[inset_0_1.5px_3px_rgba(255,255,255,0.15),0_4px_12px_rgba(10,92,102,0.15),0_0_8px_rgba(60,207,145,0.2)] border border-[#0A5C66]/10'
                            : 'bg-white/40 backdrop-blur-md text-[#0A5C66] border border-[#0A5C66]/15 hover:border-[#3CCF91]/50 hover:bg-white hover:shadow-[0_0_10px_rgba(60,207,145,0.15)] hover:scale-[1.02]' }}">
                    <i class="bi bi-compass text-[14px] {{ $isAllActive ? 'text-[#3CCF91]' : 'text-[#0A5C66]' }}"></i> All Goals
                </a>

                @foreach ($chips as $chip)
                    @php $isChipActive = $selectedBadge === $chip['value']; @endphp
                    <a href="{{ route('explore', ['badge' => $chip['value']]) }}"
                        class="relative px-5 h-11 shrink-0 rounded-full text-[12px] sm:text-[13px] font-black flex items-center gap-1.5 active:scale-95 transition-all duration-300 btn-ripple
                            {{ $isChipActive
                                ? 'bg-gradient-to-r from-[#0A5C66] to-[#0E7481] text-white shadow-[inset_0_1.5px_3px_rgba(255,255,255,0.15),0_4px_12px_rgba(10,92,102,0.15),0_0_8px_rgba(60,207,145,0.2)] border border-[#0A5C66]/10'
                                : 'bg-white/40 backdrop-blur-md text-[#0A5C66] border border-[#0A5C66]/15 hover:border-[#3CCF91]/50 hover:bg-white hover:shadow-[0_0_10px_rgba(60,207,145,0.15)] hover:scale-[1.02]' }}">
                        <i class="bi {{ $chip['icon'] }} text-[14px] {{ $isChipActive ? 'text-[#3CCF91]' : 'text-[#0A5C66]' }}"></i> {{ $chip['label'] }}
                    </a>
                @endforeach
            </div>

            @if ($searchQuery !== '' || $hasActiveFilters)
                <div class="w-full flex flex-wrap items-center gap-2">
                    @if ($searchQuery !== '')
                        <a href="{{ route('explore', array_filter(['badge' => $selectedBadge, 'duration' => $selectedDuration, 'min_amount' => $minAmount, 'max_amount' => $maxAmount])) }}"
                            class="inline-flex items-center gap-1.5 bg-[#0A5C66]/8 text-[#0A5C66] text-[11.5px] font-bold px-3 py-1.5 rounded-full">
                            <i class="bi bi-search text-[9px]"></i> "{{ $searchQuery }}" <i class="bi bi-x-lg text-[9px]"></i>
                        </a>
                    @endif
                    @if ($selectedDuration)
                        <a href="{{ route('explore', array_filter(['badge' => $selectedBadge, 'q' => $searchQuery, 'min_amount' => $minAmount, 'max_amount' => $maxAmount])) }}"
                            class="inline-flex items-center gap-1.5 bg-[#0A5C66]/8 text-[#0A5C66] text-[11.5px] font-bold px-3 py-1.5 rounded-full">
                            <i class="bi bi-calendar2-check text-[9px]"></i> {{ $selectedDuration }} <i class="bi bi-x-lg text-[9px]"></i>
                        </a>
                    @endif
                    @if ($minAmount !== null || $maxAmount !== null)
                        <a href="{{ route('explore', array_filter(['badge' => $selectedBadge, 'q' => $searchQuery, 'duration' => $selectedDuration])) }}"
                            class="inline-flex items-center gap-1.5 bg-[#0A5C66]/8 text-[#0A5C66] text-[11.5px] font-bold px-3 py-1.5 rounded-full">
                            <i class="bi bi-currency-rupee text-[9px]"></i> ₹{{ number_format($minAmount ?? $amountFloor) }}&ndash;₹{{ number_format($maxAmount ?? $amountCeil) }} <i class="bi bi-x-lg text-[9px]"></i>
                        </a>
                    @endif
                </div>
            @endif
        </div>

        @if (count($tickerItems) > 0)
            @php
                // Pure-CSS crossfade rotator (no JS left in the main app to
                // drive it): every item shares one @keyframes cycle of
                // length $tickerTotal, each phase-shifted by a negative
                // animation-delay equal to its own slot's start time - the
                // standard trick for an infinite, JS-free slideshow.
                $tickerSlot = 4; // seconds each entry is shown
                $tickerCount = count($tickerItems);
                $tickerTotal = $tickerSlot * $tickerCount;
                $fadePct = round((0.5 / $tickerTotal) * 100, 3);
                $holdEndPct = round((($tickerSlot - 0.5) / $tickerTotal) * 100, 3);
                $slotEndPct = round(($tickerSlot / $tickerTotal) * 100, 3);
            @endphp
            <style>
                @keyframes explore-ticker-cycle {
                    0% { opacity: 0; transform: translateY(6px); }
                    {{ $fadePct }}% { opacity: 1; transform: translateY(0); }
                    {{ $holdEndPct }}% { opacity: 1; transform: translateY(0); }
                    {{ $slotEndPct }}% { opacity: 0; transform: translateY(-6px); }
                    100% { opacity: 0; transform: translateY(-6px); }
                }
                .explore-ticker-item {
                    animation: explore-ticker-cycle {{ $tickerTotal }}s ease-in-out infinite;
                }
            </style>
            <div class="px-4 mt-3 flex justify-center shrink-0">
                <div class="w-full rounded-[22px] bg-white/60 backdrop-blur-md border border-[#0A5C66]/8 shadow-[0_8px_30px_rgba(10,92,102,0.03)] p-3 flex flex-col justify-center overflow-hidden h-[64px] relative">
                    <!-- Tiny Live Indicator -->
                    <div class="absolute top-1.5 right-3.5 flex items-center gap-1.5 select-none">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#3CCF91] opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-[#3CCF91]"></span>
                        </span>
                        <span class="text-[8px] font-black tracking-wider text-slate-400 font-poppins uppercase">LIVE ACTIVITY</span>
                    </div>

                    <!-- Inner Animated Wrapper -->
                    <div class="relative overflow-hidden h-[40px] w-full mt-1">
                        @foreach ($tickerItems as $i => $item)
                            <div class="explore-ticker-item absolute inset-0 flex items-center justify-between gap-2 opacity-0"
                                style="animation-delay: -{{ $i * $tickerSlot }}s">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="w-7 h-7 rounded-full bg-[#0A5C66]/8 flex items-center justify-center shrink-0 text-[#0A5C66]">
                                        <i class="bi {{ $item['planIcon'] }} text-[11px]"></i>
                                    </div>
                                    <p class="text-[11.5px] font-semibold text-slate-600 font-poppins truncate leading-tight">
                                        <span class="font-black text-[#0A5C66]">{{ $item['name'] }}</span>
                                        from {{ $item['city'] }} invested
                                        <span class="font-black text-[#19B36B]">{{ $item['amount'] }}</span>
                                        in {{ $item['planTitle'] }}
                                    </p>
                                </div>
                                <span class="text-[9.5px] font-bold text-slate-400 shrink-0 font-poppins">{{ $item['timeAgo'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- JS-free multi-select-then-view Compare flow: card checkboxes use
             form="explore-compare-form" so they submit with this empty form
             as real compare[]=uuid query params, without needing to nest a
             <form> around the whole card grid (cards are anchor-heavy;
             nested forms would be invalid HTML). Deliberately kept OUTSIDE
             .explore-layout: that's a real `display:flex; gap:24px` layout
             box (see app.css), and even a visually-empty 0x0 form as its
             first flex child still consumes a full 24px gap before the
             actual content - form="" works regardless of DOM position, so
             it doesn't need to live inside the flex container at all. --}}
        <form id="explore-compare-form" method="GET" action="{{ route('explore.compare') }}"></form>

        <!-- EXPLORE LAYOUT -->
        <div class="explore-layout w-full flex-grow flex-1">
            <!-- PLANS LIST -->
            <div id="step-plans-list" class="flex-1 flex-col pb-safe overflow-y-auto overflow-x-hidden px-4 space-y-6">

            <!-- Section: Featured Dream Goals -->
            @php
                // Pure-CSS auto-slide (no JS left in the main app to drive a
                // setInterval-based carousel): the track's own @keyframes
                // hold on each slide then slide-transitions to the next,
                // looping infinitely. Dots share ONE keyframe (the same
                // "active during the first slot" shape used by the Live
                // Activity ticker) phase-shifted per-dot via a negative
                // animation-delay, so they land on their own slide's window
                // without needing a separate keyframe block per dot.
                $slideCount = $featuredPlans->count();
                $slideSeconds = 4;
                $slideTransition = 0.5;
                $slideTotal = $slideCount * $slideSeconds;
                $dotHoldEndPct = $slideCount > 0 ? round((($slideSeconds - $slideTransition) / $slideTotal) * 100, 3) : 0;
                $dotFadePct = $slideCount > 0 ? round(min(2, $dotHoldEndPct), 3) : 0;
            @endphp
            <div id="explore-featured-container" class="flex flex-col gap-2">
                <div class="flex justify-between items-end mb-1">
                    <div>
                        <span class="text-[9px] font-bold text-[#3CCF91] uppercase tracking-wider font-poppins">Specially Curated</span>
                        <h2 class="text-[15px] font-black text-slate-800 tracking-tight font-poppins">Featured Dream Goals</h2>
                    </div>
                    @if ($slideCount > 1)
                        <!-- Slider Dots -->
                        <div class="flex gap-1.5 pb-1">
                            @for ($i = 0; $i < $slideCount; $i++)
                                <div class="explore-featured-dot h-1 rounded-full {{ $i === 0 ? 'w-3.5 bg-[#0A5C66]' : 'w-1.5 bg-slate-300' }}" style="animation-delay: -{{ $i * $slideSeconds }}s"></div>
                            @endfor
                        </div>
                    @endif
                </div>

                @if ($slideCount > 1)
                    <style>
                        @keyframes explore-featured-slide {
                            @for ($i = 0; $i < $slideCount; $i++)
                                @php
                                    $holdStart = round(($i * $slideSeconds / $slideTotal) * 100, 3);
                                    $holdEnd = round((($i + 1) * $slideSeconds - $slideTransition) / $slideTotal * 100, 3);
                                @endphp
                                {{ $holdStart }}%, {{ $holdEnd }}% { transform: translateX(-{{ $i * 100 }}%); }
                            @endfor
                            100% { transform: translateX(-{{ ($slideCount - 1) * 100 }}%); }
                        }
                        .explore-featured-track {
                            animation: explore-featured-slide {{ $slideTotal }}s ease-in-out infinite;
                        }
                        #explore-featured-container:hover .explore-featured-track {
                            animation-play-state: paused;
                        }
                        @keyframes explore-featured-dot-active {
                            0% { width: 14px; background-color: #0A5C66; }
                            {{ $dotFadePct }}% { width: 14px; background-color: #0A5C66; }
                            {{ $dotHoldEndPct }}% { width: 14px; background-color: #0A5C66; }
                            {{ min(100, $dotHoldEndPct + $dotFadePct) }}% { width: 6px; background-color: #cbd5e1; }
                            100% { width: 6px; background-color: #cbd5e1; }
                        }
                        .explore-featured-dot {
                            animation: explore-featured-dot-active {{ $slideTotal }}s ease-in-out infinite;
                        }
                        @media (prefers-reduced-motion: reduce) {
                            .explore-featured-track, .explore-featured-dot { animation: none; }
                        }
                    </style>
                @endif

                <div class="relative w-full overflow-hidden rounded-[22px] shadow-[0_8px_25px_rgba(10,92,102,0.05)] group border border-slate-100/50">
                    <div id="explore-slider" class="flex w-full h-[105px] {{ $slideCount > 1 ? 'explore-featured-track' : '' }}">

                        @forelse ($featuredPlans as $plan)
                            @php $fp = $plan->toLegacyArray(); $fpInvestors = $plan->investorCount(); @endphp
                            <a href="{{ route('plan-details', $plan) }}" class="w-full h-full flex-shrink-0 relative overflow-hidden flex items-center p-3.5 snap-center">
                                <img src="{{ $fp['image'] }}" class="absolute inset-0 w-full h-full object-cover transform group-hover:scale-103 transition-transform duration-700" alt="{{ $fp['title'] }}">
                                <div class="absolute inset-0 bg-gradient-to-r from-[#04242F] via-[#0A5C66]/85 to-[#0A5C66]/15"></div>
                                <div class="absolute top-2 right-10 w-20 h-20 bg-[#3CCF91]/15 rounded-full blur-lg pointer-events-none animate-pulse"></div>

                                <div class="relative z-10 w-full flex justify-between items-center pr-1">
                                    <div class="flex flex-col gap-0.5 text-white max-w-[65%]">
                                        <div class="flex items-center gap-1.5 mb-0.5">
                                            <span style="background: rgba(10,92,102,0.14); color: #3CCF91;" class="backdrop-blur-md border border-[#3CCF91]/25 text-[8px] font-black px-2 py-0.5 rounded-[14px] uppercase tracking-wider font-poppins">{{ $fp['badge'] }}</span>
                                            <span class="bg-white/10 backdrop-blur-md text-white/90 text-[8px] font-bold px-2 py-0.5 rounded-full border border-white/10">{{ $fp['growthRate'] }}% Return</span>
                                        </div>
                                        <h3 class="font-extrabold text-[15px] leading-tight font-poppins text-white">{{ $fp['title'] }}</h3>
                                        <p class="text-white/80 text-[10px] leading-tight font-medium">{{ $fp['subtitle'] }}</p>
                                        <span class="text-[9px] text-[#3CCF91] font-bold mt-0.5">Target: {{ $fp['minGoal'] }} @if ($fpInvestors > 0) • {{ $fpInvestors }} joined @endif</span>
                                    </div>
                                    <span class="relative overflow-hidden bg-gradient-to-r from-[#0A5C66] to-[#0E7481] text-white border border-[#3CCF91]/35 font-extrabold text-[10px] px-3.5 py-1.5 rounded-xl hover:scale-105 active:scale-95 transition-all shadow-[0_4px_12px_rgba(10,92,102,0.25),0_0_8px_rgba(60,207,145,0.2)] font-poppins flex items-center gap-1 shrink-0">
                                        Buy Now <i class="bi bi-arrow-right text-[12px] text-white"></i>
                                    </span>
                                </div>
                            </a>
                        @empty
                            <div class="w-full h-full flex-shrink-0 flex items-center justify-center text-white/70 text-[12px] font-semibold font-poppins bg-[#0A5C66]">
                                No featured goals yet
                            </div>
                        @endforelse

                    </div>
                </div>
            </div>


            @php
                $badgeStyles = [
                    'Beginner' => ['bg' => 'rgba(60,207,145,0.12)', 'color' => '#3CCF91'],
                    'Trending' => ['bg' => 'rgba(10,92,102,0.14)', 'color' => '#0A5C66'],
                    'Fast Return' => ['bg' => 'linear-gradient(135deg,#0A5C66,#11727d)', 'color' => 'white'],
                    'Verified' => ['bg' => 'rgba(60,207,145,0.15)', 'color' => '#19B36B'],
                ];
                $defaultBadgeStyle = ['bg' => 'rgba(10,92,102,0.14)', 'color' => '#0A5C66'];
            @endphp

            @forelse ($plans as $plan)
                @php
                    $cp = $plan->toLegacyArray();
                    $cpInvestors = $plan->investorCount();
                    $cpStyle = $badgeStyles[$cp['badge']] ?? $defaultBadgeStyle;
                @endphp
                <!-- Plan Card: {{ $cp['title'] }} -->
                <div class="plan-card relative bg-white rounded-[28px] overflow-hidden shadow-[0_12px_40px_rgba(0,0,0,0.03)] hover:shadow-[0_20px_50px_rgba(10,92,102,0.08)] transition-all duration-500 group border border-slate-100/90 hover:-translate-y-1">
                    <div class="absolute inset-0 bg-gradient-to-b from-transparent to-[#0A5C66]/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
                    <!-- Subtle Teal Reflection Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-[#0A5C66]/[0.02] to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"></div>

                    <!-- Cover Image -->
                    <div class="relative w-full h-[160px] overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/40 to-transparent z-10"></div>
                        <img src="{{ $cp['image'] }}" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700" alt="{{ $cp['title'] }}">

                        {{-- Compare checkbox - real form input, submits via
                             explore-compare-form's form= attribute. A labeled
                             pill (not a bare icon square) so it reads as an
                             intentional control against a busy photo, with a
                             visibly different checked state rather than a
                             near-invisible opacity change. --}}
                        <label class="group absolute top-4 left-4 z-20 cursor-pointer flex items-center gap-1 pl-2 pr-2.5 h-7 rounded-full backdrop-blur-md border transition-colors bg-slate-900/40 border-white/25 has-[:checked]:bg-[#3CCF91] has-[:checked]:border-[#3CCF91]">
                            <input type="checkbox" name="compare[]" value="{{ $plan->uuid }}" form="explore-compare-form" class="hidden">
                            <i class="bi bi-check-lg text-[11px] text-white"></i>
                            <span class="text-[9.5px] font-bold text-white uppercase tracking-wide">
                                <span class="group-has-[:checked]:hidden">Compare</span>
                                <span class="hidden group-has-[:checked]:inline">Added</span>
                            </span>
                        </label>

                        <!-- Floating Badge -->
                        <div class="absolute top-4 right-4 z-20 flex flex-col items-end gap-1.5">
                            <div style="background: {{ $cpStyle['bg'] }}; color: {{ $cpStyle['color'] }};" class="backdrop-blur-md border border-[#3CCF91]/20 text-[9px] font-bold px-2.5 py-1 rounded-[14px] uppercase tracking-wider flex items-center gap-1 shadow-sm font-poppins">
                                <i class="bi {{ $badgeIcons[$cp['badge']] ?? $defaultBadgeIcon }}"></i> {{ $cp['badge'] }}
                            </div>
                            @if ($plan->marketing_badge)
                                <div class="bg-white/15 backdrop-blur-md border border-white/25 text-white text-[9px] font-bold px-2.5 py-1 rounded-[14px] shadow-sm font-poppins">
                                    {{ $plan->marketing_badge }}
                                </div>
                            @endif
                        </div>

                        <!-- Title Content in Header Overlay -->
                        <div class="absolute bottom-4 left-5 z-20 flex items-center gap-3.5 w-[calc(100%-40px)]">
                            <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 p-2.5 flex items-center justify-center shrink-0 shadow-lg text-[#3CCF91] group-hover:scale-105 transition-transform duration-300">
                                <i class="bi {{ $cp['icon'] }} text-[22px]"></i>
                            </div>
                            <div>
                                <h3 class="text-white font-extrabold text-[19px] leading-tight font-poppins tracking-tight">{{ $cp['title'] }}</h3>
                                <p class="text-white/80 text-[10px] font-semibold font-poppins">{{ $cp['subtitle'] }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 relative z-20 bg-white/95 backdrop-blur-md">
                        <!-- Stats Grid -->
                        <div class="grid grid-cols-2 gap-2 mb-4">
                            <!-- Expected Growth -->
                            <div class="bg-slate-50/60 backdrop-blur-sm border border-slate-200/40 rounded-xl p-2 flex items-center gap-2 shadow-sm hover:border-[#0A5C66]/20 transition-all duration-300">
                                <div class="w-7 h-7 rounded-lg bg-[#0A5C66]/5 flex items-center justify-center text-[#0A5C66] shrink-0 border border-[#0A5C66]/10 shadow-[0_2px_8px_rgba(10,92,102,0.06)]">
                                    <i class="bi bi-graph-up-arrow text-[14px] text-[#0A5C66]"></i>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[9px] font-semibold text-slate-400/70 uppercase tracking-wider font-poppins truncate leading-tight">Expected Growth</span>
                                    <span class="text-[12.5px] font-black text-[#0A5C66] font-poppins truncate leading-tight">{{ $cp['expectedGrowth'] }}</span>
                                </div>
                            </div>
                            <!-- Lock Duration -->
                            <div class="bg-slate-50/60 backdrop-blur-sm border border-slate-200/40 rounded-xl p-2 flex items-center gap-2 shadow-sm hover:border-[#0A5C66]/20 transition-all duration-300">
                                <div class="w-7 h-7 rounded-lg bg-[#0A5C66]/5 flex items-center justify-center text-[#0A5C66] shrink-0 border border-[#0A5C66]/10 shadow-[0_2px_8px_rgba(10,92,102,0.06)]">
                                    <i class="bi bi-calendar2-check text-[14px] text-[#0A5C66]"></i>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[9px] font-semibold text-slate-400/70 uppercase tracking-wider font-poppins truncate leading-tight">Lock Duration</span>
                                    <span class="text-[12.5px] font-black text-[#0A5C66] font-poppins truncate leading-tight">{{ $cp['lockDuration'] }}</span>
                                </div>
                            </div>
                            <!-- Plan Investment -->
                            <div class="bg-slate-50/60 backdrop-blur-sm border border-slate-200/40 rounded-xl p-2 flex items-center gap-2 shadow-sm hover:border-[#0A5C66]/20 transition-all duration-300">
                                <div class="w-7 h-7 rounded-lg bg-[#0A5C66]/5 flex items-center justify-center text-[#0A5C66] shrink-0 border border-[#0A5C66]/10 shadow-[0_2px_8px_rgba(10,92,102,0.06)]">
                                    <i class="bi bi-currency-rupee text-[14px] text-[#0A5C66]"></i>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[9px] font-semibold text-slate-400/70 uppercase tracking-wider font-poppins truncate leading-tight">Plan Investment</span>
                                    <span class="text-[12.5px] font-black text-[#0A5C66] font-poppins truncate leading-tight">{{ $cp['planInvestment'] }}</span>
                                </div>
                            </div>
                            <!-- Daily Profit -->
                            <div class="bg-slate-50/60 backdrop-blur-sm border border-slate-200/40 rounded-xl p-2 flex items-center gap-2 shadow-sm hover:border-[#0A5C66]/20 transition-all duration-300">
                                <div class="w-7 h-7 rounded-lg bg-[#3CCF91]/10 flex items-center justify-center text-[#3CCF91] shrink-0 border border-[#3CCF91]/20 shadow-[0_2px_8px_rgba(60,207,145,0.06)]">
                                    <i class="bi bi-wallet2 text-[14px] text-[#3CCF91]"></i>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[9px] font-semibold text-slate-400/70 uppercase tracking-wider font-poppins truncate leading-tight">Daily Profit</span>
                                    <span class="text-[12.5px] font-black text-[#19B36B] font-poppins truncate leading-tight">{{ $cp['dailyProfit'] }}</span>
                                </div>
                            </div>
                            <!-- Total Return -->
                            <div class="col-span-2 bg-slate-50/60 backdrop-blur-sm border border-slate-200/40 rounded-xl p-2 flex items-center gap-2 shadow-sm hover:border-[#0A5C66]/20 transition-all duration-300">
                                <div class="w-7 h-7 rounded-lg bg-[#3CCF91]/10 flex items-center justify-center text-[#3CCF91] shrink-0 border border-[#3CCF91]/20 shadow-[0_2px_8px_rgba(60,207,145,0.06)]">
                                    <i class="bi bi-piggy-bank text-[14px] text-[#3CCF91]"></i>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[9px] font-semibold text-slate-400/70 uppercase tracking-wider font-poppins truncate leading-tight">Total Return</span>
                                    <span class="text-[12.5px] font-black text-[#19B36B] font-poppins truncate leading-tight">{{ $cp['totalReturn'] }}</span>
                                </div>
                            </div>
                        </div>

                        @if ($plan->unlock_enabled || $plan->highlights || $plan->risk_level || $plan->max_slots !== null)
                            <!-- Premium chips: unlock state, highlights, risk, available slots -->
                            <div class="flex flex-wrap items-center gap-1.5 mb-3">
                                <x-plan-badge :plan="$plan" :unlocked="! $plan->unlock_enabled || ! $plan->requires_plan_id || $purchasedPlanIds->contains($plan->requires_plan_id)" />
                                @if ($plan->risk_level)
                                    <span class="inline-flex items-center gap-1 bg-emerald-50 border border-emerald-200 text-emerald-700 text-[9.5px] font-bold px-2.5 py-1 rounded-full">
                                        <i class="bi bi-shield-check text-[9px]"></i> {{ $plan->risk_level }} Risk
                                    </span>
                                @endif
                                @foreach (array_slice($plan->highlights ?? [], 0, 2) as $highlight)
                                    <span class="inline-flex items-center gap-1 bg-[#0A5C66]/5 border border-[#0A5C66]/10 text-[#0A5C66] text-[9.5px] font-bold px-2.5 py-1 rounded-full">
                                        <i class="bi bi-stars text-[9px]"></i> {{ $highlight }}
                                    </span>
                                @endforeach
                            </div>
                            @if ($plan->max_slots !== null)
                                @php $slotsLeft = $plan->availableSlots(); $slotsPct = $plan->max_slots > 0 ? min(100, round((($plan->max_slots - $slotsLeft) / $plan->max_slots) * 100)) : 0; @endphp
                                <div class="mb-3">
                                    <div class="flex items-center justify-between text-[9.5px] font-bold text-slate-500 mb-1">
                                        <span>Available Slots</span>
                                        <span>{{ number_format($plan->max_slots - $slotsLeft) }} / {{ number_format($plan->max_slots) }}</span>
                                    </div>
                                    <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r from-[#0A5C66] to-[#3CCF91]" style="width: {{ $slotsPct }}%"></div>
                                    </div>
                                </div>
                            @endif
                        @endif

                        <!-- Bottom CTA & Avatars -->
                        <div class="flex flex-col gap-3 border-t border-slate-100/60 pt-4 mt-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="flex items-center -space-x-1.5 relative shrink-0">
                                    <div class="relative w-6 h-6 rounded-full border border-white bg-slate-100 overflow-hidden shadow-sm">
                                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed={{ $plan->id }}A" class="w-full h-full">
                                    </div>
                                    <div class="relative w-6 h-6 rounded-full border border-white bg-slate-100 overflow-hidden shadow-sm">
                                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed={{ $plan->id }}B" class="w-full h-full">
                                    </div>
                                    <div class="relative w-6 h-6 rounded-full border border-white bg-slate-100 overflow-hidden shadow-sm">
                                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed={{ $plan->id }}C" class="w-full h-full">
                                        <!-- Online pulse glow on the top avatar -->
                                        <span class="absolute bottom-0 right-0 w-1.5 h-1.5 bg-[#3CCF91] rounded-full border border-white shadow-[0_0_6px_#3CCF91]"></span>
                                    </div>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <div class="flex items-center gap-1 min-w-0">
                                        <span class="w-1.5 h-1.5 bg-[#3CCF91] rounded-full animate-pulse shrink-0 shadow-[0_0_6px_#3CCF91]"></span>
                                        <span class="text-[10px] font-extrabold text-slate-700 font-poppins leading-none truncate">
                                            @if ($cpInvestors > 0)
                                                Trusted by {{ number_format($cpInvestors) }} investor{{ $cpInvestors === 1 ? '' : 's' }}
                                            @else
                                                Be the first to invest
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2.5">
                                <div class="flex items-center gap-2 min-w-0 bg-gradient-to-r from-[#0A5C66]/8 to-[#3CCF91]/8 border border-[#0A5C66]/10 rounded-xl pl-1.5 pr-3 py-1.5">
                                    <div class="w-6 h-6 rounded-lg bg-white flex items-center justify-center shrink-0 shadow-sm">
                                        <i class="bi bi-lightning-charge text-[10px] text-[#3CCF91]"></i>
                                    </div>
                                    <div class="flex flex-col min-w-0 leading-none">
                                        <span class="text-[8.5px] font-bold text-[#0A5C66]/60 uppercase tracking-wider font-poppins">Invest</span>
                                        <span class="text-[13.5px] font-black text-[#0A5C66] font-poppins truncate">{{ $cp['planInvestment'] }}</span>
                                    </div>
                                </div>
                                <a href="{{ route('plan-details', $plan) }}" class="relative overflow-hidden bg-gradient-to-r from-[#0A5C66] to-[#0E7481] text-white font-extrabold text-[12px] px-5 py-2.5 rounded-xl transition-all duration-300 hover:scale-[1.03] hover:shadow-[0_6px_20px_rgba(10,92,102,0.25)] active:scale-95 btn-ripple border border-[#0A5C66]/20 font-poppins shadow-md flex items-center justify-center gap-1.5 hover:brightness-105 shrink-0 whitespace-nowrap">
                                    Buy Now <i class="bi bi-arrow-right text-[14px] text-white"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <!-- No Results Found View -->
                <div class="flex flex-col items-center justify-center py-12 px-4 text-center bg-white/40 backdrop-blur-md rounded-[28px] border border-slate-200/40 shadow-sm w-full mb-6">
                    <div class="w-16 h-16 bg-[#0A5C66]/5 rounded-full flex items-center justify-center mb-4 text-[#0A5C66] mx-auto shadow-inner">
                        <i class="bi bi-zoom-out text-[24px]"></i>
                    </div>
                    @php $anyFilterActive = $selectedBadge || $selectedDuration || $searchQuery !== '' || $minAmount !== null || $maxAmount !== null; @endphp
                    <h4 class="text-[16px] font-black text-slate-800 mb-1 font-poppins">No Matching Goals</h4>
                    <p class="text-[13px] text-slate-500 font-medium leading-relaxed max-w-[240px] mb-6 mx-auto">
                        @if ($searchQuery !== '')
                            We couldn't find any plans matching "{{ $searchQuery }}". Try another search or filter.
                        @elseif ($anyFilterActive)
                            We couldn't find any plans matching your active filters. Try clearing them.
                        @else
                            No investment plans are available right now. Check back soon.
                        @endif
                    </p>
                    @if ($anyFilterActive)
                        <a href="{{ route('explore') }}" class="px-5 py-2.5 bg-[#0A5C66] text-white font-extrabold text-[12.5px] rounded-xl hover:bg-[#148e9e] active:scale-95 transition-all shadow-md font-poppins">
                            Reset All Filters
                        </a>
                    @endif
                </div>
            @endforelse

            <!-- Trust Section -->
            <div class="flex flex-col gap-4 text-center items-center">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest font-poppins">GullakPe Trust & Security</span>
                <div class="grid grid-cols-3 gap-2 w-full">
                    <div class="bg-white/40 backdrop-blur-sm border border-slate-200/50 rounded-xl py-2 px-1 flex items-center justify-center gap-1.5 shadow-[0_2px_8px_rgba(0,0,0,0.01)]">
                        <i class="bi bi-bank2 text-[11px] text-[#0A5C66]"></i>
                        <span class="text-[10px] font-bold text-slate-600 font-poppins">RBI Reg.</span>
                    </div>
                    <div class="bg-white/40 backdrop-blur-sm border border-slate-200/50 rounded-xl py-2 px-1 flex items-center justify-center gap-1.5 shadow-[0_2px_8px_rgba(0,0,0,0.01)]">
                        <i class="bi bi-cash-coin text-[11px] text-[#0A5C66]"></i>
                        <span class="text-[10px] font-bold text-slate-600 font-poppins">Safe Pay</span>
                    </div>
                    <div class="bg-white/40 backdrop-blur-sm border border-slate-200/50 rounded-xl py-2 px-1 flex items-center justify-center gap-1.5 shadow-[0_2px_8px_rgba(0,0,0,0.01)]">
                        <i class="bi bi-check-circle-fill text-[11px] text-[#0A5C66]"></i>
                        <span class="text-[10px] font-bold text-slate-600 font-poppins">Verified</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Floating "Compare" submit button - submits explore-compare-form
         (real checkboxes on the cards above) as a real GET request, no JS. --}}
    <button type="submit" form="explore-compare-form" class="fixed bottom-24 right-4 z-40 flex items-center gap-2 h-12 px-4 rounded-full bg-[#0A5C66] text-white font-bold text-[12.5px] shadow-lg shadow-[#0A5C66]/25 active:scale-95 transition-all font-poppins">
        <i class="bi bi-columns-gap text-[14px]"></i> Compare
    </button>

        <!-- REAL SEARCH PANEL - toggled via #explore-search-toggle (CSS
             peer-checked, no JS). A real GET form to this same page's own
             route; badge/duration/amount filters already active are
             preserved as hidden inputs so search composes with them. -->
        <div class="hidden peer-checked/search:flex fixed inset-0 z-[200] bg-slate-900/60 backdrop-blur-md items-start justify-center p-4">
            <div class="w-full max-w-lg bg-white rounded-[24px] shadow-2xl overflow-hidden mt-16 flex flex-col border border-slate-100">
                <form method="GET" action="{{ route('explore') }}" class="p-4 border-b border-slate-100 flex items-center gap-3">
                    @if ($selectedBadge)<input type="hidden" name="badge" value="{{ $selectedBadge }}">@endif
                    @if ($selectedDuration)<input type="hidden" name="duration" value="{{ $selectedDuration }}">@endif
                    @if ($minAmount !== null)<input type="hidden" name="min_amount" value="{{ $minAmount }}">@endif
                    @if ($maxAmount !== null)<input type="hidden" name="max_amount" value="{{ $maxAmount }}">@endif
                    <div class="relative flex-1 flex items-center h-[54px] bg-slate-50 border border-slate-200/60 rounded-[18px] focus-within:bg-white focus-within:ring-2 focus-within:ring-[#3CCF91]/50 focus-within:border-[#3CCF91] transition-all duration-300">
                        <i class="bi bi-search text-slate-400 absolute left-4 text-[16px]"></i>
                        <input type="text" name="q" value="{{ $searchQuery }}" enterkeyhint="search" placeholder="Search plans by name..." autofocus class="w-full h-full pl-11 pr-14 text-[15px] font-medium text-slate-800 placeholder-slate-400 bg-transparent border-none outline-none focus:ring-0">
                        <button type="submit" class="absolute right-2 w-9 h-9 rounded-full bg-[#0A5C66] hover:bg-[#148e9e] active:scale-95 flex items-center justify-center text-white transition-all shadow-md">
                            <i class="bi bi-search text-[13px]"></i>
                        </button>
                    </div>
                    <label for="explore-search-toggle" class="text-[14.5px] font-bold text-[#0A5C66] px-2 py-3 hover:opacity-85 active:scale-95 transition-all cursor-pointer">Cancel</label>
                </form>

                @if ($searchQuery !== '')
                    <div class="p-4 bg-slate-50/30 flex items-center justify-between">
                        <span class="text-[12.5px] font-semibold text-slate-500">{{ $plans->count() }} result{{ $plans->count() === 1 ? '' : 's' }} for "{{ $searchQuery }}"</span>
                        <a href="{{ route('explore', array_filter(['badge' => $selectedBadge, 'duration' => $selectedDuration, 'min_amount' => $minAmount, 'max_amount' => $maxAmount])) }}" class="text-[12.5px] font-bold text-[#0A5C66] hover:underline">Clear</a>
                    </div>
                @endif
            </div>
        </div>

        <!-- REAL FILTER PANEL - toggled via #explore-filter-toggle. Every
             field here is a real Plan column (badge, lock_duration,
             growth_rate, investment_amount) - the old sheet's "Goal
             Type"/"Risk Level"/"Special Filters" checkboxes never matched
             any real column and always did nothing. -->
        <div class="hidden peer-checked/filter:flex fixed inset-0 z-[200] items-start justify-start bg-slate-900/60 backdrop-blur-md">
            <div class="w-[360px] max-w-[88vw] bg-white rounded-r-[28px] shadow-2xl flex flex-col h-full border border-slate-100">
                <div class="px-6 pb-4 pt-5 border-b border-slate-100 flex items-center justify-between shrink-0">
                    <h3 class="text-[20px] font-black text-[#0A5C66] font-poppins tracking-tight flex items-center gap-2">
                        <i class="bi bi-sliders text-[#3CCF91] text-[16px]"></i> Filter Goals
                    </h3>
                    <a href="{{ route('explore', array_filter(['q' => $searchQuery])) }}" class="text-[13.5px] font-bold text-slate-400 hover:text-[#0A5C66] active:scale-95 transition-all">Reset All</a>
                </div>

                <form method="GET" action="{{ route('explore') }}" class="flex-1 overflow-y-auto px-6 py-5 space-y-6 hide-scrollbar bg-slate-50/10">
                    @if ($searchQuery !== '')<input type="hidden" name="q" value="{{ $searchQuery }}">@endif

                    @if ($chips->isNotEmpty())
                        <div>
                            <h4 class="text-[12.5px] font-extrabold text-[#0A5C66] uppercase tracking-wider mb-3 font-poppins">Category</h4>
                            <div class="flex flex-wrap gap-2.5">
                                <label class="relative cursor-pointer select-none active:scale-95 transition-transform">
                                    <input type="radio" name="badge" value="" class="hidden peer" {{ $selectedBadge === null ? 'checked' : '' }}>
                                    <span class="px-4.5 py-2.5 rounded-full border border-slate-200 text-[12.5px] font-bold flex items-center gap-1.5 text-slate-700 bg-white peer-checked:bg-gradient-to-r peer-checked:from-[#0A5C66] peer-checked:to-[#0E7481] peer-checked:text-white peer-checked:border-transparent transition-all">
                                        <i class="bi bi-compass text-[11px]"></i> All
                                    </span>
                                </label>
                                @foreach ($chips as $chip)
                                    <label class="relative cursor-pointer select-none active:scale-95 transition-transform">
                                        <input type="radio" name="badge" value="{{ $chip['value'] }}" class="hidden peer" {{ $selectedBadge === $chip['value'] ? 'checked' : '' }}>
                                        <span class="px-4.5 py-2.5 rounded-full border border-slate-200 text-[12.5px] font-bold flex items-center gap-1.5 text-slate-700 bg-white peer-checked:bg-gradient-to-r peer-checked:from-[#0A5C66] peer-checked:to-[#0E7481] peer-checked:text-white peer-checked:border-transparent transition-all">
                                            <i class="bi {{ $chip['icon'] }} text-[11px]"></i> {{ $chip['label'] }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <hr class="border-slate-100">
                    @endif

                    @if ($durations->isNotEmpty())
                        <div>
                            <h4 class="text-[12.5px] font-extrabold text-[#0A5C66] uppercase tracking-wider mb-3 font-poppins">Plan Duration</h4>
                            <div class="flex flex-wrap gap-2.5">
                                <label class="relative cursor-pointer select-none active:scale-95 transition-transform">
                                    <input type="radio" name="duration" value="" class="hidden peer" {{ $selectedDuration === null ? 'checked' : '' }}>
                                    <span class="px-4.5 py-2.5 rounded-full border border-slate-200 text-[12.5px] font-bold block text-slate-700 bg-white peer-checked:bg-gradient-to-r peer-checked:from-[#0A5C66] peer-checked:to-[#0E7481] peer-checked:text-white peer-checked:border-transparent transition-all">Any</span>
                                </label>
                                @foreach ($durations as $duration)
                                    <label class="relative cursor-pointer select-none active:scale-95 transition-transform">
                                        <input type="radio" name="duration" value="{{ $duration }}" class="hidden peer" {{ $selectedDuration === $duration ? 'checked' : '' }}>
                                        <span class="px-4.5 py-2.5 rounded-full border border-slate-200 text-[12.5px] font-bold block text-slate-700 bg-white peer-checked:bg-gradient-to-r peer-checked:from-[#0A5C66] peer-checked:to-[#0E7481] peer-checked:text-white peer-checked:border-transparent transition-all">{{ $duration }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <hr class="border-slate-100">
                    @endif

                    @if ($growthRates->isNotEmpty())
                        <div>
                            <h4 class="text-[12.5px] font-extrabold text-[#0A5C66] uppercase tracking-wider mb-3 font-poppins">Return Rate</h4>
                            <div class="flex flex-wrap gap-2.5">
                                <label class="relative cursor-pointer select-none active:scale-95 transition-transform">
                                    <input type="radio" name="min_growth" value="" class="hidden peer" {{ $selectedMinGrowth === null ? 'checked' : '' }}>
                                    <span class="px-4.5 py-2.5 rounded-full border border-slate-200 text-[12.5px] font-bold block text-slate-700 bg-white peer-checked:bg-gradient-to-r peer-checked:from-[#0A5C66] peer-checked:to-[#0E7481] peer-checked:text-white peer-checked:border-transparent transition-all">Any</span>
                                </label>
                                @foreach ($growthRates as $rate)
                                    <label class="relative cursor-pointer select-none active:scale-95 transition-transform">
                                        <input type="radio" name="min_growth" value="{{ $rate }}" class="hidden peer" {{ $selectedMinGrowth === $rate ? 'checked' : '' }}>
                                        <span class="px-4.5 py-2.5 rounded-full border border-slate-200 text-[12.5px] font-bold block text-slate-700 bg-white peer-checked:bg-gradient-to-r peer-checked:from-[#0A5C66] peer-checked:to-[#0E7481] peer-checked:text-white peer-checked:border-transparent transition-all">{{ $rate }}%+</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <hr class="border-slate-100">
                    @endif

                    @if ($riskLevels->isNotEmpty())
                        <div>
                            <h4 class="text-[12.5px] font-extrabold text-[#0A5C66] uppercase tracking-wider mb-3 font-poppins">Risk Level</h4>
                            <div class="flex flex-wrap gap-2.5">
                                <label class="relative cursor-pointer select-none active:scale-95 transition-transform">
                                    <input type="radio" name="risk_level" value="" class="hidden peer" {{ $selectedRiskLevel === null ? 'checked' : '' }}>
                                    <span class="px-4.5 py-2.5 rounded-full border border-slate-200 text-[12.5px] font-bold block text-slate-700 bg-white peer-checked:bg-gradient-to-r peer-checked:from-[#0A5C66] peer-checked:to-[#0E7481] peer-checked:text-white peer-checked:border-transparent transition-all">Any</span>
                                </label>
                                @foreach ($riskLevels as $risk)
                                    <label class="relative cursor-pointer select-none active:scale-95 transition-transform">
                                        <input type="radio" name="risk_level" value="{{ $risk }}" class="hidden peer" {{ $selectedRiskLevel === $risk ? 'checked' : '' }}>
                                        <span class="px-4.5 py-2.5 rounded-full border border-slate-200 text-[12.5px] font-bold block text-slate-700 bg-white peer-checked:bg-gradient-to-r peer-checked:from-[#0A5C66] peer-checked:to-[#0E7481] peer-checked:text-white peer-checked:border-transparent transition-all">{{ $risk }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <hr class="border-slate-100">
                    @endif

                    @php
                        $sortOptions = [
                            'lowest_investment' => 'Lowest Investment',
                            'highest_return' => 'Highest Return',
                            'newest' => 'Newest',
                            'ending_soon' => 'Ending Soon',
                            'most_popular' => 'Most Popular',
                        ];
                    @endphp
                    <div>
                        <h4 class="text-[12.5px] font-extrabold text-[#0A5C66] uppercase tracking-wider mb-3 font-poppins">Sort By</h4>
                        <div class="flex flex-wrap gap-2.5">
                            <label class="relative cursor-pointer select-none active:scale-95 transition-transform">
                                <input type="radio" name="sort" value="" class="hidden peer" {{ $sort === null ? 'checked' : '' }}>
                                <span class="px-4.5 py-2.5 rounded-full border border-slate-200 text-[12.5px] font-bold block text-slate-700 bg-white peer-checked:bg-gradient-to-r peer-checked:from-[#0A5C66] peer-checked:to-[#0E7481] peer-checked:text-white peer-checked:border-transparent transition-all">Default</span>
                            </label>
                            @foreach ($sortOptions as $value => $label)
                                <label class="relative cursor-pointer select-none active:scale-95 transition-transform">
                                    <input type="radio" name="sort" value="{{ $value }}" class="hidden peer" {{ $sort === $value ? 'checked' : '' }}>
                                    <span class="px-4.5 py-2.5 rounded-full border border-slate-200 text-[12.5px] font-bold block text-slate-700 bg-white peer-checked:bg-gradient-to-r peer-checked:from-[#0A5C66] peer-checked:to-[#0E7481] peer-checked:text-white peer-checked:border-transparent transition-all">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <hr class="border-slate-100">

                    @php
                        $sliderMin = (int) floor($amountFloor);
                        $sliderMax = max($sliderMin + 1, (int) ceil($amountCeil));
                        $sliderMinVal = $minAmount !== null ? (int) $minAmount : $sliderMin;
                        $sliderMaxVal = $maxAmount !== null ? (int) $maxAmount : $sliderMax;
                        $barLeftPct = $sliderMax > $sliderMin ? (($sliderMinVal - $sliderMin) / ($sliderMax - $sliderMin)) * 100 : 0;
                        $barRightPct = $sliderMax > $sliderMin ? (($sliderMaxVal - $sliderMin) / ($sliderMax - $sliderMin)) * 100 : 100;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-[12.5px] font-extrabold text-[#0A5C66] uppercase tracking-wider font-poppins">Investment Range</h4>
                            <span class="text-[12.5px] font-black text-[#0A5C66] font-poppins bg-[#0A5C66]/5 px-2.5 py-1 rounded-lg">₹{{ number_format($sliderMinVal) }}&ndash;₹{{ number_format($sliderMaxVal) }}</span>
                        </div>

                        <!-- Real dual-bar: two native range inputs stacked on
                             one track (no JS to sync a single custom
                             thumb-pair, so each is its own real, honestly
                             separate slider) - the filled segment between
                             them is drawn from their actual current values. -->
                        <div class="relative w-full h-8 flex items-center mb-1">
                            <div class="absolute left-0 right-0 h-2 bg-slate-150 rounded-full"></div>
                            <div class="absolute h-2 bg-gradient-to-r from-[#0A5C66] to-[#3CCF91] rounded-full" style="left: {{ $barLeftPct }}%; right: {{ 100 - $barRightPct }}%;"></div>
                            <input type="range" name="min_amount" min="{{ $sliderMin }}" max="{{ $sliderMax }}" value="{{ $sliderMinVal }}" class="absolute w-full h-2 bg-transparent appearance-none cursor-pointer accent-[#0A5C66] range-thumb-only">
                            <input type="range" name="max_amount" min="{{ $sliderMin }}" max="{{ $sliderMax }}" value="{{ $sliderMaxVal }}" class="absolute w-full h-2 bg-transparent appearance-none cursor-pointer accent-[#0A5C66] range-thumb-only">
                        </div>
                        <div class="flex justify-between text-[11px] font-bold text-slate-400 select-none">
                            <span>₹{{ number_format($sliderMin) }}</span>
                            <span>₹{{ number_format($sliderMax) }}</span>
                        </div>
                    </div>

                    <button type="submit" class="relative overflow-hidden w-full h-[54px] bg-gradient-to-r from-[#0A5C66] to-[#3CCF91] text-white font-extrabold text-[15px] rounded-[18px] shadow-[0_8px_25px_rgba(10,92,102,0.2)] hover:scale-[1.02] active:scale-98 transition-all duration-300 flex items-center justify-center gap-1.5 font-poppins">
                        Apply Filters
                    </button>
                    <label for="explore-filter-toggle" class="block text-center text-[13px] font-bold text-slate-400 hover:text-[#0A5C66] cursor-pointer transition-all">Close</label>
                </form>
            </div>
            <label for="explore-filter-toggle" class="flex-1 h-full cursor-pointer"></label>
        </div>

    </div>
@endsection

