@extends('layouts.admin')

@section('title', 'Reports')

@section('content')

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="reports" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Reports" />

        <div class="px-6 md:px-10 py-8 md:py-10">

            <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Reports</h1>
            <p class="text-[13.5px] text-[#64748B] mb-6">Aggregated activity for the selected period. Export as CSV or Excel, or open a print-ready view to save as PDF.</p>

            <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                <div class="flex gap-1.5 bg-[#F1F5F9] rounded-lg p-1 w-fit">
                    @foreach ($periods as $key => $label)
                        <a href="{{ route('admin.reports', ['period' => $key]) }}"
                            class="h-8 px-4 rounded-md text-[12.5px] transition-colors flex items-center {{ $period === $key ? 'font-bold bg-white text-[#0F172A] shadow-sm' : 'font-semibold text-[#64748B]' }}">{{ $label }}</a>
                    @endforeach
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.reports.export', ['period' => $period, 'format' => 'csv']) }}" class="h-9 px-3.5 rounded-lg border border-[#CBD5E1] text-[#334155] text-[12.5px] font-bold hover:bg-[#F1F5F9] transition-colors flex items-center gap-1.5"><i class="fa-solid fa-file-csv text-[12px]"></i> CSV</a>
                    <a href="{{ route('admin.reports.export', ['period' => $period, 'format' => 'excel']) }}" class="h-9 px-3.5 rounded-lg border border-[#CBD5E1] text-[#334155] text-[12.5px] font-bold hover:bg-[#F1F5F9] transition-colors flex items-center gap-1.5"><i class="fa-solid fa-file-excel text-[12px]"></i> Excel</a>
                    <a href="{{ route('admin.reports.print', ['period' => $period]) }}" target="_blank" class="h-9 px-3.5 rounded-lg bg-[#0F172A] text-white text-[12.5px] font-bold hover:bg-[#1E293B] transition-colors flex items-center gap-1.5"><i class="fa-solid fa-file-pdf text-[12px]"></i> PDF</a>
                </div>
            </div>

            <p class="text-[12.5px] text-[#94A3B8] mb-4 font-semibold">{{ $label }} report · {{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</p>

            <div class="bg-white rounded-xl border border-[#E5E9EB] overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#F8FAFC] border-b border-[#E5E9EB]">
                            <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Metric</th>
                            <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Count</th>
                            <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($metrics as $m)
                            <tr class="border-b border-[#F1F5F9] last:border-0">
                                <td class="px-5 py-3.5 text-[13.5px] font-semibold text-[#0F172A]">{{ $m['label'] }}</td>
                                <td class="px-5 py-3.5 text-[13.5px] text-[#334155] text-right">{{ $m['count'] !== null ? number_format($m['count']) : '—' }}</td>
                                <td class="px-5 py-3.5 text-[13.5px] font-bold text-[#0F172A] text-right">{{ $m['amount'] !== null ? '₹'.number_format($m['amount'], 2) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>

@endsection
