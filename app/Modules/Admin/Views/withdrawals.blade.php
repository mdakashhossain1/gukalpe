@extends('layouts.admin')

@section('title', 'Withdrawal requests')

@section('content')

{{-- Self-hosted vanilla datatable (no jQuery) - same library the plans/users
     lists use. Files in public/libs/simple-datatables/. Search + pagination
     + sorting on the withdrawal-requests table. --}}
<link rel="stylesheet" href="{{ asset('libs/simple-datatables/style.css') }}">
<style>
    #withdrawals-table-card .datatable-top { padding: 0 0 14px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    #withdrawals-table-card .datatable-bottom { padding: 14px 0 0; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    #withdrawals-table-card .datatable-search input { height: 38px; border: 1px solid #E5E9EB; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #0F172A; outline: none; min-width: 220px; }
    #withdrawals-table-card .datatable-search input:focus { border-color: #0A5C66; box-shadow: 0 0 0 3px rgba(10,92,102,.12); }
    #withdrawals-table-card .datatable-selector { height: 38px; border: 1px solid #E5E9EB; border-radius: 8px; padding: 0 8px; font-size: 13px; color: #334155; }
    #withdrawals-table-card .datatable-info { font-size: 12.5px; color: #64748B; }
    #withdrawals-table-card .datatable-container { overflow-x: auto; border: 0; }
    #withdrawals-table-card table.datatable-table { min-width: 820px; }
    #withdrawals-table-card .datatable-pagination a { border-radius: 8px; padding: 6px 11px; font-size: 12.5px; font-weight: 600; color: #334155; }
    #withdrawals-table-card .datatable-pagination a:hover { background: #F1F5F9; }
    #withdrawals-table-card .datatable-pagination .datatable-active a { background: #0A5C66; color: #fff; }
