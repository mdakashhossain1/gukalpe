@extends('layouts.app')

@section('content')
    <div class="flex min-h-[100dvh] flex-col flex-1 bg-[#F8FAFC] pb-28 pt-safe overflow-y-auto custom-scrollbar w-full animate-fade-in-up">

        <div class="w-full px-5 py-3.5 bg-white/80 backdrop-blur-md sticky top-0 z-50 border-b border-slate-200/40 flex items-center gap-3">
            <a href="{{ route('explore') }}" class="w-10 h-10 shrink-0 rounded-full bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-[#1a153a] border border-slate-200/50 active:scale-95 transition-all">
                <i class="bi bi-chevron-left text-[16px]"></i>
            </a>
            <h1 class="font-extrabold text-[#0A5C66] text-[17px] tracking-tight font-poppins">Compare Plans</h1>
        </div>

        <div class="p-4">
            @if ($plans->count() < 2)
                <div class="flex flex-col items-center justify-center py-16 px-4 text-center bg-white/60 rounded-[24px] border border-slate-200/40">
                    <i class="bi bi-columns-gap text-[32px] text-[#0A5C66]/40 mb-3"></i>
                    <h4 class="text-[15px] font-black text-slate-800 font-poppins mb-1">Select at least 2 plans</h4>
                    <p class="text-[12.5px] text-slate-500 font-medium mb-5">Go back to Explore, check 2-4 plans, and tap Compare.</p>
                    <a href="{{ route('explore') }}" class="px-5 py-2.5 bg-[#0A5C66] text-white font-extrabold text-[12.5px] rounded-xl hover:bg-[#148e9e] active:scale-95 transition-all">Back to Explore</a>
                </div>
            @else
                <div class="overflow-x-auto -mx-4 px-4">
                    <table class="w-full min-w-[560px] border-separate border-spacing-0">
                        <thead>
                            <tr>
                                <th class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider pb-3 w-[120px]"></th>
                                @foreach ($plans as $plan)
                                    <th class="text-left pb-3 px-2 align-top">
                                        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3">
                                            <img src="{{ $plan->imageUrl() }}" class="w-full h-20 object-cover rounded-xl mb-2" alt="{{ $plan->title }}">
                                            <h3 class="text-[13px] font-black text-[#0A5C66] font-poppins leading-tight">{{ $plan->title }}</h3>
                                            <p class="text-[10px] text-slate-400 font-medium mt-0.5">{{ $plan->subtitle }}</p>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="text-[12.5px]">
                            @php
                                $rows = [
                                    'Investment' => fn ($p) => '₹'.number_format((float) $p->investment_amount, 2),
                                    'Duration' => fn ($p) => $p->durations->isNotEmpty() ? $p->durations->pluck('label')->implode(' / ') : $p->lock_duration,
                                    'Growth Rate' => fn ($p) => $p->growth_rate.'% yearly',
                                    'Total Return' => fn ($p) => '₹'.number_format((float) $p->total_return, 2),
                                    'Daily Profit' => fn ($p) => '₹'.number_format((float) $p->daily_profit, 2),
                                    'Risk Level' => fn ($p) => $p->risk_level ?? '—',
                                    'Investors' => fn ($p) => number_format($p->investorCount()),
                                    'Category' => fn ($p) => $p->badge,
                                ];
                            @endphp
                            @foreach ($rows as $label => $resolver)
                                <tr>
                                    <td class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider py-3 pr-2 border-t border-slate-100">{{ $label }}</td>
                                    @foreach ($plans as $plan)
                                        <td class="py-3 px-2 border-t border-slate-100 font-bold text-[#0A5C66] font-poppins">{{ $resolver($plan) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            <tr>
                                <td class="py-3"></td>
                                @foreach ($plans as $plan)
                                    <td class="py-3 px-2">
                                        <a href="{{ route('plan-details', $plan) }}" class="inline-flex items-center gap-1 bg-[#0A5C66] text-white font-extrabold text-[11px] px-3.5 py-2 rounded-lg hover:bg-[#148e9e] active:scale-95 transition-all whitespace-nowrap">
                                            View Plan <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
