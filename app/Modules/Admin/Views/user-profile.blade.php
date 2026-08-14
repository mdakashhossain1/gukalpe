@extends('layouts.admin')

@section('title', 'User profile')

@section('content')

{{-- Every data section on this page (investments, referrals, transactions,
     deposits, withdrawals, admin actions) uses the same self-hosted
     simple-datatables treatment as the Users/Deposits/Withdrawals/Plans
     lists elsewhere in the admin panel - search + sort + pagination per
     section instead of a plain scrollable list. --}}
<link rel="stylesheet" href="{{ asset('libs/simple-datatables/style.css') }}">
<style>
    .mini-datatable-card .datatable-top { padding: 0 0 12px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    .mini-datatable-card .datatable-bottom { padding: 12px 0 0; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    .mini-datatable-card .datatable-search input { height: 34px; border: 1px solid #E5E9EB; border-radius: 8px; padding: 0 10px; font-size: 12.5px; color: #0F172A; outline: none; min-width: 160px; }
    .mini-datatable-card .datatable-search input:focus { border-color: #0A5C66; box-shadow: 0 0 0 3px rgba(10,92,102,.12); }
    .mini-datatable-card .datatable-selector { height: 34px; border: 1px solid #E5E9EB; border-radius: 8px; padding: 0 6px; font-size: 12.5px; color: #334155; }
    .mini-datatable-card .datatable-info { font-size: 11.5px; color: #64748B; }
    .mini-datatable-card .datatable-container { overflow-x: auto; border: 0; }
    .mini-datatable-card .datatable-pagination a { border-radius: 8px; padding: 5px 9px; font-size: 12px; font-weight: 600; color: #334155; }
    .mini-datatable-card .datatable-pagination a:hover { background: #F1F5F9; }
    .mini-datatable-card .datatable-pagination .datatable-active a { background: #0A5C66; color: #fff; }
</style>

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="users" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="User profile" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <a href="{{ route('admin.users') }}" class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-[#64748B] hover:text-[#0F172A] transition-colors mb-4">
            <i class="fa-solid fa-arrow-left text-[11px]"></i> Back to Users
        </a>

        @if (session('success'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 px-4 py-3 text-[13.5px] font-medium">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-[13.5px] font-medium">{{ session('error') }}</div>
        @endif

        {{-- Header card: identity + Actions menu (client item 1) --}}
        <div class="bg-white rounded-xl border border-[#E5E9EB] p-5 mb-6 flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-4 min-w-0">
                @if ($user->avatar)
                    <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="w-14 h-14 rounded-full object-cover shrink-0 border border-[#E5E9EB]" referrerpolicy="no-referrer">
                @else
                    <div class="w-14 h-14 rounded-full bg-[#0A5C66]/10 text-[#0A5C66] font-bold text-[20px] flex items-center justify-center shrink-0 uppercase">
                        {{ mb_substr($user->name ?: '?', 0, 1) }}
                    </div>
                @endif
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="font-poppins font-bold text-[18px] text-[#0F172A]">{{ $user->name ?: 'Unnamed user' }}</h1>
                        @if ($user->isBanned())
                            <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border bg-red-50 text-red-700 border-red-200">
                                <i class="fa-solid fa-ban text-[9px]"></i> Banned
                            </span>
                        @endif
                    </div>
                    <p class="text-[13px] text-[#64748B] font-mono mt-0.5">{{ $user->phone ?: '—' }}</p>
                    <p class="text-[12px] text-[#94A3B8] mt-0.5">
                        {{ $user->hasRealEmail() ? $user->email : 'Phone signup' }}
                        · Joined {{ $user->created_at?->format('d M Y') }}
                        @if ($user->referral_code) · Referral code <span class="font-mono font-semibold text-[#334155]">{{ $user->referral_code }}</span> @endif
                    </p>
                    @if ($user->isBanned() && $user->ban_reason)
                        <p class="text-[12px] text-red-600 mt-1"><span class="font-semibold">Ban reason:</span> {{ $user->ban_reason }}</p>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($user->phone)
                    <button type="button" data-adjust-wallet data-phone="{{ $user->phone }}" data-name="{{ $user->name ?: $user->phone }}" data-balance="{{ number_format($walletBalance, 2) }}"
                        class="h-9 px-3.5 rounded-lg border border-[#0A5C66]/30 text-[#0A5C66] text-[12.5px] font-bold hover:bg-[#0A5C66]/[0.06] transition-colors active:scale-95 inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-wallet text-[11px]"></i> Wallet Management
                    </button>
                @endif
                @if ($user->isBanned())
                    <form method="POST" action="{{ route('admin.users.toggle-ban', $user) }}" onsubmit="return confirm('Unban this user? They will be able to log in again.');">
                        @csrf
                        <button type="submit" class="h-9 px-3.5 rounded-lg border border-emerald-200 text-emerald-700 text-[12.5px] font-bold hover:bg-emerald-50 transition-colors active:scale-95 inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-unlock text-[11px]"></i> Unban
                        </button>
                    </form>
                @else
                    <button type="button" data-ban-user data-name="{{ $user->name ?: $user->phone }}" data-action="{{ route('admin.users.toggle-ban', $user) }}"
                        class="h-9 px-3.5 rounded-lg border border-red-200 text-red-600 text-[12.5px] font-bold hover:bg-red-50 transition-colors active:scale-95 inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-ban text-[11px]"></i> Ban
                    </button>
                @endif
                @if ($user->phone)
                    <button type="button" data-send-notif data-phone="{{ $user->phone }}" data-name="{{ $user->name ?: $user->phone }}"
                        class="h-9 px-3.5 rounded-lg border border-[#E5E9EB] text-[#334155] text-[12.5px] font-bold hover:bg-[#F8FAFC] transition-colors active:scale-95 inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-paper-plane text-[11px]"></i> Send Notification
                    </button>
                    <a href="{{ route('admin.transactions', ['phone' => $user->phone]) }}"
                        class="h-9 px-3.5 rounded-lg border border-[#E5E9EB] text-[#334155] text-[12.5px] font-bold hover:bg-[#F8FAFC] transition-colors active:scale-95 inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-receipt text-[11px]"></i> Transactions
                    </a>
                @endif
            </div>
        </div>

        {{-- Financial summary --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
            <div class="bg-white rounded-xl border border-[#E5E9EB] p-4">
                <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-1">Wallet balance</p>
                <p class="text-[19px] font-black text-[#0F172A] font-poppins">₹{{ number_format($walletBalance, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-[#E5E9EB] p-4">
                <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-1">Total deposited</p>
                <p class="text-[19px] font-black text-emerald-600 font-poppins">₹{{ number_format($totalDeposited, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-[#E5E9EB] p-4">
                <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-1">Total withdrawn</p>
                <p class="text-[19px] font-black text-red-600 font-poppins">₹{{ number_format($totalWithdrawn, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-[#E5E9EB] p-4">
                <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-1">Active investment</p>
                <p class="text-[19px] font-black text-[#0A5C66] font-poppins">₹{{ number_format($totalInvested, 2) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Investment summary --}}
            <div id="investments" class="bg-white rounded-xl border border-[#E5E9EB] p-5 scroll-mt-6 mini-datatable-card">
                <h2 class="font-poppins font-bold text-[14.5px] text-[#0F172A] mb-3">Investment summary</h2>
                <div class="overflow-x-auto">
                    <table id="investments-table" class="w-full text-left border-collapse min-w-[420px]">
                        <thead>
                            <tr class="bg-[#F8FAFC] border-b border-[#E5E9EB]">
                                <th class="px-3 py-2.5 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Plan</th>
                                <th class="px-3 py-2.5 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Purchased</th>
                                <th class="px-3 py-2.5 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Status</th>
                                <th class="px-3 py-2.5 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8] text-right">Invested</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($holdings as $holding)
                                <tr class="border-b border-[#F1F5F9] last:border-0">
                                    <td class="px-3 py-2.5 text-[12.5px] font-semibold text-[#0F172A]">{{ $holding->plan->title ?? 'Deleted plan' }}</td>
                                    <td class="px-3 py-2.5 text-[12px] text-[#64748B] whitespace-nowrap" data-order="{{ $holding->purchased_at?->timestamp }}">{{ $holding->purchased_at?->format('d M Y') }}</td>
                                    <td class="px-3 py-2.5 whitespace-nowrap">
                                        <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border bg-slate-50 text-slate-600 border-slate-200">{{ ucfirst($holding->status) }}</span>
                                    </td>
                                    <td class="px-3 py-2.5 text-[13px] font-mono font-bold text-[#0F172A] text-right whitespace-nowrap">₹{{ number_format($holding->invested_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-3 py-6 text-center text-[13px] text-[#94A3B8] italic">No plan holdings yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Referral details --}}
            <div id="referrals" class="bg-white rounded-xl border border-[#E5E9EB] p-5 scroll-mt-6 mini-datatable-card">
                <h2 class="font-poppins font-bold text-[14.5px] text-[#0F172A] mb-3">Referral details ({{ $referrals->count() }})</h2>
                <div class="overflow-x-auto">
                    <table id="referrals-table" class="w-full text-left border-collapse min-w-[380px]">
                        <thead>
                            <tr class="bg-[#F8FAFC] border-b border-[#E5E9EB]">
                                <th class="px-3 py-2.5 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Name</th>
                                <th class="px-3 py-2.5 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Phone</th>
                                <th class="px-3 py-2.5 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($referrals as $ref)
                                <tr class="border-b border-[#F1F5F9] last:border-0">
                                    <td class="px-3 py-2.5 text-[12.5px] font-semibold text-[#0F172A]">{{ $ref->name ?: '—' }}</td>
                                    <td class="px-3 py-2.5 text-[12.5px] font-mono text-[#334155] whitespace-nowrap">{{ $ref->phone ?: '—' }}</td>
                                    <td class="px-3 py-2.5 text-[12px] text-[#64748B] whitespace-nowrap" data-order="{{ $ref->created_at?->timestamp }}">{{ $ref->created_at?->format('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-3 py-6 text-center text-[13px] text-[#94A3B8] italic">No referrals yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Transactions: the full wallet ledger for this user (client item 1's
             "Transactions" action, shown inline here rather than only as a
             deep-link out) - same Balance Before/After/Reason/Admin fields
             the Transactions page itself shows, so this view alone answers
             "what happened to this user's wallet and why". --}}
        <div id="transactions" class="bg-white rounded-xl border border-[#E5E9EB] p-5 mb-6 scroll-mt-6 mini-datatable-card">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-poppins font-bold text-[14.5px] text-[#0F172A]">Transactions</h2>
                <a href="{{ route('admin.transactions', ['phone' => $user->phone]) }}" class="text-[11.5px] font-bold text-[#0A5C66] hover:underline">All transactions</a>
            </div>
            <div class="overflow-x-auto">
                <table id="transactions-table" class="w-full text-left border-collapse min-w-[640px]">
                    <thead>
                        <tr class="border-b border-[#F1F5F9]">
                            <th class="py-2 pr-3 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Type</th>
                            <th class="py-2 pr-3 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8] text-right">Amount</th>
                            <th class="py-2 pr-3 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8] text-right">Balance after</th>
                            <th class="py-2 pr-3 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Reason</th>
                            <th class="py-2 pr-3 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Admin</th>
                            <th class="py-2 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentTransactions as $t)
                            <tr class="border-b border-[#F1F5F9] last:border-0">
                                <td class="py-2 pr-3 text-[12.5px] font-semibold text-[#334155] whitespace-nowrap">{{ $t->typeLabel() }}</td>
                                <td class="py-2 pr-3 text-[13px] font-mono font-bold text-right whitespace-nowrap {{ $t->direction === 'credit' ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $t->direction === 'credit' ? '+' : '−' }}₹{{ number_format($t->amount, 2) }}
                                </td>
                                <td class="py-2 pr-3 text-[12.5px] text-[#334155] text-right whitespace-nowrap">{{ $t->balance_after !== null ? '₹'.number_format($t->balance_after, 2) : '—' }}</td>
                                <td class="py-2 pr-3 text-[12px] text-[#64748B] max-w-[180px] truncate" title="{{ $t->meta['reason'] ?? '' }}">{{ $t->meta['reason'] ?? '—' }}</td>
                                <td class="py-2 pr-3 text-[12px] text-[#64748B] whitespace-nowrap">{{ $t->meta['admin_label'] ?? '—' }}</td>
                                <td class="py-2 text-[11.5px] text-[#94A3B8] whitespace-nowrap" data-order="{{ $t->created_at?->timestamp }}">{{ $t->created_at?->format('d M Y, h:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-[13px] text-[#94A3B8] italic">No wallet activity yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Recent deposits/withdrawals --}}
            <div class="bg-white rounded-xl border border-[#E5E9EB] p-5 mini-datatable-card">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-poppins font-bold text-[14.5px] text-[#0F172A]">Recent deposits</h2>
                    <a href="{{ route('admin.deposits', ['phone' => $user->phone]) }}" class="text-[11.5px] font-bold text-[#0A5C66] hover:underline">All deposits</a>
                </div>
                <div class="overflow-x-auto">
                    <table id="deposits-mini-table" class="w-full text-left border-collapse min-w-[440px]">
                        <thead>
                            <tr class="bg-[#F8FAFC] border-b border-[#E5E9EB]">
                                <th class="px-3 py-2.5 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Amount</th>
                                <th class="px-3 py-2.5 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Method</th>
                                <th class="px-3 py-2.5 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Status</th>
                                <th class="px-3 py-2.5 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentDeposits as $d)
                                @php
                                    $pillClasses = match ($d->status) {
                                        'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'rejected' => 'bg-red-50 text-red-700 border-red-200',
                                        default => 'bg-amber-50 text-amber-700 border-amber-200',
                                    };
                                @endphp
                                <tr class="border-b border-[#F1F5F9] last:border-0">
                                    <td class="px-3 py-2.5 text-[13px] font-mono font-bold text-[#0F172A] whitespace-nowrap">₹{{ number_format($d->amount, 2) }}</td>
                                    <td class="px-3 py-2.5 text-[12.5px] text-[#334155] whitespace-nowrap">{{ $d->method_label }}</td>
                                    <td class="px-3 py-2.5 whitespace-nowrap">
                                        <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border {{ $pillClasses }}">{{ $d->status }}</span>
                                    </td>
                                    <td class="px-3 py-2.5 text-[12px] text-[#64748B] whitespace-nowrap" data-order="{{ $d->submitted_at?->timestamp }}">{{ $d->submitted_at?->format('d M Y, h:i A') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-3 py-6 text-center text-[13px] text-[#94A3B8] italic">No deposits yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-[#E5E9EB] p-5 mini-datatable-card">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-poppins font-bold text-[14.5px] text-[#0F172A]">Recent withdrawals</h2>
                    <a href="{{ route('admin.withdrawals', ['phone' => $user->phone]) }}" class="text-[11.5px] font-bold text-[#0A5C66] hover:underline">All withdrawals</a>
                </div>
                <div class="overflow-x-auto">
                    <table id="withdrawals-mini-table" class="w-full text-left border-collapse min-w-[440px]">
                        <thead>
                            <tr class="bg-[#F8FAFC] border-b border-[#E5E9EB]">
                                <th class="px-3 py-2.5 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Amount</th>
                                <th class="px-3 py-2.5 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Method</th>
                                <th class="px-3 py-2.5 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Status</th>
                                <th class="px-3 py-2.5 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentWithdrawals as $w)
                                @php
                                    $pillClasses = match ($w->status) {
                                        'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'rejected' => 'bg-red-50 text-red-700 border-red-200',
                                        default => 'bg-amber-50 text-amber-700 border-amber-200',
                                    };
                                @endphp
                                <tr class="border-b border-[#F1F5F9] last:border-0">
                                    <td class="px-3 py-2.5 text-[13px] font-mono font-bold text-[#0F172A] whitespace-nowrap">₹{{ number_format($w->amount, 2) }}</td>
                                    <td class="px-3 py-2.5 text-[12.5px] text-[#334155] whitespace-nowrap">{{ strtoupper($w->method) }}</td>
                                    <td class="px-3 py-2.5 whitespace-nowrap">
                                        <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border {{ $pillClasses }}">{{ $w->status }}</span>
                                    </td>
                                    <td class="px-3 py-2.5 text-[12px] text-[#64748B] whitespace-nowrap" data-order="{{ $w->submitted_at?->timestamp }}">{{ $w->submitted_at?->format('d M Y, h:i A') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-3 py-6 text-center text-[13px] text-[#94A3B8] italic">No withdrawals yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Recent admin actions on this user (ban/unban, wallet adjustments,
             etc.) - separate from the Transactions section above now that
             the ledger has its own dedicated table, so this is purely the
             audit trail, not a mix of both. --}}
        <div class="bg-white rounded-xl border border-[#E5E9EB] p-5 mini-datatable-card">
            <h2 class="font-poppins font-bold text-[14.5px] text-[#0F172A] mb-3">Recent admin actions</h2>
            <div class="overflow-x-auto">
                <table id="audit-mini-table" class="w-full text-left border-collapse min-w-[560px]">
                    <thead>
                        <tr class="bg-[#F8FAFC] border-b border-[#E5E9EB]">
                            <th class="px-3 py-2.5 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Date</th>
                            <th class="px-3 py-2.5 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Action</th>
                            <th class="px-3 py-2.5 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Admin</th>
                            <th class="px-3 py-2.5 text-[10.5px] font-bold uppercase tracking-wide text-[#94A3B8]">Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentAudit as $a)
                            <tr class="border-b border-[#F1F5F9] last:border-0">
                                <td class="px-3 py-2.5 text-[12px] text-[#64748B] whitespace-nowrap" data-order="{{ $a->created_at?->timestamp }}">{{ $a->created_at?->format('d M Y, h:i A') }}</td>
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border bg-slate-50 text-slate-600 border-slate-200">{{ $a->actionLabel() }}</span>
                                </td>
                                <td class="px-3 py-2.5 text-[12.5px] font-semibold text-[#0F172A] whitespace-nowrap">{{ $a->admin_label }}</td>
                                <td class="px-3 py-2.5 text-[12.5px] text-[#334155]">{{ $a->reason ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-6 text-center text-[13px] text-[#94A3B8] italic">No admin actions recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        </div>
    </main>
</div>

{{-- Wallet-adjust modal (identical to users.blade.php) --}}
<div id="wallet-modal" class="hidden fixed inset-0 z-[600] items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" data-wallet-close></div>
    <div class="relative w-full max-w-md bg-white rounded-2xl border border-[#E5E9EB] shadow-xl p-6">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div>
                <h2 class="font-poppins font-bold text-[16px] text-[#0F172A]">Adjust wallet</h2>
                <p class="text-[12.5px] text-[#64748B] mt-0.5">
                    <span id="wallet-modal-name" class="font-semibold text-[#334155]">—</span>
                    · <span id="wallet-modal-phone" class="font-mono">—</span>
                    · Balance <span id="wallet-modal-balance" class="font-mono font-semibold text-[#0F172A]">₹0.00</span>
                </p>
            </div>
            <button type="button" data-wallet-close class="w-9 h-9 -mr-1 -mt-1 shrink-0 rounded-lg flex items-center justify-center text-[#64748B] hover:bg-[#F1F5F9] transition-colors" aria-label="Close">
                <i class="fa-solid fa-xmark text-[15px]"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.wallet-tools.adjust') }}" class="flex flex-col gap-4">
            @csrf
            <input type="hidden" name="phone" id="wallet-modal-phone-input" value="">

            <div>
                <span class="block text-[12.5px] font-semibold text-[#334155] mb-2">Operation</span>
                <input type="hidden" name="direction" id="wallet-direction" value="increase">
                <div class="grid grid-cols-2 gap-2.5">
                    <button type="button" data-dir="increase" class="wallet-dir-btn is-active">
                        <i class="fa-solid fa-plus"></i> Increase
                    </button>
                    <button type="button" data-dir="decrease" class="wallet-dir-btn">
                        <i class="fa-solid fa-minus"></i> Decrease
                    </button>
                </div>
            </div>

            <div>
                <label for="wallet-modal-amount" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Amount (₹)</label>
                <input type="number" id="wallet-modal-amount" name="amount" step="0.01" min="0.01" required placeholder="e.g. 250"
                    class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                <p class="text-[11.5px] text-[#94A3B8] mt-1.5">A decrease can't take the balance below ₹0.</p>
            </div>

            <div>
                <label for="wallet-modal-reason" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Reason (required)</label>
                <input type="text" id="wallet-modal-reason" name="reason" maxlength="255" required placeholder="e.g. Promotion bonus, refund for failed deposit"
                    class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                <p class="text-[11.5px] text-[#94A3B8] mt-1.5">Recorded in the wallet ledger and the audit log.</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" data-wallet-close class="h-10 px-4 rounded-lg border border-slate-200 text-slate-600 font-semibold text-[13.5px] hover:bg-slate-50 transition-colors">Cancel</button>
                <button type="submit" class="h-10 px-5 rounded-lg bg-brand text-white font-semibold text-[13.5px] hover:bg-brand-light transition-colors active:scale-[0.99]">Apply adjustment</button>
            </div>
        </form>
    </div>
</div>

{{-- Ban modal --}}
<div id="ban-modal" class="hidden fixed inset-0 z-[600] items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" data-ban-close></div>
    <div class="relative w-full max-w-md bg-white rounded-2xl border border-[#E5E9EB] shadow-xl p-6">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div>
                <h2 class="font-poppins font-bold text-[16px] text-[#0F172A]">Ban user</h2>
                <p class="text-[12.5px] text-[#64748B] mt-0.5">
                    <span id="ban-modal-name" class="font-semibold text-[#334155]">—</span> will be logged out and blocked from logging in.
                </p>
            </div>
            <button type="button" data-ban-close class="w-9 h-9 -mr-1 -mt-1 shrink-0 rounded-lg flex items-center justify-center text-[#64748B] hover:bg-[#F1F5F9] transition-colors" aria-label="Close">
                <i class="fa-solid fa-xmark text-[15px]"></i>
            </button>
        </div>

        <form id="ban-modal-form" method="POST" action="" class="flex flex-col gap-4">
            @csrf
            <div>
                <label for="ban-modal-reason" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Reason (required)</label>
                <textarea id="ban-modal-reason" name="reason" maxlength="255" required rows="3" placeholder="e.g. Fraudulent deposit claims, abusive behavior"
                    class="w-full rounded-lg border border-[#CBD5E1] px-3 py-2 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15"></textarea>
            </div>
            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" data-ban-close class="h-10 px-4 rounded-lg border border-slate-200 text-slate-600 font-semibold text-[13.5px] hover:bg-slate-50 transition-colors">Cancel</button>
                <button type="submit" class="h-10 px-5 rounded-lg bg-red-600 text-white font-semibold text-[13.5px] hover:bg-red-700 transition-colors active:scale-[0.99]">Ban user</button>
            </div>
        </form>
    </div>
</div>

{{-- Send notification modal - posts to the existing admin push-notification
     endpoint with target=specific and this user's phone prefilled. --}}
<div id="notif-modal" class="hidden fixed inset-0 z-[600] items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" data-notif-close></div>
    <div class="relative w-full max-w-md bg-white rounded-2xl border border-[#E5E9EB] shadow-xl p-6">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div>
                <h2 class="font-poppins font-bold text-[16px] text-[#0F172A]">Send notification</h2>
                <p class="text-[12.5px] text-[#64748B] mt-0.5">To <span id="notif-modal-name" class="font-semibold text-[#334155]">—</span></p>
            </div>
            <button type="button" data-notif-close class="w-9 h-9 -mr-1 -mt-1 shrink-0 rounded-lg flex items-center justify-center text-[#64748B] hover:bg-[#F1F5F9] transition-colors" aria-label="Close">
                <i class="fa-solid fa-xmark text-[15px]"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.push-notification.send') }}" class="flex flex-col gap-4">
            @csrf
            <input type="hidden" name="target" value="specific">
            <input type="hidden" name="phone" id="notif-modal-phone-input" value="">

            <div>
                <label for="notif-modal-title" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Title</label>
                <input type="text" id="notif-modal-title" name="title" maxlength="120" required
                    class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
            </div>
            <div>
                <label for="notif-modal-body" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Message (optional)</label>
                <textarea id="notif-modal-body" name="body" maxlength="500" rows="3"
                    class="w-full rounded-lg border border-[#CBD5E1] px-3 py-2 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" data-notif-close class="h-10 px-4 rounded-lg border border-slate-200 text-slate-600 font-semibold text-[13.5px] hover:bg-slate-50 transition-colors">Cancel</button>
                <button type="submit" class="h-10 px-5 rounded-lg bg-brand text-white font-semibold text-[13.5px] hover:bg-brand-light transition-colors active:scale-[0.99]">Send</button>
            </div>
        </form>
    </div>
</div>

<style>
    .wallet-dir-btn {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        height: 46px; border: 1.5px solid #E5E9EB; border-radius: 10px;
        font-size: 14px; font-weight: 700; color: #64748B; background: #fff;
        cursor: pointer; transition: border-color .15s, background-color .15s, color .15s;
    }
    .wallet-dir-btn:hover { background: #F8FAFC; }
    .wallet-dir-btn.is-active[data-dir="increase"] { border-color: #10B981; background: #ECFDF5; color: #047857; }
    .wallet-dir-btn.is-active[data-dir="decrease"] { border-color: #EF4444; background: #FEF2F2; color: #B91C1C; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Wallet modal
        var modal = document.getElementById('wallet-modal');
        var nameEl = document.getElementById('wallet-modal-name');
        var phoneEl = document.getElementById('wallet-modal-phone');
        var balanceEl = document.getElementById('wallet-modal-balance');
        var phoneInput = document.getElementById('wallet-modal-phone-input');
        var amountInput = document.getElementById('wallet-modal-amount');
        var reasonInput = document.getElementById('wallet-modal-reason');
        var dirInput = document.getElementById('wallet-direction');
        var dirBtns = modal ? modal.querySelectorAll('.wallet-dir-btn') : [];

        function setDirection(dir) {
            dirInput.value = dir;
            dirBtns.forEach(function (b) { b.classList.toggle('is-active', b.getAttribute('data-dir') === dir); });
        }
        dirBtns.forEach(function (b) { b.addEventListener('click', function () { setDirection(b.getAttribute('data-dir')); }); });

        function openWalletModal(btn) {
            phoneInput.value = btn.getAttribute('data-phone') || '';
            nameEl.textContent = btn.getAttribute('data-name') || '—';
            phoneEl.textContent = btn.getAttribute('data-phone') || '—';
            balanceEl.textContent = '₹' + (btn.getAttribute('data-balance') || '0.00');
            amountInput.value = '';
            reasonInput.value = '';
            setDirection('increase');
            modal.classList.remove('hidden'); modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            setTimeout(function () { amountInput.focus(); }, 30);
        }
        function closeWalletModal() {
            modal.classList.add('hidden'); modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        // Ban modal
        var banModal = document.getElementById('ban-modal');
        var banNameEl = document.getElementById('ban-modal-name');
        var banForm = document.getElementById('ban-modal-form');
        var banReasonInput = document.getElementById('ban-modal-reason');

        function openBanModal(btn) {
            banForm.action = btn.getAttribute('data-action') || '';
            banNameEl.textContent = btn.getAttribute('data-name') || '—';
            banReasonInput.value = '';
            banModal.classList.remove('hidden'); banModal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            setTimeout(function () { banReasonInput.focus(); }, 30);
        }
        function closeBanModal() {
            banModal.classList.add('hidden'); banModal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        // Notification modal
        var notifModal = document.getElementById('notif-modal');
        var notifNameEl = document.getElementById('notif-modal-name');
        var notifPhoneInput = document.getElementById('notif-modal-phone-input');

        function openNotifModal(btn) {
            notifPhoneInput.value = btn.getAttribute('data-phone') || '';
            notifNameEl.textContent = btn.getAttribute('data-name') || '—';
            notifModal.classList.remove('hidden'); notifModal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }
        function closeNotifModal() {
            notifModal.classList.add('hidden'); notifModal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('click', function (e) {
            var walletBtn = e.target.closest('[data-adjust-wallet]');
            if (walletBtn) { openWalletModal(walletBtn); return; }
            if (e.target.closest('[data-wallet-close]')) { closeWalletModal(); return; }

            var banBtn = e.target.closest('[data-ban-user]');
            if (banBtn) { openBanModal(banBtn); return; }
            if (e.target.closest('[data-ban-close]')) { closeBanModal(); return; }

            var notifBtn = e.target.closest('[data-send-notif]');
            if (notifBtn) { openNotifModal(notifBtn); return; }
            if (e.target.closest('[data-notif-close]')) { closeNotifModal(); return; }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            if (modal && !modal.classList.contains('hidden')) closeWalletModal();
            if (banModal && !banModal.classList.contains('hidden')) closeBanModal();
            if (notifModal && !notifModal.classList.contains('hidden')) closeNotifModal();
        });
    });
</script>

<script src="{{ asset('libs/simple-datatables/simple-datatables.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof simpleDatatables === 'undefined') return;

        // One helper for every data section on this page - skips tables with
        // no real rows (only the "No … yet." empty-state row), same guard
        // every other datatable-backed admin page uses.
        function initMiniTable(id, options) {
            var table = document.getElementById(id);
            if (!table || !table.querySelector('tbody tr td:not([colspan])')) return;

            new simpleDatatables.DataTable('#' + id, Object.assign({
                searchable: true,
                paging: true,
                perPage: 10,
                perPageSelect: [10, 25, 50],
                sortable: true,
                labels: {
                    placeholder: 'Search...',
                    perPage: '{select} per page',
                    noRows: 'No data found',
                    noResults: 'No results match your search',
                    info: 'Showing {start}–{end} of {rows}',
                },
            }, options || {}));
        }

        initMiniTable('investments-table');
        initMiniTable('referrals-table');
        initMiniTable('transactions-table');
        initMiniTable('deposits-mini-table');
        initMiniTable('withdrawals-mini-table');
        initMiniTable('audit-mini-table');
    });
</script>

@endsection
