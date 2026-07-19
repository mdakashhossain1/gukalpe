{{--
    Common "Insufficient Wallet Balance" popup for every plan purchase flow
    (plans.md: "Ye popup sabhi plans ke liye common rahega"). Driven by a
    session flash array set once in PlanPurchaseController - pure CSS
    show/hide (checkbox `checked` set from the flash, closed via a label),
    no JS, matching Explore's search/filter panel convention.
--}}
@php
    $insufficientBalance = session('insufficient_balance');
@endphp

@if ($insufficientBalance)
    <input type="checkbox" id="insufficient-balance-popup-toggle" class="hidden peer" checked>
    <div class="hidden peer-checked:flex fixed inset-0 z-[260] bg-slate-900/60 backdrop-blur-md items-center justify-center p-4">
        <div class="relative w-full max-w-[340px] bg-white rounded-[28px] shadow-2xl p-7 flex flex-col items-center text-center overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-amber-400 to-orange-500"></div>

            <div class="w-16 h-16 bg-amber-500/10 rounded-full flex items-center justify-center mb-5 border border-amber-500/20 shadow-inner amber-glow-pulse">
                <i class="bi bi-wallet2 text-amber-500 text-[26px]"></i>
            </div>

            <h3 class="text-[#0A5C66] text-[20px] font-black leading-tight mb-1.5 font-poppins tracking-tight">Insufficient Wallet Balance</h3>
            <p class="text-slate-500 text-[13.5px] font-medium mb-5 leading-relaxed">Your wallet balance is too low. Please add money to your wallet before investing.</p>

            <div class="w-full bg-amber-500/5 rounded-2xl p-4 border border-amber-500/10 text-left space-y-2.5 mb-6">
                <div class="flex justify-between items-center text-[12px]">
                    <span class="text-slate-400 font-semibold font-poppins">CURRENT BALANCE</span>
                    <span class="text-slate-700 font-bold font-poppins">₹{{ number_format($insufficientBalance['available'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center text-[12px]">
                    <span class="text-slate-400 font-semibold font-poppins">REQUIRED AMOUNT</span>
                    <span class="text-amber-600 font-bold font-poppins">₹{{ number_format($insufficientBalance['needed'], 2) }}</span>
                </div>
            </div>

            <div class="w-full flex flex-col gap-2.5">
                <a href="{{ route('deposits.create') }}" class="w-full bg-amber-500 text-white font-bold text-[15px] h-[48px] rounded-full active:scale-95 transition-all shadow-md shadow-amber-500/20 flex items-center justify-center">Add Money</a>
                <label for="insufficient-balance-popup-toggle" class="w-full bg-slate-100/80 text-slate-600 font-bold text-[14px] h-[44px] rounded-full hover:bg-slate-200/80 active:scale-95 transition-all cursor-pointer flex items-center justify-center">Cancel</label>
            </div>
        </div>
    </div>
@endif
