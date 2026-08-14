@extends('layouts.admin')

@section('title', $account->exists ? 'Edit USDT account' : 'Add USDT account')

@section('content')

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="payment-gateway" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="{{ $account->exists ? 'Edit USDT account' : 'Add USDT account' }}" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <a href="{{ route('admin.payment-gateway') }}" class="inline-flex items-center gap-1.5 text-[13px] font-bold text-slate-400 hover:text-[#0A5C66] transition-colors mb-4">
            <i class="fa-solid fa-arrow-left text-[12px]"></i> Back to payment gateway
        </a>

        <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">{{ $account->exists ? 'Edit USDT account' : 'Add USDT account' }}</h1>
        <p class="text-[13.5px] text-[#64748B] mb-6">Rotates least-recently-used first among all active USDT accounts whenever the deposit amount falls in the USDT amount range (set on the Payment gateway page).</p>

        <form method="POST" action="{{ $account->exists ? route('admin.payment-gateway.usdt-accounts.update', $account) : route('admin.payment-gateway.usdt-accounts.store') }}" enctype="multipart/form-data" class="flex flex-col gap-3.5 bg-white rounded-2xl border border-[#E5E9EB] p-6 max-w-xl">
            @csrf

            <div>
                <label for="usdt_address" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">USDT TRC20 wallet address</label>
                <input type="text" name="usdt_address" id="usdt_address" placeholder="Txxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" value="{{ old('usdt_address', $account->usdt_address) }}" required
                    class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                <p class="text-[11px] text-[#94A3B8] mt-1">Only the TRC20 network is supported - starts with T, 34 characters.</p>
                @error('usdt_address')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="display_name" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Display name (optional)</label>
                <input type="text" name="display_name" id="display_name" maxlength="100" placeholder="e.g. Meri Gullak Deposit" value="{{ old('display_name', $account->display_name) }}"
                    class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                @error('display_name')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="qr_image" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">QR code screenshot <span class="font-normal text-[#94A3B8]">(optional{{ $account->exists ? ' - leave empty to keep current' : '' }})</span></label>
                @if ($account->exists && $account->qr_image)
                    <div class="mb-2 flex items-center gap-2.5">
                        <img src="{{ $account->qrImageUrl() }}" alt="{{ $account->usdt_address }}" class="w-16 h-16 rounded-lg object-cover border border-[#E5E9EB]">
                        <span class="text-[11.5px] text-[#94A3B8]">Current QR</span>
                    </div>
                @endif
                <input type="file" name="qr_image" id="qr_image" accept="image/png,image/jpeg,image/webp"
                    class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[13px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15 file:mr-3 file:h-full file:border-0 file:bg-[#0A5C66]/10 file:text-[#0A5C66] file:font-semibold file:px-3 file:rounded-l-lg file:cursor-pointer">
                <p class="text-[11px] text-[#94A3B8] mt-1">JPG, PNG, or WebP · up to 4MB</p>
                @error('qr_image')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="sort_order" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Sort order (list display only)</label>
                <input type="number" name="sort_order" id="sort_order" min="0" value="{{ old('sort_order', $account->sort_order) }}"
                    class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                @error('sort_order')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
            </div>

            <label class="flex items-center gap-2.5 h-11 px-3.5 rounded-lg border border-[#CBD5E1] has-[:checked]:border-brand has-[:checked]:bg-brand/5 cursor-pointer transition-colors w-fit mt-1">
                <input type="checkbox" name="is_active" value="1" class="accent-brand" {{ old('is_active', $account->is_active) ? 'checked' : '' }}>
                <span class="text-[13.5px] font-semibold text-[#0F172A]">Active (eligible for rotation)</span>
            </label>

            <button type="submit" class="h-10 rounded-lg bg-brand text-white font-semibold text-[13.5px] hover:bg-brand-light transition-colors active:scale-[0.99] sm:w-fit sm:px-6 mt-1">
                {{ $account->exists ? 'Save changes' : 'Add USDT account' }}
            </button>
        </form>

        </div>
    </main>
</div>

@endsection
