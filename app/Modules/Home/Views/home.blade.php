@extends('layouts.app')

@section('content')
    <div id="tab-home" class="flex flex-col flex-1 bg-[#F8FAFC] min-h-[100dvh] pb-28 pt-safe overflow-y-auto custom-scrollbar w-full">

        <!-- Header -->
        <div class="px-5 pt-6 pb-2 flex items-center justify-between">
            <div class="min-w-0">
                <h1 class="text-[21px] font-black text-[#0F172A] font-poppins tracking-tight leading-tight">
                    <span>Hello,</span> @if ($user){{ explode(' ', $user->name)[0] }}@else<span>Guest</span>@endif
                </h1>
                <p class="text-[13px] text-slate-500 font-medium mt-0.5">
                    @if ($user)
                        Welcome back
                    @else
                        <a href="{{ route('login') }}" class="text-[#0A5C66] font-bold hover:underline">Sign in to get started</a>
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('portfolio') }}" aria-label="Transaction history"
                    class="relative w-10 h-10 rounded-full bg-white border border-slate-200/80 flex items-center justify-center text-slate-500 shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition-all active:scale-95 hover:border-[#0A5C66]/25 hover:text-[#0A5C66] shrink-0">
                    <i class="bi bi-clock-history text-[16px]"></i>
                </a>
                <button type="button" onclick="window.toggleLanguage && window.toggleLanguage()" aria-label="Switch language"
                    class="relative w-10 h-10 rounded-full bg-white border border-slate-200/80 flex items-center justify-center text-slate-500 shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition-all active:scale-95 hover:border-[#0A5C66]/25 hover:text-[#0A5C66] shrink-0">
                    <i class="bi bi-translate text-[16px]"></i>
                    <span data-current-lang class="absolute -bottom-1 -right-1 bg-[#0A5C66] text-white text-[8px] font-black px-1 rounded-full leading-tight border-2 border-[#F8FAFC]">EN</span>
                </button>
                <a href="{{ route('notifications') }}" aria-label="Notifications" class="relative w-10 h-10 rounded-full bg-white border border-slate-200/80 flex items-center justify-center text-slate-500 shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition-all active:scale-95 hover:border-[#0A5C66]/25 hover:text-[#0A5C66] shrink-0">
                    <i class="fa-regular fa-bell text-[16px]"></i>
                    @if ($unreadNotificationCount > 0)
                        <span class="absolute -top-1 -right-1 bg-[#EF4444] text-white text-[9.5px] font-bold h-[18px] min-w-[18px] px-1 rounded-full flex items-center justify-center border-2 border-[#F8FAFC]">
                            {{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}
                        </span>
                    @endif
                </a>
            </div>
        </div>

        <!-- Portfolio Value Hero -->
        <div class="px-5 mt-3">
            <div class="bg-gradient-to-br from-[#0A5C66] via-[#0A5C66] to-[#04242F] rounded-[22px] p-5 text-white relative overflow-hidden">
                <div class="absolute -top-20 -right-10 w-[200px] h-[200px] bg-[#3FEA8A]/[0.08] rounded-full blur-[50px] pointer-events-none"></div>

                <div class="relative z-10 flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <span class="text-[10.5px] font-bold text-white/55 uppercase tracking-wider font-poppins">Total Portfolio Value</span>
                        <div class="text-[28px] font-black text-white font-poppins tracking-tight mt-1.5 leading-none truncate">₹{{ number_format($totalCurrentValue, 2) }}</div>
                        <div class="flex items-center gap-1.5 mt-2.5 flex-wrap">
                            @if ($totalReturns >= 0)
                                <span class="inline-flex items-center gap-1 bg-[#3FEA8A]/15 text-[#5FF0A0] text-[11px] font-bold px-2 py-0.5 rounded-full">
                                    <i class="fa-solid fa-arrow-up text-[8px]"></i> ₹{{ number_format($totalReturns, 2) }} ({{ number_format($returnsPct, 2) }}%)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 bg-red-400/15 text-red-300 text-[11px] font-bold px-2 py-0.5 rounded-full">
                                    <i class="fa-solid fa-arrow-down text-[8px]"></i> ₹{{ number_format(abs($totalReturns), 2) }} ({{ number_format(abs($returnsPct), 2) }}%)
                                </span>
                            @endif
                            <span class="text-[10.5px] text-white/40 font-semibold">All time</span>
                        </div>
                    </div>

                    @if ($totalCurrentValue > 0)
                        @php
                            $sparkW = 124;
                            $sparkH = 56;
                            $sparkColor = $totalReturns >= 0 ? '#3FEA8A' : '#F87171';
                            $sparkValues = collect($chartPoints)->pluck('value')->values();
                            $sparkMin = $sparkValues->min();
                            $sparkMax = $sparkValues->max();
                            $sparkRange = max(0.01, $sparkMax - $sparkMin);
                            $sparkCoords = $sparkValues->map(function ($v, $i) use ($sparkValues, $sparkMin, $sparkRange, $sparkW, $sparkH) {
                                $x = $sparkValues->count() > 1 ? ($i / ($sparkValues->count() - 1)) * $sparkW : 0;
                                $y = $sparkH - (($v - $sparkMin) / $sparkRange) * ($sparkH - 8) - 4;
                                return [round($x, 1), round($y, 1)];
                            });
                            $sparkLine = $sparkCoords->map(fn ($c) => $c[0].','.$c[1])->implode(' ');
                            $sparkArea = '0,'.$sparkH.' '.$sparkLine.' '.$sparkW.','.$sparkH;
                            $sparkLast = $sparkCoords->last();
                            $sparkGradId = 'home-spark-fill-'.uniqid();
                        @endphp
                        <div class="w-[124px] h-[56px] shrink-0 overflow-visible select-none mt-1">
                            <svg viewBox="0 0 {{ $sparkW }} {{ $sparkH }}" class="w-full h-full overflow-visible">
                                <defs>
                                    <linearGradient id="{{ $sparkGradId }}" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="{{ $sparkColor }}" stop-opacity="0.4" />
                                        <stop offset="100%" stop-color="{{ $sparkColor }}" stop-opacity="0" />
                                    </linearGradient>
                                </defs>
                                <polygon points="{{ $sparkArea }}" fill="url(#{{ $sparkGradId }})" />
                                <polyline points="{{ $sparkLine }}" fill="none" stroke="{{ $sparkColor }}" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" class="spark-line-draw" />
                                <circle cx="{{ $sparkLast[0] }}" cy="{{ $sparkLast[1] }}" r="4" fill="{{ $sparkColor }}" opacity="0.35" class="spark-dot-pulse" />
                                <circle cx="{{ $sparkLast[0] }}" cy="{{ $sparkLast[1] }}" r="2.5" fill="{{ $sparkColor }}" stroke="#04242F" stroke-width="1" />
                            </svg>
                        </div>
                    @endif
                </div>

                <div class="relative z-10 mt-5 pt-4 border-t border-white/10 flex items-center justify-between">
                    <div class="flex items-center gap-1.5 text-[11px] font-semibold text-white/50">
                        <i class="fa-solid fa-shield-halved text-[10px] text-[#3FEA8A]"></i> Secure &amp; RBI-aligned
                    </div>
                    <a href="{{ route('portfolio') }}" class="inline-flex items-center gap-1 text-[12px] font-bold text-white transition-all active:scale-95 hover:gap-1.5">
                        <span>View portfolio</span> <i class="fa-solid fa-chevron-right text-[9px]"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Add Amount -->
        <div class="px-5 mt-4">
            <div class="bg-white rounded-[22px] border border-slate-100 shadow-[0_1px_2px_rgba(15,23,42,0.04)] p-5">
                <form id="quick-add-amount-form" method="POST" action="{{ route('deposits.start') }}">
                    @csrf
                    <div class="flex items-center justify-between">
                        <span class="text-[12px] font-bold text-slate-400 uppercase tracking-wide font-poppins">Enter Amount to Add</span>
                        <button type="button" id="quick-add-amount-reset" aria-label="Reset amount"
                            class="w-7 h-7 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 transition-colors hover:text-[#0A5C66] hover:border-[#0A5C66]/25">
                            <i class="bi bi-arrow-clockwise text-[13px]"></i>
                        </button>
                    </div>

                    <div class="flex items-baseline gap-1 mt-2">
                        <span class="text-[26px] font-black text-[#0F172A] font-poppins">₹</span>
                        <input type="number" name="amount" id="quick-add-amount-input" min="1" step="1" value="500" placeholder="0"
                            class="w-full bg-transparent outline-none text-[28px] font-black text-[#0F172A] font-poppins tracking-tight placeholder:text-slate-300">
                    </div>

                    <div class="flex items-center gap-2 mt-2.5 flex-wrap">
                        <span class="inline-flex items-center gap-1 bg-[#19B36B]/10 text-[#19B36B] text-[10.5px] font-bold px-2 py-0.5 rounded-full">
                            <i class="bi bi-lightning-charge-fill text-[9px]"></i> Instant Deposit
                        </span>
                        <span class="text-[11px] text-slate-400 font-semibold">Most users add ₹5,000</span>
                    </div>

                    @php
                        $quickAmountPresets = [
                            ['value' => 100, 'label' => '₹100'],
                            ['value' => 500, 'label' => '₹500'],
                            ['value' => 1000, 'label' => '₹1,000'],
                            ['value' => 2000, 'label' => '₹2,000'],
                            ['value' => 5000, 'label' => '₹5,000'],
                            ['value' => 10000, 'label' => '₹10k'],
                            ['value' => 20000, 'label' => '₹20k'],
                            ['value' => 50000, 'label' => '₹50k'],
                        ];
                    @endphp
                    <div class="grid grid-cols-4 gap-2 mt-4">
                        @foreach ($quickAmountPresets as $preset)
                            <button type="button" data-quick-amount="{{ $preset['value'] }}"
                                class="quick-amount-chip relative flex items-center justify-center h-10 rounded-[12px] border text-[12.5px] font-bold transition-all active:scale-95
                                    {{ $preset['value'] === 500 ? 'border-[#0A5C66] bg-[#0A5C66]/5 text-[#0A5C66] ring-1 ring-[#0A5C66]/20' : 'border-slate-100 bg-slate-50 text-slate-600' }}">
                                @if ($preset['value'] === 500)
                                    <span class="absolute -top-2 left-1/2 -translate-x-1/2 bg-[#0A5C66] text-white text-[8px] font-bold px-1.5 py-[1px] rounded-full whitespace-nowrap">Most Popular</span>
                                @endif
                                {{ $preset['label'] }}
                            </button>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-3 mt-4">
                        <a href="{{ route('withdrawals.create') }}"
                            class="flex-1 h-12 rounded-[14px] border border-[#0A5C66]/20 text-[#0A5C66] font-bold text-[13.5px] flex items-center justify-center gap-2 transition-all active:scale-[0.98] hover:bg-[#0A5C66]/5">
                            <i class="bi bi-box-arrow-right text-[14px]"></i> Withdraw
                        </a>
                        <button type="submit"
                            class="btn-shimmer-cta flex-1 h-12 rounded-[14px] bg-gradient-to-br from-[#0A5C66] via-[#0A5C66] to-[#04242F] text-white font-bold text-[13.5px] flex items-center justify-center gap-2 transition-all active:scale-[0.98] hover:brightness-110">
                            <i class="bi bi-plus-lg text-[14px]"></i> Add Money
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            (function () {
                var form = document.getElementById('quick-add-amount-form');
                if (!form) return;

                var input = document.getElementById('quick-add-amount-input');
                var chips = form.querySelectorAll('[data-quick-amount]');
                var defaultAmount = input.value;
                var activeClasses = ['border-[#0A5C66]', 'bg-[#0A5C66]/5', 'text-[#0A5C66]', 'ring-1', 'ring-[#0A5C66]/20'];
                var inactiveClasses = ['border-slate-100', 'bg-slate-50', 'text-slate-600'];

                function setActiveChip(amount) {
                    chips.forEach(function (chip) {
                        var isActive = chip.getAttribute('data-quick-amount') === String(amount);
                        chip.classList.remove.apply(chip.classList, isActive ? inactiveClasses : activeClasses);
                        chip.classList.add.apply(chip.classList, isActive ? activeClasses : inactiveClasses);
                    });
                }

                chips.forEach(function (chip) {
                    chip.addEventListener('click', function () {
                        var amount = chip.getAttribute('data-quick-amount');
                        input.value = amount;
                        setActiveChip(amount);
                    });
                });

                input.addEventListener('input', function () {
                    setActiveChip(input.value);
                });

                document.getElementById('quick-add-amount-reset').addEventListener('click', function () {
                    input.value = defaultAmount;
                    setActiveChip(defaultAmount);
                });
            })();
        </script>

        <!-- Quick Summary -->
        <div class="px-5 mt-8">
            <div class="flex items-center justify-between mb-4 pr-0.5">
                <h3 class="text-[19px] font-black text-[#0F172A] font-poppins tracking-tight">Quick Summary</h3>
                <a href="{{ route('portfolio') }}" class="text-[13px] font-bold text-[#0A5C66] hover:underline">View all</a>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="flex items-center gap-3 bg-white rounded-[22px] border border-slate-100 shadow-[0_4px_16px_rgba(15,23,42,0.06)] p-4">
                    <div class="w-11 h-11 rounded-full bg-[#19B36B]/10 flex items-center justify-center text-[#19B36B] shrink-0">
                        <i class="bi bi-wallet2 text-[18px]"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[11.5px] font-semibold text-slate-400 font-poppins truncate">Invested Amount</span>
                        <div class="text-[15px] font-extrabold text-[#0F172A] font-poppins mt-1 leading-none truncate">₹{{ number_format($totalInvested, 2) }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-white rounded-[22px] border border-slate-100 shadow-[0_4px_16px_rgba(15,23,42,0.06)] p-4">
                    <div class="w-11 h-11 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-500 shrink-0">
                        <i class="bi bi-pie-chart-fill text-[17px]"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[11.5px] font-semibold text-slate-400 font-poppins truncate">Total Returns</span>
                        <div class="text-[15px] font-extrabold text-[#0F172A] font-poppins mt-1 leading-none truncate">₹{{ number_format($totalReturns, 2) }}</div>
                        <div class="text-[11.5px] font-bold text-[#19B36B] mt-1">{{ $returnsPct >= 0 ? '+' : '' }}{{ number_format($returnsPct, 2) }}%</div>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-white rounded-[22px] border border-slate-100 shadow-[0_4px_16px_rgba(15,23,42,0.06)] p-4">
                    <div class="w-11 h-11 rounded-full bg-[#19B36B]/10 flex items-center justify-center text-[#19B36B] shrink-0">
                        <i class="bi bi-graph-up-arrow text-[18px]"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[11.5px] font-semibold text-slate-400 font-poppins truncate">Today's Gain</span>
                        <div class="text-[15px] font-extrabold text-[#0F172A] font-poppins mt-1 leading-none truncate">₹{{ number_format($todayProfit, 2) }}</div>
                        <div class="text-[11.5px] font-bold text-[#19B36B] mt-1">{{ $todayProfitPct >= 0 ? '+' : '' }}{{ number_format($todayProfitPct, 2) }}%</div>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-white rounded-[22px] border border-slate-100 shadow-[0_4px_16px_rgba(15,23,42,0.06)] p-4">
                    <div class="w-11 h-11 rounded-full bg-slate-200/70 flex items-center justify-center text-slate-500 shrink-0">
                        <i class="bi bi-bank text-[18px]"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[11.5px] font-semibold text-slate-400 font-poppins truncate">Available Balance</span>
                        <div class="text-[15px] font-extrabold text-[#0F172A] font-poppins mt-1 leading-none truncate">₹{{ number_format($balance, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Featured Investment Plans -->
        <div class="mt-7">
            <div class="flex items-center justify-between mb-2.5 px-5">
                <h3 class="text-[15px] font-black text-[#0F172A] font-poppins tracking-tight">Featured investment plans</h3>
                <a href="{{ route('explore') }}" class="text-[12px] font-bold text-[#0A5C66] hover:underline">View all</a>
            </div>

            @if ($featuredPlans->isNotEmpty())
                <div class="flex overflow-x-auto gap-3 pb-1 px-5 snap-x snap-mandatory hide-scrollbar">
                    @foreach ($featuredPlans as $plan)
                        @php $fp = $plan->toLegacyArray(); @endphp
                        <a href="{{ route('plan-details', $plan) }}" class="min-w-[152px] snap-center bg-white rounded-[18px] border border-slate-100 shadow-[0_1px_2px_rgba(15,23,42,0.04)] overflow-hidden shrink-0 transition-all active:scale-[0.98] hover:shadow-[0_8px_20px_-8px_rgba(15,23,42,0.15)] hover:border-slate-200">
                            <div class="h-[84px] w-full relative overflow-hidden">
                                <img src="{{ $fp['image'] }}" class="w-full h-full object-cover" alt="{{ $fp['title'] }}">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/45 via-black/0 to-transparent"></div>
                                <div class="absolute top-2 left-2 w-7 h-7 rounded-lg bg-white/95 flex items-center justify-center text-[#0A5C66] shadow-sm">
                                    <i class="bi {{ $fp['icon'] }} text-[12px]"></i>
                                </div>
                            </div>
                            <div class="p-3">
                                <h4 class="text-[12px] font-black text-[#0F172A] font-poppins leading-tight truncate">{{ $fp['title'] }}</h4>
                                <div class="flex items-baseline gap-1 mt-1.5">
                                    <span class="text-[13.5px] font-black text-[#19B36B] font-poppins">{{ $fp['growthRate'] }}%</span>
                                    <span class="text-[9px] font-bold text-slate-400">p.a.</span>
                                </div>
                                <p class="text-[9.5px] font-semibold text-slate-400 mt-1 pt-1 border-t border-slate-50"><span>Invest</span> {{ $fp['planInvestment'] }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Secure Your Future Banner -->
        <div class="px-5 mt-7 md:flex md:justify-start">
            <a href="{{ route('explore') }}" class="block rounded-[20px] overflow-hidden shadow-[0_4px_16px_rgba(15,23,42,0.08)] transition-all active:scale-[0.98] hover:shadow-[0_8px_24px_rgba(15,23,42,0.14)] md:max-w-[380px]">
                <img src="{{ asset('assets/home_banner.png') }}" alt="Secure Your Future - Invest in trusted assets and grow your wealth securely" class="w-full h-auto block">
            </a>
        </div>

        <!-- Trust Strip -->
        <div class="px-5 mt-4 mb-2">
            <div class="flex items-center justify-center gap-2.5 py-2">
                <div class="flex items-center -space-x-2 shrink-0">
                    @foreach (['A', 'B', 'C', 'D'] as $seed)
                        <div class="w-6 h-6 rounded-full border-2 border-[#F8FAFC] bg-slate-100 overflow-hidden">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Home{{ $seed }}" class="w-full h-full">
                        </div>
                    @endforeach
                </div>
                <p class="text-[11.5px] font-semibold text-slate-500">
                    @if ($totalInvestors > 0)
                        <span class="font-black text-[#0F172A]">{{ number_format($totalInvestors) }}</span>
                        <span>{{ $totalInvestors === 1 ? 'investor' : 'investors' }}</span>
                        <span>trust GullakPe</span>
                    @else
                        <span>Be among the first to invest on GullakPe</span>
                    @endif
                </p>
            </div>
        </div>

    </div>
@endsection
