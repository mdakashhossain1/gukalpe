@extends('layouts.admin')

@section('title', 'Withdrawal settings')

@section('content')

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="withdrawal-settings" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Withdrawal settings" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        @if (session('success'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 px-4 py-3 text-[13.5px] font-medium">{{ session('success') }}</div>
        @endif

        <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Withdrawal settings</h1>
        <p class="text-[13.5px] text-[#64748B] mb-6">Limits and payout methods for user withdrawal requests - kept separate from the Referral program page.</p>

        <div class="bg-white rounded-2xl border border-[#E5E9EB] p-6">
            <form method="POST" action="{{ route('admin.withdrawal-settings.update') }}" class="flex flex-col gap-3.5">
                @csrf

                <div>
                    <h3 class="text-[13.5px] font-bold text-[#0F172A] mb-3">Withdrawal Limits (Global Policy)</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label for="setting-withdrawal-min-amount" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Min withdrawal (₹)</label>
                            <input type="number" name="withdrawal_min_amount" id="setting-withdrawal-min-amount" min="0" step="1" value="{{ old('withdrawal_min_amount', $settings['withdrawal_min_amount']) }}"
                                class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        </div>
                        <div>
                            <label for="setting-withdrawal-daily-limit" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Daily withdrawal limit (₹)</label>
                            <input type="number" name="withdrawal_daily_limit" id="setting-withdrawal-daily-limit" min="0" step="1" value="{{ old('withdrawal_daily_limit', $settings['withdrawal_daily_limit']) }}"
                                class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        </div>
                        <div>
                            <label for="setting-withdrawal-max-per-day" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Max requests / day</label>
                            <input type="number" name="withdrawal_max_per_day" id="setting-withdrawal-max-per-day" min="1" step="1" value="{{ old('withdrawal_max_per_day', $settings['withdrawal_max_per_day']) }}"
                                class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        </div>
                        <div>
                            <label for="setting-withdrawal-max-per-transaction" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Max per transaction (₹)</label>
                            <input type="number" name="withdrawal_max_per_transaction" id="setting-withdrawal-max-per-transaction" min="0" step="1" value="{{ old('withdrawal_max_per_transaction', $settings['withdrawal_max_per_transaction']) }}"
                                class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        </div>
                    </div>
                </div>

                <div class="pt-4 mt-2 border-t border-[#E5E9EB]">
                    <h3 class="text-[13.5px] font-bold text-[#0F172A] mb-1">Withdrawal Methods</h3>
                    <p class="text-[11.5px] text-[#94A3B8] mb-3">Enable/disable each payout method independently - e.g. turn Bank off temporarily during maintenance without any code change. <strong>Processing is always manual</strong>: this app has no live payment gateway, so approving a withdrawal only debits the wallet - the admin still pays out the destination shown by hand, for every method.</p>
                    <div class="flex flex-col gap-2.5">
                        @php
                            $methodSwitches = [
                                ['withdrawal_method_bank_enabled', 'Bank Account', 'Account number + IFSC'],
                                ['withdrawal_method_upi_enabled', 'UPI', 'UPI ID, e.g. name@bank'],
                                ['withdrawal_method_usdt_enabled', 'USDT (TRC20)', 'Tron network wallet address'],
                            ];
                        @endphp
                        @foreach ($methodSwitches as [$key, $label, $hint])
                            <label for="setting-{{ $key }}" class="flex items-center justify-between gap-3 py-2 px-3 rounded-lg border border-[#E5E9EB] bg-white cursor-pointer">
                                <span>
                                    <span class="block text-[13px] font-semibold text-[#0F172A]">{{ $label }}</span>
                                    <span class="block text-[11px] text-[#94A3B8]">{{ $hint }}</span>
                                </span>
                                <input type="checkbox" name="{{ $key }}" id="setting-{{ $key }}" value="1"
                                    {{ ($settings[$key] ?? 'false') === 'true' ? 'checked' : '' }}
                                    class="w-5 h-5 rounded accent-brand shrink-0">
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="h-10 rounded-lg bg-[#0F172A] text-white font-semibold text-[13.5px] hover:bg-[#1E293B] transition-colors active:scale-[0.99] mt-1 sm:w-fit sm:px-6">
                    Save withdrawal settings
                </button>
            </form>
        </div>

        </div>
    </main>
</div>

@endsection
