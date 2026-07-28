@extends('layouts.admin')

@section('title', 'Wallet adjustment')

@section('content')

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="wallet" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Wallet adjustment" />

        <div class="px-6 md:px-10 py-8 md:py-10 max-w-2xl">

        <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Wallet adjustment</h1>
        <p class="text-[13.5px] text-[#64748B] mb-6">Increase or decrease a user's real wallet balance. Changes apply immediately, show on the Overview page, and notify the user.</p>

        <form method="POST" action="{{ route('admin.wallet-tools.adjust') }}" class="flex flex-col gap-4 bg-white rounded-2xl border border-[#E5E9EB] p-6">
            @csrf

            <div>
                <label for="wallet-phone" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Phone number</label>
                <input type="tel" id="wallet-phone" name="phone" inputmode="numeric" pattern="[0-9]*" maxlength="10" required
                    value="{{ old('phone') }}" placeholder="10-digit phone number"
                    class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
            </div>

            <div>
                <span class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Direction</span>
                <div class="inline-flex rounded-lg border border-[#CBD5E1] p-1 bg-[#F8FAFC] gap-1">
                    <label class="cursor-pointer">
                        <input type="radio" name="direction" value="increase" class="peer sr-only" {{ old('direction', 'increase') === 'increase' ? 'checked' : '' }}>
                        <span class="flex items-center gap-1.5 h-9 px-5 rounded-md text-[13px] font-semibold text-[#64748B] peer-checked:bg-white peer-checked:text-emerald-700 peer-checked:shadow-sm transition-colors">
                            <i class="fa-solid fa-plus text-[11px]"></i> Increase
                        </span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="direction" value="decrease" class="peer sr-only" {{ old('direction') === 'decrease' ? 'checked' : '' }}>
                        <span class="flex items-center gap-1.5 h-9 px-5 rounded-md text-[13px] font-semibold text-[#64748B] peer-checked:bg-white peer-checked:text-red-700 peer-checked:shadow-sm transition-colors">
                            <i class="fa-solid fa-minus text-[11px]"></i> Decrease
                        </span>
                    </label>
                </div>
            </div>

            <div>
                <label for="wallet-amount" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Amount (₹)</label>
                <input type="number" id="wallet-amount" name="amount" step="0.01" min="0.01" required
                    value="{{ old('amount') }}" placeholder="e.g. 250"
                    class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                <p class="text-[11.5px] text-[#94A3B8] mt-1.5">A decrease can't take the balance below ₹0.</p>
            </div>

            <button type="submit" class="h-10 rounded-lg bg-brand text-white font-semibold text-[13.5px] hover:bg-brand-light transition-colors active:scale-[0.99] sm:w-fit sm:px-6">
                Apply adjustment
            </button>
        </form>

        </div>
    </main>
</div>

@endsection
