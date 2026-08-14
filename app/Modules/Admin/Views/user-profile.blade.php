@extends('layouts.admin')

@section('title', 'User profile')

@section('content')

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
            <div id="investments" class="bg-white rounded-xl border border-[#E5E9EB] p-5 scroll-mt-6">
                <h2 class="font-poppins font-bold text-[14.5px] text-[#0F172A] mb-3">Investment summary</h2>
                <div class="flex flex-col gap-2 max-h-[360px] overflow-y-auto">
                    @forelse ($holdings as $holding)
                        <div class="flex items-center justify-between gap-2 border-b border-[#F1F5F9] last:border-0 pb-2 last:pb-0">
                            <div class="min-w-0">
                                <p class="text-[13px] font-semibold text-[#0F172A] truncate">{{ $holding->plan->title ?? 'Deleted plan' }}</p>
                                <p class="text-[11.5px] text-[#94A3B8]">{{ $holding->purchased_at?->format('d M Y') }} · {{ ucfirst($holding->status) }}</p>
                            </div>
                            <span class="text-[13px] font-mono font-bold text-[#0F172A] shrink-0">₹{{ number_format($holding->invested_amount, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-[13px] text-[#94A3B8] italic">No plan holdings yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Referral details --}}
            <div id="referrals" class="bg-white rounded-xl border border-[#E5E9EB] p-5 scroll-mt-6">
                <h2 class="font-poppins font-bold text-[14.5px] text-[#0F172A] mb-3">Referral details ({{ $referrals->count() }})</h2>
                <div class="flex flex-col gap-2 max-h-[360px] overflow-y-auto">
                    @forelse ($referrals as $ref)
                        <div class="flex items-center justify-between gap-2 border-b border-[#F1F5F9] last:border-0 pb-2 last:pb-0">
                            <p class="text-[13px] font-semibold text-[#0F172A] truncate">{{ $ref->name ?: $ref->phone ?: '—' }}</p>
                            <span class="text-[11.5px] text-[#94A3B8] shrink-0">{{ $ref->created_at?->format('d M Y') }}</span>
                        </div>
                    @empty
                        <p class="text-[13px] text-[#94A3B8] italic">No referrals yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Recent deposits/withdrawals --}}
            <div class="bg-white rounded-xl border border-[#E5E9EB] p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-poppins font-bold text-[14.5px] text-[#0F172A]">Recent deposits</h2>
                    <a href="{{ route('admin.deposits', ['phone' => $user->phone]) }}" class="text-[11.5px] font-bold text-[#0A5C66] hover:underline">All deposits</a>
                </div>
                <div class="flex flex-col gap-2 max-h-[280px] overflow-y-auto">
                    @forelse ($recentDeposits as $d)
                        <div class="flex items-center justify-between gap-2 border-b border-[#F1F5F9] last:border-0 pb-2 last:pb-0">
                            <p class="text-[12.5px] text-[#334155]">{{ $d->submitted_at?->format('d M, h:i A') }} · <span class="uppercase font-bold text-[10.5px]">{{ $d->status }}</span></p>
                            <span class="text-[13px] font-mono font-bold text-[#0F172A]">₹{{ number_format($d->amount, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-[13px] text-[#94A3B8] italic">No deposits yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-xl border border-[#E5E9EB] p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-poppins font-bold text-[14.5px] text-[#0F172A]">Recent withdrawals</h2>
                    <a href="{{ route('admin.withdrawals', ['phone' => $user->phone]) }}" class="text-[11.5px] font-bold text-[#0A5C66] hover:underline">All withdrawals</a>
                </div>
                <div class="flex flex-col gap-2 max-h-[280px] overflow-y-auto">
                    @forelse ($recentWithdrawals as $w)
                        <div class="flex items-center justify-between gap-2 border-b border-[#F1F5F9] last:border-0 pb-2 last:pb-0">
                            <p class="text-[12.5px] text-[#334155]">{{ $w->submitted_at?->format('d M, h:i A') }} · <span class="uppercase font-bold text-[10.5px]">{{ $w->status }}</span></p>
                            <span class="text-[13px] font-mono font-bold text-[#0F172A]">₹{{ number_format($w->amount, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-[13px] text-[#94A3B8] italic">No withdrawals yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent activity: wallet ledger + admin audit trail on this user --}}
        <div class="bg-white rounded-xl border border-[#E5E9EB] p-5">
            <h2 class="font-poppins font-bold text-[14.5px] text-[#0F172A] mb-3">Recent activity</h2>
            <div class="flex flex-col gap-2">
                @forelse ($recentTransactions as $t)
                    <div class="flex items-center justify-between gap-2 border-b border-[#F1F5F9] last:border-0 pb-2 last:pb-0">
                        <p class="text-[12.5px] text-[#334155]">{{ $t->created_at?->format('d M Y, h:i A') }} · {{ $t->typeLabel() }}</p>
                        <span class="text-[13px] font-mono font-bold {{ $t->direction === 'credit' ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $t->direction === 'credit' ? '+' : '-' }}₹{{ number_format($t->amount, 2) }}
                        </span>
                    </div>
                @empty
                    <p class="text-[13px] text-[#94A3B8] italic">No wallet activity yet.</p>
                @endforelse

                @foreach ($recentAudit as $a)
                    <div class="flex items-center justify-between gap-2 border-b border-[#F1F5F9] last:border-0 pb-2 last:pb-0">
                        <p class="text-[12.5px] text-[#334155]">{{ $a->created_at?->format('d M Y, h:i A') }} · {{ $a->actionLabel() }} by {{ $a->admin_label }}{{ $a->reason ? ' — '.$a->reason : '' }}</p>
                    </div>
                @endforeach
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

@endsection
