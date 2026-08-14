@extends('layouts.admin')

@section('title', 'Referral commissions')

@section('content')

{{-- Self-hosted vanilla datatable - same library/pattern as deposits.blade.php. --}}
<link rel="stylesheet" href="{{ asset('libs/simple-datatables/style.css') }}">
<style>
    #commissions-table-card .datatable-top { padding: 0 0 14px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    #commissions-table-card .datatable-bottom { padding: 14px 0 0; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    #commissions-table-card .datatable-search input { height: 38px; border: 1px solid #E5E9EB; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #0F172A; outline: none; min-width: 220px; }
    #commissions-table-card .datatable-search input:focus { border-color: #0A5C66; box-shadow: 0 0 0 3px rgba(10,92,102,.12); }
    #commissions-table-card .datatable-selector { height: 38px; border: 1px solid #E5E9EB; border-radius: 8px; padding: 0 8px; font-size: 13px; color: #334155; }
    #commissions-table-card .datatable-info { font-size: 12.5px; color: #64748B; }
    #commissions-table-card .datatable-container { overflow-x: auto; border: 0; }
    #commissions-table-card table.datatable-table { min-width: 1080px; }
    #commissions-table-card .datatable-pagination a { border-radius: 8px; padding: 6px 11px; font-size: 12.5px; font-weight: 600; color: #334155; }
    #commissions-table-card .datatable-pagination a:hover { background: #F1F5F9; }
    #commissions-table-card .datatable-pagination .datatable-active a { background: #0A5C66; color: #fff; }
