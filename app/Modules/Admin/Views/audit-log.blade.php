@extends('layouts.admin')

@section('title', 'Audit log')

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
</style>

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="audit-log" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Audit log" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Audit log</h1>
        <p class="text-[13.5px] text-[#64748B] mb-6">Permanent, database-backed record of every money- or state-changing admin action: who did it, what, why, and when. This is separate from the localStorage-only "Activity logs" page, which is a referral/commission simulation debug console.</p>

        <div class="flex gap-1.5 mb-4 bg-[#F1F5F9] rounded-lg p-1 w-fit flex-wrap">
            <a href="{{ route('admin.audit-log') }}"
                class="h-8 px-4 rounded-md text-[12.5px] transition-colors flex items-center {{ $action === 'all' ? 'font-bold bg-white text-[#0F172A] shadow-sm' : 'font-semibold text-[#64748B]' }}">
                All
            </a>
            @foreach ($actions as $a)
                <a href="{{ route('admin.audit-log', ['action' => $a]) }}"
                    class="h-8 px-4 rounded-md text-[12.5px] transition-colors flex items-center {{ $action === $a ? 'font-bold bg-white text-[#0F172A] shadow-sm' : 'font-semibold text-[#64748B]' }}">
                    {{ ucfirst(str_replace('_', ' ', $a)) }}
                </a>
            @endforeach
        </div>

        <div class="bg-white rounded-xl border border-[#E5E9EB] p-4" id="audit-table-card">
            <div class="overflow-x-auto">
                <table id="audit-table" class="w-full text-left border-collapse min-w-[980px]">
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
                                    {{ $entry->target_type ? $entry->target_type.' #'.$entry->target_id : '—' }}
                                </td>
                                <td class="px-4 py-3 align-middle text-[12.5px] text-[#334155] max-w-[220px] truncate" title="{{ $entry->reason }}">{{ $entry->reason ?: '—' }}</td>
                                <td class="px-4 py-3 align-middle text-[11.5px] font-mono text-[#94A3B8] max-w-[280px] truncate" title="{{ $entry->meta ? json_encode($entry->meta) : '' }}">
                                    {{ $entry->meta ? json_encode($entry->meta) : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-[13.5px] text-[#94A3B8] italic">No audit entries yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
                labels: {
                    placeholder: 'Search audit log...',
                    perPage: '{select} per page',
                    noRows: 'No audit entries found',
                    noResults: 'No entries match your search',
                    info: 'Showing {start}–{end} of {rows} entries',
                },
            });
        });
    </script>
@endif

@endsection
