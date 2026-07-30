@extends('layouts.admin')

@section('title', 'Overview')

@section('content')

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="overview" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Overview" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Overview</h1>
        <p class="text-[13.5px] text-[#64748B] mb-6">Real numbers from the database - money flow, users, requests, and plans.</p>

        {{-- Money --}}
        <h2 class="text-[12px] font-bold text-[#94A3B8] uppercase tracking-wide mb-3">Money</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 mb-6">
            <x-admin-stat-tile label="Total wallet balance" :value="'₹'.number_format($totalWalletBalance, 2)" icon="fa-sack-dollar" accent="#0A5C66" />
            <x-admin-stat-tile label="Total deposited" :value="'₹'.number_format($totalDeposited, 2)" icon="fa-download" accent="#059669" />
            <x-admin-stat-tile label="Total withdrawn" :value="'₹'.number_format($totalWithdrawn, 2)" icon="fa-arrow-up-from-bracket" accent="#DC2626" />
            <x-admin-stat-tile label="Net inflow" :value="'₹'.number_format($netInflow, 2)" icon="fa-scale-balanced" :accent="$netInflow >= 0 ? '#059669' : '#DC2626'" />
            <x-admin-stat-tile label="Active investments" :value="'₹'.number_format($totalInvested, 2)" icon="fa-chart-line" accent="#0A5C66" />
            <x-admin-stat-tile label="Active holdings" :value="number_format($activeHoldings)" icon="fa-layer-group" accent="#0A5C66" />
        </div>

        {{-- Users --}}
        <h2 class="text-[12px] font-bold text-[#94A3B8] uppercase tracking-wide mb-3">Users</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3.5 mb-6">
            <x-admin-stat-tile label="Total users" :value="number_format($totalUsers)" icon="fa-users" accent="#0A5C66" />
            <x-admin-stat-tile label="New today" :value="number_format($signupsToday)" icon="fa-user-plus" accent="#0A5C66" />
            <x-admin-stat-tile label="New this week" :value="number_format($signups7d)" icon="fa-calendar-week" accent="#0A5C66" />
            <x-admin-stat-tile label="Google sign-ins" :value="number_format($googleUsers)" icon="fa-user-check" accent="#2a78d6" />
            <x-admin-stat-tile label="Banned users" :value="number_format($bannedUsers)" icon="fa-ban" :accent="$bannedUsers > 0 ? '#DC2626' : '#94A3B8'" />
        </div>

        {{-- Requests & plans --}}
        <h2 class="text-[12px] font-bold text-[#94A3B8] uppercase tracking-wide mb-3">Requests &amp; plans</h2>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3.5 mb-8">
            {{-- Deposit requests breakdown --}}
            <div class="bg-white rounded-2xl border border-[#E5E9EB] p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[13px] font-bold text-[#0F172A]">Deposit requests</span>
                    <i class="fa-solid fa-money-bill-transfer text-[13px] text-[#0A5C66]"></i>
                </div>
                <div class="flex flex-col gap-2.5">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-[12.5px] text-[#64748B]"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Pending</span>
                        <span class="text-[13px] font-bold text-[#0F172A]">{{ number_format($pendingDepositCount) }} <span class="text-[11px] font-medium text-[#94A3B8]">· ₹{{ number_format($pendingDepositAmount, 2) }}</span></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-[12.5px] text-[#64748B]"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Approved</span>
                        <span class="text-[13px] font-bold text-[#0F172A]">{{ number_format($depApprovedCount) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-[12.5px] text-[#64748B]"><span class="w-2 h-2 rounded-full bg-red-500"></span> Rejected</span>
                        <span class="text-[13px] font-bold text-[#0F172A]">{{ number_format($depRejectedCount) }}</span>
                    </div>
                </div>
            </div>

            {{-- Withdrawal requests breakdown --}}
            <div class="bg-white rounded-2xl border border-[#E5E9EB] p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[13px] font-bold text-[#0F172A]">Withdrawal requests</span>
                    <i class="fa-solid fa-money-bill-transfer fa-flip-horizontal text-[13px] text-[#0A5C66]"></i>
                </div>
                <div class="flex flex-col gap-2.5">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-[12.5px] text-[#64748B]"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Pending</span>
                        <span class="text-[13px] font-bold text-[#0F172A]">{{ number_format($pendingWithdrawalCount) }} <span class="text-[11px] font-medium text-[#94A3B8]">· ₹{{ number_format($pendingWithdrawalAmount, 2) }}</span></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-[12.5px] text-[#64748B]"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Approved</span>
                        <span class="text-[13px] font-bold text-[#0F172A]">{{ number_format($wdApprovedCount) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-[12.5px] text-[#64748B]"><span class="w-2 h-2 rounded-full bg-red-500"></span> Rejected</span>
                        <span class="text-[13px] font-bold text-[#0F172A]">{{ number_format($wdRejectedCount) }}</span>
                    </div>
                </div>
            </div>

            {{-- Plans --}}
            <div class="bg-white rounded-2xl border border-[#E5E9EB] p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[13px] font-bold text-[#0F172A]">Investment plans</span>
                    <i class="fa-solid fa-layer-group text-[13px] text-[#0A5C66]"></i>
                </div>
                <div class="flex flex-col gap-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[12.5px] text-[#64748B]">Total plans</span>
                        <span class="text-[13px] font-bold text-[#0F172A]">{{ number_format($totalPlans) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-[12.5px] text-[#64748B]"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Active</span>
                        <span class="text-[13px] font-bold text-[#0F172A]">{{ number_format($activePlans) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-[12.5px] text-[#64748B]"><span class="w-2 h-2 rounded-full bg-slate-300"></span> Disabled</span>
                        <span class="text-[13px] font-bold text-[#0F172A]">{{ number_format($totalPlans - $activePlans) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts --}}
        <h2 class="text-[12px] font-bold text-[#94A3B8] uppercase tracking-wide mb-3">Trends</h2>
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <x-admin-line-chart
                id="chart-volume"
                title="Deposits vs withdrawals"
                subtitle="Requested amount per day, last 14 days (all statuses)"
                value-prefix="₹"
                :points="$series->map(fn ($row) => [
                    'label' => $row['date']->format('d M'),
                    'values' => ['deposits' => $row['deposits'], 'withdrawals' => $row['withdrawals']],
                ])->all()"
                :series="[
                    ['key' => 'deposits', 'label' => 'Deposits', 'color' => '#2a78d6'],
                    ['key' => 'withdrawals', 'label' => 'Withdrawals', 'color' => '#008300'],
                ]"
            />

            <x-admin-line-chart
                id="chart-signups"
                title="New user signups"
                subtitle="Accounts created per day, last 14 days"
                :points="$series->map(fn ($row) => [
                    'label' => $row['date']->format('d M'),
                    'values' => ['signups' => $row['signups']],
                ])->all()"
                :series="[
                    ['key' => 'signups', 'label' => 'Signups', 'color' => '#2a78d6'],
                ]"
            />
        </div>

        </div>
    </main>
</div>

@endsection