</style>

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="referral-commissions" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" :pending-referral-commission-count="$pendingReferralCommissionCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Referral commissions" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Referral commissions</h1>
        <p class="text-[13.5px] text-[#64748B] mb-6">Every commission is created Pending and holds no wallet movement until you Approve it here. Reject/Adjust/Reverse all require a reason.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3.5 mb-6">
            <x-admin-stat-tile label="Total referrals" :value="number_format($totalReferrals)" icon="fa-user-group" accent="#0A5C66" />
            <x-admin-stat-tile label="Active referrals" :value="number_format($activeReferrals)" icon="fa-user-check" accent="#0A5C66" />
            <x-admin-stat-tile label="Total commission" :value="'₹'.number_format($totalCommission, 2)" icon="fa-sack-dollar" accent="#0A5C66" />
            <x-admin-stat-tile label="Pending commission" :value="'₹'.number_format($pendingCommission, 2)" icon="fa-hourglass-half" accent="#D97706" />
            <x-admin-stat-tile label="Paid commission" :value="'₹'.number_format($paidCommission, 2)" icon="fa-circle-check" accent="#059669" />
        </div>

        @if ($phone)
            <div class="mb-4 flex items-center justify-between gap-3 bg-[#0A5C66]/[0.06] border border-[#0A5C66]/20 rounded-lg px-4 py-2.5">
                <p class="text-[12.5px] font-semibold text-[#0A5C66]">Filtered to phone <span class="font-mono">{{ $phone }}</span></p>
                <a href="{{ route('admin.referral-commissions', ['status' => $status]) }}" class="text-[12px] font-bold text-[#0A5C66] hover:underline">Clear filter</a>
            </div>
        @endif

        <div class="flex gap-1.5 mb-4 bg-[#F1F5F9] rounded-lg p-1 w-fit">
            @foreach (['pending' => 'Pending', 'paid' => 'Paid', 'rejected' => 'Rejected', 'reversed' => 'Reversed'] as $key => $label)
                <a href="{{ route('admin.referral-commissions', ['status' => $key] + ($phone ? ['phone' => $phone] : [])) }}"
                    class="h-8 px-4 rounded-md text-[12.5px] transition-colors flex items-center {{ $status === $key ? 'font-bold bg-white text-[#0F172A] shadow-sm' : 'font-semibold text-[#64748B]' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="bg-white rounded-xl border border-[#E5E9EB] p-4" id="commissions-table-card">
            <div class="overflow-x-auto">
                <table id="commissions-table" class="w-full text-left border-collapse min-w-[1080px]">
                    <thead>
                        <tr class="bg-[#F8FAFC] border-b border-[#E5E9EB]">
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Referrer</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Referred user</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Date</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Source</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Amount</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Commission</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Status</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($commissions as $commission)
                            @php
                                $pillClasses = match ($commission->status) {
                                    'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'rejected' => 'bg-red-50 text-red-700 border-red-200',
                                    'reversed' => 'bg-slate-100 text-slate-600 border-slate-200',
                                    default => 'bg-amber-50 text-amber-700 border-amber-200',
                                };
                                $sourceLabel = $commission->source === 'deposit' ? 'Deposit' : 'Purchase';
                                $qualifyingAmount = $commission->source === 'deposit'
                                    ? $commission->depositRequest?->amount
                                    : $commission->userPlan?->invested_amount;
                            @endphp
                            <tr class="border-b border-[#F1F5F9] last:border-0 hover:bg-[#F8FAFC] transition-colors">
                                <td class="px-4 py-3 align-middle text-[13px] text-[#0F172A]">
                                    <span class="font-semibold">{{ $commission->referrer?->name ?: '—' }}</span>
                                    <span class="block text-[11.5px] font-mono text-[#94A3B8]">{{ $commission->referrer?->phone }}</span>
                                </td>
                                <td class="px-4 py-3 align-middle text-[13px] text-[#0F172A]">
                                    <span class="font-semibold">{{ $commission->referredUser?->name ?: '—' }}</span>
                                    <span class="block text-[11.5px] font-mono text-[#94A3B8]">{{ $commission->referredUser?->phone }}</span>
                                </td>
                                <td class="px-4 py-3 align-middle text-[12px] text-[#64748B] whitespace-nowrap">{{ $commission->created_at->format('d M Y, h:i A') }}</td>
                                <td class="px-4 py-3 align-middle text-[12.5px] text-[#334155] whitespace-nowrap">{{ $sourceLabel }}</td>
                                <td class="px-4 py-3 align-middle text-[13px] text-[#334155] whitespace-nowrap">{{ $qualifyingAmount !== null ? '₹'.number_format($qualifyingAmount, 2) : '—' }}</td>
                                <td class="px-4 py-3 align-middle text-[13.5px] font-bold text-[#0F172A] whitespace-nowrap">
                                    ₹{{ number_format($commission->amount, 2) }}
                                    <span class="block text-[11px] font-normal text-[#94A3B8]">{{ rtrim(rtrim(number_format($commission->commission_percent, 2), '0'), '.') }}%</span>
                                </td>
                                <td class="px-4 py-3 align-middle whitespace-nowrap">
                                    <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border {{ $pillClasses }}">{{ $commission->status }}</span>
                                </td>
                                <td class="px-4 py-3 align-middle text-right whitespace-nowrap">
                                    @if ($commission->status === 'pending')
                                        <div class="inline-flex gap-2 justify-end">
                                            <form method="POST" action="{{ route('admin.referral-commissions.approve', $commission) }}"
                                                onsubmit="return confirm('Approve this ₹{{ number_format($commission->amount, 2) }} commission for {{ $commission->referrer?->phone }}? This will credit the wallet immediately.');">
                                                @csrf
                                                <button type="submit" class="h-9 px-3.5 rounded-lg bg-emerald-600 text-white text-[12.5px] font-bold hover:bg-emerald-700 transition-colors active:scale-95">Approve</button>
                                            </form>
                                            <button type="button" data-reason-modal data-reason-action="adjust"
                                                data-action="{{ route('admin.referral-commissions.adjust', $commission) }}"
                                                data-name="{{ $commission->referrer?->name ?: $commission->referrer?->phone }}"
                                                data-amount="{{ $commission->amount }}"
                                                class="h-9 px-3.5 rounded-lg border border-[#CBD5E1] text-[#334155] text-[12.5px] font-bold hover:bg-[#F8FAFC] transition-colors active:scale-95">Adjust</button>
                                            <button type="button" data-reason-modal data-reason-action="reject"
                                                data-action="{{ route('admin.referral-commissions.reject', $commission) }}"
                                                data-name="{{ $commission->referrer?->name ?: $commission->referrer?->phone }}"
                                                class="h-9 px-3.5 rounded-lg border border-red-200 text-red-600 text-[12.5px] font-bold hover:bg-red-50 transition-colors active:scale-95">Reject</button>
                                        </div>
                                    @elseif ($commission->status === 'paid')
                                        <button type="button" data-reason-modal data-reason-action="reverse"
                                            data-action="{{ route('admin.referral-commissions.reverse', $commission) }}"
                                            data-name="{{ $commission->referrer?->name ?: $commission->referrer?->phone }}"
                                            class="h-9 px-3.5 rounded-lg border border-red-200 text-red-600 text-[12.5px] font-bold hover:bg-red-50 transition-colors active:scale-95">Reverse</button>
                                    @elseif ($commission->reason)
                                        <span class="text-[11.5px] text-[#64748B] italic">{{ $commission->reason }}</span>
                                    @else
                                        <span class="text-[12px] text-[#CBD5E1]">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-[13.5px] text-[#94A3B8] italic">No {{ $status }} referral commissions.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        </div>
    </main>
</div>

{{-- One shared reason modal for Reject/Adjust/Reverse - only the form
     action/title/amount-field-visibility change per action, same "single
     modal driven by data-* attributes on whichever button was clicked"
     pattern as the Ban-user modal in users.blade.php. Approve has no modal
     (asymmetric: it needs no reason, matches deposits' plain-confirm Approve). --}}
<div id="reason-modal" class="hidden fixed inset-0 z-[600] items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" data-reason-close></div>
    <div class="relative w-full max-w-md bg-white rounded-2xl border border-[#E5E9EB] shadow-xl p-6">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div>
                <h2 id="reason-modal-title" class="font-poppins font-bold text-[16px] text-[#0F172A]">Reject commission</h2>
                <p class="text-[12.5px] text-[#64748B] mt-0.5">
                    <span id="reason-modal-name" class="font-semibold text-[#334155]">—</span>
                </p>
            </div>
            <button type="button" data-reason-close class="w-9 h-9 -mr-1 -mt-1 shrink-0 rounded-lg flex items-center justify-center text-[#64748B] hover:bg-[#F1F5F9] transition-colors" aria-label="Close">
                <i class="fa-solid fa-xmark text-[15px]"></i>
            </button>
        </div>

        <form id="reason-modal-form" method="POST" action="" class="flex flex-col gap-4">
            @csrf
            <div id="reason-modal-amount-field" class="hidden">
                <label for="reason-modal-amount" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">New amount (₹)</label>
                <input type="number" id="reason-modal-amount" name="amount" min="0.01" step="0.01"
                    class="w-full rounded-lg border border-[#CBD5E1] px-3 py-2 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
            </div>
            <div>
                <label for="reason-modal-reason" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Reason (required)</label>
                <textarea id="reason-modal-reason" name="reason" maxlength="255" required rows="3" placeholder="e.g. Duplicate account, invalid transaction"
                    class="w-full rounded-lg border border-[#CBD5E1] px-3 py-2 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15"></textarea>
                <p class="text-[11.5px] text-[#94A3B8] mt-1.5">Recorded in the audit log.</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" data-reason-close class="h-10 px-4 rounded-lg border border-slate-200 text-slate-600 font-semibold text-[13.5px] hover:bg-slate-50 transition-colors">Cancel</button>
                <button id="reason-modal-submit" type="submit" class="h-10 px-5 rounded-lg bg-red-600 text-white font-semibold text-[13.5px] hover:bg-red-700 transition-colors active:scale-[0.99]">Reject</button>
            </div>
        </form>
    </div>
</div>

@if ($commissions->isNotEmpty())
    <script src="{{ asset('libs/simple-datatables/simple-datatables.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof simpleDatatables === 'undefined') return;

            new simpleDatatables.DataTable('#commissions-table', {
                searchable: true,
                paging: true,
                perPage: 15,
                perPageSelect: [15, 25, 50, 100],
                sortable: true,
                columns: [{ select: 7, sortable: false }],
                labels: {
                    placeholder: 'Search commissions...',
                    perPage: '{select} per page',
                    noRows: 'No referral commissions found',
                    noResults: 'No commissions match your search',
                    info: 'Showing {start}–{end} of {rows} commissions',
                },
            });
        });
    </script>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('reason-modal');
        if (!modal) return;
        var titleEl = document.getElementById('reason-modal-title');
        var nameEl = document.getElementById('reason-modal-name');
        var form = document.getElementById('reason-modal-form');
        var reasonInput = document.getElementById('reason-modal-reason');
        var amountField = document.getElementById('reason-modal-amount-field');
        var amountInput = document.getElementById('reason-modal-amount');
        var submitBtn = document.getElementById('reason-modal-submit');

        var copy = {
            reject: { title: 'Reject commission', submit: 'Reject', submitClass: 'bg-red-600 hover:bg-red-700' },
            adjust: { title: 'Adjust commission', submit: 'Save adjustment', submitClass: 'bg-brand hover:bg-brand-light' },
            reverse: { title: 'Reverse commission', submit: 'Reverse', submitClass: 'bg-red-600 hover:bg-red-700' },
        };

        function openModal(btn) {
            var action = btn.getAttribute('data-reason-action') || 'reject';
            var c = copy[action] || copy.reject;

            form.action = btn.getAttribute('data-action') || '';
            titleEl.textContent = c.title;
            nameEl.textContent = btn.getAttribute('data-name') || '—';
            reasonInput.value = '';
            submitBtn.textContent = c.submit;
            submitBtn.className = 'h-10 px-5 rounded-lg text-white font-semibold text-[13.5px] transition-colors active:scale-[0.99] ' + c.submitClass;

            if (action === 'adjust') {
                amountField.classList.remove('hidden');
                amountInput.value = btn.getAttribute('data-amount') || '';
                amountInput.required = true;
            } else {
                amountField.classList.add('hidden');
                amountInput.required = false;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            setTimeout(function () { reasonInput.focus(); }, 30);
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-reason-modal]');
            if (btn) { openModal(btn); return; }
            if (e.target.closest('[data-reason-close]')) closeModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });
    });
</script>

@endsection
