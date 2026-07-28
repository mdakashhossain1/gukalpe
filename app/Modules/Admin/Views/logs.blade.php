@extends('layouts.admin')

@section('title', 'Activity logs')

@section('content')

<style>
    /* Self-contained switch so its colors never depend on the Tailwind build. */
    .logs-switch { position: relative; width: 40px; height: 24px; border-radius: 9999px; background: #10B981; transition: background-color .15s; cursor: pointer; flex-shrink: 0; border: 0; padding: 0; }
    .logs-switch.is-off { background: #CBD5E1; }
    .logs-switch-knob { position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; border-radius: 9999px; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.2); transition: transform .15s; transform: translateX(16px); }
    .logs-switch.is-off .logs-switch-knob { transform: translateX(0); }
</style>

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="logs" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Activity logs" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <div id="admin-toast" class="hidden mb-6 rounded-lg border px-4 py-3 text-[13.5px] font-medium" role="status" aria-live="polite"></div>

        <div class="mb-6 flex items-start gap-2.5 bg-amber-50 border border-amber-200 rounded-[14px] p-3.5">
            <i class="fa-solid fa-flask text-[13px] text-amber-600 mt-0.5"></i>
            <p class="text-[12px] text-amber-800 font-medium leading-relaxed">Demo tooling - these entries are written by the Simulations page to this browser's local storage only, not a real event log.</p>
        </div>

        <div class="flex items-center justify-between gap-3 mb-1">
            <h1 class="font-poppins font-bold text-[20px] text-[#0F172A]">Activity logs</h1>
            <div class="flex items-center gap-4 shrink-0">
                <div class="flex items-center gap-2 select-none">
                    <span id="logs-toggle-label" class="text-[12.5px] font-semibold text-[#334155]">Logging on</span>
                    <button type="button" id="logs-toggle" role="switch" aria-checked="true" aria-label="Toggle logging" class="logs-switch">
                        <span class="logs-switch-knob"></span>
                    </button>
                </div>
                <button id="btn-clear-logs" type="button" class="text-[12.5px] font-semibold text-[#B91C1C] hover:underline">Clear</button>
            </div>
        </div>
        <p class="text-[13.5px] text-[#64748B] mb-6">Local only (never stored in the database) — cleared logs can't be recovered. Turn logging off to stop new entries being recorded.</p>

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
                class="min-h-[72px] max-h-[420px] rounded-lg bg-[#0F172A] p-3 text-[11.5px] font-mono text-[#6EE7B7] overflow-y-auto whitespace-pre-wrap leading-relaxed">
                No log entries yet.
            </div>
            <div id="log-panel-commission" data-log-panel="commission" role="tabpanel" hidden
                class="min-h-[72px] max-h-[420px] rounded-lg bg-[#0F172A] p-3 text-[11.5px] font-mono text-[#6EE7B7] overflow-y-auto whitespace-pre-wrap leading-relaxed">
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
    // Shared with the Simulations page's logEvent() - when '0', new events
    // are not recorded. Default (missing key) = logging on.
    const LOGS_ENABLED_KEY = 'gullakpe_admin_logs_enabled';

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

    // Logging on/off toggle - persisted in localStorage, honored by the
    // Simulations page before it writes any new entry.
    const logsToggle = document.getElementById('logs-toggle');
    const logsToggleLabel = document.getElementById('logs-toggle-label');

    function logsEnabled() {
        return localStorage.getItem(LOGS_ENABLED_KEY) !== '0';
    }

    function reflectToggle() {
        const on = logsEnabled();
        logsToggle.classList.toggle('is-off', !on);
        logsToggle.setAttribute('aria-checked', String(on));
        logsToggleLabel.textContent = on ? 'Logging on' : 'Logging off';
    }

    if (logsToggle) {
        reflectToggle();
        logsToggle.addEventListener('click', () => {
            localStorage.setItem(LOGS_ENABLED_KEY, logsEnabled() ? '0' : '1');
            reflectToggle();
            showToast(logsEnabled() ? 'Logging turned on.' : 'Logging turned off — new events won\'t be recorded.');
        });
    }

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
