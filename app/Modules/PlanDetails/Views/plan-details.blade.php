@extends('layouts.app')

@section('content')
    <div id="tab-plan-details" class="flex min-h-[100dvh] w-full flex-col flex-1 bg-[#F4F7F8] pb-36 pt-safe overflow-y-auto custom-scrollbar animate-fade-in-up">
        
        <!-- Sticky Premium Header (Step 3) -->
        <div class="sticky top-0 z-50 bg-white/85 backdrop-blur-md border-b border-slate-100/80 px-5 py-4 flex items-center justify-between shadow-sm">
            <a href="{{ route('explore') }}" class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-[#0A5C66] hover:bg-slate-100 active:scale-95 transition-all">
                <i class="bi bi-arrow-left text-[20px]"></i>
            </a>
            <h2 class="text-[17px] font-black text-[#0A5C66] font-poppins tracking-tight">{{ $p['title'] }}</h2>
            <span class="w-10 h-10"></span>
        </div>

        <div class="p-4 space-y-[14px]">
            <!-- Hero Banner (Step 4) -->
            <div class="relative w-full h-[210px] rounded-[24px] overflow-hidden shadow-md group">
                <img src="{{ $p['image'] }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" alt="{{ $p['title'] }}">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/95 via-slate-900/50 to-transparent z-10"></div>

                <div class="absolute inset-0 p-6 flex flex-col justify-between z-20">
                    <div class="flex justify-between items-start gap-2">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <div class="backdrop-blur-md border border-white/20 text-[10px] font-bold px-3 py-1.5 rounded-[14px] uppercase tracking-wider flex items-center gap-1.5 shadow-sm font-poppins text-white">
                                <i class="bi {{ $badgeIcons[$p['badge']] ?? $defaultBadgeIcon }} text-[10px]"></i>
                                <span>{{ $p['badge'] }}</span>
                            </div>
                            @if ($plan->marketing_badge)
                                @php $badgeColor = $plan->marketingBadgeColorClasses(); @endphp
                                <div class="backdrop-blur-md border {{ $badgeColor['border'] }} {{ $badgeColor['bg'] }} {{ $badgeColor['text'] }} text-[10px] font-bold px-3 py-1.5 rounded-[14px] flex items-center gap-1.5 shadow-sm font-poppins">
                                    @if ($plan->marketing_badge_icon)
                                        <i class="bi {{ $plan->marketing_badge_icon }} text-[10px]"></i>
                                    @endif
                                    {{ $plan->marketing_badge }}
                                </div>
                            @endif
                        </div>
                        <x-plan-badge :plan="$plan" :unlocked="$hasUnlocked" />
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 p-3 flex items-center justify-center shrink-0 shadow-lg text-white">
                            <i class="bi {{ $p['icon'] }} text-[26px]"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-white font-extrabold text-[22px] leading-tight font-poppins tracking-tight">{{ $p['title'] }}</h3>
                            <p class="text-white/80 text-[12px] font-semibold font-poppins mt-0.5 truncate">{{ $p['subtitle'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            @if ($plan->durations->isNotEmpty())
                {{-- Hidden duration radios - always rendered when the plan has
                     real duration options, regardless of which calculator UI
                     is shown below. form="plan-purchase-form" so the selected
                     duration submits with Invest Now without needing to
                     physically nest inside that form. The flexible-amount JS
                     calculator (below) checks/unchecks these directly when
                     its own duration pills are clicked; the fixed-amount
                     pure-CSS calculator drives them the normal radio way. --}}
                @foreach ($plan->durations as $duration)
                    <input type="radio" name="duration_id" id="pd-dur-{{ $duration->id }}" value="{{ $duration->id }}"
                        class="hidden" form="plan-purchase-form" {{ $duration->is_default ? 'checked' : '' }}>
                @endforeach
            @endif

            @if ($plan->isFlexibleAmount())
              @if ($activePot)
                {{-- Top-up mode: the user already has one ongoing pot for
                     this plan (Plan::isTopupPot()) - show its status instead
                     of the initial pick-an-amount slider, and a smaller
                     "Add More" control that only changes invested_amount on
                     the SAME UserPlan row (shared single maturity date, see
                     PlanPurchaseController::topUp()). --}}
                @php
                    $potMax = (float) $plan->max_investment_amount;
                    $potTotal = (float) $activePot->invested_amount;
                    $potRemaining = max(0, $potMax - $potTotal);
                    $potProgressPct = $potMax > 0 ? min(100, round(($potTotal / $potMax) * 100)) : 0;
                    $potTotalReturn = (float) ($activePot->total_return ?? $potTotal);
                    $potStep = $potRemaining > 0 ? max(1, (int) round($potRemaining / 100)) : 1;
                    $potDefaultAdd = min($potStep, $potRemaining);
                @endphp
                <div class="bg-white rounded-[24px] border border-slate-100 shadow-sm p-5" id="pd-topup-calc"
                    data-remaining="{{ $potRemaining }}" data-step="{{ $potStep }}"
                    data-current-total="{{ $potTotal }}" data-days="{{ $activePot->planDuration?->duration_days ?? 0 }}"
                    data-rate="{{ $activePot->planDuration?->growth_rate ?? 0 }}">
                    <div class="flex items-center justify-between mb-1">
                        <h4 class="text-[14px] font-black text-[#0A5C66] font-poppins">Your Investment Pot</h4>
                        <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">Running</span>
                    </div>
                    <p class="text-[11px] text-slate-400 font-medium mb-4">Keep adding until you reach the max - your return is based on the total by maturity.</p>

                    <div class="flex items-end justify-between mb-1.5">
                        <span class="text-[24px] font-black text-[#0F172A] font-poppins">₹{{ number_format($potTotal, 0) }}</span>
                        <span class="text-[11px] font-bold text-slate-400">of ₹{{ number_format($potMax, 0) }}</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden mb-4">
                        <div class="h-full rounded-full bg-gradient-to-r from-[#0A5C66] to-[#3CCF91]" style="width: {{ $potProgressPct }}%"></div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="stat-card">
                            <div class="icon bg-[#19B36B]/5 border border-[#19B36B]/10 text-[#19B36B]"><i class="bi bi-piggy-bank text-[18px]"></i></div>
                            <div class="flex flex-col min-w-0"><span class="title">Expected Return</span><span id="pd-topup-return" class="value text-[#19B36B]">₹{{ number_format($potTotalReturn, 0) }}</span></div>
                        </div>
                        <div class="stat-card">
                            <div class="icon bg-[#0A5C66]/5 border border-[#0A5C66]/10 text-[#0A5C66]"><i class="bi bi-calendar2-check text-[18px]"></i></div>
                            <div class="flex flex-col min-w-0"><span class="title">Matures On</span><span class="value text-[#0A5C66]">{{ $activePot->matures_at?->format('d M Y') ?? '-' }}</span></div>
                        </div>
                    </div>

                    @if ($potRemaining > 0)
                        <div class="pt-4 border-t border-slate-100">
                            <span class="text-[11.5px] font-bold text-[#0A5C66] block mb-2">Add More (up to ₹{{ number_format($potRemaining, 0) }} left)</span>
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <button type="button" id="pd-topup-dec" aria-label="Decrease amount" class="w-9 h-9 shrink-0 rounded-full bg-slate-100 text-[#0A5C66] flex items-center justify-center active:scale-95 transition-all hover:bg-slate-200"><i class="bi bi-dash-lg"></i></button>
                                <span id="pd-topup-display" class="text-[22px] font-black text-[#0F172A] font-poppins">₹{{ number_format($potDefaultAdd, 0) }}</span>
                                <button type="button" id="pd-topup-inc" aria-label="Increase amount" class="w-9 h-9 shrink-0 rounded-full bg-slate-100 text-[#0A5C66] flex items-center justify-center active:scale-95 transition-all hover:bg-slate-200"><i class="bi bi-plus-lg"></i></button>
                            </div>
                            <input type="range" id="pd-topup-slider" min="{{ $potStep }}" max="{{ $potRemaining }}" step="{{ $potStep }}" value="{{ $potDefaultAdd }}" class="w-full accent-[#0A5C66]" aria-label="Top-up amount">
                        </div>
                    @else
                        <div class="pt-4 border-t border-slate-100 text-center">
                            <span class="text-[12px] font-bold text-emerald-600"><i class="bi bi-check-circle-fill"></i> Maximum investment reached</span>
                        </div>
                    @endif
                </div>

                @if ($potRemaining > 0)
                    <input type="hidden" name="amount" id="pd-topup-amount-input" form="plan-purchase-form" value="{{ $potDefaultAdd }}">

                    <script>
                    (function () {
                        var container = document.getElementById('pd-topup-calc');
                        if (!container) return;

                        var remaining = parseFloat(container.dataset.remaining);
                        var step = parseFloat(container.dataset.step) || 1;
                        var currentTotal = parseFloat(container.dataset.currentTotal) || 0;
                        var days = parseFloat(container.dataset.days) || 0;
                        var rate = parseFloat(container.dataset.rate) || 0;

                        var slider = document.getElementById('pd-topup-slider');
                        var display = document.getElementById('pd-topup-display');
                        var hiddenInput = document.getElementById('pd-topup-amount-input');
                        var decBtn = document.getElementById('pd-topup-dec');
                        var incBtn = document.getElementById('pd-topup-inc');
                        var returnEl = document.getElementById('pd-topup-return');
                        var investBtnAmount = document.getElementById('pd-invest-btn-amount');

                        function formatMoney(value) {
                            return '₹' + Math.round(value).toLocaleString('en-IN');
                        }

                        function recalc() {
                            var addAmount = parseFloat(slider.value);
                            display.textContent = formatMoney(addAmount);
                            hiddenInput.value = addAmount;
                            if (investBtnAmount) investBtnAmount.textContent = formatMoney(addAmount);

                            if (days > 0) {
                                var newTotal = currentTotal + addAmount;
                                var years = days / 365;
                                var totalReturn = newTotal * (1 + (rate / 100) * years);
                                returnEl.textContent = formatMoney(totalReturn);
                            }
                        }

                        slider.addEventListener('input', recalc);
                        decBtn.addEventListener('click', function () {
                            slider.value = Math.max(step, parseFloat(slider.value) - step);
                            recalc();
                        });
                        incBtn.addEventListener('click', function () {
                            slider.value = Math.min(remaining, parseFloat(slider.value) + step);
                            recalc();
                        });

                        recalc();
                    })();
                    </script>
                @endif
              @else
                @php
                    $flexMin = (float) $plan->min_investment_amount;
                    $flexMax = (float) $plan->max_investment_amount;
                    $flexStep = max(1, (int) round(($flexMax - $flexMin) / 100));
                    $flexDefaultDuration = $plan->defaultDuration();
                @endphp
                {{-- "Choose Your Investment" - a real drag-slider with live
                     recalculation (amount x selected duration's growth_rate),
                     matching the reference SIP-style UI. This is the one
                     deliberate, scoped exception to this app's no-JS
                     convention (see MEMORY.md) - true continuous-drag live
                     arithmetic isn't something pure CSS can do; every other
                     interaction on this page (tabs, FAQ accordion, fixed-
                     amount calculator) stays JS-free as before. --}}
                <div class="bg-white rounded-[24px] border border-slate-100 shadow-sm p-5" id="pd-flex-calc"
                    data-min="{{ $flexMin }}" data-max="{{ $flexMax }}" data-step="{{ $flexStep }}" data-balance="{{ (float) $balance }}">
                    <h4 class="text-[14px] font-black text-[#0A5C66] font-poppins mb-1">Choose Your Investment</h4>
                    <p class="text-[11px] text-slate-400 font-medium mb-4">Drag the slider or use +/- to pick an amount.</p>

                    @if ($plan->durations->count() > 1)
                        <div class="flex gap-2 mb-5" id="pd-flex-durations">
                            @foreach ($plan->durations as $duration)
                                <button type="button"
                                    class="pd-flex-dur-label flex-1 h-10 rounded-full border border-slate-200 text-[12.5px] font-bold text-slate-600 flex items-center justify-center transition-all {{ $duration->is_default ? 'pd-flex-dur-active' : '' }}"
                                    data-duration-id="{{ $duration->id }}" data-days="{{ $duration->duration_days }}" data-rate="{{ $duration->growth_rate }}">
                                    {{ $duration->label }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex items-center justify-between gap-3 mb-3">
                        <button type="button" id="pd-amount-dec" aria-label="Decrease amount" class="w-10 h-10 shrink-0 rounded-full bg-slate-100 text-[#0A5C66] flex items-center justify-center active:scale-95 transition-all hover:bg-slate-200">
                            <i class="bi bi-dash-lg"></i>
                        </button>
                        <span id="pd-amount-display" class="text-[28px] font-black text-[#0F172A] font-poppins">₹{{ number_format($flexMin, 0) }}</span>
                        <button type="button" id="pd-amount-inc" aria-label="Increase amount" class="w-10 h-10 shrink-0 rounded-full bg-slate-100 text-[#0A5C66] flex items-center justify-center active:scale-95 transition-all hover:bg-slate-200">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>

                    <input type="range" id="pd-amount-slider" min="{{ $flexMin }}" max="{{ $flexMax }}" step="{{ $flexStep }}" value="{{ $flexMin }}"
                        class="w-full accent-[#0A5C66] mb-1.5" aria-label="Investment amount">
                    <div class="flex justify-between text-[10px] font-bold text-slate-400 mb-4">
                        <span>₹{{ number_format($flexMin, 0) }}</span>
                        <span>₹{{ number_format($flexMax, 0) }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div class="stat-card">
                            <div class="icon bg-[#19B36B]/5 border border-[#19B36B]/10 text-[#19B36B]"><i class="bi bi-piggy-bank text-[18px]"></i></div>
                            <div class="flex flex-col min-w-0"><span class="title">Expected Return</span><span id="pd-flex-return" class="value text-[#19B36B]">₹0</span></div>
                        </div>
                        <div class="stat-card">
                            <div class="icon bg-[#19B36B]/5 border border-[#19B36B]/10 text-[#19B36B]"><i class="bi bi-graph-up-arrow text-[18px]"></i></div>
                            <div class="flex flex-col min-w-0"><span class="title">Daily Growth</span><span id="pd-flex-daily" class="value text-[#19B36B]">₹0</span></div>
                        </div>
                        <div class="stat-card col-span-2">
                            <div class="icon bg-[#0A5C66]/5 border border-[#0A5C66]/10 text-[#0A5C66]"><i class="bi bi-calendar2-check text-[18px]"></i></div>
                            <div class="flex flex-col min-w-0"><span class="title">Maturity Date</span><span id="pd-flex-maturity" class="value text-[#0A5C66]">-</span></div>
                        </div>
                    </div>

                    <div class="bg-[#0A5C66]/5 rounded-2xl p-4 border border-[#0A5C66]/10">
                        <span class="text-[10.5px] font-bold text-[#0A5C66] uppercase tracking-wider font-poppins">Portfolio Preview</span>
                        <div class="grid grid-cols-3 gap-2 mt-2.5 text-left">
                            <div class="flex flex-col"><span class="text-[9.5px] font-semibold text-slate-400 uppercase">Current Balance</span><span class="text-[12.5px] font-black text-[#0A5C66] font-poppins">₹{{ number_format($balance, 0) }}</span></div>
                            <div class="flex flex-col"><span class="text-[9.5px] font-semibold text-slate-400 uppercase">After Investment</span><span id="pd-flex-after" class="text-[12.5px] font-black text-[#0A5C66] font-poppins">₹{{ number_format(max(0, $balance - $flexMin), 0) }}</span></div>
                            <div class="flex flex-col"><span class="text-[9.5px] font-semibold text-slate-400 uppercase">Expected Maturity</span><span id="pd-flex-maturity-value" class="text-[12.5px] font-black text-[#19B36B] font-poppins">₹0</span></div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="amount" id="pd-flex-amount-input" form="plan-purchase-form" value="{{ $flexMin }}">

                <script>
                (function () {
                    var container = document.getElementById('pd-flex-calc');
                    if (!container) return;

                    var min = parseFloat(container.dataset.min);
                    var max = parseFloat(container.dataset.max);
                    var step = parseFloat(container.dataset.step) || 1;
                    var balance = parseFloat(container.dataset.balance) || 0;

                    var slider = document.getElementById('pd-amount-slider');
                    var display = document.getElementById('pd-amount-display');
                    var hiddenInput = document.getElementById('pd-flex-amount-input');
                    var decBtn = document.getElementById('pd-amount-dec');
                    var incBtn = document.getElementById('pd-amount-inc');
                    var durationButtons = document.querySelectorAll('.pd-flex-dur-label');

                    var returnEl = document.getElementById('pd-flex-return');
                    var dailyEl = document.getElementById('pd-flex-daily');
                    var maturityEl = document.getElementById('pd-flex-maturity');
                    var afterEl = document.getElementById('pd-flex-after');
                    var maturityValueEl = document.getElementById('pd-flex-maturity-value');
                    var investBtnAmount = document.getElementById('pd-invest-btn-amount');

                    function formatMoney(value) {
                        return '₹' + Math.round(value).toLocaleString('en-IN');
                    }

                    function selectedDuration() {
                        var active = document.querySelector('.pd-flex-dur-active');
                        if (!active) return null;
                        return { days: parseFloat(active.dataset.days), rate: parseFloat(active.dataset.rate) };
                    }

                    function recalc() {
                        var amount = parseFloat(slider.value);
                        display.textContent = formatMoney(amount);
                        hiddenInput.value = amount;
                        if (investBtnAmount) investBtnAmount.textContent = formatMoney(amount);
                        afterEl.textContent = formatMoney(Math.max(0, balance - amount));

                        var duration = selectedDuration();
                        if (!duration) return;

                        var years = duration.days / 365;
                        var totalReturn = amount * (1 + (duration.rate / 100) * years);

                        returnEl.textContent = formatMoney(totalReturn);
                        dailyEl.textContent = formatMoney((totalReturn - amount) / duration.days);
                        maturityValueEl.textContent = formatMoney(totalReturn);

                        var maturityDate = new Date();
                        maturityDate.setDate(maturityDate.getDate() + duration.days);
                        maturityEl.textContent = maturityDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                    }

                    slider.addEventListener('input', recalc);

                    decBtn.addEventListener('click', function () {
                        slider.value = Math.max(min, parseFloat(slider.value) - step);
                        recalc();
                    });
                    incBtn.addEventListener('click', function () {
                        slider.value = Math.min(max, parseFloat(slider.value) + step);
                        recalc();
                    });

                    durationButtons.forEach(function (button) {
                        button.addEventListener('click', function () {
                            durationButtons.forEach(function (b) { b.classList.remove('pd-flex-dur-active'); });
                            button.classList.add('pd-flex-dur-active');

                            var radio = document.getElementById('pd-dur-' + button.dataset.durationId);
                            if (radio) radio.checked = true;

                            recalc();
                        });
                    });

                    recalc();
                })();
                </script>
              @endif
            @elseif ($plan->durations->isNotEmpty())
                {{-- Estimated Calculator + Portfolio Preview (pure CSS, radios
                     + :has() - see .pd-tabs above for the same technique).
                     Amount is fixed per plan, durations are a small admin-set
                     list, so "live" calculation is really "pick a duration,
                     see its precomputed numbers" - no JS needed. --}}
                <div class="pd-calc">
                    <style>
                        .pd-calc-pane { display: none; }
                        @foreach ($plan->durations as $duration)
                        .pd-calc:has(#pd-dur-{{ $duration->id }}:checked) .pd-calc-pane-{{ $duration->id }} { display: block; }
                        .pd-calc:has(#pd-dur-{{ $duration->id }}:checked) .pd-calc-label-{{ $duration->id }} {
                            background: linear-gradient(to right, #0A5C66, #0E7481); color: #fff; border-color: transparent;
                        }
                        @endforeach
                    </style>

                    <div class="bg-white rounded-[24px] border border-slate-100 shadow-sm p-5">
                        <h4 class="text-[14px] font-black text-[#0A5C66] font-poppins mb-3">Estimated Calculator</h4>

                        <div class="flex gap-2 mb-4">
                            @foreach ($plan->durations as $duration)
                                <label for="pd-dur-{{ $duration->id }}" class="pd-calc-label-{{ $duration->id }} flex-1 h-10 rounded-full border border-slate-200 text-[12.5px] font-bold text-slate-600 flex items-center justify-center cursor-pointer transition-all">{{ $duration->label }}</label>
                            @endforeach
                        </div>

                        @foreach ($plan->durations as $duration)
                            @php
                                $afterInvestment = max(0, $balance - (float) $plan->investment_amount);
                            @endphp
                            <div class="pd-calc-pane pd-calc-pane-{{ $duration->id }} space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="stat-card">
                                        <div class="icon bg-[#19B36B]/5 border border-[#19B36B]/10 text-[#19B36B]"><i class="bi bi-piggy-bank text-[18px]"></i></div>
                                        <div class="flex flex-col min-w-0"><span class="title">Expected Return</span><span class="value text-[#19B36B]">₹{{ number_format((float) $duration->total_return, 2) }}</span></div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="icon bg-[#19B36B]/5 border border-[#19B36B]/10 text-[#19B36B]"><i class="bi bi-graph-up-arrow text-[18px]"></i></div>
                                        <div class="flex flex-col min-w-0"><span class="title">Daily Growth</span><span class="value text-[#19B36B]">₹{{ number_format((float) $duration->daily_profit, 2) }}</span></div>
                                    </div>
                                    <div class="stat-card col-span-2">
                                        <div class="icon bg-[#0A5C66]/5 border border-[#0A5C66]/10 text-[#0A5C66]"><i class="bi bi-calendar2-check text-[18px]"></i></div>
                                        <div class="flex flex-col min-w-0"><span class="title">Maturity Date</span><span class="value text-[#0A5C66]">{{ $duration->projectedMaturityDate()->format('d M Y') }}</span></div>
                                    </div>
                                </div>

                                <div class="bg-[#0A5C66]/5 rounded-2xl p-4 border border-[#0A5C66]/10">
                                    <span class="text-[10.5px] font-bold text-[#0A5C66] uppercase tracking-wider font-poppins">Portfolio Preview</span>
                                    <div class="grid grid-cols-3 gap-2 mt-2.5 text-left">
                                        <div class="flex flex-col"><span class="text-[9.5px] font-semibold text-slate-400 uppercase">Current Balance</span><span class="text-[12.5px] font-black text-[#0A5C66] font-poppins">₹{{ number_format($balance, 0) }}</span></div>
                                        <div class="flex flex-col"><span class="text-[9.5px] font-semibold text-slate-400 uppercase">After Investment</span><span class="text-[12.5px] font-black text-[#0A5C66] font-poppins">₹{{ number_format($afterInvestment, 0) }}</span></div>
                                        <div class="flex flex-col"><span class="text-[9.5px] font-semibold text-slate-400 uppercase">Expected Maturity</span><span class="text-[12.5px] font-black text-[#19B36B] font-poppins">₹{{ number_format((float) $duration->total_return, 0) }}</span></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($plan->highlights)
                {{-- Plan Highlights chips --}}
                <div class="flex flex-wrap gap-2">
                    @foreach ($plan->highlights as $highlight)
                        <span class="inline-flex items-center gap-1.5 bg-[#0A5C66]/5 border border-[#0A5C66]/10 text-[#0A5C66] text-[10.5px] font-bold px-3 py-1.5 rounded-full">
                            <i class="bi bi-stars text-[10px]"></i> {{ $highlight }}
                        </span>
                    @endforeach
                    @if ($plan->risk_level)
                        <span class="inline-flex items-center gap-1.5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-[10.5px] font-bold px-3 py-1.5 rounded-full">
                            <i class="bi bi-shield-check text-[10px]"></i> {{ $plan->risk_level }} Risk
                        </span>
                    @endif
                </div>
            @endif

            {{-- Progress Timeline - preview by default, highlights the real
                 current step once the viewer actually holds this plan. --}}
            @php
                $timelineSteps = ['Deposit', 'Investment Active', 'Daily Growth', 'Maturity', 'Withdraw'];
                $activeStepIndex = null;
                if ($existingHolding) {
                    $activeStepIndex = $existingHolding->status === \App\Models\UserPlan::STATUS_WITHDRAWN ? 4
                        : ($existingHolding->matures_at && $existingHolding->matures_at->isPast() ? 3 : 1);
                }
            @endphp
            <div class="bg-white rounded-[24px] border border-slate-100 shadow-sm p-5">
                <h4 class="text-[14px] font-black text-[#0A5C66] font-poppins mb-4">Investment Journey</h4>
                <div class="flex items-start">
                    @foreach ($timelineSteps as $index => $step)
                        <div class="flex flex-col items-center gap-1.5 flex-1">
                            <div @class([
                                'w-7 h-7 rounded-full flex items-center justify-center shrink-0 text-[11px] font-bold',
                                'bg-[#0A5C66] text-white' => $activeStepIndex !== null && $index <= $activeStepIndex,
                                'bg-slate-100 text-slate-400' => $activeStepIndex === null || $index > $activeStepIndex,
                            ])>
                                @if ($activeStepIndex !== null && $index < $activeStepIndex)
                                    <i class="bi bi-check-lg"></i>
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </div>
                            <span class="text-[9px] font-bold text-slate-500 text-center leading-tight font-poppins">{{ $step }}</span>
                        </div>
                        @if (! $loop->last)
                            <div @class([
                                'h-[2px] flex-1 mt-3.5 mx-[-6px]',
                                'bg-[#0A5C66]' => $activeStepIndex !== null && $index < $activeStepIndex,
                                'bg-slate-100' => $activeStepIndex === null || $index >= $activeStepIndex,
                            ])></div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- ================= Real tab switcher (radios + :has(), no JS
                 needed for the main app) - see .pd-tabs rules in app.css ================= -->
            <div class="pd-tabs">
                <input type="radio" name="pd-tab" id="pd-tab-summary" class="hidden" checked>
                <input type="radio" name="pd-tab" id="pd-tab-benefits" class="hidden">
                <input type="radio" name="pd-tab" id="pd-tab-details" class="hidden">
                <input type="radio" name="pd-tab" id="pd-tab-faqs" class="hidden">
                <input type="radio" name="pd-tab" id="pd-tab-terms" class="hidden">

                <div class="w-full h-[46px] p-1 bg-slate-100 rounded-full flex items-center gap-0.5 border border-slate-200/40 relative select-none shrink-0 overflow-x-auto hide-scrollbar">
                    <label for="pd-tab-summary" class="pd-tab-label pd-tab-label-summary flex-1 min-w-[62px] h-full rounded-full text-[12.5px] tracking-wide flex items-center justify-center cursor-pointer transition-all whitespace-nowrap px-2">Summary</label>
                    <label for="pd-tab-benefits" class="pd-tab-label pd-tab-label-benefits flex-1 min-w-[62px] h-full rounded-full text-[12.5px] tracking-wide flex items-center justify-center cursor-pointer transition-all whitespace-nowrap px-2">Benefits</label>
                    <label for="pd-tab-details" class="pd-tab-label pd-tab-label-details flex-1 min-w-[62px] h-full rounded-full text-[12.5px] tracking-wide flex items-center justify-center cursor-pointer transition-all whitespace-nowrap px-2">Details</label>
                    <label for="pd-tab-faqs" class="pd-tab-label pd-tab-label-faqs flex-1 min-w-[62px] h-full rounded-full text-[12.5px] tracking-wide flex items-center justify-center cursor-pointer transition-all whitespace-nowrap px-2">FAQs</label>
                    <label for="pd-tab-terms" class="pd-tab-label pd-tab-label-terms flex-1 min-w-[62px] h-full rounded-full text-[12.5px] tracking-wide flex items-center justify-center cursor-pointer transition-all whitespace-nowrap px-2">Terms</label>
                </div>

                <!-- Tab Content Area -->
                <div class="w-full space-y-[14px] mt-[14px]">

                <!-- SUMMARY -->
                <div class="pd-tab-pane pd-tab-pane-summary">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="stat-card">
                            <div class="icon bg-[#0A5C66]/5 border border-[#0A5C66]/10 text-[#0A5C66]">
                                <i class="bi bi-graph-up-arrow text-[18px] text-[#0A5C66]"></i>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="title">Expected Growth</span>
                                <span class="value text-[#0A5C66]">{{ $p['expectedGrowth'] }}</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="icon bg-[#0A5C66]/5 border border-[#0A5C66]/10 text-[#0A5C66]">
                                <i class="bi bi-calendar2-check text-[18px] text-[#0A5C66]"></i>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="title">Lock Duration</span>
                                <span class="value text-[#0A5C66]">{{ $p['lockDuration'] }}</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="icon bg-[#19B36B]/5 border border-[#19B36B]/10 text-[#19B36B]">
                                <i class="bi bi-wallet2 text-[18px] text-[#19B36B]"></i>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="title">Daily Profit</span>
                                <span class="value text-[#19B36B]">{{ $p['dailyProfit'] }}</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="icon bg-[#19B36B]/5 border border-[#19B36B]/10 text-[#19B36B]">
                                <i class="bi bi-piggy-bank text-[18px] text-[#19B36B]"></i>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="title">Total Return</span>
                                <span class="value text-[#19B36B]">{{ $p['totalReturn'] }}</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="icon bg-[#0A5C66]/5 border border-[#0A5C66]/10 text-[#0A5C66]">
                                <i class="bi bi-wallet2 text-[18px] text-[#0A5C66]"></i>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="title">Plan Investment</span>
                                <span class="value text-[#0A5C66]">{{ $p['planInvestment'] }}</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="icon bg-[#19B36B]/5 border border-[#19B36B]/10 text-[#19B36B]">
                                <i class="bi bi-shield-check text-[18px] text-[#19B36B]"></i>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="title">Secure Withdrawal</span>
                                <span class="value text-[#19B36B]">Instant & Safe</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BENEFITS -->
                <div class="pd-tab-pane pd-tab-pane-benefits">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-2">
                            <div class="w-8 h-8 rounded-full bg-[#19B36B]/8 flex items-center justify-center text-[#19B36B]">
                                <i class="bi bi-graph-up-arrow text-[18px]"></i>
                            </div>
                            <span class="text-[12px] font-black text-[#0A5C66] font-poppins">Verified Performance</span>
                            <span class="text-[10px] text-slate-400 font-medium font-poppins leading-normal">Stable historical growth tracking</span>
                        </div>
                        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-2">
                            <div class="w-8 h-8 rounded-full bg-[#3CCF91]/12 flex items-center justify-center text-[#3CCF91]">
                                <i class="bi bi-stars text-[18px]"></i>
                            </div>
                            <span class="text-[12px] font-black text-[#0A5C66] font-poppins">Beginner Friendly</span>
                            <span class="text-[10px] text-slate-400 font-medium font-poppins leading-normal">Start investing with simple steps</span>
                        </div>
                        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-2">
                            <div class="w-8 h-8 rounded-full bg-[#0A5C66]/5 flex items-center justify-center text-[#0A5C66]">
                                <i class="bi bi-shield-check text-[18px]"></i>
                            </div>
                            <span class="text-[12px] font-black text-[#0A5C66] font-poppins">Trusted Returns</span>
                            <span class="text-[10px] text-slate-400 font-medium font-poppins leading-normal">Protected investment experience</span>
                        </div>
                        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-2">
                            <div class="w-8 h-8 rounded-full bg-[#0A5C66]/5 flex items-center justify-center text-[#0A5C66]">
                                <i class="bi bi-bullseye text-[18px]"></i>
                            </div>
                            <span class="text-[12px] font-black text-[#0A5C66] font-poppins">Smart Goal Planning</span>
                            <span class="text-[10px] text-slate-400 font-medium font-poppins leading-normal">Designed for long-term goals</span>
                        </div>
                    </div>
                </div>

                <!-- DETAILS -->
                <div class="pd-tab-pane pd-tab-pane-details">
                    <div class="bg-white p-5 rounded-[24px] border border-slate-100 shadow-sm space-y-4 text-left">
                        <div class="flex justify-between items-center py-2.5 border-b border-slate-100/50">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider font-poppins">Plan Category</span>
                            <span class="text-[13px] font-black text-[#0A5C66] font-poppins">{{ $p['badge'] }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2.5 border-b border-slate-100/50">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider font-poppins">Investment Type</span>
                            <span class="text-[13px] font-black text-[#0A5C66] font-poppins">One-Time Goal Plan</span>
                        </div>
                        <div class="flex justify-between items-center py-2.5 border-b border-slate-100/50">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider font-poppins">Maturity Period</span>
                            <span class="text-[13px] font-black text-[#0A5C66] font-poppins">{{ $p['lockDuration'] }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2.5 border-b border-slate-100/50">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider font-poppins">Withdrawal Policy</span>
                            <span class="text-[13px] font-black text-[#0A5C66] font-poppins">{{ $p['lockDuration'] === 'Flexible' ? 'Withdraw anytime' : 'Available after lock-in ends' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2.5 border-b border-slate-100/50">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider font-poppins">Activation Time</span>
                            <span class="text-[13px] font-black text-[#0A5C66] font-poppins">Instant after successful purchase</span>
                        </div>
                        <div class="flex justify-between items-center py-2.5 border-b border-slate-100/50">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider font-poppins">Support</span>
                            <span class="text-[13px] font-black text-[#0A5C66] font-poppins">24×7 Help Available</span>
                        </div>
                        <div class="flex justify-between items-center py-2.5">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider font-poppins">Risk Level</span>
                            <span class="text-[13px] font-black text-[#19B36B] font-poppins">{{ $plan->risk_level ?? 'Low' }} Risk</span>
                        </div>
                    </div>
                </div>

                <!-- FAQS -->
                <div class="pd-tab-pane pd-tab-pane-faqs">
                    @php
                        $faqs = $plan->faqs ?: [
                            ['q' => 'How returns work?', 'a' => 'Returns are estimated based on selected plan performance and payout duration.'],
                            ['q' => 'Can I withdraw anytime?', 'a' => 'Flexible plans allow easier withdrawals while locked plans unlock after maturity.'],
                            ['q' => 'Is my investment secure?', 'a' => 'Your transactions and account activity are protected with secure verification systems.'],
                            ['q' => 'When do payouts start?', 'a' => 'Daily growth tracking and payout timelines begin after successful activation.'],
                        ];
                    @endphp
                    <div class="divide-y divide-slate-100 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                        @foreach ($faqs as $i => $faq)
                            <div class="faq-item">
                                <label class="w-full px-5 py-4 flex items-center justify-between text-left cursor-pointer active:bg-slate-50 transition-colors">
                                    <input type="checkbox" class="hidden faq-check">
                                    <span class="text-[13px] font-extrabold text-[#0A5C66] font-poppins pr-3">{{ $faq['q'] }}</span>
                                    <i class="bi bi-chevron-down faq-chevron text-[16px] text-slate-400 shrink-0"></i>
                                </label>
                                <div class="faq-answer">
                                    <p class="px-5 pb-4 text-[12px] text-slate-500 font-medium leading-relaxed font-poppins">{{ $faq['a'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- TERMS -->
                <div class="pd-tab-pane pd-tab-pane-terms">
                    <div class="bg-white p-5 rounded-[24px] border border-slate-100 shadow-sm text-left">
                        @if ($plan->terms)
                            <p class="text-[12.5px] text-slate-600 font-medium leading-relaxed font-poppins whitespace-pre-line">{{ $plan->terms }}</p>
                        @else
                            <p class="text-[12.5px] text-slate-400 font-medium leading-relaxed font-poppins">Standard GullakPe investment terms apply to this plan. Returns shown are estimated and may vary depending on plan conditions.</p>
                        @endif
                    </div>
                </div>

                </div>
            </div>

            <!-- BUY SECTION -->
            @php
                $potStillOpen = $activePot
                    ? ((float) $plan->max_investment_amount - (float) $activePot->invested_amount) > 0
                    : null;
            @endphp
            <div class="buy-action-card">
                @auth
                    @if ($activePot && ! $potStillOpen)
                        <div class="w-full h-14 rounded-full bg-slate-100 text-slate-400 font-black text-[15px] font-poppins flex items-center justify-center gap-2">
                            <i class="bi bi-check-circle-fill"></i> Maximum Investment Reached
                        </div>
                    @else
                        <form id="plan-purchase-form" method="POST" action="{{ route('plans.purchase', $plan) }}">
                            @csrf
                            <button type="submit" class="w-full h-14 rounded-full bg-gradient-to-r from-[#0A5C66] to-[#0E7481] text-white font-black text-[15px] font-poppins flex items-center justify-center gap-2 shadow-lg shadow-[#0A5C66]/20 active:scale-[0.98] transition-all">
                                @if ($activePot)
                                    <span>Add <span id="pd-invest-btn-amount">₹{{ number_format($potDefaultAdd, 0) }}</span> to Investment</span>
                                @elseif ($plan->isFlexibleAmount())
                                    <span>Invest Now · <span id="pd-invest-btn-amount">₹{{ number_format((float) $plan->min_investment_amount, 0) }}</span></span>
                                @else
                                    <span>Invest Now · {{ $p['planInvestment'] }}</span>
                                @endif
                                <i class="bi bi-arrow-right"></i>
                            </button>
                            <p class="text-center text-[11px] text-slate-400 font-semibold font-poppins mt-2">Wallet balance: ₹{{ number_format($balance, 2) }} · Fast • Secure • Verified</p>
                        </form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="w-full h-14 rounded-full bg-gradient-to-r from-[#0A5C66] to-[#0E7481] text-white font-black text-[15px] font-poppins flex items-center justify-center gap-2 shadow-lg shadow-[#0A5C66]/20 active:scale-[0.98] transition-all">
                        <span>Log In to Invest · {{ $p['planInvestment'] }}</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                @endauth
            </div>

            <!-- Trust & Security Strip -->
            <div class="flex flex-wrap items-center justify-center gap-2 pt-2 pb-1">
                <div class="flex items-center gap-1.5 bg-[#0A5C66]/5 border border-[#0A5C66]/10 px-3 py-1.5 rounded-full shadow-sm">
                    <i class="bi bi-shield-check text-[14px] text-[#0A5C66]"></i>
                    <span class="text-[10px] font-bold text-[#0A5C66] font-poppins">Secure Encryption</span>
                </div>
                <div class="flex items-center gap-1.5 bg-[#0A5C66]/5 border border-[#0A5C66]/10 px-3 py-1.5 rounded-full shadow-sm">
                    <i class="bi bi-lock-fill text-[14px] text-[#0A5C66]"></i>
                    <span class="text-[10px] font-bold text-[#0A5C66] font-poppins">Protected Transactions</span>
                </div>
                <div class="flex items-center gap-1.5 bg-[#0A5C66]/5 border border-[#0A5C66]/10 px-3 py-1.5 rounded-full shadow-sm">
                    <i class="bi bi-check-circle-fill text-[14px] text-[#0A5C66]"></i>
                    <span class="text-[10px] font-bold text-[#0A5C66] font-poppins">Verified Platform</span>
                </div>
            </div>

            <!-- Support Section -->
            <div class="bg-white rounded-[24px] border border-slate-100 shadow-sm p-4 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-[#0E7680]/10 flex items-center justify-center text-[#0E7680] shrink-0">
                        <i class="bi bi-headset text-[20px]"></i>
                    </div>
                    <div>
                        <h5 class="text-[13px] font-black text-[#0A5C66] font-poppins">Need Help?</h5>
                        <p class="text-[10.5px] text-slate-500 font-medium font-poppins leading-normal">Our support team is available to assist your goal journey.</p>
                    </div>
                </div>
                <button onclick="window.contactSupport()" class="shrink-0 bg-[#0A5C66] text-white font-extrabold text-[11px] px-3.5 py-2.5 rounded-full active:scale-95 transition-all shadow-md shadow-[#0A5C66]/10 font-poppins">Contact Support</button>
            </div>

            <!-- Legal Links -->
            <div class="flex items-center justify-center gap-3 text-[10.5px] font-bold text-[#0A5C66] font-poppins opacity-60 pt-2">
                <a href="#privacy" onclick="window.showLegal('Privacy Policy'); return false;" class="hover:underline hover:opacity-100 transition-opacity">Privacy Policy</a>
                <span class="w-1 h-1 rounded-full bg-[#0A5C66]/30"></span>
                <a href="#terms" onclick="window.showLegal('Terms & Conditions'); return false;" class="hover:underline hover:opacity-100 transition-opacity">Terms & Conditions</a>
                <span class="w-1 h-1 rounded-full bg-[#0A5C66]/30"></span>
                <a href="#refunds" onclick="window.showLegal('Refund Policy'); return false;" class="hover:underline hover:opacity-100 transition-opacity">Refund Policy</a>
                <span class="w-1 h-1 rounded-full bg-[#0A5C66]/30"></span>
                <a href="#support" onclick="window.contactSupport(); return false;" class="hover:underline hover:opacity-100 transition-opacity">Help & Support</a>
            </div>

            <!-- Disclaimer -->
            <p class="text-[9.5px] font-semibold text-slate-400 font-poppins text-center leading-normal max-w-[280px] mx-auto opacity-80 pt-1 pb-4">
                Returns shown are estimated values and may vary depending on selected plan conditions.
            </p>
        </div>
    </div>
@endsection
