@extends('layouts.admin')

@section('title', 'Activity logs')

@section('content')

<link rel="stylesheet" href="{{ asset('libs/simple-datatables/style.css') }}">
<style>
    #logs-users-table-card .datatable-top { padding: 0 0 14px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    #logs-users-table-card .datatable-bottom { padding: 14px 0 0; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    #logs-users-table-card .datatable-search input { height: 38px; border: 1px solid #E5E9EB; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #0F172A; outline: none; min-width: 220px; }
    #logs-users-table-card .datatable-search input:focus { border-color: #0A5C66; box-shadow: 0 0 0 3px rgba(10,92,102,.12); }
    #logs-users-table-card .datatable-selector { height: 38px; border: 1px solid #E5E9EB; border-radius: 8px; padding: 0 8px; font-size: 13px; color: #334155; }
    #logs-users-table-card .datatable-info { font-size: 12.5px; color: #64748B; }
    #logs-users-table-card .datatable-container { overflow-x: auto; border: 0; }
    #logs-users-table-card table.datatable-table { min-width: 820px; }
    #logs-users-table-card .datatable-pagination a { border-radius: 8px; padding: 6px 11px; font-size: 12.5px; font-weight: 600; color: #334155; }
    #logs-users-table-card .datatable-pagination a:hover { background: #F1F5F9; }
    #logs-users-table-card .datatable-pagination .datatable-active a { background: #0A5C66; color: #fff; }

    .logs-i-btn {
        width: 34px; height: 34px; border-radius: 9999px; border: 1px solid #CBD5E1; background: #fff;
        color: #0A5C66; display: inline-flex; align-items: center; justify-content: center;
        font-size: 14px; transition: background-color .15s, border-color .15s;
    }
    .logs-i-btn:hover { background: #F0FDFA; border-color: #0A5C66; }

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

        <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Activity logs</h1>
        <p class="text-[13.5px] text-[#64748B] mb-6">Every registered user. Click the <i class="fa-solid fa-circle-info text-[#0A5C66]"></i> button on any row to open that user's full activity: wallet, deposits, withdrawals, investments, referrals, and recent admin actions.</p>

        <div class="bg-white rounded-xl border border-[#E5E9EB] p-4 mb-8" id="logs-users-table-card">
            <div class="overflow-x-auto">
                <table id="logs-users-table" class="w-full text-left border-collapse min-w-[820px]">
                    <thead>
                        <tr class="bg-[#F8FAFC] border-b border-[#E5E9EB]">
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">User</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Phone</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Wallet</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Joined</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr class="border-b border-[#F1F5F9] last:border-0 hover:bg-[#F8FAFC] transition-colors">
                                <td class="px-4 py-3 align-middle">
                                    <div class="flex items-center gap-3">
                                        @if ($user->avatar)
                                            <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="w-9 h-9 rounded-full object-cover shrink-0 border border-[#E5E9EB]" referrerpolicy="no-referrer">
                                        @else
                                            <div class="w-9 h-9 rounded-full bg-[#0A5C66]/10 text-[#0A5C66] font-bold text-[13px] flex items-center justify-center shrink-0 uppercase">
                                                {{ mb_substr($user->name ?: '?', 0, 1) }}
                                            </div>
                                        @endif
                                        <div class="flex flex-col gap-0.5 min-w-0">
                                            <span class="text-[13.5px] font-bold text-[#0F172A]">{{ $user->name ?: '—' }}</span>
                                            @if ($user->isBanned())
                                                <span class="w-fit text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border bg-red-50 text-red-700 border-red-200">
                                                    <i class="fa-solid fa-ban text-[9px]"></i> Banned
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-middle text-[13px] font-mono text-[#334155] whitespace-nowrap">{{ $user->phone ?: '—' }}</td>
                                <td class="px-4 py-3 align-middle text-[13px] font-mono font-semibold text-[#0F172A] whitespace-nowrap">₹{{ number_format($user->wallet_balance, 2) }}</td>
                                <td class="px-4 py-3 align-middle text-[13px] text-[#64748B] whitespace-nowrap" data-order="{{ $user->created_at?->timestamp }}">
                                    {{ $user->created_at?->format('d M Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 align-middle text-right whitespace-nowrap">
                                    <a href="{{ route('admin.users.show', $user) }}" class="logs-i-btn" title="View {{ $user->name ?: $user->phone }}'s full details" aria-label="View details">
                                        <i class="fa-solid fa-circle-info"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-[13.5px] text-[#94A3B8] italic">No users yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Referral/commission simulation debug console - pre-existing,
             paired with the Simulations page, tests commission-calculation
             math rather than admin actions. Kept separate below the real
             log rather than removed, since it's a different tool for a
             different purpose - just no longer presented as "the" Activity
             Logs page. --}}
        <div id="admin-toast" class="hidden mb-6 rounded-lg border px-4 py-3 text-[13.5px] font-medium" role="status" aria-live="polite"></div>

        <div class="flex items-center justify-between gap-3 mb-1">
            <h2 class="font-poppins font-bold text-[15px] text-[#0F172A]">Referral/commission simulation console</h2>
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
        <p class="text-[13.5px] text-[#64748B] mb-4">Browser-local only, written by the Simulations page - for testing referral/commission calculation logic, not a record of real admin actions.</p>

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
                class="h-[40vh] min-h-[240px] rounded-lg bg-[#0F172A] p-3 text-[11.5px] font-mono text-[#6EE7B7] overflow-y-auto whitespace-pre-wrap leading-relaxed">
                No log entries yet.
            </div>
            <div id="log-panel-commission" data-log-panel="commission" role="tabpanel" hidden
                class="h-[40vh] min-h-[240px] rounded-lg bg-[#0F172A] p-3 text-[11.5px] font-mono text-[#6EE7B7] overflow-y-auto whitespace-pre-wrap leading-relaxed">
                No log entries yet.
            </div>
        </div>

        </div>
    </main>
</div>

@if ($users->isNotEmpty())
    <script src="{{ asset('libs/simple-datatables/simple-datatables.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof simpleDatatables === 'undefined') return;

            new simpleDatatables.DataTable('#logs-users-table', {
                searchable: true,
                paging: true,
                perPage: 25,
                perPageSelect: [25, 50, 100],
                sortable: true,
                // Last column is the "i" details button, not sortable text.
                columns: [{ select: 4, sortable: false }],
                labels: {
                    placeholder: 'Search users...',
                    perPage: '{select} per page',
                    noRows: 'No users found',
                    noResults: 'No users match your search',
                    info: 'Showing {start}–{end} of {rows} users',
                },
            });
        });
    </script>
@endif

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
