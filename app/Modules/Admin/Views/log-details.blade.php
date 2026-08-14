@extends('layouts.admin')

@section('title', 'Activity log entry')

@section('content')

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="logs" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Activity log entry" />

        <div class="px-6 md:px-10 py-8 md:py-10 max-w-2xl">

        <a href="{{ route('admin.logs') }}" class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-[#64748B] hover:text-[#0F172A] transition-colors mb-4">
            <i class="fa-solid fa-arrow-left text-[11px]"></i> Back to Activity logs
        </a>

        <div class="flex items-center gap-2.5 mb-1 flex-wrap">
            <h1 class="font-poppins font-bold text-[20px] text-[#0F172A]">{{ $entry->actionLabel() }}</h1>
            <span class="text-[10.5px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border bg-slate-50 text-slate-600 border-slate-200">{{ $entry->action }}</span>
        </div>
        <p class="text-[13.5px] text-[#64748B] mb-6">{{ $entry->created_at?->format('d M Y, h:i A') }}</p>

        <div class="bg-white rounded-2xl border border-[#E5E9EB] p-6 flex flex-col gap-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-1">Admin</p>
                    <p class="text-[14px] font-semibold text-[#0F172A]">{{ $entry->admin_label }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-1">Date/Time</p>
                    <p class="text-[14px] font-semibold text-[#0F172A]">{{ $entry->created_at?->format('d M Y, h:i:s A') }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-1">Target</p>
                    @if ($targetUser)
                        <a href="{{ route('admin.users.show', $targetUser) }}" class="text-[14px] font-semibold text-[#0A5C66] hover:underline">
                            {{ $targetUser->name ?: $targetUser->phone ?: ('#'.$targetUser->id) }} <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                        </a>
                    @else
                        <p class="text-[14px] font-semibold text-[#0F172A] font-mono">{{ $entry->target_type ? $entry->target_type.' #'.$entry->target_id : '—' }}</p>
                    @endif
                </div>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-1">IP address</p>
                    <p class="text-[14px] font-semibold text-[#0F172A] font-mono">{{ $entry->ip ?: '—' }}</p>
                </div>
            </div>

            <div class="pt-4 border-t border-[#F1F5F9]">
                <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-1">Reason</p>
                <p class="text-[14px] text-[#334155]">{{ $entry->reason ?: '—' }}</p>
            </div>

            <div class="pt-4 border-t border-[#F1F5F9]">
                <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8] mb-3">Details</p>
                @if ($entry->meta)
                    <div class="flex flex-col gap-2.5">
                        @foreach ($entry->meta as $key => $value)
                            <div class="flex items-start justify-between gap-4 border-b border-[#F1F5F9] last:border-0 pb-2.5 last:pb-0">
                                <span class="text-[12.5px] font-semibold text-[#64748B] shrink-0">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                                <span class="text-[13.5px] font-mono font-semibold text-[#0F172A] text-right break-all">
                                    @if (is_bool($value)) {{ $value ? 'Yes' : 'No' }}
                                    @elseif ($value === null || $value === '') —
                                    @else {{ $value }}
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-[13px] text-[#94A3B8] italic">No extra details.</p>
                @endif
            </div>
        </div>

        </div>
    </main>
</div>

@endsection
