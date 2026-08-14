@extends('layouts.admin')

@section('title', 'Referral program')

@section('content')

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="settings" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Referral program" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Referral program</h1>
        <p class="text-[13.5px] text-[#64748B] mb-6">The toggle applies immediately; the fields below need Save.</p>

        <div class="bg-white rounded-2xl border border-[#E5E9EB] p-6">
            {{-- Real, server-persisted setting (app_settings table) - applies
                 to every user's browser, not just the admin's. Instant-apply
                 on click, as the heading above says. --}}
            <form method="POST" action="{{ route('admin.settings.referral-toggle') }}" class="flex items-center justify-between py-1 mb-4">
                @csrf
                <span class="text-[13.5px] font-semibold text-[#0F172A]">Program enabled</span>
                <button type="submit" role="switch" aria-checked="{{ $settings['referral_enabled'] === 'true' ? 'true' : 'false' }}"
                    class="relative w-11 h-6 rounded-full transition-colors shrink-0 {{ $settings['referral_enabled'] === 'true' ? 'bg-brand' : 'bg-[#CBD5E1]' }}">
                    <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow-sm transition-transform {{ $settings['referral_enabled'] === 'true' ? 'translate-x-5' : '' }}"></span>
                </button>
            </form>

            <form method="POST" action="{{ route('admin.settings.update') }}" class="flex flex-col gap-3.5 pt-1 border-t border-[#E5E9EB]">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
                    <div>
                        <label for="setting-commission-percent" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Commission (%)</label>
                        <input type="number" name="commission_percent" id="setting-commission-percent" min="0" max="100" step="0.1" value="{{ old('commission_percent', $settings['commission_percent']) }}"
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                    </div>
                    <div>
                        <label for="setting-max-deposit-limit" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Max deposit (₹)</label>
                        <input type="number" name="max_deposit_limit" id="setting-max-deposit-limit" min="0" step="1" value="{{ old('max_deposit_limit', $settings['max_deposit_limit']) }}"
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                    </div>
                    <div>
                        <label for="setting-referral-min-qualifying-amount" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Min qualifying amount (₹)</label>
                        <input type="number" name="referral_min_qualifying_amount" id="setting-referral-min-qualifying-amount" min="0" step="0.01" placeholder="No minimum"
                            value="{{ old('referral_min_qualifying_amount', $settings['referral_min_qualifying_amount']) }}"
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        <p class="text-[11px] text-[#94A3B8] mt-1">Deposit/purchase must be at least this to earn commission. Blank = no minimum.</p>
                    </div>
                    <div>
                        <label for="setting-referral-max-qualifying-amount" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Max qualifying amount (₹)</label>
                        <input type="number" name="referral_max_qualifying_amount" id="setting-referral-max-qualifying-amount" min="0" step="0.01" placeholder="No maximum"
                            value="{{ old('referral_max_qualifying_amount', $settings['referral_max_qualifying_amount']) }}"
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        <p class="text-[11px] text-[#94A3B8] mt-1">Blank = no maximum.</p>
                    </div>
                </div>

                <div class="pt-4 mt-2 border-t border-[#E5E9EB]">
                    <h3 class="text-[13.5px] font-bold text-[#0F172A] mb-1">Commission source</h3>
                    <p class="text-[11.5px] text-[#94A3B8] mb-3">Which qualifying activity earns the referrer a commission. Both can be on at once - they're independent, one-time-per-referred-user rewards.</p>
                    <div class="flex flex-col gap-2.5">
                        @foreach ([
                            ['referral_source_plan_purchase_enabled', 'Plan purchase', "Referred user's first investment"],
                            ['referral_source_deposit_enabled', 'Deposit', "Referred user's first qualifying approved deposit"],
                        ] as [$key, $label, $hint])
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

                <div class="pt-4 mt-2 border-t border-[#E5E9EB]">
                    <a href="{{ route('admin.withdrawal-settings') }}" class="flex items-center justify-between gap-3 py-3 px-4 rounded-lg border border-[#E5E9EB] bg-[#F8FAFC] hover:bg-[#F1F5F9] transition-colors">
                        <span>
                            <span class="block text-[13.5px] font-bold text-[#0F172A]">Withdrawal settings moved</span>
                            <span class="block text-[11.5px] text-[#94A3B8]">Limits and Bank/UPI/USDT method toggles now live on their own page, separate from the referral program.</span>
                        </span>
                        <i class="fa-solid fa-arrow-right text-[13px] text-[#64748B]"></i>
                    </a>
                </div>

                <div class="pt-4 mt-2 border-t border-[#E5E9EB]">
                    <h3 class="text-[13.5px] font-bold text-[#0F172A] mb-1">System Settings (Kill-switches)</h3>
                    <p class="text-[11.5px] text-[#94A3B8] mb-3">Turn core flows on/off instantly. Maintenance mode blocks the whole app (this admin panel stays open).</p>
                    <div class="flex flex-col gap-2.5">
                        @php
                            $switches = [
                                ['maintenance_mode', 'Maintenance mode', 'Show a maintenance page to all users', true],
                                ['allow_registration', 'Allow new registrations', 'New phone numbers can sign up', false],
                                ['allow_investment', 'Allow new investments', 'Users can purchase plans', false],
                                ['allow_withdrawals', 'Allow withdrawals', 'Users can request withdrawals', false],
                            ];
                        @endphp
                        @foreach ($switches as [$key, $label, $hint, $danger])
                            <label for="setting-{{ $key }}" class="flex items-center justify-between gap-3 py-2 px-3 rounded-lg border {{ $danger ? 'border-[#FECACA] bg-[#FEF2F2]' : 'border-[#E5E9EB] bg-white' }} cursor-pointer">
                                <span>
                                    <span class="block text-[13px] font-semibold text-[#0F172A]">{{ $label }}</span>
                                    <span class="block text-[11px] text-[#94A3B8]">{{ $hint }}</span>
                                </span>
                                <input type="checkbox" name="{{ $key }}" id="setting-{{ $key }}" value="1"
                                    {{ ($settings[$key] ?? 'false') === 'true' ? 'checked' : '' }}
                                    class="w-5 h-5 rounded {{ $danger ? 'accent-red-600' : 'accent-brand' }} shrink-0">
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="h-10 rounded-lg bg-[#0F172A] text-white font-semibold text-[13.5px] hover:bg-[#1E293B] transition-colors active:scale-[0.99] mt-1 sm:w-fit sm:px-6">
                    Save settings
                </button>
            </form>
        </div>

        </div>
    </main>
</div>

@endsection
