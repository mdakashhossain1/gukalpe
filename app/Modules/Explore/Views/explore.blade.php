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

        <!-- EXPLORE LAYOUT -->
        <div class="explore-layout w-full flex-grow flex-1">
            <!-- PLANS LIST -->
            <div id="step-plans-list" class="flex-1 flex-col pb-safe overflow-y-auto overflow-x-hidden px-4 pt-2 space-y-5">

            @forelse ($plans as $plan)
                @php
                    $cp = $plan->toLegacyArray();
                    $isFlexible = $plan->isFlexibleAmount();
                    $priceLabel = $isFlexible
                        ? '₹'.number_format((float) $plan->min_investment_amount, 0).'&ndash;₹'.number_format((float) $plan->max_investment_amount, 0)
                        : '₹'.number_format((float) $plan->investment_amount, (float) $plan->investment_amount == (int) $plan->investment_amount ? 0 : 2);
                    $priceCaption = $isFlexible ? 'Flexible Amount' : 'One-Time Investment';
                    
                    $mBadge = strtoupper($plan->marketing_badge ?? '');
                    if (str_contains($mBadge, 'POPULAR')) {
                        $mBadgeClass = 'bg-[#FFF4E5] text-[#FF8A00]';
                        $mBadgeIcon = 'bi-star-fill text-[#FF8A00]';
                    } elseif (str_contains($mBadge, 'RETURN')) {
                        $mBadgeClass = 'bg-[#E8F8F0] text-[#19B36B]';
                        $mBadgeIcon = 'bi-graph-up-arrow text-[#19B36B]';
                    } elseif (str_contains($mBadge, 'STEADY') || str_contains($mBadge, 'GROWTH')) {
                        $mBadgeClass = 'bg-[#F3E8FF] text-[#9333EA]';
                        $mBadgeIcon = 'bi-trophy text-[#9333EA]';
                    } else {
                        $mBadgeClass = 'bg-[#FFF4E5] text-[#FF8A00]';
                        $mBadgeIcon = $plan->marketing_badge_icon ? 'bi '.$plan->marketing_badge_icon : 'bi-star-fill text-[#FF8A00]';
                    }

                    $cBadge = strtoupper($cp['badge'] ?? '');
                    if ($cBadge === 'STARTER') {
                        $tBadgeClass = 'bg-[#E8F8EE] text-[#22C55E]';
                    } elseif ($cBadge === 'BEGINNER') {
                        $tBadgeClass = 'bg-[#EFF6FF] text-[#3B82F6]';
                    } elseif ($cBadge === 'PREMIUM') {
                        $tBadgeClass = 'bg-[#FEF3C7] text-[#D97706]';
                    } else {
                        $tBadgeClass = 'bg-[#E8F8EE] text-[#22C55E]';
                    }
                @endphp
                <div class="relative bg-white rounded-[20px] sm:rounded-[26px] border border-slate-100/90 shadow-[0_4px_24px_rgba(0,0,0,0.03)] hover:shadow-[0_8px_32px_rgba(0,0,0,0.07)] transition-all duration-300 p-4 sm:p-6">

                    <!-- TOP BADGES ROW -->
                    <div class="flex items-center justify-between gap-2 mb-3 sm:mb-4">
                        @if ($plan->marketing_badge)
                            <span class="inline-flex items-center gap-1 sm:gap-1.5 {{ $mBadgeClass }} text-[9.5px] sm:text-[11px] font-extrabold uppercase tracking-wide px-2.5 sm:px-3.5 py-0.5 sm:py-1 rounded-full shadow-2xs">
                                <i class="bi {{ $mBadgeIcon }} text-[9.5px] sm:text-[11px]"></i>
                                <span>{{ $plan->marketing_badge }}</span>
                            </span>
                        @else
                            <span></span>
                        @endif

                        <span class="inline-flex items-center text-[9.5px] sm:text-[10.5px] font-black uppercase tracking-wider px-2.5 sm:px-3 py-0.5 sm:py-1 rounded-full {{ $tBadgeClass }}">
                            {{ $cp['badge'] }}
                        </span>
                    </div>

                    <!-- TITLE & SUBTITLE HEADER (FULL WIDTH, NATURAL WRAPPING) -->
                    <div class="mb-3">
                        <h3 class="text-[17px] sm:text-[21px] font-extrabold text-[#0D1F3C] font-poppins leading-tight tracking-tight">{{ $cp['title'] }}</h3>
                        <p class="text-[11.5px] sm:text-[13px] text-slate-500 font-medium leading-relaxed mt-0.5 max-w-full">{{ $cp['subtitle'] }}</p>
                    </div>

                    <!-- MAIN CARD CONTENT (HORIZONTAL ICON + METRICS) -->
                    <div class="grid grid-cols-[auto_1fr] md:grid-cols-[auto_1fr_auto] gap-3.5 sm:gap-6 items-center">

                        <!-- LEFT: CIRCULAR ICON CONTAINER (PROMINENT POD) -->
                        <div class="flex items-center justify-center shrink-0">
                            <div class="w-18 h-18 sm:w-28 sm:h-28 rounded-full bg-[#F2F7F8] border border-[#E4EFEF] flex items-center justify-center shadow-inner relative overflow-hidden group">
                                @if(!empty($cp['image']) && (str_starts_with($cp['image'], 'http') || str_contains($cp['image'], '/assets/') || str_contains($cp['image'], 'unsplash')))
                                    <img src="{{ $cp['image'] }}" alt="{{ $cp['title'] }}" class="w-12 h-12 sm:w-20 sm:h-20 object-contain transition-transform duration-300 group-hover:scale-105">
                                @else
                                    <i class="bi {{ $cp['icon'] ?? 'bi-shield-check' }} text-[32px] sm:text-[44px] text-[#0A5C66] transition-transform duration-300 group-hover:scale-110"></i>
                                @endif
                            </div>
                        </div>

                        <!-- CENTER: METRICS & TRUST -->
                        <div class="min-w-0 flex flex-col justify-between">
                            <!-- 3-COLUMN METRICS GRID WITH VERTICAL DIVIDERS -->
                            <div class="flex items-center gap-2 sm:gap-6 py-0.5 overflow-x-auto hide-scrollbar">
                                <!-- Col 1: Interest Rate -->
                                <div class="pr-2 sm:pr-6 border-r border-slate-200/90 shrink-0">
                                    <p class="text-[7.5px] sm:text-[10px] font-bold text-slate-400 uppercase tracking-wider font-poppins mb-0.5 sm:mb-1 leading-tight">Interest Rate<br>(Yearly)</p>
                                    <p class="text-[16px] sm:text-[24px] font-black text-[#19B36B] font-poppins leading-none">{{ $cp['growthRate'] }}%</p>
                                </div>
                                <!-- Col 2: Total Return -->
                                <div class="px-2 sm:px-6 border-r border-slate-200/90 shrink-0">
                                    <p class="text-[7.5px] sm:text-[10px] font-bold text-slate-400 uppercase tracking-wider font-poppins mb-0.5 sm:mb-1">Total Return</p>
                                    <p class="text-[16px] sm:text-[24px] font-black text-[#19B36B] font-poppins leading-none">{{ $cp['totalReturn'] }}</p>
                                </div>
                                <!-- Col 3: Duration -->
                                <div class="pl-1 shrink-0">
                                    <p class="text-[7.5px] sm:text-[10px] font-bold text-slate-400 uppercase tracking-wider font-poppins mb-0.5 sm:mb-1">Duration</p>
                                    @if ($plan->durations && $plan->durations->count() > 1)
                                        <div class="relative inline-flex items-center bg-white border border-slate-200/90 rounded-lg px-1.5 sm:px-3 py-0.5 sm:py-1 text-[10px] sm:text-[13px] font-bold text-[#0D1F3C] shadow-2xs mt-0.5">
                                            <select class="appearance-none bg-transparent pr-4 sm:pr-6 focus:outline-none cursor-pointer font-poppins text-[10px] sm:text-[13px] font-bold text-[#0D1F3C]">
                                                @foreach($plan->durations as $dur)
                                                    <option value="{{ $dur->id }}" {{ $dur->is_default ? 'selected' : '' }} class="text-slate-800 bg-white font-bold">{{ $dur->label ?? ($dur->duration_days ? $dur->duration_days . ' Days' : $cp['lockDuration']) }}</option>
                                                @endforeach
                                            </select>
                                            <i class="bi bi-chevron-down text-[8px] sm:text-[10px] text-slate-400 absolute right-1 sm:right-2.5 pointer-events-none"></i>
                                        </div>
                                    @else
                                        <p class="text-[14px] sm:text-[22px] font-black text-[#19B36B] font-poppins leading-none">{{ $cp['lockDuration'] }}</p>
                                    @endif
                                </div>
                            </div>

                            <!-- TRUST INDICATORS ROW WITH DIVIDER -->
                            <div class="flex items-center gap-2 sm:gap-6 mt-2.5 sm:mt-4 pt-0.5">
                                <div class="flex items-center gap-1.5 sm:gap-2 pr-2.5 sm:pr-4 border-r border-slate-200/80">
                                    <i class="bi bi-lock text-[13px] sm:text-[20px] text-[#0A5C66] shrink-0"></i>
                                    <div class="text-[8px] sm:text-[10px] font-extrabold text-[#0D1F3C] leading-tight font-poppins whitespace-nowrap">
                                        End-to-End Encryption
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 sm:gap-2">
                                    <i class="bi bi-shield-check text-[13px] sm:text-[20px] text-[#0A5C66] shrink-0"></i>
                                    <div class="text-[8px] sm:text-[10px] font-extrabold text-[#0D1F3C] leading-tight font-poppins whitespace-nowrap">
                                        100% Trusted &amp; Secure
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT: PRICE & BUY NOW BUTTON (DESKTOP ONLY MD+) -->
                        <div class="hidden md:flex flex-col items-end justify-center gap-4 shrink-0">
                            <div class="text-right">
                                <p class="text-[28px] sm:text-[34px] font-black text-[#0D1F3C] font-poppins leading-none tracking-tight">{!! $priceLabel !!}</p>
                                <p class="text-[11.5px] text-slate-400 font-semibold mt-1 tracking-tight">{{ $priceCaption }}</p>
                            </div>
                            <a href="{{ route('plan-details', $plan) }}" class="inline-flex items-center gap-2.5 bg-[#0A5C66] hover:bg-[#07464d] text-white font-extrabold text-[14px] sm:text-[15px] px-7 py-3 rounded-2xl active:scale-95 transition-all shadow-md shadow-[#0A5C66]/15 font-poppins btn-ripple shrink-0">
                                Buy Now <i class="bi bi-arrow-right text-[15px]"></i>
                            </a>
                        </div>
                    </div>

                    <!-- PRICE & BUY NOW ROW (MOBILE ONLY BOTTOM ROW) -->
                    <div class="flex md:hidden items-center justify-between gap-2.5 mt-3 pt-2.5 border-t border-slate-100">
                        <div>
                            <p class="text-[20px] font-black text-[#0D1F3C] font-poppins leading-none tracking-tight">{!! $priceLabel !!}</p>
                            <p class="text-[10px] text-slate-400 font-semibold mt-0.5 tracking-tight">{{ $priceCaption }}</p>
                        </div>
                        <a href="{{ route('plan-details', $plan) }}" class="inline-flex items-center gap-1.5 bg-[#0A5C66] hover:bg-[#07464d] text-white font-extrabold text-[12.5px] px-5 py-2 rounded-xl active:scale-95 transition-all shadow-sm font-poppins btn-ripple shrink-0">
                            Buy Now <i class="bi bi-arrow-right text-[13px]"></i>
                        </a>
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

        </div>
    </div>

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