</style>

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="withdrawals" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Withdrawal requests" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Withdrawal requests</h1>
        <p class="text-[13.5px] text-[#64748B] mb-6">Manual cash-out requests (Bank / UPI / USDT). Approving debits the user's wallet immediately - pay out to the destination shown yourself, outside this system.</p>

        @if ($phone)
            <div class="mb-4 flex items-center justify-between gap-3 bg-[#0A5C66]/[0.06] border border-[#0A5C66]/20 rounded-lg px-4 py-2.5">
                <p class="text-[12.5px] font-semibold text-[#0A5C66]">Filtered to phone <span class="font-mono">{{ $phone }}</span></p>
                <a href="{{ route('admin.withdrawals', ['status' => $status]) }}" class="text-[12px] font-bold text-[#0A5C66] hover:underline">Clear filter</a>
            </div>
        @endif

        <div class="flex gap-1.5 mb-4 bg-[#F1F5F9] rounded-lg p-1 w-fit">
            @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
                <a href="{{ route('admin.withdrawals', ['status' => $key] + ($phone ? ['phone' => $phone] : [])) }}"
                    class="h-8 px-4 rounded-md text-[12.5px] transition-colors flex items-center {{ $status === $key ? 'font-bold bg-white text-[#0F172A] shadow-sm' : 'font-semibold text-[#64748B]' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="bg-white rounded-xl border border-[#E5E9EB] p-4" id="withdrawals-table-card">
            <div class="overflow-x-auto">
                <table id="withdrawals-table" class="w-full text-left border-collapse min-w-[820px]">
                    <thead>
                        <tr class="bg-[#F8FAFC] border-b border-[#E5E9EB]">
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Amount</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Phone</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Method</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Destination</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Status</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Submitted</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($withdrawals as $withdraw)
                            @php
                                $pillClasses = match ($withdraw->status) {
                                    'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'rejected' => 'bg-red-50 text-red-700 border-red-200',
                                    default => 'bg-amber-50 text-amber-700 border-amber-200',
                                };
                            @endphp
                            <tr class="border-b border-[#F1F5F9] last:border-0 hover:bg-[#F8FAFC] transition-colors">
                                <td class="px-4 py-3 align-middle text-[13.5px] font-bold text-[#0F172A] whitespace-nowrap">₹{{ number_format($withdraw->amount, 2) }}</td>
                                <td class="px-4 py-3 align-middle text-[13px] font-mono text-[#334155] whitespace-nowrap">{{ $withdraw->phone }}</td>
                                @php
                                    $methodClasses = match ($withdraw->method) {
                                        'bank' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'usdt' => 'bg-violet-50 text-violet-700 border-violet-200',
                                        default => 'bg-teal-50 text-teal-700 border-teal-200',
                                    };
                                @endphp
                                <td class="px-4 py-3 align-middle whitespace-nowrap">
                                    <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border {{ $methodClasses }}">{{ strtoupper($withdraw->method) }}</span>
                                </td>
                                <td class="px-4 py-3 align-middle text-[12.5px] font-mono text-[#334155] max-w-[220px] truncate" title="{{ $withdraw->destinationLabel() }}">{{ $withdraw->destinationLabel() }}</td>
                                <td class="px-4 py-3 align-middle whitespace-nowrap">
                                    <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border {{ $pillClasses }}">{{ $withdraw->status }}</span>
                                </td>
                                <td class="px-4 py-3 align-middle text-[12px] text-[#64748B] whitespace-nowrap">{{ $withdraw->submitted_at->format('d M Y, h:i A') }}</td>
                                <td class="px-4 py-3 align-middle text-right whitespace-nowrap">
                                    @if ($withdraw->status === 'pending')
                                        <div class="inline-flex gap-2 justify-end">
                                            <form method="POST" action="{{ route('admin.withdrawals.approve', $withdraw) }}"
                                                onsubmit="return confirm('Approve this ₹{{ number_format($withdraw->amount, 2) }} withdrawal for {{ $withdraw->phone }}? This will debit the wallet immediately - you still need to pay out {{ $withdraw->destinationLabel() }} yourself.');">
                                                @csrf
                                                <button type="submit" class="h-9 px-3.5 rounded-lg bg-emerald-600 text-white text-[12.5px] font-bold hover:bg-emerald-700 transition-colors active:scale-95">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.withdrawals.reject', $withdraw) }}" class="inline-flex items-center gap-1.5"
                                                onsubmit="return confirm('Reject this withdrawal request? This cannot be undone.');">
                                                @csrf
                                                <input type="text" name="admin_note" maxlength="500" placeholder="Reason (optional)"
                                                    class="h-9 w-40 rounded-lg border border-[#CBD5E1] px-2.5 text-[12px] text-[#0F172A] outline-none focus:border-brand focus:ring-2 focus:ring-brand/15">
                                                <button type="submit" class="h-9 px-3.5 rounded-lg border border-red-200 text-red-600 text-[12.5px] font-bold hover:bg-red-50 transition-colors active:scale-95">Reject</button>
                                            </form>
                                        </div>
                                    @elseif ($withdraw->status === 'rejected' && $withdraw->admin_note)
                                        <span class="text-[11.5px] text-[#64748B] italic">{{ $withdraw->admin_note }}</span>
                                    @else
                                        <span class="text-[12px] text-[#CBD5E1]">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-[13.5px] text-[#94A3B8] italic">No {{ $status }} withdrawal requests.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        </div>
    </main>
</div>

@if ($withdrawals->isNotEmpty())
    <script src="{{ asset('libs/simple-datatables/simple-datatables.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof simpleDatatables === 'undefined') return;

            new simpleDatatables.DataTable('#withdrawals-table', {
                searchable: true,
                paging: true,
                perPage: 15,
                perPageSelect: [15, 25, 50, 100],
                sortable: true,
                columns: [{ select: 6, sortable: false }],
                labels: {
                    placeholder: 'Search withdrawals...',
                    perPage: '{select} per page',
                    noRows: 'No withdrawal requests found',
                    noResults: 'No withdrawals match your search',
                    info: 'Showing {start}–{end} of {rows} requests',
                },
            });
        });
    </script>
@endif

@endsection
