@extends('layouts.admin')

@section('title', 'Transactions')

@section('content')

<link rel="stylesheet" href="{{ asset('libs/simple-datatables/style.css') }}">
<style>
    #transactions-table-card .datatable-top { padding: 0 0 14px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    #transactions-table-card .datatable-bottom { padding: 14px 0 0; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    #transactions-table-card .datatable-search input { height: 38px; border: 1px solid #E5E9EB; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #0F172A; outline: none; min-width: 220px; }
    #transactions-table-card .datatable-search input:focus { border-color: #0A5C66; box-shadow: 0 0 0 3px rgba(10,92,102,.12); }
    #transactions-table-card .datatable-selector { height: 38px; border: 1px solid #E5E9EB; border-radius: 8px; padding: 0 8px; font-size: 13px; color: #334155; }
    #transactions-table-card .datatable-info { font-size: 12.5px; color: #64748B; }
    #transactions-table-card .datatable-container { overflow-x: auto; border: 0; }
    #transactions-table-card table.datatable-table { min-width: 860px; }
    #transactions-table-card .datatable-pagination a { border-radius: 8px; padding: 6px 11px; font-size: 12.5px; font-weight: 600; color: #334155; }
    #transactions-table-card .datatable-pagination a:hover { background: #F1F5F9; }
    #transactions-table-card .datatable-pagination .datatable-active a { background: #0A5C66; color: #fff; }
</style>

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="transactions" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Transactions" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Transactions</h1>
        <p class="text-[13.5px] text-[#64748B] mb-6">Unified wallet ledger — every completed money movement (Add Money, Plan Purchase, Profit Credit, Referral, Cashback, Withdrawal, Manual adjustments). Pending requests live on the Deposit / Withdrawal pages.</p>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-6">
            <div class="bg-white rounded-xl border border-[#E5E9EB] p-4">
                <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-1">Total credited</p>
                <p class="text-[19px] font-black text-emerald-600 font-poppins">₹{{ number_format($totalCredit, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-[#E5E9EB] p-4">
                <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-1">Total debited</p>
                <p class="text-[19px] font-black text-red-600 font-poppins">₹{{ number_format($totalDebit, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-[#E5E9EB] p-4 col-span-2 sm:col-span-1">
                <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-1">Net wallet flow</p>
                <p class="text-[19px] font-black text-[#0F172A] font-poppins">₹{{ number_format($totalCredit - $totalDebit, 2) }}</p>
            </div>
        </div>

        <div class="flex gap-1.5 mb-4 overflow-x-auto hide-scrollbar bg-[#F1F5F9] rounded-lg p-1 w-fit max-w-full">
            <a href="{{ route('admin.transactions') }}"
                class="h-8 px-4 rounded-md text-[12.5px] transition-colors flex items-center whitespace-nowrap {{ $type === 'all' ? 'font-bold bg-white text-[#0F172A] shadow-sm' : 'font-semibold text-[#64748B]' }}">All</a>
            @foreach ($typeLabels as $key => $label)
                <a href="{{ route('admin.transactions', ['type' => $key]) }}"
                    class="h-8 px-4 rounded-md text-[12.5px] transition-colors flex items-center whitespace-nowrap {{ $type === $key ? 'font-bold bg-white text-[#0F172A] shadow-sm' : 'font-semibold text-[#64748B]' }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="bg-white rounded-xl border border-[#E5E9EB] p-4" id="transactions-table-card">
            <div class="overflow-x-auto">
                <table id="transactions-table" class="w-full text-left border-collapse min-w-[860px]">
                    <thead>
                        <tr class="bg-[#F8FAFC] border-b border-[#E5E9EB]">
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Txn ID</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">User</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Type</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Amount</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Balance after</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Status</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $txn)
                            @php $isCredit = $txn->direction === 'credit'; @endphp
                            <tr class="border-b border-[#F1F5F9] last:border-0 hover:bg-[#F8FAFC] transition-colors">
                                <td class="px-4 py-3 align-middle text-[12px] font-mono text-[#64748B] whitespace-nowrap">TXN-{{ str_pad($txn->id, 6, '0', STR_PAD_LEFT) }}</td>
                                <td class="px-4 py-3 align-middle whitespace-nowrap">
                                    <span class="block text-[13px] font-semibold text-[#0F172A]">{{ $names[$txn->phone] ?? '—' }}</span>
                                    <span class="block text-[11.5px] font-mono text-[#94A3B8]">{{ $txn->phone }}</span>
                                </td>
                                <td class="px-4 py-3 align-middle text-[12.5px] font-semibold text-[#334155] whitespace-nowrap">{{ $txn->typeLabel() }}</td>
                                <td class="px-4 py-3 align-middle text-right text-[13.5px] font-bold whitespace-nowrap {{ $isCredit ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $isCredit ? '+' : '−' }}₹{{ number_format($txn->amount, 2) }}
                                </td>
                                <td class="px-4 py-3 align-middle text-right text-[13px] text-[#334155] whitespace-nowrap">{{ $txn->balance_after !== null ? '₹'.number_format($txn->balance_after, 2) : '—' }}</td>
                                <td class="px-4 py-3 align-middle whitespace-nowrap">
                                    <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border bg-emerald-50 text-emerald-700 border-emerald-200">{{ $txn->status }}</span>
                                </td>
                                <td class="px-4 py-3 align-middle text-[12px] text-[#64748B] whitespace-nowrap">{{ $txn->created_at->format('d M Y, h:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-[13.5px] text-[#94A3B8] italic">No transactions{{ $type !== 'all' ? ' of this type' : '' }} yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        </div>
    </main>
</div>

@if ($transactions->isNotEmpty())
    <script src="{{ asset('libs/simple-datatables/simple-datatables.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof simpleDatatables === 'undefined') return;
            new simpleDatatables.DataTable('#transactions-table', {
                searchable: true,
                paging: true,
                perPage: 20,
                perPageSelect: [20, 50, 100],
                sortable: true,
                labels: {
                    placeholder: 'Search transactions...',
                    perPage: '{select} per page',
                    noRows: 'No transactions found',
                    noResults: 'No transactions match your search',
                    info: 'Showing {start}–{end} of {rows} transactions',
                },
            });
        });
    </script>
@endif

@endsection
