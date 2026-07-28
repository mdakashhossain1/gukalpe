@extends('layouts.admin')

@section('title', 'Deposit requests')

@section('content')

{{-- Self-hosted vanilla datatable (no jQuery) - same library the plans/users
     lists use. Files in public/libs/simple-datatables/. Search + pagination
     + sorting on the deposit-requests table. --}}
<link rel="stylesheet" href="{{ asset('libs/simple-datatables/style.css') }}">
<style>
    #deposits-table-card .datatable-top { padding: 0 0 14px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    #deposits-table-card .datatable-bottom { padding: 14px 0 0; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    #deposits-table-card .datatable-search input { height: 38px; border: 1px solid #E5E9EB; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #0F172A; outline: none; min-width: 220px; }
    #deposits-table-card .datatable-search input:focus { border-color: #0A5C66; box-shadow: 0 0 0 3px rgba(10,92,102,.12); }
    #deposits-table-card .datatable-selector { height: 38px; border: 1px solid #E5E9EB; border-radius: 8px; padding: 0 8px; font-size: 13px; color: #334155; }
    #deposits-table-card .datatable-info { font-size: 12.5px; color: #64748B; }
    #deposits-table-card .datatable-container { overflow-x: auto; border: 0; }
    #deposits-table-card table.datatable-table { min-width: 900px; }
    #deposits-table-card .datatable-pagination a { border-radius: 8px; padding: 6px 11px; font-size: 12.5px; font-weight: 600; color: #334155; }
    #deposits-table-card .datatable-pagination a:hover { background: #F1F5F9; }
    #deposits-table-card .datatable-pagination .datatable-active a { background: #0A5C66; color: #fff; }
</style>

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="deposits" :pending-deposit-count="$pendingCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Deposit requests" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Deposit requests</h1>
        <p class="text-[13.5px] text-[#64748B] mb-6">Manual UPI "Add Money" submissions. Approving credits the user's wallet immediately; rejecting releases the UTR so it can be resubmitted.</p>

        <div class="flex gap-1.5 mb-4 bg-[#F1F5F9] rounded-lg p-1 w-fit">
            @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
                <a href="{{ route('admin.deposits', ['status' => $key]) }}"
                    class="h-8 px-4 rounded-md text-[12.5px] transition-colors flex items-center {{ $status === $key ? 'font-bold bg-white text-[#0F172A] shadow-sm' : 'font-semibold text-[#64748B]' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="bg-white rounded-xl border border-[#E5E9EB] p-4" id="deposits-table-card">
            <div class="overflow-x-auto">
                <table id="deposits-table" class="w-full text-left border-collapse min-w-[900px]">
                    <thead>
                        <tr class="bg-[#F8FAFC] border-b border-[#E5E9EB]">
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Amount</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Phone</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Method</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">UTR</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Status</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Submitted</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($deposits as $deposit)
                            @php
                                $pillClasses = match ($deposit->status) {
                                    'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'rejected' => 'bg-red-50 text-red-700 border-red-200',
                                    default => 'bg-amber-50 text-amber-700 border-amber-200',
                                };
                            @endphp
                            <tr class="border-b border-[#F1F5F9] last:border-0 hover:bg-[#F8FAFC] transition-colors">
                                <td class="px-4 py-3 align-middle text-[13.5px] font-bold text-[#0F172A] whitespace-nowrap">₹{{ number_format($deposit->amount, 2) }}</td>
                                <td class="px-4 py-3 align-middle text-[13px] font-mono text-[#334155] whitespace-nowrap">{{ $deposit->phone }}</td>
                                <td class="px-4 py-3 align-middle text-[13px] text-[#334155] whitespace-nowrap">{{ $deposit->method_label }}</td>
                                <td class="px-4 py-3 align-middle text-[12.5px] font-mono text-[#334155]">{{ $deposit->utr }}</td>
                                <td class="px-4 py-3 align-middle whitespace-nowrap">
                                    <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border {{ $pillClasses }}">{{ $deposit->status }}</span>
                                </td>
                                <td class="px-4 py-3 align-middle text-[12px] text-[#64748B] whitespace-nowrap">{{ $deposit->submitted_at->format('d M Y, h:i A') }}</td>
                                <td class="px-4 py-3 align-middle text-right whitespace-nowrap">
                                    @if ($deposit->status === 'pending')
                                        <div class="inline-flex gap-2 justify-end">
                                            <form method="POST" action="{{ route('admin.deposits.approve', $deposit) }}">
                                                @csrf
                                                <button type="submit" class="h-9 px-3.5 rounded-lg bg-emerald-600 text-white text-[12.5px] font-bold hover:bg-emerald-700 transition-colors active:scale-95">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.deposits.reject', $deposit) }}">
                                                @csrf
                                                <button type="submit" class="h-9 px-3.5 rounded-lg border border-red-200 text-red-600 text-[12.5px] font-bold hover:bg-red-50 transition-colors active:scale-95">Reject</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-[12px] text-[#CBD5E1]">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-[13.5px] text-[#94A3B8] italic">No {{ $status }} deposit requests.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        </div>
    </main>
</div>

@if ($deposits->isNotEmpty())
    <script src="{{ asset('libs/simple-datatables/simple-datatables.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof simpleDatatables === 'undefined') return;

            new simpleDatatables.DataTable('#deposits-table', {
                searchable: true,
                paging: true,
                perPage: 15,
                perPageSelect: [15, 25, 50, 100],
                sortable: true,
                columns: [{ select: 6, sortable: false }],
                labels: {
                    placeholder: 'Search deposits...',
                    perPage: '{select} per page',
                    noRows: 'No deposit requests found',
                    noResults: 'No deposits match your search',
                    info: 'Showing {start}–{end} of {rows} requests',
                },
            });
        });
    </script>
@endif

@endsection
