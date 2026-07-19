@extends('layouts.simple')

@section('title', 'Withdraw to UPI')

@section('content')

    <div class="mt-2 mb-6">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-[13px] font-bold text-slate-400 hover:text-[#0A5C66] transition-colors mb-4">
            <i class="fa-solid fa-arrow-left text-[12px]"></i> Back to GullakPe
        </a>
        <h1 class="text-[22px] font-black text-[#0A5C66] font-poppins tracking-tight">Withdraw to UPI</h1>
        <p class="text-[13.5px] text-slate-500 font-medium mt-1">Cash out from your wallet to your own UPI ID. An admin reviews every request before payout.</p>
    </div>

    <div class="bg-[#0A5C66]/5 border border-[#0A5C66]/15 rounded-[16px] p-4 mb-5">
        <span class="block text-[10.5px] font-bold text-slate-400 uppercase tracking-wide mb-1">Available balance</span>
        <span class="text-[20px] font-black text-[#0A5C66] font-poppins tracking-tight">₹{{ number_format($balance, 2) }}</span>
    </div>

    <form method="POST" action="{{ route('withdrawals.store') }}" class="flex flex-col gap-5 pb-10">
        @csrf

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

        <!-- Payout UPI ID -->
        <div>
            <label for="payout_upi_id" class="block text-[12px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">Your UPI ID to receive the payout</label>
            <input type="text" id="payout_upi_id" name="payout_upi_id" required
                placeholder="e.g. name@okhdfcbank" value="{{ old('payout_upi_id') }}"
                class="w-full h-12 rounded-[14px] border border-slate-200 px-4 text-[15px] font-bold text-slate-800 outline-none focus:border-[#0A5C66] focus:ring-1 focus:ring-[#0A5C66] transition-colors">
            @error('payout_upi_id')
                <p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full h-[52px] rounded-[16px] bg-[#0A5C66] text-white font-bold text-[15px] hover:bg-[#0E7481] active:scale-[0.98] transition-all shadow-md shadow-[#0A5C66]/20">
            Submit withdrawal request
        </button>

        <div class="flex items-start gap-2.5 bg-slate-50 border border-slate-100 rounded-[14px] p-3.5">
            <i class="fa-solid fa-shield-halved text-[13px] text-slate-400 mt-0.5"></i>
            <p class="text-[11.5px] text-slate-500 font-medium leading-relaxed">Withdrawals are verified manually and usually paid out within a few hours.</p>
        </div>
    </form>

@endsection
