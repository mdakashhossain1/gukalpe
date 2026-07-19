@extends('layouts.admin')

@section('title', 'Wallet adjustment')

@section('content')

<div class="flex min-h-screen">

    <x-admin-sidebar active="wallet" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Wallet adjustment" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <div id="admin-toast" class="hidden mb-6 rounded-lg border px-4 py-3 text-[13.5px] font-medium" role="status" aria-live="polite"></div>

        <div class="mb-6 flex items-start gap-2.5 bg-amber-50 border border-amber-200 rounded-[14px] p-3.5">
            <i class="fa-solid fa-flask text-[13px] text-amber-600 mt-0.5"></i>
            <p class="text-[12px] text-amber-800 font-medium leading-relaxed">Demo tooling - adjustments here are stored in this browser's local storage only, not the real wallet balances shown on the Overview page.</p>
        </div>

        <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Wallet adjustment</h1>
        <p class="text-[13.5px] text-[#64748B] mb-6">Add or subtract from a user's simulated wallet balance.</p>

        <form id="wallet-adjust-form" class="flex flex-col gap-3.5 bg-white rounded-2xl border border-[#E5E9EB] p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label for="wallet-phone" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Phone number</label>
                    <input type="tel" id="wallet-phone" inputmode="numeric" pattern="[0-9]*" maxlength="10" placeholder="10-digit phone number"
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                </div>
                <div>
                    <label for="wallet-amount" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Amount (₹, use − to subtract)</label>
                    <input type="number" id="wallet-amount" step="0.01" placeholder="e.g. 250 or -100"
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                </div>
            </div>
            <button type="submit" class="h-10 rounded-lg bg-brand text-white font-semibold text-[13.5px] hover:bg-brand-light transition-colors active:scale-[0.99] sm:w-fit sm:px-6">
                Apply adjustment
            </button>
        </form>

        </div>
    </main>
</div>

<script>
(function () {
    function showToast(message, kind = 'success') {
        const toast = document.getElementById('admin-toast');
        if (!toast) return;

        const styles = {
            success: 'bg-[#F0FDF4] border-[#86EFAC]/60 text-[#166534]',
            error: 'bg-[#FEF2F2] border-[#FCA5A5]/60 text-[#B91C1C]',
        };

        toast.className = `mb-6 rounded-lg border px-4 py-3 text-[13.5px] font-medium ${styles[kind]}`;
        toast.textContent = message;
        toast.classList.remove('hidden');

        clearTimeout(showToast._timer);
        showToast._timer = setTimeout(() => toast.classList.add('hidden'), 4000);
    }

    function walletKey(phone) {
        return `bachatpe_wallet_balance_${phone}`;
    }

    function getWallet(phone) {
        return parseFloat(localStorage.getItem(walletKey(phone)) || '0');
    }

    function setWallet(phone, amount) {
        localStorage.setItem(walletKey(phone), amount.toString());
    }

    function logEvent(key, message) {
        const logs = JSON.parse(localStorage.getItem(key) || '[]');
        const timestamp = new Date().toLocaleTimeString('en-IN', { hour12: false }) + ' ' + new Date().toLocaleDateString('en-IN');
        logs.unshift(`[${timestamp}] ${message}`);
        localStorage.setItem(key, JSON.stringify(logs.slice(0, 100)));
    }

    const REFERRAL_LOGS_KEY = 'gullakpe_admin_referral_logs';

    const walletForm = document.getElementById('wallet-adjust-form');
    if (!walletForm) return;

    walletForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const phoneInput = document.getElementById('wallet-phone');
        const amountInput = document.getElementById('wallet-amount');
        const phone = phoneInput.value.trim();
        const amount = parseFloat(amountInput.value);

        if (!/^\d{10}$/.test(phone)) {
            showToast('Enter a valid 10-digit phone number.', 'error');
            return;
        }
        if (isNaN(amount) || amount === 0) {
            showToast('Enter a non-zero amount.', 'error');
            return;
        }

        const current = getWallet(phone);
        const next = Math.max(0, current + amount);
        setWallet(phone, next);
        logEvent(REFERRAL_LOGS_KEY, `Manual adjustment: ${amount > 0 ? '+' : ''}₹${amount.toFixed(2)} applied to ${phone}. New balance ₹${next.toFixed(2)}.`);

        showToast(`Applied. ${phone}'s balance is now ₹${next.toFixed(2)}.`);
        amountInput.value = '';
    });
})();
</script>

@endsection
