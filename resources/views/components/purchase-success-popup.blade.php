{{--
    Post-purchase celebration popup. One component, three copy/detail
    branches keyed off plan_type (plans.md's "Growth Plan Purchase Success"
    and "Trust Builder Purchase Success" popups, plus a generic fallback for
    every other plan) - avoids duplicating the whole card structure per
    variant. Driven by the purchase_success session flash set in
    PlanPurchaseController::purchase() on every successful purchase.
--}}
@php
    $purchase = session('purchase_success');
@endphp

@if ($purchase)
    <input type="checkbox" id="purchase-success-popup-toggle" class="hidden peer" checked>
    <div class="hidden peer-checked:flex fixed inset-0 z-[270] bg-slate-900/60 backdrop-blur-md items-center justify-center p-4">
        <div class="relative w-full max-w-[360px] bg-white rounded-[28px] shadow-2xl p-7 flex flex-col items-center text-center overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-[#19B36B]"></div>

            <div class="w-16 h-16 bg-[#19B36B]/10 rounded-full flex items-center justify-center mb-5 border border-[#19B36B]/20 shadow-inner success-glow-pulse">
                <i class="fa-solid fa-circle-check text-[#19B36B] text-[30px] checkmark-animate"></i>
            </div>

            @if ($purchase['is_topup'] ?? false)
                <h3 class="text-[#0A5C66] text-[21px] font-black leading-tight mb-1.5 font-poppins tracking-tight">Top-Up Successful</h3>
                <p class="text-slate-500 text-[13.5px] font-medium mb-5 leading-relaxed">You've added more to your {{ $purchase['title'] }} pot.</p>

                <div class="w-full bg-[#0A5C66]/5 rounded-2xl p-4 border border-[#0A5C66]/10 text-left space-y-2.5 mb-6">
                    <div class="flex justify-between items-center text-[12px]">
                        <span class="text-slate-400 font-semibold font-poppins">ADDED NOW</span>
                        <span class="text-[#0A5C66] font-bold font-poppins">₹{{ number_format($purchase['topup_amount'], 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-[12px]">
                        <span class="text-slate-400 font-semibold font-poppins">TOTAL INVESTED</span>
                        <span class="text-[#0A5C66] font-bold font-poppins">₹{{ number_format($purchase['amount'], 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-[12px]">
                        <span class="text-slate-400 font-semibold font-poppins">EXPECTED RETURN AT MATURITY</span>
                        <span class="text-[#19B36B] font-bold font-poppins">₹{{ number_format($purchase['total_return'], 2) }}</span>
                    </div>
                    @if ($purchase['matures_at'])
                        <div class="flex justify-between items-center text-[12px]">
                            <span class="text-slate-400 font-semibold font-poppins">MATURES ON</span>
                            <span class="text-[#0A5C66] font-bold font-poppins">{{ $purchase['matures_at']->format('d M Y') }}</span>
                        </div>
                    @endif
                </div>

                <div class="w-full flex flex-col gap-2.5">
                    <a href="{{ route('portfolio') }}" class="w-full bg-[#0E7680] text-white font-bold text-[15px] h-[48px] rounded-full active:scale-95 transition-all shadow-md shadow-[#0E7680]/20 flex items-center justify-center">View Portfolio</a>
                </div>
            @elseif ($purchase['plan_type'] === 'growth')
                <h3 class="text-[#0A5C66] text-[21px] font-black leading-tight mb-1.5 font-poppins tracking-tight">Congratulations!</h3>
                <p class="text-slate-500 text-[13.5px] font-medium mb-5 leading-relaxed">Growth Plan Activated Successfully</p>

                <div class="w-full bg-[#0A5C66]/5 rounded-2xl p-4 border border-[#0A5C66]/10 text-left space-y-2 mb-6">
                    @foreach (['Trust Builder Plan', 'Premium Plans', 'Portfolio Tracking', 'Daily Growth', 'Investment History'] as $unlocked)
                        <div class="flex items-center gap-2 text-[12.5px] font-semibold text-[#0A5C66]">
                            <i class="bi bi-check-circle-fill text-[#3CCF91] text-[13px] shrink-0"></i> {{ $unlocked }}
                        </div>
                    @endforeach
                </div>

                <div class="w-full flex flex-col gap-2.5">
                    <a href="{{ route('portfolio') }}" class="w-full bg-[#0E7680] text-white font-bold text-[15px] h-[48px] rounded-full active:scale-95 transition-all shadow-md shadow-[#0E7680]/20 flex items-center justify-center">Continue</a>
                    <a href="{{ route('rewards') }}" class="w-full text-[#0A5C66] font-bold text-[13px] h-[38px] rounded-full hover:bg-[#0A5C66]/5 active:scale-95 transition-all flex items-center justify-center gap-1.5">
                        <i class="bi bi-gift-fill text-[12px]"></i> Invite a Friend & Earn
                    </a>
                </div>
            @elseif ($purchase['plan_type'] === 'trust_builder')
                <h3 class="text-[#0A5C66] text-[21px] font-black leading-tight mb-1.5 font-poppins tracking-tight">Trust Builder Activated</h3>
                <p class="text-slate-500 text-[13.5px] font-medium mb-5 leading-relaxed">Your first investment is on its way to maturity.</p>

                <div class="w-full bg-[#0A5C66]/5 rounded-2xl p-4 border border-[#0A5C66]/10 text-left space-y-2.5 mb-6">
                    <div class="flex justify-between items-center text-[12px]">
                        <span class="text-slate-400 font-semibold font-poppins">INVESTMENT</span>
                        <span class="text-[#0A5C66] font-bold font-poppins">₹{{ number_format($purchase['amount'], 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-[12px]">
                        <span class="text-slate-400 font-semibold font-poppins">DURATION</span>
                        <span class="text-[#0A5C66] font-bold font-poppins">{{ $purchase['duration_label'] }}</span>
                    </div>
                    <div class="flex justify-between items-center text-[12px]">
                        <span class="text-slate-400 font-semibold font-poppins">EXPECTED COMPLETION</span>
                        <span class="text-[#19B36B] font-bold font-poppins">{{ $purchase['matures_at']?->format('d M Y') ?? 'Tomorrow' }}</span>
                    </div>
                </div>

                <div class="w-full flex flex-col gap-2.5">
                    <a href="{{ route('portfolio') }}" class="w-full bg-[#0E7680] text-white font-bold text-[15px] h-[48px] rounded-full active:scale-95 transition-all shadow-md shadow-[#0E7680]/20 flex items-center justify-center">Track Investment</a>
                </div>
            @else
                <h3 class="text-[#0A5C66] text-[21px] font-black leading-tight mb-1.5 font-poppins tracking-tight">Investment Activated</h3>
                <p class="text-slate-500 text-[13.5px] font-medium mb-5 leading-relaxed">Your goal plan is now active successfully.</p>

                <div class="w-full bg-[#0A5C66]/5 rounded-2xl p-4 border border-[#0A5C66]/10 text-left space-y-2.5 mb-6">
                    <div class="flex justify-between items-center text-[12px]">
                        <span class="text-slate-400 font-semibold font-poppins">PLAN NAME</span>
                        <span class="text-[#0A5C66] font-bold font-poppins">{{ $purchase['title'] }}</span>
                    </div>
                    <div class="flex justify-between items-center text-[12px]">
                        <span class="text-slate-400 font-semibold font-poppins">INVESTMENT</span>
                        <span class="text-[#0A5C66] font-bold font-poppins">₹{{ number_format($purchase['amount'], 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-[12px]">
                        <span class="text-slate-400 font-semibold font-poppins">EXPECTED RETURN</span>
                        <span class="text-[#19B36B] font-bold font-poppins">₹{{ number_format($purchase['total_return'], 2) }}</span>
                    </div>
                </div>

                <div class="w-full flex flex-col gap-2.5">
                    <a href="{{ route('portfolio') }}" class="w-full bg-[#0E7680] text-white font-bold text-[15px] h-[48px] rounded-full active:scale-95 transition-all shadow-md shadow-[#0E7680]/20 flex items-center justify-center">View Portfolio</a>
                </div>
            @endif
        </div>
    </div>
@endif
