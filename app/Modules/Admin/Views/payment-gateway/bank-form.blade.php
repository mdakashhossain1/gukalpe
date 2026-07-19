@extends('layouts.admin')

@section('title', $account->exists ? 'Edit bank account' : 'Add bank account')

@section('content')

<div class="flex min-h-screen">

    <x-admin-sidebar active="payment-gateway" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="{{ $account->exists ? 'Edit bank account' : 'Add bank account' }}" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <a href="{{ route('admin.payment-gateway') }}" class="inline-flex items-center gap-1.5 text-[13px] font-bold text-slate-400 hover:text-[#0A5C66] transition-colors mb-4">
            <i class="fa-solid fa-arrow-left text-[12px]"></i> Back to payment gateway
        </a>

        <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">{{ $account->exists ? 'Edit bank account' : 'Add bank account' }}</h1>
        <p class="text-[13.5px] text-[#64748B] mb-6">Shown to users at random alongside every other active bank account whenever Bank Transfer is the selected method on Add Money.</p>

        <form method="POST" action="{{ $account->exists ? route('admin.payment-gateway.bank-accounts.update', $account) : route('admin.payment-gateway.bank-accounts.store') }}" class="flex flex-col gap-3.5 bg-white rounded-2xl border border-[#E5E9EB] p-6 max-w-xl">
            @csrf

            <div>
                <label for="account_holder_name" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Account holder name</label>
                <input type="text" name="account_holder_name" id="account_holder_name" maxlength="150" value="{{ old('account_holder_name', $account->account_holder_name) }}" required
                    class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                @error('account_holder_name')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label for="account_number" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Account number</label>
                    <input type="text" name="account_number" id="account_number" maxlength="30" value="{{ old('account_number', $account->account_number) }}" required
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                    @error('account_number')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="ifsc_code" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">IFSC code</label>
                    <input type="text" name="ifsc_code" id="ifsc_code" maxlength="11" placeholder="e.g. HDFC0001234" value="{{ old('ifsc_code', $account->ifsc_code) }}" required
                        oninput="this.value = this.value.toUpperCase();"
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15 uppercase">
                    @error('ifsc_code')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label for="bank_name" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Bank name</label>
                    <input type="text" name="bank_name" id="bank_name" maxlength="100" value="{{ old('bank_name', $account->bank_name) }}" required
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                    @error('bank_name')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="branch_name" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Branch (optional)</label>
                    <input type="text" name="branch_name" id="branch_name" maxlength="100" value="{{ old('branch_name', $account->branch_name) }}"
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                    @error('branch_name')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="sort_order" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Sort order (list display only)</label>
                <input type="number" name="sort_order" id="sort_order" min="0" value="{{ old('sort_order', $account->sort_order) }}"
                    class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                @error('sort_order')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
            </div>

            <label class="flex items-center gap-2.5 h-11 px-3.5 rounded-lg border border-[#CBD5E1] has-[:checked]:border-brand has-[:checked]:bg-brand/5 cursor-pointer transition-colors w-fit mt-1">
                <input type="checkbox" name="is_active" value="1" class="accent-brand" {{ old('is_active', $account->is_active) ? 'checked' : '' }}>
                <span class="text-[13.5px] font-semibold text-[#0F172A]">Active (eligible for random selection)</span>
            </label>

            <button type="submit" class="h-10 rounded-lg bg-brand text-white font-semibold text-[13.5px] hover:bg-brand-light transition-colors active:scale-[0.99] sm:w-fit sm:px-6 mt-1">
                {{ $account->exists ? 'Save changes' : 'Add bank account' }}
            </button>
        </form>

        </div>
    </main>
</div>

@endsection
