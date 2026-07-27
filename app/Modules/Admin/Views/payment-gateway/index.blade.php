@extends('layouts.admin')

@section('title', 'Payment gateway')

@section('content')

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="payment-gateway" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Payment gateway" />

        <div class="px-6 md:px-10 py-8 md:py-10 flex flex-col gap-8">

        <div>
            <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Payment gateway</h1>
            <p class="text-[13.5px] text-[#64748B]">Controls what every user sees on the Add Money page. The account shown is chosen by the deposit <strong>amount</strong> - each account below has an optional amount range. If several accounts (UPI or bank) match the amount, one is picked at random.</p>
        </div>

        @php
            // Human-readable amount window for an account row - both bounds are
            // optional, so a row with neither set accepts every amount.
            $rangeLabel = function ($account) {
                $min = $account->min_amount;
                $max = $account->max_amount;
                if ($min === null && $max === null) {
                    return 'Any amount';
                }
                if ($min !== null && $max !== null) {
                    return '₹'.number_format((float) $min).' – ₹'.number_format((float) $max);
                }
                if ($min !== null) {
                    return '₹'.number_format((float) $min).'+';
                }
                return 'Up to ₹'.number_format((float) $max);
            };
        @endphp

        {{-- UPI accounts --}}
        <div class="flex flex-col gap-3">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-poppins font-bold text-[15px] text-[#0F172A]">UPI accounts</h2>
                <a href="{{ route('admin.payment-gateway.upi-accounts.create') }}" class="h-9 px-3.5 rounded-lg bg-brand text-white font-semibold text-[12.5px] hover:bg-brand-light transition-colors active:scale-[0.99] flex items-center gap-2">
                    <i class="fa-solid fa-plus text-[11px]"></i> Add UPI account
                </a>
            </div>

            <div class="flex flex-col gap-3">
                @forelse ($upiAccounts as $account)
                    <div class="bg-white rounded-xl border border-[#E5E9EB] p-4 flex items-center gap-4 flex-wrap {{ $account->is_active ? '' : 'opacity-60' }}">
                        <img src="{{ $account->qrImageUrl() }}" alt="{{ $account->upi_id }}" class="w-14 h-14 rounded-lg object-cover shrink-0 border border-[#E5E9EB]">
                        <div class="flex flex-col gap-0.5 min-w-[200px] flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-[14px] font-bold text-[#0F172A]">{{ $account->upi_id }}</span>
                                <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border {{ $account->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200' }}">
                                    {{ $account->is_active ? 'Active' : 'Disabled' }}
                                </span>
                                <span class="text-[10.5px] font-bold px-2 py-0.5 rounded-full border bg-[#0A5C66]/5 text-[#0A5C66] border-[#0A5C66]/20">
                                    <i class="fa-solid fa-indian-rupee-sign text-[9px]"></i> {{ $rangeLabel($account) }}
                                </span>
                            </div>
                            <span class="text-[12px] text-[#64748B]">{{ $account->display_name ?: 'No display name' }}{{ $account->mobile_number ? ' · '.$account->mobile_number : '' }}</span>
                        </div>

                        <div class="flex gap-2 shrink-0">
                            <a href="{{ route('admin.payment-gateway.upi-accounts.edit', $account) }}" class="h-9 px-3.5 rounded-lg border border-slate-200 text-slate-600 text-[12.5px] font-bold hover:bg-slate-50 transition-colors active:scale-95 flex items-center">Edit</a>
                            <form method="POST" action="{{ route('admin.payment-gateway.upi-accounts.toggle-active', $account) }}">
                                @csrf
                                <button type="submit" class="h-9 px-3.5 rounded-lg border text-[12.5px] font-bold transition-colors active:scale-95 {{ $account->is_active ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }}">
                                    {{ $account->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.payment-gateway.upi-accounts.delete', $account) }}" onsubmit="return confirm('Delete this UPI account?');">
                                @csrf
                                <button type="submit" class="h-9 px-3.5 rounded-lg border border-red-200 text-red-600 text-[12.5px] font-bold hover:bg-red-50 transition-colors active:scale-95">Delete</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-[13.5px] text-[#94A3B8] italic">No UPI accounts yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Bank accounts --}}
        <div class="flex flex-col gap-3">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-poppins font-bold text-[15px] text-[#0F172A]">Bank accounts</h2>
                <a href="{{ route('admin.payment-gateway.bank-accounts.create') }}" class="h-9 px-3.5 rounded-lg bg-brand text-white font-semibold text-[12.5px] hover:bg-brand-light transition-colors active:scale-[0.99] flex items-center gap-2">
                    <i class="fa-solid fa-plus text-[11px]"></i> Add bank account
                </a>
            </div>

            <div class="flex flex-col gap-3">
                @forelse ($bankAccounts as $account)
                    <div class="bg-white rounded-xl border border-[#E5E9EB] p-4 flex items-center gap-4 flex-wrap {{ $account->is_active ? '' : 'opacity-60' }}">
                        <div class="w-11 h-11 rounded-full bg-[#0A5C66]/10 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-building-columns text-[16px] text-[#0A5C66]"></i>
                        </div>
                        <div class="flex flex-col gap-0.5 min-w-[220px] flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-[14px] font-bold text-[#0F172A]">{{ $account->bank_name }}</span>
                                <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border {{ $account->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200' }}">
                                    {{ $account->is_active ? 'Active' : 'Disabled' }}
                                </span>
                                <span class="text-[10.5px] font-bold px-2 py-0.5 rounded-full border bg-[#0A5C66]/5 text-[#0A5C66] border-[#0A5C66]/20">
                                    <i class="fa-solid fa-indian-rupee-sign text-[9px]"></i> {{ $rangeLabel($account) }}
                                </span>
                            </div>
                            <span class="text-[12px] text-[#64748B]">{{ $account->account_holder_name }} · {{ \Illuminate\Support\Str::mask($account->account_number, '*', 0, -4) }} · {{ $account->ifsc_code }}</span>
                        </div>

                        <div class="flex gap-2 shrink-0">
                            <a href="{{ route('admin.payment-gateway.bank-accounts.edit', $account) }}" class="h-9 px-3.5 rounded-lg border border-slate-200 text-slate-600 text-[12.5px] font-bold hover:bg-slate-50 transition-colors active:scale-95 flex items-center">Edit</a>
                            <form method="POST" action="{{ route('admin.payment-gateway.bank-accounts.toggle-active', $account) }}">
                                @csrf
                                <button type="submit" class="h-9 px-3.5 rounded-lg border text-[12.5px] font-bold transition-colors active:scale-95 {{ $account->is_active ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }}">
                                    {{ $account->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.payment-gateway.bank-accounts.delete', $account) }}" onsubmit="return confirm('Delete this bank account?');">
                                @csrf
                                <button type="submit" class="h-9 px-3.5 rounded-lg border border-red-200 text-red-600 text-[12.5px] font-bold hover:bg-red-50 transition-colors active:scale-95">Delete</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-[13.5px] text-[#94A3B8] italic">No bank accounts yet.</p>
                @endforelse
            </div>
        </div>

        </div>
    </main>
</div>

@endsection
