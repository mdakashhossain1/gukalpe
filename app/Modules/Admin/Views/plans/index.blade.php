@extends('layouts.admin')

@section('title', 'Investment plans')

@section('content')

{{-- Self-hosted vanilla datatable (no jQuery). Files live in
     public/libs/simple-datatables/ - see MEMORY.md. Gives the plans table
     built-in search + pagination + column sorting. --}}
<link rel="stylesheet" href="{{ asset('libs/simple-datatables/style.css') }}">
<style>
    /* Theme simple-datatables to match the admin console */
    #plans-table-card .datatable-top { padding: 0 0 14px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    #plans-table-card .datatable-bottom { padding: 14px 0 0; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    #plans-table-card .datatable-search input {
        height: 38px; border: 1px solid #E5E9EB; border-radius: 8px; padding: 0 12px;
        font-size: 13px; color: #0F172A; outline: none; min-width: 220px;
    }
    #plans-table-card .datatable-search input:focus { border-color: #0A5C66; box-shadow: 0 0 0 3px rgba(10,92,102,.12); }
    #plans-table-card .datatable-selector { height: 38px; border: 1px solid #E5E9EB; border-radius: 8px; padding: 0 8px; font-size: 13px; color: #334155; }
    #plans-table-card .datatable-info { font-size: 12.5px; color: #64748B; }
    #plans-table-card .datatable-container { overflow-x: auto; border: 0; }
    #plans-table-card table.datatable-table { min-width: 900px; }
    #plans-table-card .datatable-pagination a { border-radius: 8px; padding: 6px 11px; font-size: 12.5px; font-weight: 600; color: #334155; }
    #plans-table-card .datatable-pagination a:hover { background: #F1F5F9; }
    #plans-table-card .datatable-pagination .datatable-active a { background: #0A5C66; color: #fff; }
    #plans-table-card .datatable-empty { text-align: center; color: #94A3B8; font-style: italic; padding: 32px 0; }
</style>

<div class="flex flex-col md:flex-row min-h-screen">

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

        <div class="bg-white rounded-xl border border-[#E5E9EB] p-4" id="plans-table-card">
            <div class="overflow-x-auto">
                <table id="plans-table" class="w-full text-left border-collapse min-w-[900px]">
                    <thead>
                        <tr class="bg-[#F8FAFC] border-b border-[#E5E9EB]">
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Plan</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Financials</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Type &amp; access</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Durations</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Status</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($plans as $plan)
                            <tr class="border-b border-[#F1F5F9] last:border-0 hover:bg-[#F8FAFC] transition-colors {{ $plan->is_active ? '' : 'opacity-60' }}">
                                <td class="px-4 py-3 align-top">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $plan->imageUrl() }}" alt="{{ $plan->title }}" class="w-11 h-11 rounded-lg object-cover shrink-0 border border-[#E5E9EB]">
                                        <div class="w-9 h-9 rounded-full bg-[#0A5C66]/10 flex items-center justify-center shrink-0 overflow-hidden">
                                            @if ($plan->iconImageUrl())
                                                <img src="{{ $plan->iconImageUrl() }}" alt="{{ $plan->title }} icon" class="w-full h-full object-contain">
                                            @else
                                                <i class="bi {{ $plan->icon ?: 'bi-piggy-bank' }} text-[14px] text-[#0A5C66]"></i>
                                            @endif
                                        </div>
                                        <div class="flex flex-col gap-0.5 min-w-[160px]">
                                            <span class="text-[13.5px] font-bold text-[#0F172A]">{{ $plan->title }}</span>
                                            <span class="text-[12px] text-[#64748B]">{{ $plan->subtitle }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex flex-col gap-0.5 text-[12px] font-mono text-[#334155] whitespace-nowrap">
                                        <span>₹{{ number_format($plan->investment_amount, 2) }}</span>
                                        <span class="text-[#64748B]">{{ $plan->growth_rate }}%/yr · {{ $plan->lock_duration }}</span>
                                        <span class="text-emerald-600">+₹{{ number_format($plan->daily_profit, 2) }}/day</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex flex-wrap items-center gap-1.5 max-w-[240px]">
                                        <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border bg-amber-50 text-amber-700 border-amber-200">{{ $plan->badge }}</span>
                                        @if ($plan->plan_type)
                                            <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border bg-indigo-50 text-indigo-700 border-indigo-200">{{ $plan->plan_type === 'trust_builder' ? 'Trust Builder' : 'Growth' }}</span>
                                        @endif
                                        @if ($plan->unlock_enabled)
                                            <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border bg-sky-50 text-sky-700 border-sky-200">
                                                <i class="bi bi-lock-fill"></i> Requires {{ optional($plan->requiresPlan)->title ?? '—' }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    @if ($plan->durations->isNotEmpty())
                                        <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border bg-teal-50 text-teal-700 border-teal-200 whitespace-nowrap">{{ $plan->durations->count() }} durations</span>
                                    @else
                                        <span class="text-[12px] text-[#94A3B8]">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border whitespace-nowrap {{ $plan->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200' }}">
                                        {{ $plan->is_active ? 'Active' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex gap-2 justify-end shrink-0">
                                        <a href="{{ route('admin.plans.edit', $plan) }}" class="h-9 px-3.5 rounded-lg border border-slate-200 text-slate-600 text-[12.5px] font-bold hover:bg-slate-50 transition-colors active:scale-95 flex items-center">Edit</a>
                                        <form method="POST" action="{{ route('admin.plans.toggle-active', $plan) }}">
                                            @csrf
                                            <button type="submit" class="h-9 px-3.5 rounded-lg border text-[12.5px] font-bold transition-colors active:scale-95 {{ $plan->is_active ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }}">
                                                {{ $plan->is_active ? 'Disable' : 'Enable' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-[13.5px] text-[#94A3B8] italic">No plans yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        </div>
    </main>
</div>

@if ($plans->isNotEmpty())
    <script src="{{ asset('libs/simple-datatables/simple-datatables.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof simpleDatatables === 'undefined') return;

            new simpleDatatables.DataTable('#plans-table', {
                searchable: true,
                paging: true,
                perPage: 10,
                perPageSelect: [10, 25, 50, 100],
                sortable: true,
                // Actions column holds buttons/forms - sorting it is meaningless.
                columns: [{ select: 5, sortable: false }],
                labels: {
                    placeholder: 'Search plans...',
                    perPage: '{select} per page',
                    noRows: 'No plans found',
                    noResults: 'No plans match your search',
                    info: 'Showing {start}–{end} of {rows} plans',
                },
            });
        });
    </script>
@endif

@endsection
