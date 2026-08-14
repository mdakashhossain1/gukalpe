@extends('layouts.admin')

@section('title', 'Activity logs')

@section('content')

<link rel="stylesheet" href="{{ asset('libs/simple-datatables/style.css') }}">
<style>
    #audit-table-card .datatable-top { padding: 0 0 14px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    #audit-table-card .datatable-bottom { padding: 14px 0 0; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    #audit-table-card .datatable-search input { height: 38px; border: 1px solid #E5E9EB; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #0F172A; outline: none; min-width: 220px; }
    #audit-table-card .datatable-search input:focus { border-color: #0A5C66; box-shadow: 0 0 0 3px rgba(10,92,102,.12); }
    #audit-table-card .datatable-selector { height: 38px; border: 1px solid #E5E9EB; border-radius: 8px; padding: 0 8px; font-size: 13px; color: #334155; }
    #audit-table-card .datatable-info { font-size: 12.5px; color: #64748B; }
    #audit-table-card .datatable-container { overflow-x: auto; border: 0; }
    #audit-table-card table.datatable-table { min-width: 980px; }
    #audit-table-card .datatable-pagination a { border-radius: 8px; padding: 6px 11px; font-size: 12.5px; font-weight: 600; color: #334155; }
    #audit-table-card .datatable-pagination a:hover { background: #F1F5F9; }
    #audit-table-card .datatable-pagination .datatable-active a { background: #0A5C66; color: #fff; }

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
        <p class="text-[13.5px] text-[#64748B] mb-6">Permanent, database-backed record of every money- or state-changing admin action: who did it, what, why, and when. Wallet changes, deposit/withdrawal approvals, bans, plan/settings/payment-gateway changes, and admin/role changes all land here - never dependent on browser storage.</p>

        <div class="flex gap-1.5 mb-4 bg-[#F1F5F9] rounded-lg p-1 w-fit flex-wrap">
            <a href="{{ route('admin.logs') }}"
                class="h-8 px-4 rounded-md text-[12.5px] transition-colors flex items-center {{ $action === 'all' ? 'font-bold bg-white text-[#0F172A] shadow-sm' : 'font-semibold text-[#64748B]' }}">
                All
            </a>
            @foreach ($actions as $a)
                <a href="{{ route('admin.logs', ['action' => $a]) }}"
                    class="h-8 px-4 rounded-md text-[12.5px] transition-colors flex items-center {{ $action === $a ? 'font-bold bg-white text-[#0F172A] shadow-sm' : 'font-semibold text-[#64748B]' }}">
                    {{ ucfirst(str_replace('_', ' ', $a)) }}
                </a>
            @endforeach
        </div>

        {{-- Every detail visible directly in the table - no click-through
             required. With a high volume of activity, having to open each
             entry on its own page to see what actually happened isn't
             practical, so the full meta renders as a wrapped list of chips
             right in the row instead of behind a "View" link. --}}
        <div class="bg-white rounded-xl border border-[#E5E9EB] p-4 mb-8" id="audit-table-card">
            <div class="overflow-x-auto">
                <table id="audit-table" class="w-full text-left border-collapse min-w-[1180px]">
                    <thead>
                        <tr class="bg-[#F8FAFC] border-b border-[#E5E9EB]">
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">When</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Admin</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Action</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Target</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Reason</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr class="border-b border-[#F1F5F9] last:border-0 hover:bg-[#F8FAFC] transition-colors">
                                <td class="px-4 py-3 align-middle text-[12px] text-[#64748B] whitespace-nowrap" data-order="{{ $entry->created_at?->timestamp }}">
                                    {{ $entry->created_at?->format('d M Y, h:i A') }}
                                </td>
                                <td class="px-4 py-3 align-middle text-[13px] font-semibold text-[#0F172A] whitespace-nowrap">{{ $entry->admin_label }}</td>
                                <td class="px-4 py-3 align-middle whitespace-nowrap">
                                    <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border bg-slate-50 text-slate-600 border-slate-200">{{ $entry->actionLabel() }}</span>
                                </td>
                                <td class="px-4 py-3 align-middle text-[12.5px] font-mono text-[#334155] whitespace-nowrap">
                                    @if ($entry->target_type === 'User' && ($targetUser = $targetUsers[$entry->target_id] ?? null))
                                        <a href="{{ route('admin.users.show', $targetUser) }}" class="text-[#0A5C66] hover:underline">User #{{ $entry->target_id }}</a>
                                    @else
                                        {{ $entry->target_type ? $entry->target_type.' #'.$entry->target_id : '—' }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-middle text-[12.5px] text-[#334155] max-w-[240px]">{{ $entry->reason ?: '—' }}</td>
                                <td class="px-4 py-3 align-middle max-w-[320px]">
                                    @if ($entry->meta)
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($entry->meta as $key => $value)
                                                <span class="inline-flex items-baseline gap-1 text-[11px] px-2 py-1 rounded-md bg-[#F8FAFC] border border-[#F1F5F9] whitespace-nowrap">
                                                    <span class="font-semibold text-[#94A3B8]">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                    <span class="font-mono font-semibold text-[#334155]">
                                                        @if (is_bool($value)) {{ $value ? 'Yes' : 'No' }}
                                                        @elseif ($value === null || $value === '') —
                                                        @else {{ $value }}
                                                        @endif
                                                    </span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-[12px] text-[#CBD5E1]">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-[13.5px] text-[#94A3B8] italic">No activity recorded yet.</td>
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

@if ($entries->isNotEmpty())
    <script src="{{ asset('libs/simple-datatables/simple-datatables.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof simpleDatatables === 'undefined') return;

            new simpleDatatables.DataTable('#audit-table', {
                searchable: true,
                paging: true,
                perPage: 25,
                perPageSelect: [25, 50, 100],
                sortable: true,
                // Details is a set of meta chips, not a single sortable value.
                columns: [{ select: 5, sortable: false }],
                labels: {
                    placeholder: 'Search activity logs...',
                    perPage: '{select} per page',
                    noRows: 'No activity found',
                    noResults: 'No entries match your search',
                    info: 'Showing {start}–{end} of {rows} entries',
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
