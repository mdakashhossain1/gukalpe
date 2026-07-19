@extends('layouts.admin')

@section('title', 'Payment gateway')

@section('content')

<div class="flex min-h-screen">

    <x-admin-sidebar active="payment-gateway" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Payment gateway" />

        <div class="px-6 md:px-10 py-8 md:py-10 flex flex-col gap-8">

        <div>
            <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Payment gateway</h1>
            <p class="text-[13.5px] text-[#64748B]">Controls what every user sees on the Add Money page. Exactly one of these is live at a time. A random active account of the active method is shown on every page load.</p>
        </div>

        {{-- Collection mode - a single flat choice between the two manual
             methods. Exactly one is live at a time. --}}
        <form method="POST" action="{{ route('admin.payment-gateway.settings') }}" class="flex flex-col gap-4 bg-white rounded-2xl border border-[#E5E9EB] p-6">
            @csrf
            <h2 class="font-poppins font-bold text-[15px] text-[#0F172A]">Collection mode</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="flex items-center gap-3 h-16 px-4 rounded-xl border border-[#CBD5E1] has-[:checked]:border-brand has-[:checked]:bg-brand/5 cursor-pointer transition-colors">
                    <input type="radio" name="payment_mode" value="upi" class="accent-brand" {{ $settings['payment_mode'] === 'upi' ? 'checked' : '' }}>
                    <span class="flex flex-col">
                        <span class="text-[13.5px] font-bold text-[#0F172A]">UPI</span>
                        <span class="text-[11.5px] text-[#64748B]">QR code, verified manually via UTR</span>
                    </span>
                </label>
                <label class="flex items-center gap-3 h-16 px-4 rounded-xl border border-[#CBD5E1] has-[:checked]:border-brand has-[:checked]:bg-brand/5 cursor-pointer transition-colors">
                    <input type="radio" name="payment_mode" value="bank" class="accent-brand" {{ $settings['payment_mode'] === 'bank' ? 'checked' : '' }}>
                    <span class="flex flex-col">
                        <span class="text-[13.5px] font-bold text-[#0F172A]">Bank Transfer</span>
                        <span class="text-[11.5px] text-[#64748B]">Account/IFSC, verified manually via UTR</span>
                    </span>
                </label>
            </div>

            <button type="submit" class="h-10 rounded-lg bg-brand text-white font-semibold text-[13.5px] hover:bg-brand-light transition-colors active:scale-[0.99] sm:w-fit sm:px-6">
                Save settings
            </button>
        </form>

        @if ($settings['payment_mode'] === 'upi')
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
                                <div class="flex items-center gap-2">
                                    <span class="text-[14px] font-bold text-[#0F172A]">{{ $account->upi_id }}</span>
                                    <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border {{ $account->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200' }}">
                                        {{ $account->is_active ? 'Active' : 'Disabled' }}
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
        @else
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
                                <div class="flex items-center gap-2">
                                    <span class="text-[14px] font-bold text-[#0F172A]">{{ $account->bank_name }}</span>
                                    <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border {{ $account->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200' }}">
                                        {{ $account->is_active ? 'Active' : 'Disabled' }}
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
        @endif

        </div>
    </main>
</div>

@endsection
