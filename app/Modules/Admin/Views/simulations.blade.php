@extends('layouts.admin')

@section('title', 'Simulations')

@section('content')

<div class="flex min-h-screen">

    <x-admin-sidebar active="simulations" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Simulations" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <div id="admin-toast" class="hidden mb-6 rounded-lg border px-4 py-3 text-[13.5px] font-medium" role="status" aria-live="polite"></div>

        <div class="mb-6 flex items-start gap-2.5 bg-amber-50 border border-amber-200 rounded-[14px] p-3.5">
            <i class="fa-solid fa-flask text-[13px] text-amber-600 mt-0.5"></i>
            <p class="text-[12px] text-amber-800 font-medium leading-relaxed">Demo tooling - these buttons write to this browser's local storage only, mimicking flows that would normally run on a schedule or from a real referral.</p>
        </div>

        <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Simulations</h1>
        <p class="text-[13.5px] text-[#64748B] mb-6">Trigger flows that would normally run on a schedule or from a real referral.</p>

        <div class="flex flex-col gap-3 bg-white rounded-2xl border border-[#E5E9EB] p-6">
            <button id="btn-simulate-commission" type="button"
                class="flex items-center justify-between h-11 px-4 rounded-lg border border-[#CBD5E1] hover:border-brand/50 hover:bg-brand/[0.03] transition-colors text-left">
                <span class="text-[13.5px] font-semibold text-[#0F172A]">Release daily commission</span>
                <i class="fa-solid fa-chevron-right text-[11px] text-[#94A3B8]"></i>
            </button>
            <button id="btn-simulate-referral" type="button"
                class="flex items-center justify-between h-11 px-4 rounded-lg border border-[#CBD5E1] hover:border-brand/50 hover:bg-brand/[0.03] transition-colors text-left">
                <span class="text-[13.5px] font-semibold text-[#0F172A]">Simulate a completed referral</span>
                <i class="fa-solid fa-chevron-right text-[11px] text-[#94A3B8]"></i>
            </button>
        </div>

        </div>
    </main>
</div>

<script>
(function () {
    // Real, saved settings, so the simulated math stays consistent with
    // what's actually configured on the Referral program page.
    const opsSettings = @json(['commissionPercent' => (float) $settings['commission_percent'], 'cashbackAmount' => (float) $settings['cashback_amount']]);
    const DEMO_REFERRER_PHONE = 'demo-referrer';
    const REFERRAL_LOGS_KEY = 'gullakpe_admin_referral_logs';
    const COMMISSION_LOGS_KEY = 'gullakpe_admin_commission_logs';

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

    const simulateCommissionBtn = document.getElementById('btn-simulate-commission');
    if (simulateCommissionBtn) {
        simulateCommissionBtn.addEventListener('click', () => {
            const { commissionPercent, cashbackAmount } = opsSettings;
            const commissionAmount = (cashbackAmount * commissionPercent) / 100;

            const balance = getWallet(DEMO_REFERRER_PHONE);
            setWallet(DEMO_REFERRER_PHONE, balance + commissionAmount);

            logEvent(COMMISSION_LOGS_KEY, `Settlement: Rolled over ₹${commissionAmount.toFixed(2)} pending commission to Available status for demo referrer.`);
            showToast(`Released ₹${commissionAmount.toFixed(2)} commission to the demo referrer account.`);
        });
    }

    const simulateReferralBtn = document.getElementById('btn-simulate-referral');
    if (simulateReferralBtn) {
        simulateReferralBtn.addEventListener('click', () => {
            const { commissionPercent, cashbackAmount } = opsSettings;
            const commissionEarned = (cashbackAmount * commissionPercent) / 100;
            const mockNames = ['Aarav', 'Priya', 'Karan', 'Neha', 'Rahul'];
            const mockName = mockNames[Math.floor(Math.random() * mockNames.length)];

            const balance = getWallet(DEMO_REFERRER_PHONE);
            setWallet(DEMO_REFERRER_PHONE, balance + cashbackAmount);

            logEvent(REFERRAL_LOGS_KEY, `Qualified: Mock friend "${mockName}" completed a qualifying investment. Cashback of ₹${cashbackAmount.toFixed(2)} credited instantly to demo referrer.`);
            logEvent(COMMISSION_LOGS_KEY, `Commission Created: Referral commission of ₹${commissionEarned.toFixed(2)} (${commissionPercent}%) generated from mock friend "${mockName}"'s investment. Status: PENDING.`);

            showToast(`Simulated referral from "${mockName}" — cashback credited, commission logged as pending.`);
        });
    }
})();
</script>

@endsection
