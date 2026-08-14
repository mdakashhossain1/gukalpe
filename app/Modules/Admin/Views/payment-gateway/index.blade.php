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
            <p class="text-[13.5px] text-[#64748B]">Controls what every user sees on the Add Money page. The deposit <strong>amount</strong> decides which <strong>method</strong> is shown; the specific account then rotates least-recently-used first among all active accounts of that method - use the priority order below to influence which account comes up first.</p>
        </div>

        {{-- Method-level amount ranges. The amount picks the method (UPI vs
             Bank); a blank Max means "no upper limit"; leaving BOTH blank turns
             that method off. If both ranges cover the amount, one is chosen at
             random. --}}
        <form method="POST" action="{{ route('admin.payment-gateway.settings') }}" class="flex flex-col gap-4 bg-white rounded-2xl border border-[#E5E9EB] p-6">
            @csrf
            <div>
                <h2 class="font-poppins font-bold text-[15px] text-[#0F172A]">Amount ranges</h2>
                <p class="text-[12.5px] text-[#64748B] mt-0.5">e.g. UPI ₹1–₹200, Bank ₹201 and up. Blank Max = no upper limit. Leave a whole row blank to disable that method.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="rounded-xl border border-[#E5E9EB] p-4 flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-mobile-screen-button text-[#0A5C66] text-[13px]"></i>
                        <span class="text-[13.5px] font-bold text-[#0F172A]">UPI range</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="upi_min_amount" class="block text-[11.5px] font-semibold text-[#334155] mb-1">Min ₹</label>
                            <input type="number" name="upi_min_amount" id="upi_min_amount" min="0" step="0.01" placeholder="0" value="{{ old('upi_min_amount', $settings['upi_min_amount']) }}"
                                class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                            @error('upi_min_amount')<p class="text-[12px] font-semibold text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="upi_max_amount" class="block text-[11.5px] font-semibold text-[#334155] mb-1">Max ₹</label>
                            <input type="number" name="upi_max_amount" id="upi_max_amount" min="0" step="0.01" placeholder="No limit" value="{{ old('upi_max_amount', $settings['upi_max_amount']) }}"
                                class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                            @error('upi_max_amount')<p class="text-[12px] font-semibold text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-[#E5E9EB] p-4 flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-building-columns text-[#0A5C66] text-[13px]"></i>
                        <span class="text-[13.5px] font-bold text-[#0F172A]">Bank range</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="bank_min_amount" class="block text-[11.5px] font-semibold text-[#334155] mb-1">Min ₹</label>
                            <input type="number" name="bank_min_amount" id="bank_min_amount" min="0" step="0.01" placeholder="0" value="{{ old('bank_min_amount', $settings['bank_min_amount']) }}"
                                class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                            @error('bank_min_amount')<p class="text-[12px] font-semibold text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="bank_max_amount" class="block text-[11.5px] font-semibold text-[#334155] mb-1">Max ₹</label>
                            <input type="number" name="bank_max_amount" id="bank_max_amount" min="0" step="0.01" placeholder="No limit" value="{{ old('bank_max_amount', $settings['bank_max_amount']) }}"
                                class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                            @error('bank_max_amount')<p class="text-[12px] font-semibold text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="h-10 rounded-lg bg-brand text-white font-semibold text-[13.5px] hover:bg-brand-light transition-colors active:scale-[0.99] sm:w-fit sm:px-6">
                Save ranges
            </button>
        </form>

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
                            </div>
                            <span class="text-[12px] text-[#64748B]">{{ $account->display_name ?: 'No display name' }}{{ $account->mobile_number ? ' · '.$account->mobile_number : '' }}</span>
                        </div>

                        <div class="flex gap-2 shrink-0">
                            <form method="POST" action="{{ route('admin.payment-gateway.upi-accounts.move', $account) }}">
                                @csrf
                                <input type="hidden" name="direction" value="up">
                                <button type="submit" {{ $loop->first ? 'disabled' : '' }} class="h-9 w-9 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition-colors active:scale-95 flex items-center justify-center disabled:opacity-30 disabled:pointer-events-none" title="Move up (higher priority)">
                                    <i class="fa-solid fa-arrow-up text-[12px]"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.payment-gateway.upi-accounts.move', $account) }}">
                                @csrf
                                <input type="hidden" name="direction" value="down">
                                <button type="submit" {{ $loop->last ? 'disabled' : '' }} class="h-9 w-9 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition-colors active:scale-95 flex items-center justify-center disabled:opacity-30 disabled:pointer-events-none" title="Move down (lower priority)">
                                    <i class="fa-solid fa-arrow-down text-[12px]"></i>
                                </button>
                            </form>
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
                            </div>
                            <span class="text-[12px] text-[#64748B]">{{ $account->account_holder_name }} · {{ \Illuminate\Support\Str::mask($account->account_number, '*', 0, -4) }} · {{ $account->ifsc_code }}</span>
                        </div>

                        <div class="flex gap-2 shrink-0">
                            <form method="POST" action="{{ route('admin.payment-gateway.bank-accounts.move', $account) }}">
                                @csrf
                                <input type="hidden" name="direction" value="up">
                                <button type="submit" {{ $loop->first ? 'disabled' : '' }} class="h-9 w-9 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition-colors active:scale-95 flex items-center justify-center disabled:opacity-30 disabled:pointer-events-none" title="Move up (higher priority)">
                                    <i class="fa-solid fa-arrow-up text-[12px]"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.payment-gateway.bank-accounts.move', $account) }}">
                                @csrf
                                <input type="hidden" name="direction" value="down">
                                <button type="submit" {{ $loop->last ? 'disabled' : '' }} class="h-9 w-9 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition-colors active:scale-95 flex items-center justify-center disabled:opacity-30 disabled:pointer-events-none" title="Move down (lower priority)">
                                    <i class="fa-solid fa-arrow-down text-[12px]"></i>
                                </button>
                            </form>
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
