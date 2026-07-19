@extends('layouts.admin')

@section('title', 'Activity logs')

@section('content')

<div class="flex min-h-screen">

    <x-admin-sidebar active="logs" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Activity logs" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <div id="admin-toast" class="hidden mb-6 rounded-lg border px-4 py-3 text-[13.5px] font-medium" role="status" aria-live="polite"></div>

        <div class="mb-6 flex items-start gap-2.5 bg-amber-50 border border-amber-200 rounded-[14px] p-3.5">
            <i class="fa-solid fa-flask text-[13px] text-amber-600 mt-0.5"></i>
            <p class="text-[12px] text-amber-800 font-medium leading-relaxed">Demo tooling - these entries are written by the Simulations page to this browser's local storage only, not a real event log.</p>
        </div>

        <div class="flex items-center justify-between mb-1">
            <h1 class="font-poppins font-bold text-[20px] text-[#0F172A]">Activity logs</h1>
            <button id="btn-clear-logs" type="button" class="text-[12.5px] font-semibold text-[#B91C1C] hover:underline">Clear</button>
        </div>
        <p class="text-[13.5px] text-[#64748B] mb-6">Local only — cleared logs can't be recovered.</p>

        <div class="bg-white rounded-2xl border border-[#E5E9EB] p-6">
            <div class="flex gap-1.5 mb-3 bg-[#F1F5F9] rounded-lg p-1" role="tablist">
                <button type="button" role="tab" aria-selected="true" data-log-tab="referral"
                    class="log-tab flex-1 h-8 rounded-md text-[12.5px] font-bold transition-colors bg-white text-[#0F172A] shadow-sm">
                    Referral
                </button>
                <button type="button" role="tab" aria-selected="false" data-log-tab="commission"
                    class="log-tab flex-1 h-8 rounded-md text-[12.5px] font-semibold transition-colors text-[#64748B]">
                    Commission
                </button>
            </div>

            <div id="log-panel-referral" data-log-panel="referral" role="tabpanel"
                class="h-56 rounded-lg bg-[#0F172A] p-3 text-[11.5px] font-mono text-[#6EE7B7] overflow-y-auto whitespace-pre-wrap leading-relaxed">
                No log entries yet.
            </div>
            <div id="log-panel-commission" data-log-panel="commission" role="tabpanel" hidden
                class="h-56 rounded-lg bg-[#0F172A] p-3 text-[11.5px] font-mono text-[#6EE7B7] overflow-y-auto whitespace-pre-wrap leading-relaxed">
                No log entries yet.
            </div>
        </div>

        </div>
    </main>
</div>

<script>
(function () {
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

    const logTabs = document.querySelectorAll('.log-tab');
    const logPanels = {
        referral: document.getElementById('log-panel-referral'),
        commission: document.getElementById('log-panel-commission'),
    };

    function renderLogs() {
        const referralLogs = JSON.parse(localStorage.getItem(REFERRAL_LOGS_KEY) || '[]');
        const commissionLogs = JSON.parse(localStorage.getItem(COMMISSION_LOGS_KEY) || '[]');

        if (logPanels.referral) {
            logPanels.referral.textContent = referralLogs.length ? referralLogs.join('\n') : 'No log entries yet.';
        }
        if (logPanels.commission) {
            logPanels.commission.textContent = commissionLogs.length ? commissionLogs.join('\n') : 'No log entries yet.';
        }
    }

    logTabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const active = tab.dataset.logTab;

            logTabs.forEach((t) => {
                const isActive = t === tab;
                t.setAttribute('aria-selected', String(isActive));
                t.classList.toggle('bg-white', isActive);
                t.classList.toggle('shadow-sm', isActive);
                t.classList.toggle('text-[#0F172A]', isActive);
                t.classList.toggle('font-bold', isActive);
                t.classList.toggle('text-[#64748B]', !isActive);
                t.classList.toggle('font-semibold', !isActive);
            });

            Object.entries(logPanels).forEach(([key, panel]) => {
                if (panel) panel.hidden = key !== active;
            });
        });
    });

    const clearLogsBtn = document.getElementById('btn-clear-logs');
    if (clearLogsBtn) {
        clearLogsBtn.addEventListener('click', () => {
            if (!confirm('Clear all referral and commission logs? This cannot be undone.')) return;
            localStorage.setItem(REFERRAL_LOGS_KEY, '[]');
            localStorage.setItem(COMMISSION_LOGS_KEY, '[]');
            renderLogs();
            showToast('Logs cleared.');
        });
    }

    renderLogs();
})();
</script>

@endsection
