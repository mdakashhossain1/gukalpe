@extends('layouts.admin')

@section('title', 'Plan analytics')

@section('content')

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="plan-analytics" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Plan analytics" />

        <div class="px-6 md:px-10 py-8 md:py-10">

            <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Plan analytics</h1>
            <p class="text-[13.5px] text-[#64748B] mb-6">Per-plan performance — detail-page views, purchases, conversion, and money mobilised. View tracking begins from this release onward.</p>

            <div class="grid grid-cols-3 gap-3 mb-6">
                <div class="bg-white rounded-xl border border-[#E5E9EB] p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-1">Total views</p>
                    <p class="text-[19px] font-black text-[#0F172A] font-poppins">{{ number_format($totals['views']) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-[#E5E9EB] p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-1">Total purchases</p>
                    <p class="text-[19px] font-black text-[#0F172A] font-poppins">{{ number_format($totals['purchases']) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-[#E5E9EB] p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-1">Total invested</p>
                    <p class="text-[19px] font-black text-emerald-600 font-poppins">₹{{ number_format($totals['invested'], 2) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-[#E5E9EB] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[760px]">
                        <thead>
                            <tr class="bg-[#F8FAFC] border-b border-[#E5E9EB]">
                                <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Plan</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Views</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Purchases</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Conversion</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Running</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Completed</th>
                                <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Total invested</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $r)
                                <tr class="border-b border-[#F1F5F9] last:border-0 hover:bg-[#F8FAFC] transition-colors">
                                    <td class="px-5 py-3.5 text-[13.5px] font-semibold text-[#0F172A]">{{ $r['title'] }}</td>
                                    <td class="px-4 py-3.5 text-[13px] text-[#334155] text-right">{{ number_format($r['views']) }}</td>
                                    <td class="px-4 py-3.5 text-[13px] text-[#334155] text-right">{{ number_format($r['purchases']) }}</td>
                                    <td class="px-4 py-3.5 text-[13px] text-right font-bold {{ $r['conversion'] >= 5 ? 'text-emerald-600' : 'text-[#334155]' }}">{{ number_format($r['conversion'], 1) }}%</td>
                                    <td class="px-4 py-3.5 text-[13px] text-[#334155] text-right">{{ number_format($r['running']) }}</td>
                                    <td class="px-4 py-3.5 text-[13px] text-[#334155] text-right">{{ number_format($r['completed']) }}</td>
                                    <td class="px-5 py-3.5 text-[13px] font-bold text-[#0F172A] text-right">₹{{ number_format($r['invested'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-5 py-8 text-center text-[13.5px] text-[#94A3B8] italic">No plans yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

@endsection
