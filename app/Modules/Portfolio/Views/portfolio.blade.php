@extends('layouts.app')

@section('content')
    <div id="tab-portfolio" class="flex min-h-[100dvh] flex-col flex-1 bg-[#F8FAFC] pb-28 pt-safe overflow-y-auto custom-scrollbar w-full">
        <!-- Header -->
        <div class="px-5 pt-8 pb-6 bg-[#0A5C66] rounded-b-[32px] text-white shadow-md relative overflow-hidden shrink-0">
            <div class="absolute inset-0 bg-white/5 mix-blend-overlay pointer-events-none"></div>
            <div class="flex items-center gap-2 relative z-10 mb-1">
                <h2 class="text-[28px] font-black tracking-tight font-poppins">Portfolio</h2>
                @if ($isVip)
                    <span class="inline-flex items-center gap-1 bg-amber-400/20 border border-amber-300/40 text-amber-200 text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full">
                        <i class="bi bi-gem text-[10px]"></i> VIP
                    </span>
                @endif
            </div>
            <p class="text-[14px] text-white/80 font-medium mb-1 relative z-10">Track your investments and growth.</p>
            @if (! $isVip && $purchaseCount > 0)
                <p class="text-[11px] text-white/60 font-semibold mb-5 relative z-10">{{ $purchaseCount }}/{{ $vipThreshold }} investments to unlock VIP Badge</p>
            @else
                <div class="mb-6"></div>
            @endif

            <div class="bg-white/10 rounded-2xl p-5 border border-white/20 backdrop-blur-sm relative z-10 shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="text-left">
                        <span class="text-[12px] font-bold uppercase tracking-wider text-white/70 block mb-0.5">Portfolio Overview</span>
                        <div class="text-[30px] font-black tracking-tight text-white font-poppins leading-none my-1">₹{{ number_format($totalCurrentValue, 2) }}</div>
                        <div class="text-[12px] font-bold text-[#3FEA8A] mt-0.5">+₹{{ number_format($todayProfit, 2) }} <span>Today</span></div>
                    </div>
                    @if ($activeCount > 0)
                        <div class="w-[110px] h-[45px] shrink-0 overflow-hidden select-none">
                            @php
                                $sparkValues = collect($chartPoints)->pluck('value')->slice(-7)->values();
                                $sparkMin = $sparkValues->min();
                                $sparkMax = $sparkValues->max();
                                $sparkRange = max(0.01, $sparkMax - $sparkMin);
                                $sparkPoints = $sparkValues->values()->map(function ($v, $i) use ($sparkValues, $sparkMin, $sparkRange) {
                                    $x = $sparkValues->count() > 1 ? ($i / ($sparkValues->count() - 1)) * 110 : 0;
                                    $y = 45 - (($v - $sparkMin) / $sparkRange) * 40 - 2.5;
                                    return round($x, 1).','.round($y, 1);
                                })->implode(' ');
                            @endphp
                            <svg viewBox="0 0 110 45" class="w-full h-full">
                                <polyline points="{{ $sparkPoints }}" fill="none" stroke="#3FEA8A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-2 pt-4 mt-4 border-t border-white/15">
                    <div class="text-left">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-white/60 block mb-0.5">Total Invested</span>
                        <span class="text-[14px] font-black text-white font-poppins">₹{{ number_format($totalInvested, 2) }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-white/60 block mb-0.5">Active Plans</span>
                        <span class="text-[14px] font-black text-white font-poppins">{{ $activeCount }} <span>{{ $activeCount === 1 ? 'Plan' : 'Plans' }}</span></span>
                    </div>
                </div>
            </div>
        </div>

        @if ($activeCount > 0)
            <!-- Active Investments Section -->
            <div class="px-5 mt-6 flex flex-col">
                <h3 class="text-[18px] font-black text-[#1a153a] tracking-tight font-poppins mb-4">Active Investments</h3>
                <div class="flex flex-col gap-4">
                    @foreach ($holdings as $holding)
                        <div class="bg-white rounded-[22px] border border-slate-100 shadow-sm p-4 flex items-center gap-3.5">
                            <div class="w-14 h-14 rounded-2xl bg-[#0A5C66]/5 flex items-center justify-center shrink-0 overflow-hidden">
                                <img src="{{ $holding['image'] }}" class="w-full h-full object-cover" alt="{{ $holding['title'] }}">
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-[14px] font-black text-[#1a153a] font-poppins truncate">{{ $holding['title'] }}</h4>
                                <p class="text-[11px] text-slate-400 font-medium truncate"><span>Invested</span> {{ $holding['purchasedAt']->format('d M Y') }} · {{ $holding['lockDuration'] }}</p>
                                <div class="flex items-center gap-1 mt-1">
                                    <span class="w-1.5 h-1.5 bg-[#3CCF91] rounded-full"></span>
                                    <span class="text-[10px] font-bold text-[#19B36B]">+₹{{ number_format($holding['dailyProfit'], 2) }}<span>/day</span></span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Value</span>
                                <span class="text-[15px] font-black text-[#0A5C66] font-poppins">₹{{ number_format($holding['currentValue'], 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($activeCount > 0)
            <!-- Portfolio Performance Section -->
            <div class="px-5 mt-8 flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-[18px] font-black text-[#1a153a] tracking-tight font-poppins text-left">Portfolio Growth</h3>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Last 30 Days</span>
                </div>

                <!-- Graph Card -->
                <div class="bg-white rounded-[24px] border border-slate-100 p-5 shadow-[0_4px_16px_rgba(0,0,0,0.02)] flex flex-col relative overflow-hidden">
                    @php
                        $chartValues = collect($chartPoints)->pluck('value');
                        $chartMin = $chartValues->min();
                        $chartMax = $chartValues->max();
                        $chartRange = max(0.01, $chartMax - $chartMin);
                        $chartCount = $chartValues->count();
                        $chartCoords = $chartValues->values()->map(function ($v, $i) use ($chartCount, $chartMin, $chartRange) {
                            $x = $chartCount > 1 ? ($i / ($chartCount - 1)) * 300 : 0;
                            $y = 160 - (($v - $chartMin) / $chartRange) * 150 - 5;
                            return [round($x, 1), round($y, 1)];
                        });
                        $chartLine = $chartCoords->map(fn ($c) => $c[0].','.$c[1])->implode(' ');
                        $chartArea = '0,160 '.$chartLine.' 300,160';
                    @endphp
                    <div class="relative w-full h-[180px] overflow-hidden">
                        <svg viewBox="0 0 300 180" preserveAspectRatio="none" class="w-full h-full">
                            <polygon points="{{ $chartArea }}" fill="#0A5C66" fill-opacity="0.08" />
                            <polyline points="{{ $chartLine }}" fill="none" stroke="#0A5C66" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <div class="flex items-center justify-between text-[10px] font-semibold text-slate-400 mt-1">
                        <span>{{ $chartPoints[0]['date']->format('d M') }}</span>
                        <span>Today</span>
                    </div>
                </div>
            </div>
        @endif

        @if ($transactions->isNotEmpty())
            <!-- Recent Transactions Section -->
            <div class="px-5 mt-8 mb-6 flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-[18px] font-black text-[#1a153a] tracking-tight font-poppins">Recent Transactions</h3>
                </div>
                <div class="space-y-3">
                    @foreach ($transactions as $txn)
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3.5 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $txn['type'] === 'purchase' ? 'bg-[#0A5C66]/5 text-[#0A5C66]' : 'bg-[#19B36B]/10 text-[#19B36B]' }}">
                                <i class="bi {{ $txn['type'] === 'purchase' ? $txn['icon'] : 'bi-arrow-down' }}"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-[13px] font-black text-[#1a153a] font-poppins truncate"><span>{{ $txn['type'] === 'purchase' ? 'Invested in' : 'Withdrawn from' }}</span> {{ $txn['title'] }}</h4>
                                <p class="text-[10.5px] text-slate-400 font-medium">{{ $txn['date']->format('d M Y, h:i A') }}</p>
                            </div>
                            <span class="text-[13px] font-black font-poppins {{ $txn['type'] === 'purchase' ? 'text-[#0A5C66]' : 'text-[#19B36B]' }}">{{ $txn['type'] === 'purchase' ? '-' : '+' }}₹{{ number_format($txn['amount'], 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Empty State Content -->
        <div @if ($hasActivity) class="hidden" @else class="px-6 flex flex-col items-center justify-center flex-1 text-center py-16" @endif>
            <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center mb-6 border border-slate-100 shadow-[0_10px_30px_rgba(0,0,0,0.05)] relative">
                <div class="absolute inset-0 bg-gradient-to-tr from-slate-50 to-slate-100 rounded-full animate-pulse opacity-50"></div>
                <i class="fa-solid fa-box-open text-[36px] text-slate-300 relative z-10"></i>
            </div>
            <h3 class="text-[20px] font-black text-[#1a153a] mb-2 font-poppins tracking-tight">Start Your First Goal</h3>
            <p class="text-[14px] text-slate-500 font-medium leading-relaxed max-w-[260px] mx-auto mb-8">Invest in smart plans and track your growth securely.</p>
            
            <a href="{{ route('explore') }}" class="w-full max-w-[240px] h-[52px] bg-gradient-to-r from-[#0A5C66] to-[#148e9e] text-[#F8FAFC] font-bold text-[15px] rounded-[20px] shadow-[0_10px_20px_rgba(10,92,102,0.2)] hover:shadow-[0_15px_30px_rgba(10,92,102,0.3)] active:scale-95 transition-all btn-ripple flex items-center justify-center gap-2">
                Explore Plans <i class="fa-solid fa-arrow-right text-[12px]"></i>
            </a>
        </div>
    </div>
@endsection

