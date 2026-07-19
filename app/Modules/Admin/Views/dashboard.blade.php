@extends('layouts.admin')

@section('title', 'Overview')

@section('content')

<div class="flex min-h-screen">

    <x-admin-sidebar active="overview" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Overview" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Overview</h1>
        <p class="text-[13.5px] text-[#64748B] mb-6">Real numbers from the database - deposits, withdrawals, users, and wallet balances.</p>

        {{-- KPI row --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 mb-6">
            <x-admin-stat-tile label="Total users" :value="number_format($totalUsers)" icon="fa-users" accent="#0A5C66" />
            <x-admin-stat-tile label="Total wallet balance" :value="'₹'.number_format($totalWalletBalance, 2)" icon="fa-sack-dollar" accent="#0A5C66" />
            <x-admin-stat-tile label="Pending deposits" :value="number_format($pendingDepositCount)" icon="fa-money-bill-transfer" accent="#D97706" />
            <x-admin-stat-tile label="Pending withdrawals" :value="number_format($pendingWithdrawalCount)" icon="fa-money-bill-transfer fa-flip-horizontal" accent="#D97706" />
        </div>

        {{-- Charts --}}
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
