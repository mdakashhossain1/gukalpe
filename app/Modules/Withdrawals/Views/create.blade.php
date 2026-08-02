@extends('layouts.simple')

@section('title', 'Withdraw Money')

@section('content')

    @php
        $anyMethodEnabled = in_array(true, $methodsEnabled, true);
        $defaultMethod = collect($methodsEnabled)->filter()->keys()->first() ?? 'upi';
        $currentMethod = old('method', $defaultMethod);
    @endphp

    <div class="mt-2 mb-6">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-[13px] font-bold text-slate-400 hover:text-[#0A5C66] transition-colors mb-4">
            <i class="fa-solid fa-arrow-left text-[12px]"></i> Back to GullakPe
        </a>
        <h1 class="text-[22px] font-black text-[#0A5C66] font-poppins tracking-tight">Withdraw Money</h1>
        <p class="text-[13.5px] text-slate-500 font-medium mt-1">Cash out from your wallet. An admin reviews every request before payout.</p>
    </div>

    <div class="bg-[#0A5C66]/5 border border-[#0A5C66]/15 rounded-[16px] p-4 mb-5">
        <span class="block text-[10.5px] font-bold text-slate-400 uppercase tracking-wide mb-1">Available balance</span>
        <span class="text-[20px] font-black text-[#0A5C66] font-poppins tracking-tight">₹{{ number_format($balance, 2) }}</span>
    </div>

    @if (! $anyMethodEnabled)
        <div class="bg-amber-50 border border-amber-200 rounded-[14px] p-4 text-[13px] font-semibold text-amber-700">
            No withdrawal method is currently available. Please try again later.
        </div>
    @else
    <form method="POST" action="{{ route('withdrawals.store') }}" class="flex flex-col gap-5 pb-10">
        @csrf
        <input type="hidden" name="method" id="wd-method-input" value="{{ $currentMethod }}">

        <!-- Amount -->
        <div class="premium-card p-5">
            <label for="amount" class="block text-[12px] font-bold text-slate-400 uppercase tracking-wide mb-2">Amount to withdraw</label>
            <div class="flex items-center border-b-[3px] border-slate-100 focus-within:border-[#3FEA8A] transition-colors pb-2">
                <span class="text-3xl text-slate-800 font-black mr-2">₹</span>
                <input type="number" id="amount" name="amount" min="1" step="1" required
                    value="{{ old('amount') }}" placeholder="0"
                    class="w-full bg-transparent outline-none text-3xl text-[#1a153a] font-black tracking-tight placeholder:text-slate-300">
            </div>
            @error('amount')
                <p class="text-[12px] font-semibold text-red-500 mt-2">{{ $message }}</p>
            @enderror
        </div>

        <!-- Phone -->
        <div>
            <label for="phone" class="block text-[12px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">Your registered phone number</label>
            <input type="tel" id="phone" name="phone" inputmode="numeric" maxlength="10" required
                placeholder="10-digit phone number" value="{{ old('phone') }}"
                class="w-full h-12 rounded-[14px] border border-slate-200 px-4 text-[15px] font-bold text-slate-800 outline-none focus:border-[#0A5C66] focus:ring-1 focus:ring-[#0A5C66] transition-colors">
            <p class="text-[11.5px] text-slate-400 font-medium mt-1.5">Used to identify which wallet to debit once approved.</p>
            @error('phone')
                <p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <!-- Withdrawal method -->
        <div>
            <span class="block text-[12px] font-bold text-slate-500 uppercase tracking-wide mb-2">Withdrawal method</span>
            <div class="inline-flex rounded-[14px] border border-slate-200 overflow-hidden w-full" id="wd-method-switcher">
                @if ($methodsEnabled['bank'])
                    <button type="button" data-method="bank" class="wd-method-btn flex-1 h-11 text-[12.5px] font-bold transition-colors">
                        <i class="fa-solid fa-building-columns mr-1"></i> Bank
                    </button>
                @endif
                @if ($methodsEnabled['upi'])
                    <button type="button" data-method="upi" class="wd-method-btn flex-1 h-11 text-[12.5px] font-bold transition-colors">
                        <i class="fa-solid fa-mobile-screen mr-1"></i> UPI
                    </button>
                @endif
                @if ($methodsEnabled['usdt'])
                    <button type="button" data-method="usdt" class="wd-method-btn flex-1 h-11 text-[12.5px] font-bold transition-colors">
                        <i class="fa-brands fa-bitcoin mr-1"></i> USDT (TRC20)
                    </button>
                @endif
            </div>
            @error('method')
                <p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <!-- UPI panel -->
        <div id="wd-panel-upi" class="flex flex-col gap-2">
            <label for="payout_upi_id" class="block text-[12px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">Your UPI ID to receive the payout</label>
            <input type="text" id="payout_upi_id" name="payout_upi_id"
                placeholder="e.g. name@okhdfcbank" value="{{ old('payout_upi_id') }}"
                class="w-full h-12 rounded-[14px] border border-slate-200 px-4 text-[15px] font-bold text-slate-800 outline-none focus:border-[#0A5C66] focus:ring-1 focus:ring-[#0A5C66] transition-colors">
            @error('payout_upi_id')
                <p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <!-- Bank Account panel -->
        <div id="wd-panel-bank" class="flex flex-col gap-3">
            <div>
                <label for="bank_account_holder" class="block text-[12px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">Account holder name</label>
                <input type="text" id="bank_account_holder" name="bank_account_holder" value="{{ old('bank_account_holder') }}"
                    class="w-full h-12 rounded-[14px] border border-slate-200 px-4 text-[15px] font-bold text-slate-800 outline-none focus:border-[#0A5C66] focus:ring-1 focus:ring-[#0A5C66] transition-colors">
                @error('bank_account_holder')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="bank_account_number" class="block text-[12px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">Account number</label>
                <input type="text" inputmode="numeric" id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number') }}"
                    class="w-full h-12 rounded-[14px] border border-slate-200 px-4 text-[15px] font-bold text-slate-800 outline-none focus:border-[#0A5C66] focus:ring-1 focus:ring-[#0A5C66] transition-colors">
                @error('bank_account_number')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="bank_account_number_confirmation" class="block text-[12px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">Confirm account number</label>
                <input type="text" inputmode="numeric" id="bank_account_number_confirmation" name="bank_account_number_confirmation"
                    class="w-full h-12 rounded-[14px] border border-slate-200 px-4 text-[15px] font-bold text-slate-800 outline-none focus:border-[#0A5C66] focus:ring-1 focus:ring-[#0A5C66] transition-colors">
            </div>
            <div>
                <label for="bank_ifsc" class="block text-[12px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">IFSC code</label>
                <input type="text" id="bank_ifsc" name="bank_ifsc" placeholder="e.g. HDFC0001234" value="{{ old('bank_ifsc') }}"
                    class="w-full h-12 rounded-[14px] border border-slate-200 px-4 text-[15px] font-bold text-slate-800 outline-none focus:border-[#0A5C66] focus:ring-1 focus:ring-[#0A5C66] transition-colors uppercase">
                @error('bank_ifsc')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="bank_name" class="block text-[12px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">Bank name</label>
                <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name') }}"
                    class="w-full h-12 rounded-[14px] border border-slate-200 px-4 text-[15px] font-bold text-slate-800 outline-none focus:border-[#0A5C66] focus:ring-1 focus:ring-[#0A5C66] transition-colors">
                @error('bank_name')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="bank_branch" class="block text-[12px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">Branch (optional)</label>
                <input type="text" id="bank_branch" name="bank_branch" value="{{ old('bank_branch') }}"
                    class="w-full h-12 rounded-[14px] border border-slate-200 px-4 text-[15px] font-bold text-slate-800 outline-none focus:border-[#0A5C66] focus:ring-1 focus:ring-[#0A5C66] transition-colors">
            </div>
        </div>

        <!-- USDT panel -->
        <div id="wd-panel-usdt" class="flex flex-col gap-2">
            <label for="usdt_address" class="block text-[12px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">TRC20 (Tron) wallet address</label>
            <input type="text" id="usdt_address" name="usdt_address" placeholder="Txxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" value="{{ old('usdt_address') }}"
                class="w-full h-12 rounded-[14px] border border-slate-200 px-4 text-[15px] font-bold text-slate-800 outline-none focus:border-[#0A5C66] focus:ring-1 focus:ring-[#0A5C66] transition-colors">
            <p class="text-[11.5px] text-slate-400 font-medium">Only the TRC20 network is supported. Double-check the address - transfers to a wrong address can't be reversed.</p>
            @error('usdt_address')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="w-full h-[52px] rounded-[16px] bg-[#0A5C66] text-white font-bold text-[15px] hover:bg-[#0E7481] active:scale-[0.98] transition-all shadow-md shadow-[#0A5C66]/20">
            Submit withdrawal request
        </button>

        <div class="flex items-start gap-2.5 bg-slate-50 border border-slate-100 rounded-[14px] p-3.5">
            <i class="fa-solid fa-shield-halved text-[13px] text-slate-400 mt-0.5"></i>
            <p class="text-[11.5px] text-slate-500 font-medium leading-relaxed">Withdrawals are verified manually and usually paid out within a few hours.</p>
        </div>
    </form>

    <script>
        (function () {
            var methodInput = document.getElementById('wd-method-input');
            var buttons = document.querySelectorAll('.wd-method-btn');
            var panels = {
                bank: document.getElementById('wd-panel-bank'),
                upi: document.getElementById('wd-panel-upi'),
                usdt: document.getElementById('wd-panel-usdt'),
            };
            var activeClass = 'flex-1 h-11 text-[12.5px] font-bold transition-colors bg-[#0A5C66] text-white wd-method-btn';
            var inactiveClass = 'flex-1 h-11 text-[12.5px] font-bold transition-colors bg-white text-slate-500 wd-method-btn';

            function setMethod(method) {
                methodInput.value = method;
                Object.keys(panels).forEach(function (key) {
                    if (panels[key]) panels[key].style.display = key === method ? '' : 'none';
                });
                buttons.forEach(function (btn) {
                    btn.className = btn.getAttribute('data-method') === method ? activeClass : inactiveClass;
                });
            }

            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () { setMethod(btn.getAttribute('data-method')); });
            });

            setMethod(methodInput.value || 'upi');
        })();
    </script>
    @endif

@endsection
