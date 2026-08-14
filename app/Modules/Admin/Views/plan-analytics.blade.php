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

            {{-- Custom date range (client item 11). Scopes purchases by
                 purchased_at and profit/maturity by wallet_transactions.created_at;
                 views/conversion stay lifetime (there's no per-day view log). --}}
            <form method="GET" action="{{ route('admin.plan-analytics') }}" class="flex flex-wrap items-end gap-3 mb-6 bg-white rounded-xl border border-[#E5E9EB] p-4">
                <div>
                    <label for="analytics-from" class="block text-[11.5px] font-semibold text-[#334155] mb-1.5">From</label>
                    <input type="date" name="from" id="analytics-from" value="{{ $from }}"
                        class="h-10 rounded-lg border border-[#CBD5E1] px-3 text-[13.5px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                </div>
                <div>
                    <label for="analytics-to" class="block text-[11.5px] font-semibold text-[#334155] mb-1.5">To</label>
                    <input type="date" name="to" id="analytics-to" value="{{ $to }}"
                        class="h-10 rounded-lg border border-[#CBD5E1] px-3 text-[13.5px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                </div>
                <button type="submit" class="h-10 px-4 rounded-lg bg-[#0F172A] text-white font-semibold text-[12.5px] hover:bg-[#1E293B] transition-colors active:scale-[0.99]">Apply</button>
                @if ($from || $to)
                    <a href="{{ route('admin.plan-analytics') }}" class="h-10 px-4 rounded-lg border border-[#E5E9EB] text-[#64748B] font-semibold text-[12.5px] hover:bg-[#F8FAFC] transition-colors flex items-center">Clear</a>
                @endif
            </form>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
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
                <div class="bg-white rounded-xl border border-[#E5E9EB] p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-1">Average investment</p>
                    <p class="text-[19px] font-black text-[#0F172A] font-poppins">₹{{ number_format($totals['average_investment'], 2) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                <div class="bg-white rounded-xl border border-[#E5E9EB] p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-1">Active investors</p>
                    <p class="text-[19px] font-black text-[#0A5C66] font-poppins">{{ number_format($totals['active_investors']) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-[#E5E9EB] p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-1">Completed investors</p>
                    <p class="text-[19px] font-black text-[#334155] font-poppins">{{ number_format($totals['completed_investors']) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-[#E5E9EB] p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-1">Total profit</p>
                    <p class="text-[19px] font-black text-emerald-600 font-poppins">₹{{ number_format($totals['profit'], 2) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-[#E5E9EB] p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-1">Total maturity</p>
                    <p class="text-[19px] font-black text-[#0F172A] font-poppins">₹{{ number_format($totals['maturity'], 2) }}</p>
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
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Active Investors</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Completed Investors</th>
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
