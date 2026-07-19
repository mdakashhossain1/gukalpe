@extends('layouts.admin')

@section('title', 'Withdrawal requests')

@section('content')

<div class="flex min-h-screen">

    <x-admin-sidebar active="withdrawals" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Withdrawal requests" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Withdrawal requests</h1>
        <p class="text-[13.5px] text-[#64748B] mb-6">Manual UPI cash-out requests. Approving debits the user's wallet immediately - pay out to their UPI ID yourself, outside this system.</p>

        <div class="flex gap-1.5 mb-4 bg-[#F1F5F9] rounded-lg p-1 w-fit">
            @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
                <a href="{{ route('admin.withdrawals', ['status' => $key]) }}"
                    class="h-8 px-4 rounded-md text-[12.5px] transition-colors flex items-center {{ $status === $key ? 'font-bold bg-white text-[#0F172A] shadow-sm' : 'font-semibold text-[#64748B]' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="flex flex-col gap-3 w-full">
            @forelse ($withdrawals as $withdraw)
                @php
                    $pillClasses = match ($withdraw->status) {
                        'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'rejected' => 'bg-red-50 text-red-700 border-red-200',
                        default => 'bg-amber-50 text-amber-700 border-amber-200',
                    };
                @endphp
                <div class="bg-white rounded-xl border border-[#E5E9EB] p-4 flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex flex-col gap-0.5 min-w-[180px]">
                        <div class="flex items-center gap-2">
                            <span class="text-[14px] font-bold text-[#0F172A]">₹{{ number_format($withdraw->amount, 2) }}</span>
                            <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border {{ $pillClasses }}">{{ $withdraw->status }}</span>
                        </div>
                        <span class="text-[12px] text-[#64748B]">{{ $withdraw->phone }}</span>
                        <span class="text-[11.5px] font-mono text-[#334155]">Payout UPI: {{ $withdraw->payout_upi_id }}</span>
                        <span class="text-[11px] text-[#94A3B8]">{{ $withdraw->submitted_at->format('d M Y, h:i A') }}</span>
                    </div>

                    @if ($withdraw->status === 'pending')
                        <div class="flex gap-2 shrink-0">
                            <form method="POST" action="{{ route('admin.withdrawals.approve', $withdraw) }}">
                                @csrf
                                <button type="submit" class="h-9 px-3.5 rounded-lg bg-emerald-600 text-white text-[12.5px] font-bold hover:bg-emerald-700 transition-colors active:scale-95">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('admin.withdrawals.reject', $withdraw) }}">
                                @csrf
                                <button type="submit" class="h-9 px-3.5 rounded-lg border border-red-200 text-red-600 text-[12.5px] font-bold hover:bg-red-50 transition-colors active:scale-95">Reject</button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-[13.5px] text-[#94A3B8] italic">No {{ $status }} withdrawal requests.</p>
            @endforelse
        </div>

        </div>
    </main>
</div>

@endsection
