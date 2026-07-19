@extends('layouts.admin')

@section('title', 'Investment plans')

@section('content')

<div class="flex min-h-screen">

    <x-admin-sidebar active="plans" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Investment plans" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <div class="flex items-start justify-between gap-3 mb-6">
            <div>
                <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Investment plans</h1>
                <p class="text-[13.5px] text-[#64748B]">The real catalog shown on Explore/Home - editing here changes what every user sees. Existing holders keep whatever amount/rate they bought at; only new purchases use the updated numbers.</p>
            </div>
            <a href="{{ route('admin.plans.create') }}" class="h-10 px-4 rounded-lg bg-brand text-white font-semibold text-[13.5px] hover:bg-brand-light transition-colors active:scale-[0.99] flex items-center gap-2 shrink-0">
                <i class="fa-solid fa-plus text-[12px]"></i> Add plan
            </a>
        </div>

        <div class="flex flex-col gap-3 w-full">
            @forelse ($plans as $plan)
                <div class="bg-white rounded-xl border border-[#E5E9EB] p-4 flex items-center gap-4 flex-wrap {{ $plan->is_active ? '' : 'opacity-60' }}">
                    <img src="{{ $plan->imageUrl() }}" alt="{{ $plan->title }}" class="w-11 h-11 rounded-lg object-cover shrink-0 border border-[#E5E9EB]">
                    <div class="w-9 h-9 rounded-full bg-[#0A5C66]/10 flex items-center justify-center shrink-0">
                        <i class="bi {{ $plan->icon }} text-[14px] text-[#0A5C66]"></i>
                    </div>
                    <div class="flex flex-col gap-0.5 min-w-[200px] flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-[14px] font-bold text-[#0F172A]">{{ $plan->title }}</span>
                            <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border {{ $plan->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200' }}">
                                {{ $plan->is_active ? 'Active' : 'Disabled' }}
                            </span>
                            <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border bg-amber-50 text-amber-700 border-amber-200">{{ $plan->badge }}</span>
                            @if ($plan->plan_type)
                                <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border bg-indigo-50 text-indigo-700 border-indigo-200">{{ $plan->plan_type === 'trust_builder' ? 'Trust Builder' : 'Growth' }}</span>
                            @endif
                            @if ($plan->unlock_enabled)
                                <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border bg-sky-50 text-sky-700 border-sky-200">
                                    <i class="bi bi-lock-fill"></i> Requires {{ optional($plan->requiresPlan)->title ?? '—' }}
                                </span>
                            @endif
                            @if ($plan->durations->isNotEmpty())
                                <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border bg-teal-50 text-teal-700 border-teal-200">{{ $plan->durations->count() }} durations</span>
                            @endif
                        </div>
                        <span class="text-[12px] text-[#64748B]">{{ $plan->subtitle }}</span>
                        <span class="text-[11.5px] font-mono text-[#334155]">₹{{ number_format($plan->investment_amount, 2) }} · {{ $plan->growth_rate }}%/yr · {{ $plan->lock_duration }} · +₹{{ number_format($plan->daily_profit, 2) }}/day</span>
                    </div>

                    <div class="flex gap-2 shrink-0">
                        <a href="{{ route('admin.plans.edit', $plan) }}" class="h-9 px-3.5 rounded-lg border border-slate-200 text-slate-600 text-[12.5px] font-bold hover:bg-slate-50 transition-colors active:scale-95 flex items-center">Edit</a>
                        <form method="POST" action="{{ route('admin.plans.toggle-active', $plan) }}">
                            @csrf
                            <button type="submit" class="h-9 px-3.5 rounded-lg border text-[12.5px] font-bold transition-colors active:scale-95 {{ $plan->is_active ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }}">
                                {{ $plan->is_active ? 'Disable' : 'Enable' }}
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-[13.5px] text-[#94A3B8] italic">No plans yet.</p>
            @endforelse
        </div>

        </div>
    </main>
</div>

@endsection
