@extends('layouts.app')

@section('content')
    <div id="tab-plan-details" class="flex min-h-[100dvh] w-full flex-col flex-1 bg-[#F4F7F8] pb-36 pt-safe overflow-y-auto custom-scrollbar animate-fade-in-up">
        
        <!-- Sticky Premium Header -->
        <div class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-slate-100/80 px-4 py-3 flex items-center justify-between gap-2 shadow-2xs">
            <a href="{{ route('explore') }}" class="w-9 h-9 rounded-full bg-slate-100/80 flex items-center justify-center text-[#0A5C66] hover:bg-slate-200 active:scale-95 transition-all shrink-0">
                <i class="bi bi-arrow-left text-[18px]"></i>
            </a>
            <h2 class="text-[15px] sm:text-[16px] font-black text-[#0D1F3C] font-poppins tracking-tight truncate min-w-0 flex-1 text-center">{{ $p['title'] }}</h2>
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" onclick="sharePlan(this, '{{ $p['title'] }}')" class="w-9 h-9 rounded-full bg-slate-100/80 flex items-center justify-center text-slate-600 hover:bg-slate-200 active:scale-95 transition-all">
                    <i class="bi bi-share text-[15px]"></i>
                </button>
                <button type="button" class="w-9 h-9 rounded-full bg-slate-100/80 flex items-center justify-center text-rose-500 hover:bg-slate-200 active:scale-95 transition-all">
                    <i class="bi bi-heart-fill text-[15px]"></i>
                </button>
            </div>
        </div>

        <div class="p-3.5 sm:p-5 space-y-4 max-w-3xl mx-auto w-full">

            <!-- 1. HERO PLAN CARD & HEADER BANNER -->
            <div class="relative bg-gradient-to-br from-[#EAF4FB] via-[#E3EFFA] to-[#D6E8F7] rounded-[24px] overflow-hidden border border-slate-100 shadow-sm">
                <div class="relative w-full">
                    <!-- Building image faded into the light background on the left -->
                    <img src="{{ $p['image'] }}" class="absolute inset-y-0 right-0 h-full w-[62%] sm:w-[58%] object-cover"
                        style="mask-image: linear-gradient(to right, transparent, black 40%); -webkit-mask-image: linear-gradient(to right, transparent, black 40%);"
                        alt="{{ $p['title'] }}">

                    {{-- In normal flow (not absolute) so its height - which varies with
                         title length/font size across screens - sets the banner's own
                         height. pb-24/28 reserves room below for the YTM tab so a
                         wrapped 2-line title can never grow into it. --}}
                    <div class="relative z-10 p-4 sm:p-5 pb-24 sm:pb-28 flex flex-col">
                        <!-- Top Badges -->
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <div class="w-11 h-11 rounded-2xl bg-white shadow-md flex items-center justify-center text-[#0A5C66] shrink-0">
                                    <i class="bi {{ $p['icon'] ?? 'bi-building' }} text-[20px]"></i>
                                </div>
                                <span class="bg-purple-100 text-purple-700 text-[9.5px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full shadow-sm">
                                    NEWLY ADDED
                                </span>
                            </div>
                            <div class="bg-white shadow-md px-3.5 py-2 rounded-2xl text-center shrink-0">
                                <p class="text-[16px] font-black text-[#19B36B] font-poppins leading-none">{{ $p['growthRate'] }}%</p>
                                <p class="text-[8px] font-bold text-slate-400 uppercase tracking-tight mt-0.5">Yearly Return</p>
                            </div>
                        </div>

                        <!-- Main Title Block -->
                        <div class="mt-3 sm:mt-4">
                            <h1 class="text-[#0D1F3C] font-extrabold text-[22px] sm:text-[26px] font-poppins leading-tight tracking-tight">{{ $p['title'] }}</h1>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-slate-600 text-[12px] font-semibold font-poppins">{{ $p['badge'] }} Plan</span>
                                <span class="text-[#19B36B] text-[11px] font-bold flex items-center gap-1">
                                    <i class="bi bi-patch-check-fill text-[12px]"></i> Verified &amp; Secure
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Effective Return tab: pre-cut corner-shape asset (rounded top-left, diagonal cut bottom-right) stretched to the panel box -->
                    <div class="absolute bottom-0 left-0 w-[64%] sm:w-[54%] p-4 sm:p-5 z-10"
                        style="background-image: url('{{ asset('assets/ui/plan-return-tab-shape.png') }}'); background-size: 100% 100%; background-repeat: no-repeat;">
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-[22px] font-black text-[#0D1F3C] font-poppins leading-none">{{ $p['growthRate'] ?? '11.35' }}%</span>
                            <span class="text-[11px] font-bold text-slate-400 font-poppins">YTM</span>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mt-0.5">Effective Return (Yearly)</p>
                    </div>
                </div>
            </div>

            <!-- HIDDEN DURATION RADIOS FOR FORM SUBMISSION -->
            @if ($plan->durations->isNotEmpty())
                @foreach ($plan->durations as $duration)
                    <input type="radio" name="duration_id" id="pd-dur-{{ $duration->id }}" value="{{ $duration->id }}"
                        class="hidden" form="plan-purchase-form" {{ $duration->is_default ? 'checked' : '' }}>
                @endforeach
            @endif

            <!-- 2. 4 METRIC PODS GRID -->
            <div class="grid grid-cols-4 gap-1.5 sm:gap-3">
                <div class="bg-white p-2 sm:p-3 rounded-2xl border border-slate-100 shadow-2xs flex flex-col items-center text-center">
                    <div class="w-7 h-7 sm:w-10 sm:h-10 rounded-full bg-emerald-50 flex items-center justify-center mb-1 sm:mb-1.5">
                        <img src="{{ asset('assets/ui/metric-daily-profit.png') }}" alt="" class="w-4 h-4 sm:w-6 sm:h-6">
                    </div>
                    <span class="text-[7.5px] sm:text-[9.5px] font-bold text-slate-400 uppercase font-poppins leading-tight">Daily Profit</span>
                    <span class="text-[11px] sm:text-[14px] font-black text-[#19B36B] font-poppins mt-0.5 truncate max-w-full">{{ $p['dailyProfit'] }}</span>
                </div>
                <div class="bg-white p-2 sm:p-3 rounded-2xl border border-slate-100 shadow-2xs flex flex-col items-center text-center">
                    <div class="w-7 h-7 sm:w-10 sm:h-10 rounded-full bg-amber-50 flex items-center justify-center mb-1 sm:mb-1.5">
                        <img src="{{ asset('assets/ui/metric-total-profit.png') }}" alt="" class="w-4 h-4 sm:w-6 sm:h-6">
                    </div>
                    <span class="text-[7.5px] sm:text-[9.5px] font-bold text-slate-400 uppercase font-poppins leading-tight">Total Profit</span>
                    <span class="text-[11px] sm:text-[14px] font-black text-[#19B36B] font-poppins mt-0.5 truncate max-w-full">{{ $p['totalReturn'] }}</span>
                </div>
                <div class="bg-white p-2 sm:p-3 rounded-2xl border border-slate-100 shadow-2xs flex flex-col items-center text-center">
                    <div class="w-7 h-7 sm:w-10 sm:h-10 rounded-full bg-blue-50 flex items-center justify-center mb-1 sm:mb-1.5">
                        <img src="{{ asset('assets/ui/metric-duration.png') }}" alt="" class="w-4 h-4 sm:w-6 sm:h-6">
                    </div>
                    <span class="text-[7.5px] sm:text-[9.5px] font-bold text-slate-400 uppercase font-poppins leading-tight">Duration</span>
                    <span class="text-[11px] sm:text-[14px] font-black text-[#19B36B] font-poppins mt-0.5 truncate max-w-full">{{ $p['lockDuration'] }}</span>
                </div>
                <div class="bg-white p-2 sm:p-3 rounded-2xl border border-slate-100 shadow-2xs flex flex-col items-center text-center">
                    <div class="w-7 h-7 sm:w-10 sm:h-10 rounded-full bg-purple-50 flex items-center justify-center mb-1 sm:mb-1.5">
                        <img src="{{ asset('assets/ui/metric-maturity.png') }}" alt="" class="w-4 h-4 sm:w-6 sm:h-6">
                    </div>
                    <span class="text-[7.5px] sm:text-[9.5px] font-bold text-slate-400 uppercase font-poppins leading-tight">Maturity</span>
                    <span class="text-[11px] sm:text-[14px] font-black text-[#19B36B] font-poppins mt-0.5 truncate max-w-full">{{ $p['totalReturn'] }}</span>
                </div>
            </div>

            <!-- 3. SELECT DURATION PILLS -->
            @if ($plan->durations->count() > 0)
                <div class="bg-white p-3.5 sm:p-4 rounded-[22px] border border-slate-100 shadow-2xs">
                    <label class="text-[11.5px] sm:text-[12px] font-extrabold text-[#0D1F3C] font-poppins block mb-3">Select Duration</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-2.5">
                        @foreach ($plan->durations as $dur)
                            <button type="button"
                                onclick="selectDuration('pd-dur-{{ $dur->id }}', this)"
                                class="dur-pill-btn relative p-2.5 sm:p-3 rounded-xl border border-slate-200 text-left transition-all {{ $dur->is_default ? 'bg-[#0A5C66] text-white shadow-md' : 'bg-slate-50/50 text-slate-700 hover:bg-slate-100' }}">
                                @if($dur->is_default)
                                    <span class="absolute -top-2 left-1/2 -translate-x-1/2 bg-amber-400 text-slate-900 text-[7.5px] sm:text-[8px] font-black uppercase px-2 py-0.5 rounded-full shadow-2xs whitespace-nowrap">
                                        ⭐ MOST POPULAR
                                    </span>
                                @endif
                                <p class="text-[12px] sm:text-[13px] font-extrabold font-poppins leading-tight truncate">{{ $dur->label }}</p>
                                <p class="text-[9.5px] sm:text-[10px] opacity-80 font-bold mt-0.5 truncate">{{ $dur->growth_rate }}% Return</p>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- 4. SET YOUR INVESTMENT AMOUNT / ACTIVE POT WIDGET -->
            @if ($activePot)
                @php
                    $potMax = (float) $plan->max_investment_amount;
                    $potTotal = (float) $activePot->invested_amount;
                    $potRemaining = max(0, $potMax - $potTotal);
                    $potProgressPct = $potMax > 0 ? min(100, round(($potTotal / $potMax) * 100)) : 0;
                    $potTotalReturn = (float) ($activePot->total_return ?? $potTotal);
                    $potStep = $potRemaining > 0 ? max(1, (int) round($potRemaining / 100)) : 1;
                    $potDefaultAdd = min($potStep, $potRemaining);
                @endphp
                <div class="bg-white rounded-[24px] border border-slate-100 shadow-2xs p-4 sm:p-5" id="pd-topup-calc"
                    data-remaining="{{ $potRemaining }}" data-step="{{ $potStep }}"
                    data-current-total="{{ $potTotal }}" data-days="{{ $activePot->planDuration?->duration_days ?? 0 }}"
                    data-rate="{{ $activePot->planDuration?->growth_rate ?? 0 }}">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <h4 class="text-[13px] sm:text-[14px] font-black text-[#0A5C66] font-poppins truncate">Your Investment Pot</h4>
                        <span class="text-[9.5px] sm:text-[10px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full shrink-0">Running</span>
                    </div>
                    <p class="text-[10.5px] sm:text-[11px] text-slate-400 font-medium mb-4">Keep adding until you reach the max - your return is based on the total by maturity.</p>

                    <div class="flex items-end justify-between flex-wrap gap-1 mb-1.5">
                        <span class="text-[20px] sm:text-[24px] font-black text-[#0F172A] font-poppins truncate">₹{{ number_format($potTotal, 0) }}</span>
                        <span class="text-[10.5px] sm:text-[11px] font-bold text-slate-400 shrink-0">of ₹{{ number_format($potMax, 0) }}</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden mb-4">
                        <div class="h-full rounded-full bg-gradient-to-r from-[#0A5C66] to-[#3CCF91]" style="width: {{ $potProgressPct }}%"></div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5 sm:gap-3 mb-4">
                        <div class="stat-card p-2.5 sm:p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-center gap-2 sm:gap-2.5">
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-[#19B36B]/10 text-[#19B36B] flex items-center justify-center shrink-0"><i class="bi bi-piggy-bank text-[14px] sm:text-[16px]"></i></div>
                            <div class="flex flex-col min-w-0"><span class="text-[8.5px] sm:text-[9.5px] font-bold text-slate-400 uppercase truncate">Expected Return</span><span id="pd-topup-return" class="text-[12px] sm:text-[13px] font-black text-[#19B36B] truncate">₹{{ number_format($potTotalReturn, 0) }}</span></div>
                        </div>
                        <div class="stat-card p-2.5 sm:p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-center gap-2 sm:gap-2.5">
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-[#0A5C66]/10 text-[#0A5C66] flex items-center justify-center shrink-0"><i class="bi bi-calendar2-check text-[14px] sm:text-[16px]"></i></div>
                            <div class="flex flex-col min-w-0"><span class="text-[8.5px] sm:text-[9.5px] font-bold text-slate-400 uppercase truncate">Matures On</span><span class="text-[12px] sm:text-[13px] font-black text-[#0A5C66] truncate">{{ $activePot->matures_at?->format('d M Y') ?? '-' }}</span></div>
                        </div>
                    </div>

                    @if ($potRemaining > 0)
                        <div class="pt-4 border-t border-slate-100">
                            <span class="text-[10.5px] sm:text-[11.5px] font-bold text-[#0A5C66] block mb-2">Add More (up to ₹{{ number_format($potRemaining, 0) }} left)</span>
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <button type="button" id="pd-topup-dec" aria-label="Decrease amount" class="w-8 h-8 sm:w-9 sm:h-9 shrink-0 rounded-full bg-slate-100 text-[#0A5C66] flex items-center justify-center active:scale-95 transition-all hover:bg-slate-200"><i class="bi bi-dash-lg"></i></button>
                                <span id="pd-topup-display" class="text-[19px] sm:text-[22px] font-black text-[#0F172A] font-poppins truncate">₹{{ number_format($potDefaultAdd, 0) }}</span>
                                <button type="button" id="pd-topup-inc" aria-label="Increase amount" class="w-8 h-8 sm:w-9 sm:h-9 shrink-0 rounded-full bg-slate-100 text-[#0A5C66] flex items-center justify-center active:scale-95 transition-all hover:bg-slate-200"><i class="bi bi-plus-lg"></i></button>
                            </div>
                            <input type="range" id="pd-topup-slider" min="{{ $potStep }}" max="{{ $potRemaining }}" step="{{ $potStep }}" value="{{ $potDefaultAdd }}" class="w-full accent-[#0A5C66]" aria-label="Top-up amount">
                        </div>
                    @else
                        <div class="pt-4 border-t border-slate-100 text-center">
                            <span class="text-[11px] sm:text-[12px] font-bold text-emerald-600"><i class="bi bi-check-circle-fill"></i> Maximum investment reached</span>
                        </div>
                    @endif
                </div>
            @else
                @php
                    $flexMin = (float) ($plan->min_investment_amount ?? 199);
                    $flexMax = (float) ($plan->max_investment_amount ?? 999);
                    $flexStep = max(1, (int) round(($flexMax - $flexMin) / 50));
                @endphp
                <div class="bg-white p-4 sm:p-5 rounded-[24px] border border-slate-100 shadow-2xs space-y-4" id="pd-flex-calc"
                    data-min="{{ $flexMin }}" data-max="{{ $flexMax }}" data-step="{{ $flexStep }}" data-balance="{{ (float) $balance }}">
                    
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-[12.5px] sm:text-[14px] font-extrabold text-[#0D1F3C] font-poppins min-w-0 truncate">Choose Your Investment</h3>
                        <span class="text-[10px] sm:text-[11px] font-extrabold text-[#0A5C66] flex items-center gap-1 cursor-pointer shrink-0">
                            Custom Amount <i class="bi bi-pencil-square text-[12px]"></i>
                        </span>
                    </div>

                    <div class="text-center py-2">
                        <span id="pd-amount-display" class="text-[34px] font-black text-[#0D1F3C] font-poppins leading-none">₹{{ number_format($flexMin, 0) }}</span>
                        <span class="text-[13px] font-bold text-slate-400 font-poppins">/ Monthly</span>
                    </div>

                    <!-- Slider -->
                    <div>
                        <input type="range" id="pd-amount-slider" min="{{ $flexMin }}" max="{{ $flexMax }}" step="{{ $flexStep }}" value="{{ $flexMin }}"
                            class="w-full accent-[#0A5C66] cursor-pointer h-2 bg-slate-100 rounded-lg">
                        <div class="flex justify-between text-[11px] font-bold text-slate-400 mt-1">
                            <span>₹{{ number_format($flexMin, 0) }}</span>
                            <span>₹{{ number_format($flexMax, 0) }}</span>
                        </div>
                    </div>

                    <!-- Live Projection Calculation Card -->
                    <div class="bg-[#F6FAFA] p-3 sm:p-4 rounded-2xl border border-[#E0EFEF]">
                        <div class="flex items-center gap-1.5 sm:gap-3">
                            <div class="shrink-0">
                                <p class="text-[11px] sm:text-[13px] font-extrabold text-[#0D1F3C] font-poppins whitespace-nowrap leading-tight">
                                    <span id="pd-calc-summary-amount">₹{{ number_format($flexMin, 0) }}</span> <span class="text-slate-400 font-bold">/ Monthly</span>
                                </p>
                                <p class="text-[8.5px] sm:text-[10px] font-bold text-purple-500 mt-0.5 whitespace-nowrap">for 1 Year</p>
                            </div>

                            <span class="text-[14px] sm:text-[18px] font-black text-slate-300 shrink-0">=</span>

                            <p id="pd-flex-return" class="text-[16px] sm:text-[22px] font-black text-[#19B36B] font-poppins leading-none flex-1 min-w-0 text-center truncate">₹{{ number_format($flexMin * 1.25, 0) }}+</p>

                            <div class="w-[1px] h-8 bg-slate-200 shrink-0"></div>

                            <div class="shrink-0 flex items-center gap-1.5">
                                <div class="text-[7.5px] sm:text-[10px] font-bold text-slate-500 leading-snug whitespace-nowrap">
                                    <p>Investment: <strong id="pd-calc-invested" class="text-slate-800">₹{{ number_format($flexMin * 12, 0) }}</strong></p>
                                    <p>Profit: <strong id="pd-calc-profit" class="text-[#19B36B]">₹{{ number_format($flexMin * 0.25 * 12, 0) }}+</strong></p>
                                </div>
                                <div class="w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-emerald-50 text-[#19B36B] flex items-center justify-center shrink-0">
                                    <i class="bi bi-graph-up-arrow text-[10px] sm:text-[12px]"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-[9px] text-slate-400 font-medium text-center mt-2 border-t border-slate-200/50 pt-1.5">
                            *Projected value based on selected plan and expected returns.
                        </p>
                    </div>
                </div>
            @endif

            <!-- 5. WHY CHOOSE THIS PLAN? (3 + 2 CARDS) -->
            <div>
                <h4 class="text-[13px] font-extrabold text-[#0D1F3C] font-poppins mb-3">Why choose this plan?</h4>
                <div class="grid grid-cols-3 gap-2 sm:gap-3 mb-2 sm:mb-3">
                    <div class="bg-white p-2.5 sm:p-4 rounded-2xl border border-slate-100 shadow-2xs text-center flex flex-col items-center justify-center">
                        <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-full bg-emerald-50 flex items-center justify-center mb-1.5 sm:mb-2.5">
                            <img src="{{ asset('assets/ui/why-choose-shield-check.png') }}" alt="" class="w-5 h-5 sm:w-7 sm:h-7">
                        </div>
                        <p class="text-[10.5px] sm:text-[13px] font-extrabold text-[#0D1F3C] leading-tight font-poppins">Secure Investment</p>
                        <p class="text-[8.5px] sm:text-[10.5px] text-slate-400 font-medium mt-1 leading-tight">Bank grade security</p>
                    </div>
                    <div class="bg-white p-2.5 sm:p-4 rounded-2xl border border-slate-100 shadow-2xs text-center flex flex-col items-center justify-center">
                        <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-full bg-blue-50 flex items-center justify-center mb-1.5 sm:mb-2.5">
                            <img src="{{ asset('assets/ui/why-choose-daily-profit.png') }}" alt="" class="w-5 h-5 sm:w-7 sm:h-7">
                        </div>
                        <p class="text-[10.5px] sm:text-[13px] font-extrabold text-[#0D1F3C] leading-tight font-poppins">Daily Profit</p>
                        <p class="text-[8.5px] sm:text-[10.5px] text-slate-400 font-medium mt-1 leading-tight">Earn profit every day</p>
                    </div>
                    <div class="bg-white p-2.5 sm:p-4 rounded-2xl border border-slate-100 shadow-2xs text-center flex flex-col items-center justify-center">
                        <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-full bg-amber-50 flex items-center justify-center mb-1.5 sm:mb-2.5">
                            <img src="{{ asset('assets/ui/why-choose-lightning.png') }}" alt="" class="w-5 h-5 sm:w-7 sm:h-7">
                        </div>
                        <p class="text-[10.5px] sm:text-[13px] font-extrabold text-[#0D1F3C] leading-tight font-poppins">Instant Activation</p>
                        <p class="text-[8.5px] sm:text-[10.5px] text-slate-400 font-medium mt-1 leading-tight">Start earning instantly</p>
                    </div>
                </div>

                <!-- Second row: remaining 3 cards -->
                <div class="grid grid-cols-3 gap-2 sm:gap-3">
                    <div class="bg-white p-2.5 sm:p-4 rounded-2xl border border-slate-100 shadow-2xs text-center flex flex-col items-center justify-center">
                        <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-full bg-purple-50 flex items-center justify-center mb-1.5 sm:mb-2.5">
                            <img src="{{ asset('assets/ui/why-choose-encryption.png') }}" alt="" class="w-5 h-5 sm:w-7 sm:h-7">
                        </div>
                        <p class="text-[10.5px] sm:text-[13px] font-extrabold text-[#0D1F3C] leading-tight font-poppins">End-to-End Encryption</p>
                        <p class="text-[8.5px] sm:text-[10.5px] text-slate-400 font-medium mt-1 leading-tight">256-bit protection</p>
                    </div>
                    <div class="bg-white p-2.5 sm:p-4 rounded-2xl border border-slate-100 shadow-2xs text-center flex flex-col items-center justify-center">
                        <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-full bg-teal-50 flex items-center justify-center mb-1.5 sm:mb-2.5">
                            <img src="{{ asset('assets/ui/why-choose-support.png') }}" alt="" class="w-5 h-5 sm:w-7 sm:h-7">
                        </div>
                        <p class="text-[10.5px] sm:text-[13px] font-extrabold text-[#0D1F3C] leading-tight font-poppins">24x7 Support</p>
                        <p class="text-[8.5px] sm:text-[10.5px] text-slate-400 font-medium mt-1 leading-tight">We are always here</p>
                    </div>
                    <div class="bg-white p-2.5 sm:p-4 rounded-2xl border border-slate-100 shadow-2xs text-center flex flex-col items-center justify-center">
                        <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-full bg-teal-50 flex items-center justify-center mb-1.5 sm:mb-2.5">
                            <img src="{{ asset('assets/ui/icon2.png') }}" alt="" class="w-5 h-5 sm:w-7 sm:h-7">
                        </div>
                        <p class="text-[10.5px] sm:text-[13px] font-extrabold text-[#0D1F3C] leading-tight font-poppins">Flexible Withdrawal</p>
                        <p class="text-[8.5px] sm:text-[10.5px] text-slate-400 font-medium mt-1 leading-tight">Withdraw anytime you like</p>
                    </div>
                </div>
            </div>

            <!-- 6. SOCIAL PROOF & CONSENT CHECKBOX -->
            <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-100 shadow-2xs space-y-4">
                <div class="flex items-center divide-x divide-slate-100 py-2">
                    <div class="flex items-center gap-2 flex-1 min-w-0 pr-1.5">
                        <img src="{{ asset('assets/ui/trusted-investors-shield.png') }}" alt="" class="w-8 h-8 sm:w-10 sm:h-10 shrink-0">
                        <div class="text-left min-w-0">
                            <p class="text-[7.5px] sm:text-[9px] text-slate-400 font-semibold leading-tight truncate">Trusted by</p>
                            <p class="text-[11.5px] sm:text-[14px] font-black text-[#0D1F3C] font-poppins leading-tight truncate">1,50,000+</p>
                            <p class="text-[7.5px] sm:text-[9px] text-slate-400 font-semibold leading-tight truncate">Happy Investors</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-1 min-w-0 px-1.5">
                        <img src="{{ asset('assets/ui/rating-star-laurel.png') }}" alt="" class="w-8 h-8 sm:w-10 sm:h-10 shrink-0">
                        <div class="text-left min-w-0">
                            <p class="text-[11.5px] sm:text-[14px] font-black text-[#0D1F3C] font-poppins leading-tight truncate">4.8/5</p>
                            <p class="text-[7.5px] sm:text-[9px] text-slate-400 font-semibold leading-tight truncate">Investor Rating</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-1 min-w-0 pl-1.5">
                        <img src="{{ asset('assets/ui/money-safe-lock.png') }}" alt="" class="w-8 h-8 sm:w-10 sm:h-10 shrink-0">
                        <div class="text-left min-w-0">
                            <p class="text-[11px] sm:text-[13px] font-black text-[#0D1F3C] font-poppins leading-tight truncate">Safe &amp; Secure</p>
                            <p class="text-[7.5px] sm:text-[9px] text-slate-400 font-semibold leading-tight truncate">Your money is safe</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-start gap-2 pt-2 border-t border-slate-100 text-[10px] text-slate-500 font-medium leading-normal">
                    <input type="checkbox" checked id="terms-check" class="accent-[#0A5C66] mt-0.5 rounded">
                    <label for="terms-check" class="cursor-pointer">
                        By proceeding, I agree to GullakPe's <a href="#" class="underline text-[#0A5C66] font-bold">Terms &amp; Conditions</a>, <a href="#" class="underline text-[#0A5C66] font-bold">Privacy Policy</a>, <a href="#" class="underline text-[#0A5C66] font-bold">Disclaimer</a> and consent to KYC processing.
                    </label>
                </div>
            </div>

            <!-- 7. SECTION: "Higher Returns. Same Safety." -->
            <div class="pt-3 text-center">
                <!-- Title Header -->
                <div class="flex items-center justify-center gap-2.5 sm:gap-4 mb-4">
                    <span class="flex-1 max-w-[60px] sm:max-w-[160px] h-[2px] rounded-full"
                        style="background: linear-gradient(to right, rgba(245,158,11,0) 0%, rgba(245,158,11,.25) 20%, rgba(245,158,11,.7) 50%, #f59e0b 100%);"></span>
                    <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-amber-500 shrink-0"></span>
                    <h2 class="text-[18px] sm:text-[24px] font-extrabold text-[#0D1F3C] font-poppins leading-tight shrink-0">
                        Higher Returns.<br>
                        <span class="text-amber-500">Same Safety.</span>
                    </h2>
                    <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-amber-500 shrink-0"></span>
                    <span class="flex-1 max-w-[60px] sm:max-w-[160px] h-[2px] rounded-full"
                        style="background: linear-gradient(to left, rgba(245,158,11,0) 0%, rgba(245,158,11,.25) 20%, rgba(245,158,11,.7) 50%, #f59e0b 100%);"></span>
                </div>

                <!-- 3 Feature Cards -->
                <div class="grid grid-cols-3 gap-2 sm:gap-3 text-left">
                    <div class="bg-white p-2.5 sm:p-4 rounded-2xl border border-slate-100 shadow-2xs flex flex-col items-center text-center">
                        <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-full bg-emerald-50 flex items-center justify-center mb-1.5 sm:mb-2.5">
                            <img src="{{ asset('assets/ui/icon2.png') }}" alt="" class="w-5 h-5 sm:w-7 sm:h-7">
                        </div>
                        <h4 class="text-[10.5px] sm:text-[13px] font-extrabold text-[#0D1F3C] font-poppins leading-tight">Withdraw Anytime</h4>
                        <p class="text-[8.5px] sm:text-[10.5px] text-slate-400 font-medium mt-1 leading-tight">Withdraw your investment whenever eligible.</p>
                    </div>

                    <div class="bg-white p-2.5 sm:p-4 rounded-2xl border border-slate-100 shadow-2xs flex flex-col items-center text-center">
                        <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-full bg-emerald-50 flex items-center justify-center mb-1.5 sm:mb-2.5">
                            <img src="{{ asset('assets/ui/icon3.png') }}" alt="" class="w-5 h-5 sm:w-7 sm:h-7">
                        </div>
                        <h4 class="text-[10.5px] sm:text-[13px] font-extrabold text-[#0D1F3C] font-poppins leading-tight">Secured Investment</h4>
                        <p class="text-[8.5px] sm:text-[10.5px] text-slate-400 font-medium mt-1 leading-tight">Your investment is backed by verified &amp; secure assets.</p>
                    </div>

                    <div class="bg-white p-2.5 sm:p-4 rounded-2xl border border-slate-100 shadow-2xs flex flex-col items-center text-center">
                        <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-full bg-emerald-50 flex items-center justify-center mb-1.5 sm:mb-2.5">
                            <img src="{{ asset('assets/ui/why-choose-daily-profit.png') }}" alt="" class="w-5 h-5 sm:w-7 sm:h-7">
                        </div>
                        <h4 class="text-[10.5px] sm:text-[13px] font-extrabold text-[#0D1F3C] font-poppins leading-tight">Regular Returns</h4>
                        <p class="text-[8.5px] sm:text-[10.5px] text-slate-400 font-medium mt-1 leading-tight">Profit is credited as per selected plan.</p>
                    </div>
                </div>
            </div>

            <!-- 8. SECTION: "Historical Returns Growth" (AREA CHART) -->
            @php
                // Illustrative compounding of a fixed principal at this plan's own
                // growth_rate, not generic hardcoded numbers - so the curve/labels/
                // headline % actually change per plan instead of always reading
                // "172%" and the same five made-up rupee figures regardless of plan.
                $chartYears = 5;
                $chartRate = (float) ($plan->growth_rate ?? 11.35);
                $chartBase = 50000;
                $chartValues = [$chartBase];
                for ($i = 1; $i <= $chartYears; $i++) {
                    $chartValues[] = $chartValues[$i - 1] * (1 + $chartRate / 100);
                }
                $chartGrowthPct = $chartBase > 0 ? round((end($chartValues) / $chartBase - 1) * 100) : 0;
                $chartCurrentYear = (int) date('Y');
                $chartYearLabels = range($chartCurrentYear - $chartYears, $chartCurrentYear);

                $chartAxisMax = max(50000, ceil(($chartValues[$chartYears] * 1.08) / 25000) * 25000);
                $chartXStart = 50;
                $chartXEnd = 380;
                $chartYTop = 20;
                $chartYBottom = 110;
                $chartPoints = [];
                foreach ($chartValues as $i => $value) {
                    $x = $chartXStart + $i * (($chartXEnd - $chartXStart) / $chartYears);
                    $y = $chartYBottom - ($value / $chartAxisMax) * ($chartYBottom - $chartYTop);
                    $chartPoints[] = ['x' => round($x, 1), 'y' => round($y, 1), 'value' => $value];
                }
                $chartPathD = 'M ' . $chartPoints[0]['x'] . ' ' . $chartPoints[0]['y'];
                foreach (array_slice($chartPoints, 1) as $pt) {
                    $chartPathD .= ' L ' . $pt['x'] . ' ' . $pt['y'];
                }
                $chartAreaPoints = implode(' ', array_map(fn ($pt) => $pt['x'] . ',' . $pt['y'], $chartPoints))
                    . " {$chartXEnd},{$chartYBottom} {$chartXStart},{$chartYBottom}";
            @endphp
            <div class="bg-white p-4 sm:p-6 rounded-[24px] border border-slate-100 shadow-2xs space-y-4">
                <div class="flex items-center justify-center gap-3">
                    <span class="w-12 h-[1px] bg-slate-200"></span>
                    <h3 class="text-[15px] font-extrabold text-[#0D1F3C] font-poppins">Historical Returns Growth</h3>
                    <span class="w-12 h-[1px] bg-slate-200"></span>
                </div>

                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pt-2">
                    <div class="shrink-0">
                        <p class="text-[12px] font-bold text-slate-500 font-poppins">Investment Growth</p>
                        <p class="text-[34px] font-black text-[#19B36B] font-poppins leading-none mt-1">{{ $chartGrowthPct }}%</p>
                        <p class="text-[11px] font-bold text-slate-400 font-poppins mt-1">in last {{ $chartYears }} years</p>
                    </div>

                    <!-- SVG Gradient Growth Curve Chart -->
                    <div class="w-full sm:w-2/3 h-[160px] relative">
                        <svg class="w-full h-full overflow-visible" viewBox="0 0 400 140" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#19B36B" stop-opacity="0.35"/>
                                    <stop offset="100%" stop-color="#19B36B" stop-opacity="0.0"/>
                                </linearGradient>
                            </defs>

                            <!-- Y Grid lines -->
                            <line x1="40" y1="20" x2="390" y2="20" stroke="#f1f5f9" stroke-dasharray="3,3" />
                            <line x1="40" y1="50" x2="390" y2="50" stroke="#f1f5f9" stroke-dasharray="3,3" />
                            <line x1="40" y1="80" x2="390" y2="80" stroke="#f1f5f9" stroke-dasharray="3,3" />
                            <line x1="40" y1="110" x2="390" y2="110" stroke="#f1f5f9" stroke-dasharray="3,3" />

                            <!-- Y Axis labels -->
                            <text x="0" y="25" fill="#94a3b8" font-size="9" font-weight="bold">₹{{ number_format($chartAxisMax, 0) }}</text>
                            <text x="0" y="55" fill="#94a3b8" font-size="9" font-weight="bold">₹{{ number_format($chartAxisMax * 2 / 3, 0) }}</text>
                            <text x="0" y="85" fill="#94a3b8" font-size="9" font-weight="bold">₹{{ number_format($chartAxisMax / 3, 0) }}</text>
                            <text x="0" y="115" fill="#94a3b8" font-size="9" font-weight="bold">₹0</text>

                            <!-- Gradient Area -->
                            <polygon points="{{ $chartAreaPoints }}" fill="url(#chartGrad)" />

                            <!-- Curve Line -->
                            <path d="{{ $chartPathD }}" fill="none" stroke="#0A5C66" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />

                            <!-- Data Points & Labels -->
                            @foreach ($chartPoints as $i => $pt)
                                @continue($i === 0)
                                @php
                                    $isLast = $i === $chartYears;
                                    $labelX = $isLast ? $pt['x'] - 34 : max(0, $pt['x'] - 15);
                                    $labelY = $isLast ? $pt['y'] - 8 : $pt['y'] - 10;
                                @endphp
                                <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="{{ $isLast ? 5 : 4 }}" fill="{{ $isLast ? '#19B36B' : '#0A5C66' }}" />
                                <text x="{{ $labelX }}" y="{{ $labelY }}" fill="{{ $isLast ? '#19B36B' : '#0D1F3C' }}" font-size="{{ $isLast ? 9 : 8 }}" font-weight="bold">₹{{ number_format($pt['value'], 0) }}</text>
                            @endforeach
                        </svg>

                        <!-- X Axis Years -->
                        <div class="flex justify-between text-[10px] font-bold text-slate-400 pl-10 pr-2 mt-1">
                            @foreach ($chartYearLabels as $year)
                                <span>{{ $year }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <p class="text-[9px] text-slate-400 font-medium text-center border-t border-slate-100 pt-2">
                    Past performance is for illustration only and does not guarantee future returns.
                </p>
            </div>

            <!-- 9. SECTION: "Real Investment. Real Benefits." -->
            <div class="pt-2 text-center">
                <div class="flex items-center justify-center gap-2.5 sm:gap-4 mb-4">
                    <span class="flex-1 max-w-[60px] sm:max-w-[160px] h-[2px] rounded-full"
                        style="background: linear-gradient(to right, rgba(83,182,199,0) 0%, rgba(83,182,199,.25) 20%, rgba(83,182,199,.7) 50%, #53b6c7 100%);"></span>
                    <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-[#008ca5] shrink-0"></span>
                    <h3 class="text-[15px] sm:text-[22px] font-bold text-[#17344d] font-poppins whitespace-nowrap shrink-0">Real Investment. Real Benefits.</h3>
                    <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-[#008ca5] shrink-0"></span>
                    <span class="flex-1 max-w-[60px] sm:max-w-[160px] h-[2px] rounded-full"
                        style="background: linear-gradient(to left, rgba(83,182,199,0) 0%, rgba(83,182,199,.25) 20%, rgba(83,182,199,.7) 50%, #53b6c7 100%);"></span>
                </div>

                <div class="grid grid-cols-3 gap-2 sm:gap-3 text-left">
                    <div class="bg-white p-2.5 sm:p-4 rounded-2xl border border-slate-100 shadow-2xs flex flex-col items-center text-center">
                        <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-full bg-amber-50 flex items-center justify-center mb-1.5 sm:mb-2.5">
                            <img src="{{ asset('assets/ui/icon1.png') }}" alt="" class="w-4.5 h-4.5 sm:w-6 sm:h-6">
                        </div>
                        <h4 class="text-[10.5px] sm:text-[13px] font-extrabold text-[#0D1F3C] font-poppins leading-tight">Start from ₹199</h4>
                        <p class="text-[8.5px] sm:text-[10.5px] text-slate-400 font-medium mt-1 leading-tight">Minimum investment starts at ₹199.</p>
                    </div>

                    <div class="bg-white p-2.5 sm:p-4 rounded-2xl border border-slate-100 shadow-2xs flex flex-col items-center text-center">
                        <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-full bg-teal-50 flex items-center justify-center mb-1.5 sm:mb-2.5">
                            <img src="{{ asset('assets/ui/icon2.png') }}" alt="" class="w-4.5 h-4.5 sm:w-6 sm:h-6">
                        </div>
                        <h4 class="text-[10.5px] sm:text-[13px] font-extrabold text-[#0D1F3C] font-poppins leading-tight">Withdraw Anytime</h4>
                        <p class="text-[8.5px] sm:text-[10.5px] text-slate-400 font-medium mt-1 leading-tight">Flexible withdrawals as per plan terms.</p>
                    </div>

                    <div class="bg-white p-2.5 sm:p-4 rounded-2xl border border-slate-100 shadow-2xs flex flex-col items-center text-center">
                        <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-full bg-emerald-50 flex items-center justify-center mb-1.5 sm:mb-2.5">
                            <img src="{{ asset('assets/ui/icon3.png') }}" alt="" class="w-4.5 h-4.5 sm:w-6 sm:h-6">
                        </div>
                        <h4 class="text-[10.5px] sm:text-[13px] font-extrabold text-[#0D1F3C] font-poppins leading-tight">Safe &amp; Secure</h4>
                        <p class="text-[8.5px] sm:text-[10.5px] text-slate-400 font-medium mt-1 leading-tight">Bank-grade encrypted investment.</p>
                    </div>
                </div>
            </div>

            <!-- 10. SECTION: "Why Trust GullakPe" (WITH LAUREL WREATH) -->
            <div class="pt-2 text-center">
                <div class="flex justify-center mb-4">
                    <img src="{{ asset('assets/ui/Capture.png') }}" alt="Why Trust GullakPe" class="h-auto w-[260px] sm:w-[300px]">
                </div>

                <div class="grid grid-cols-3 gap-2 sm:gap-3 text-left">
                    <div class="bg-white p-2.5 sm:p-4 rounded-2xl border border-slate-100 shadow-2xs flex flex-col items-center text-center">
                        <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-full bg-emerald-50 text-[#19B36B] flex items-center justify-center mb-1.5 sm:mb-2.5">
                            <i class="bi bi-patch-check-fill text-[16px] sm:text-[22px]"></i>
                        </div>
                        <h4 class="text-[10.5px] sm:text-[13px] font-extrabold text-[#0D1F3C] font-poppins leading-tight">Verified Platform</h4>
                        <p class="text-[8.5px] sm:text-[10.5px] text-slate-400 font-medium mt-1 leading-tight">Trusted digital investment experience.</p>
                    </div>

                    <div class="bg-white p-2.5 sm:p-4 rounded-2xl border border-slate-100 shadow-2xs flex flex-col items-center text-center">
                        <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-full bg-emerald-50 text-[#19B36B] flex items-center justify-center mb-1.5 sm:mb-2.5">
                            <i class="bi bi-shield-lock-fill text-[16px] sm:text-[22px]"></i>
                        </div>
                        <h4 class="text-[10.5px] sm:text-[13px] font-extrabold text-[#0D1F3C] font-poppins leading-tight">Secure Plans</h4>
                        <p class="text-[8.5px] sm:text-[10.5px] text-slate-400 font-medium mt-1 leading-tight">All plans are protected with bank-grade encryption.</p>
                    </div>

                    <div class="bg-white p-2.5 sm:p-4 rounded-2xl border border-slate-100 shadow-2xs flex flex-col items-center text-center">
                        <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-full bg-emerald-50 text-[#19B36B] flex items-center justify-center mb-1.5 sm:mb-2.5">
                            <i class="bi bi-lock-fill text-[16px] sm:text-[22px]"></i>
                        </div>
                        <h4 class="text-[10.5px] sm:text-[13px] font-extrabold text-[#0D1F3C] font-poppins leading-tight">100% Safe</h4>
                        <p class="text-[8.5px] sm:text-[10.5px] text-slate-400 font-medium mt-1 leading-tight">Your investment stays safe &amp; protected.</p>
                    </div>
                </div>
            </div>

            <!-- 11. SECURITY GRADIENT BANNER -->
            <div class="btn-shimmer-cta bg-gradient-to-r from-[#032128] via-[#0A5C66] to-[#042A33] rounded-[22px] p-4 text-white flex items-center justify-between gap-3 shadow-md relative overflow-hidden">
                <!-- Decorative diagonal strokes, bottom-left corner -->
                <div class="absolute -bottom-6 -left-6 w-36 h-28 pointer-events-none"
                    style="background: repeating-linear-gradient(115deg, rgba(255,255,255,0.35) 0px, rgba(255,255,255,0.35) 1.5px, transparent 1.5px, transparent 9px); -webkit-mask-image: linear-gradient(115deg, black 10%, transparent 65%); mask-image: linear-gradient(115deg, black 10%, transparent 65%);"></div>

                <div class="flex items-center gap-3 relative z-10">
                    <div class="w-11 h-11 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center shrink-0">
                        <img src="{{ asset('assets/ui/money-protected-shield.png') }}" alt="" class="w-7 h-7">
                    </div>
                    <div>
                        <p class="text-[13px] font-extrabold font-poppins leading-tight">Your money is protected</p>
                        <p class="text-[11px] text-white/80 font-medium leading-tight">with advanced security.</p>
                    </div>
                </div>
                <span class="bg-white text-emerald-600 font-extrabold text-[11px] px-3 py-1.5 rounded-full shadow-sm flex items-center gap-1 shrink-0 relative z-10">
                    <i class="bi bi-check-circle-fill text-[12px]"></i> Verified
                </span>
            </div>

            <!-- 12. FREQUENTLY ASKED QUESTIONS ACCORDION -->
            <div class="bg-white rounded-[24px] border border-slate-100 shadow-2xs p-4 sm:p-5 space-y-3">
                <div class="flex items-center justify-center gap-3 mb-2">
                    <span class="w-8 h-[1px] bg-slate-200"></span>
                    <h3 class="text-[15px] font-extrabold text-[#0D1F3C] font-poppins">Frequently Asked Questions</h3>
                    <span class="w-8 h-[1px] bg-slate-200"></span>
                </div>

                @php
                    $faqs = [
                        ['q' => 'How do I earn profit?', 'a' => 'Profit is calculated based on your invested amount and plan growth rate, credited directly to your GullakPe wallet.'],
                        ['q' => 'Is my investment safe?', 'a' => 'Yes, 100% safe. All plans are backed by bank-grade 256-bit encryption and verified financial assets.'],
                        ['q' => 'Can I withdraw before maturity?', 'a' => 'Flexible plans allow instant withdrawals anytime. Locked plans unlock automatically upon reaching maturity.'],
                        ['q' => 'What is the minimum investment?', 'a' => 'Minimum investment starts at just ₹199 per plan.'],
                        ['q' => 'How is profit calculated?', 'a' => 'Profit accrues daily based on the annual percentage rate (YTM) divided by 365 days.'],
                    ];
                @endphp

                <div class="divide-y divide-slate-100">
                    @foreach ($faqs as $i => $faq)
                        <div class="faq-item">
                            <label class="w-full py-3.5 flex items-center justify-between text-left cursor-pointer active:bg-slate-50 transition-colors">
                                <input type="checkbox" class="hidden faq-check">
                                <span class="text-[13px] font-extrabold text-[#0D1F3C] font-poppins pr-3">{{ $faq['q'] }}</span>
                                <i class="bi bi-chevron-down faq-chevron text-[14px] text-slate-400 shrink-0"></i>
                            </label>
                            <div class="faq-answer">
                                <p class="pb-3 text-[12px] text-slate-500 font-medium leading-relaxed font-poppins">{{ $faq['a'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- DISCLAIMER & FOOTER -->
            <p class="text-[9.5px] font-semibold text-slate-400 font-poppins text-center leading-normal max-w-[300px] mx-auto pt-2 pb-6">
                Returns shown are estimated values and may vary depending on selected plan conditions.
            </p>
        </div>

        <!-- HIDDEN REAL PURCHASE FORM -->
        <form id="plan-purchase-form" method="POST" action="{{ route('plans.purchase', $plan) }}">
            @csrf
            <input type="hidden" name="amount" id="pd-flex-amount-input" value="{{ $flexMin }}">
        </form>
    </div>

    {{-- Sticky bar lives outside #tab-plan-details on purpose: that div carries
         animate-fade-in-up, whose forwards-filled keyframe leaves a static
         transform on it after the animation ends. A transform on an ancestor
         becomes the containing block for position:fixed descendants, which
         silently turned this bar into something that scrolled with the page
         instead of sticking to the viewport. --}}

    <!-- 13. FIXED BOTTOM STICKY INVESTMENT BAR -->
        <div class="fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md border-t border-slate-200/80 shadow-[0_-8px_30px_rgba(0,0,0,0.08)] px-3.5 pt-3 sm:px-4 sm:pt-4" style="padding-bottom: max(0.875rem, env(safe-area-inset-bottom));">
            <div class="max-w-3xl mx-auto flex items-center justify-between gap-2 sm:gap-3">
                <div class="flex items-center gap-1.5 sm:gap-2.5 min-w-0 flex-1">
                    <div class="flex w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-emerald-50 text-[#19B36B] items-center justify-center shrink-0 shadow-inner">
                        <i class="bi bi-piggy-bank text-[15px] sm:text-[18px]"></i>
                    </div>
                    <div class="min-w-0 shrink">
                        <p class="text-[8.5px] sm:text-[10px] font-bold text-slate-400 uppercase tracking-tight truncate">You Invest</p>
                        <p class="text-[13px] sm:text-[17px] font-black text-[#0D1F3C] font-poppins leading-none truncate">
                            <span id="sticky-amount-display">₹{{ number_format($flexMin, 0) }}</span> <span class="text-[8.5px] sm:text-[10px] font-bold text-slate-400">/mo</span>
                        </p>
                    </div>
                    <div class="h-7 w-[1px] bg-slate-200 mx-1 shrink-0"></div>
                    <div class="min-w-0 shrink">
                        <p class="text-[8.5px] sm:text-[10px] font-bold text-slate-400 uppercase tracking-tight truncate">You Earn (1Y)</p>
                        <p class="text-[13px] sm:text-[15px] font-black text-[#19B36B] font-poppins leading-none truncate">
                            <span id="sticky-return-display">₹{{ number_format($flexMin * 1.25, 0) }}</span> <span class="text-[8.5px] sm:text-[10px] text-[#19B36B] font-bold">(26.7%)</span>
                        </p>
                    </div>
                </div>

                @auth
                    <button type="submit" form="plan-purchase-form" class="btn-shimmer bg-[#061826] hover:bg-[#030D14] text-white font-extrabold text-[12px] sm:text-[15px] px-3.5 sm:px-6 py-2.5 sm:py-3 rounded-xl sm:rounded-2xl active:scale-95 transition-all shadow-md font-poppins shrink-0 flex items-center gap-1.5 sm:gap-2 whitespace-nowrap">
                        <span class="relative z-10 flex items-center gap-1.5 sm:gap-2">
                            Invest <span id="sticky-btn-amount">₹{{ number_format($flexMin, 0) }}</span> <span class="hidden sm:inline">Monthly</span> <i class="bi bi-arrow-right text-[12px] sm:text-[15px]"></i>
                        </span>
                    </button>
                @else
                    <a href="{{ route('login') }}" class="btn-shimmer bg-[#061826] hover:bg-[#030D14] text-white font-extrabold text-[12px] sm:text-[15px] px-3.5 sm:px-6 py-2.5 sm:py-3 rounded-xl sm:rounded-2xl active:scale-95 transition-all shadow-md font-poppins shrink-0 flex items-center gap-1.5 sm:gap-2 whitespace-nowrap">
                        <span class="relative z-10 flex items-center gap-1.5 sm:gap-2">
                            Log In to Invest <i class="bi bi-arrow-right text-[12px] sm:text-[15px]"></i>
                        </span>
                    </a>
                @endauth
            </div>
        </div>

    <!-- DURATION PILL SELECTION: previously only added the "selected" classes
         to the clicked button and stripped them from the rest, but never
         restored the "unselected" classes it had removed - so a clicked
         button ended up with both class sets present at once (fighting over
         the same background/text-color properties) and the old selection
         was left with neither, producing the broken/invisible pill look. -->
    <script>
    function selectDuration(radioId, button) {
        var radio = document.getElementById(radioId);
        if (radio) radio.checked = true;
        var selected = ['bg-[#0A5C66]', 'text-white', 'shadow-md'];
        var unselected = ['bg-slate-50/50', 'text-slate-700', 'hover:bg-slate-100'];
        document.querySelectorAll('.dur-pill-btn').forEach(function (btn) {
            btn.classList.remove.apply(btn.classList, selected);
            btn.classList.add.apply(btn.classList, unselected);
        });
        button.classList.remove.apply(button.classList, unselected);
        button.classList.add.apply(button.classList, selected);
    }
    </script>

    <!-- SHARE BUTTON: navigator.share needs a secure context (HTTPS, or
         localhost) and isn't implemented in every desktop browser - it was
         silently doing nothing wherever it's unavailable. Fall back to
         copying the link so the click always does something visible. -->
    <script>
    function sharePlan(button, title) {
        var shareData = { title: title, url: window.location.href };
        if (navigator.share) {
            navigator.share(shareData).catch(function () {});
            return;
        }
        var icon = button.querySelector('i');
        var restore = icon.className;
        function flash(className) {
            icon.className = className;
            setTimeout(function () { icon.className = restore; }, 1500);
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(shareData.url)
                .then(function () { flash('bi bi-check-lg text-[15px] text-emerald-600'); })
                .catch(function () { flash('bi bi-exclamation-lg text-[15px] text-rose-500'); });
        } else {
            flash('bi bi-exclamation-lg text-[15px] text-rose-500');
        }
    }
    </script>

    <!-- DYNAMIC SLIDER RECALCULATION SCRIPT -->
    <script>
    (function () {
        var slider = document.getElementById('pd-amount-slider');
        var amountDisplay = document.getElementById('pd-amount-display');
        var hiddenInput = document.getElementById('pd-flex-amount-input');
        var summaryAmount = document.getElementById('pd-calc-summary-amount');
        var summaryInvested = document.getElementById('pd-calc-invested');
        var summaryProfit = document.getElementById('pd-calc-profit');
        var summaryReturn = document.getElementById('pd-flex-return');
        var stickyAmount = document.getElementById('sticky-amount-display');
        var stickyBtnAmount = document.getElementById('sticky-btn-amount');
        var stickyReturn = document.getElementById('sticky-return-display');

        function formatMoney(val) {
            return '₹' + Math.round(val).toLocaleString('en-IN');
        }

        function updateValues() {
            if (!slider) return;
            var val = parseFloat(slider.value) || 199;
            var formatted = formatMoney(val);

            if (amountDisplay) amountDisplay.textContent = formatted;
            if (hiddenInput) hiddenInput.value = val;
            if (summaryAmount) summaryAmount.textContent = formatted;
            if (stickyAmount) stickyAmount.textContent = formatted;
            if (stickyBtnAmount) stickyBtnAmount.textContent = formatted;

            var yearlyInvested = val * 12;
            var yearlyProfit = val * 0.267 * 12;
            var totalReturn = yearlyInvested + yearlyProfit;

            if (summaryInvested) summaryInvested.textContent = formatMoney(yearlyInvested);
            if (summaryProfit) summaryProfit.textContent = formatMoney(yearlyProfit) + '+';
            if (summaryReturn) summaryReturn.textContent = formatMoney(totalReturn) + '+';
            if (stickyReturn) stickyReturn.textContent = formatMoney(totalReturn);
        }

        if (slider) {
            slider.addEventListener('input', updateValues);
            updateValues();
        }
    })();
    </script>
@endsection
