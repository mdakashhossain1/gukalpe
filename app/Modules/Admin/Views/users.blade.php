@extends('layouts.admin')

@section('title', 'Users')

@section('content')

{{-- Self-hosted vanilla datatable (no jQuery) - same library the plans list
     uses. Files live in public/libs/simple-datatables/. Gives the users
     table built-in search + pagination + column sorting. --}}
<link rel="stylesheet" href="{{ asset('libs/simple-datatables/style.css') }}">
<style>
    #users-table-card .datatable-top { padding: 0 0 14px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    #users-table-card .datatable-bottom { padding: 14px 0 0; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    #users-table-card .datatable-search input {
        height: 38px; border: 1px solid #E5E9EB; border-radius: 8px; padding: 0 12px;
        font-size: 13px; color: #0F172A; outline: none; min-width: 220px;
    }
    #users-table-card .datatable-search input:focus { border-color: #0A5C66; box-shadow: 0 0 0 3px rgba(10,92,102,.12); }
    #users-table-card .datatable-selector { height: 38px; border: 1px solid #E5E9EB; border-radius: 8px; padding: 0 8px; font-size: 13px; color: #334155; }
    #users-table-card .datatable-info { font-size: 12.5px; color: #64748B; }
    #users-table-card .datatable-container { overflow-x: auto; border: 0; }
    #users-table-card table.datatable-table { min-width: 980px; }
    #users-table-card .datatable-pagination a { border-radius: 8px; padding: 6px 11px; font-size: 12.5px; font-weight: 600; color: #334155; }
    #users-table-card .datatable-pagination a:hover { background: #F1F5F9; }
    #users-table-card .datatable-pagination .datatable-active a { background: #0A5C66; color: #fff; }

    /* Increase/Decrease selector - explicit CSS so the active state is always
       visible regardless of the Tailwind build. */
    .wallet-dir-btn {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        height: 46px; border: 1.5px solid #E5E9EB; border-radius: 10px;
        font-size: 14px; font-weight: 700; color: #64748B; background: #fff;
        cursor: pointer; transition: border-color .15s, background-color .15s, color .15s;
    }
    .wallet-dir-btn:hover { background: #F8FAFC; }
    .wallet-dir-btn.is-active[data-dir="increase"] { border-color: #10B981; background: #ECFDF5; color: #047857; }
    .wallet-dir-btn.is-active[data-dir="decrease"] { border-color: #EF4444; background: #FEF2F2; color: #B91C1C; }

    /* Actions dropdown menu - one shared floating panel (fixed positioning,
       not per-row) so it can never get clipped by the table's own
       overflow-x-auto scroll container, the way an absolutely-positioned
       per-row menu would be. */
    .ad-item {
        display: flex; align-items: center; gap: 10px; width: 100%;
        padding: 9px 14px; font-size: 13px; font-weight: 600; color: #334155;
        text-align: left; background: none; border: 0; cursor: pointer;
        transition: background-color .1s;
    }
    .ad-item:hover { background: #F8FAFC; }
    .ad-item i { width: 16px; text-align: center; font-size: 12px; color: #64748B; }
    .ad-item.is-disabled { opacity: .4; pointer-events: none; }
    .ad-item.is-danger { color: #DC2626; }
    .ad-item.is-danger i { color: #DC2626; }
    .ad-item.is-success { color: #047857; }
    .ad-item.is-success i { color: #047857; }
</style>

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="users" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Users" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <div class="flex items-start justify-between gap-3 mb-6">
            <div>
                <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Users</h1>
                <p class="text-[13.5px] text-[#64748B]">Every registered account. Phone signups use a synthetic email, so their Email shows as "Phone signup".</p>
            </div>
            <span class="shrink-0 h-9 px-3.5 rounded-lg bg-[#0A5C66]/10 text-[#0A5C66] font-bold text-[13px] flex items-center gap-2">
                <i class="fa-solid fa-users text-[12px]"></i> {{ number_format($users->count()) }} total
            </span>
        </div>

        <div class="bg-white rounded-xl border border-[#E5E9EB] p-4" id="users-table-card">
            <div class="overflow-x-auto">
                <table id="users-table" class="w-full text-left border-collapse min-w-[980px]">
                    <thead>
                        <tr class="bg-[#F8FAFC] border-b border-[#E5E9EB]">
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">User</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Phone</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Email</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Wallet</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Referral code</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Referrals</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Joined</th>
                            <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr class="border-b border-[#F1F5F9] last:border-0 hover:bg-[#F8FAFC] transition-colors">
                                <td class="px-4 py-3 align-middle">
                                    <div class="flex items-center gap-3">
                                        @if ($user->avatar)
                                            <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="w-9 h-9 rounded-full object-cover shrink-0 border border-[#E5E9EB]" referrerpolicy="no-referrer">
                                        @else
                                            <div class="w-9 h-9 rounded-full bg-[#0A5C66]/10 text-[#0A5C66] font-bold text-[13px] flex items-center justify-center shrink-0 uppercase">
                                                {{ mb_substr($user->name ?: '?', 0, 1) }}
                                            </div>
                                        @endif
                                        <div class="flex flex-col gap-0.5 min-w-0">
                                            <span class="text-[13.5px] font-bold text-[#0F172A]">{{ $user->name ?: '—' }}</span>
                                            @if ($user->isBanned())
                                                <span class="w-fit text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border bg-red-50 text-red-700 border-red-200">
                                                    <i class="fa-solid fa-ban text-[9px]"></i> Banned
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-middle text-[13px] font-mono text-[#334155] whitespace-nowrap">{{ $user->phone ?: '—' }}</td>
                                <td class="px-4 py-3 align-middle text-[13px] text-[#334155]">
                                    @if ($user->hasRealEmail())
                                        {{ $user->email }}
                                    @else
                                        <span class="text-[11.5px] font-semibold text-[#94A3B8] italic">Phone signup</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-middle text-[13px] font-mono font-semibold text-[#0F172A] whitespace-nowrap">₹{{ number_format($user->wallet_balance, 2) }}</td>
                                <td class="px-4 py-3 align-middle whitespace-nowrap">
                                    @if ($user->referral_code)
                                        <span class="text-[11.5px] font-mono font-bold px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 border border-slate-200">{{ $user->referral_code }}</span>
                                    @else
                                        <span class="text-[13px] text-[#94A3B8]">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-middle text-[13px] text-[#334155]">{{ $user->referrals_count }}</td>
                                <td class="px-4 py-3 align-middle text-[13px] text-[#64748B] whitespace-nowrap" data-order="{{ $user->created_at?->timestamp }}">
                                    {{ $user->created_at?->format('d M Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 align-middle text-right whitespace-nowrap">
                                    <button type="button"
                                        data-actions-toggle
                                        data-name="{{ $user->name ?: $user->phone ?: ('#'.$user->id) }}"
                                        data-phone="{{ $user->phone }}"
                                        data-balance="{{ number_format($user->wallet_balance, 2) }}"
                                        data-banned="{{ $user->isBanned() ? '1' : '0' }}"
                                        data-profile-url="{{ route('admin.users.show', $user) }}"
                                        data-transactions-url="{{ $user->phone ? route('admin.transactions', ['phone' => $user->phone]) : '' }}"
                                        data-deposits-url="{{ $user->phone ? route('admin.deposits', ['phone' => $user->phone]) : '' }}"
                                        data-withdrawals-url="{{ $user->phone ? route('admin.withdrawals', ['phone' => $user->phone]) : '' }}"
                                        data-toggle-ban-url="{{ route('admin.users.toggle-ban', $user) }}"
                                        class="h-9 px-3.5 rounded-lg border border-[#E5E9EB] text-[#334155] text-[12.5px] font-bold hover:bg-[#F8FAFC] transition-colors active:scale-95 inline-flex items-center gap-1.5">
                                        Actions <i class="fa-solid fa-chevron-down text-[9px]"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-[13.5px] text-[#94A3B8] italic">No users yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        </div>
    </main>
</div>

{{-- One shared adjust-wallet modal for the whole table; each row's "Adjust"
     button just fills in the phone/name. Posts to the same real endpoint the
     old standalone Wallet adjustment page used (admin.wallet-tools.adjust). --}}
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
                <p class="text-[11.5px] text-[#94A3B8] mt-1.5">Recorded in the wallet ledger and the admin security log.</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" data-wallet-close class="h-10 px-4 rounded-lg border border-slate-200 text-slate-600 font-semibold text-[13.5px] hover:bg-slate-50 transition-colors">Cancel</button>
                <button type="submit" class="h-10 px-5 rounded-lg bg-brand text-white font-semibold text-[13.5px] hover:bg-brand-light transition-colors active:scale-[0.99]">Apply adjustment</button>
            </div>
        </form>
    </div>
</div>

{{-- Ban modal - required Reason field replaces the old plain JS confirm().
     Unban has no modal since there's nothing to justify about restoring
     access (handled inline above). --}}
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
                <p class="text-[11.5px] text-[#94A3B8] mt-1.5">Recorded in the audit log and shown to the user in their ban notification.</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" data-ban-close class="h-10 px-4 rounded-lg border border-slate-200 text-slate-600 font-semibold text-[13.5px] hover:bg-slate-50 transition-colors">Cancel</button>
                <button type="submit" class="h-10 px-5 rounded-lg bg-red-600 text-white font-semibold text-[13.5px] hover:bg-red-700 transition-colors active:scale-[0.99]">Ban user</button>
            </div>
        </form>
    </div>
</div>

{{-- Send notification modal - posts to the existing admin push-notification
     endpoint with target=specific and the clicked row's phone prefilled. --}}
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

{{-- Hidden form used only for the one-click Unban path from the Actions
     menu - Ban goes through #ban-modal (requires a reason), Unban doesn't
     (nothing to justify about restoring access), so it just submits. --}}
<form id="unban-quick-form" method="POST" action="" class="hidden">
    @csrf
</form>

{{-- Actions dropdown menu (client item 1: "Actions -> View Profile, Wallet
     Management, Transactions, Investments, Deposits, Withdrawals, Referral
     Details, Send Notification, Ban / Unban" - each its own distinct
     action). One shared panel, repositioned and repopulated with the
     clicked row's data/URLs on open - see the note on .ad-item above for
     why this isn't a per-row absolutely-positioned menu. --}}
<div id="actions-dropdown" class="hidden fixed z-[550] w-56 bg-white rounded-xl border border-[#E5E9EB] shadow-lg py-1.5">
    <a id="ad-view-profile" href="#" class="ad-item"><i class="fa-solid fa-user"></i> View Profile</a>
    <button type="button" id="ad-wallet" data-adjust-wallet class="ad-item"><i class="fa-solid fa-wallet"></i> Wallet Management</button>
    <a id="ad-transactions" href="#" class="ad-item"><i class="fa-solid fa-receipt"></i> Transactions</a>
    <a id="ad-investments" href="#" class="ad-item"><i class="fa-solid fa-chart-line"></i> Investments</a>
    <a id="ad-deposits" href="#" class="ad-item"><i class="fa-solid fa-money-bill-transfer"></i> Deposits</a>
    <a id="ad-withdrawals" href="#" class="ad-item"><i class="fa-solid fa-money-bill-transfer fa-flip-horizontal"></i> Withdrawals</a>
    <a id="ad-referrals" href="#" class="ad-item"><i class="fa-solid fa-user-group"></i> Referral Details</a>
    <button type="button" id="ad-notify" data-send-notif class="ad-item"><i class="fa-solid fa-paper-plane"></i> Send Notification</button>
    <div class="my-1 border-t border-[#F1F5F9]"></div>
    <button type="button" id="ad-ban" class="ad-item is-danger">
        <i id="ad-ban-icon" class="fa-solid fa-ban"></i> <span id="ad-ban-label">Ban user</span>
    </button>
</div>

@if ($users->isNotEmpty())
    <script src="{{ asset('libs/simple-datatables/simple-datatables.js') }}"></script>
@endif
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof simpleDatatables !== 'undefined' && document.querySelector('#users-table tbody tr td:not([colspan])')) {
            new simpleDatatables.DataTable('#users-table', {
                searchable: true,
                paging: true,
                perPage: 15,
                perPageSelect: [15, 25, 50, 100],
                sortable: true,
                // Last column is the Adjust button - sorting it is meaningless.
                columns: [{ select: 7, sortable: false }],
                labels: {
                    placeholder: 'Search users...',
                    perPage: '{select} per page',
                    noRows: 'No users found',
                    noResults: 'No users match your search',
                    info: 'Showing {start}–{end} of {rows} users',
                },
            });
        }

        // Adjust-wallet modal. Delegated click so it keeps working after the
        // datatable re-renders rows on search/paging.
        var modal = document.getElementById('wallet-modal');
        if (!modal) return;
        var nameEl = document.getElementById('wallet-modal-name');
        var phoneEl = document.getElementById('wallet-modal-phone');
        var balanceEl = document.getElementById('wallet-modal-balance');
        var phoneInput = document.getElementById('wallet-modal-phone-input');
        var amountInput = document.getElementById('wallet-modal-amount');
        var reasonInput = document.getElementById('wallet-modal-reason');
        var dirInput = document.getElementById('wallet-direction');
        var dirBtns = modal.querySelectorAll('.wallet-dir-btn');

        function setDirection(dir) {
            dirInput.value = dir;
            dirBtns.forEach(function (b) {
                b.classList.toggle('is-active', b.getAttribute('data-dir') === dir);
            });
        }
        dirBtns.forEach(function (b) {
            b.addEventListener('click', function () { setDirection(b.getAttribute('data-dir')); });
        });

        function openModal(btn) {
            phoneInput.value = btn.getAttribute('data-phone') || '';
            nameEl.textContent = btn.getAttribute('data-name') || '—';
            phoneEl.textContent = btn.getAttribute('data-phone') || '—';
            balanceEl.textContent = '₹' + (btn.getAttribute('data-balance') || '0.00');
            amountInput.value = '';
            reasonInput.value = '';
            setDirection('increase');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            setTimeout(function () { amountInput.focus(); }, 30);
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-adjust-wallet]');
            if (btn) { openModal(btn); return; }
            if (e.target.closest('[data-wallet-close]')) closeModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });

        // Ban modal - same open/close pattern as the wallet modal above.
        var banModal = document.getElementById('ban-modal');
        if (!banModal) return;
        var banNameEl = document.getElementById('ban-modal-name');
        var banForm = document.getElementById('ban-modal-form');
        var banReasonInput = document.getElementById('ban-modal-reason');

        function openBanModal(btn) {
            banForm.action = btn.getAttribute('data-action') || '';
            banNameEl.textContent = btn.getAttribute('data-name') || '—';
            banReasonInput.value = '';
            banModal.classList.remove('hidden');
            banModal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            setTimeout(function () { banReasonInput.focus(); }, 30);
        }

        function closeBanModal() {
            banModal.classList.add('hidden');
            banModal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('click', function (e) {
            var banBtn = e.target.closest('[data-ban-user]');
            if (banBtn) { openBanModal(banBtn); return; }
            if (e.target.closest('[data-ban-close]')) closeBanModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !banModal.classList.contains('hidden')) closeBanModal();
        });

        // Send-notification modal - same open/close pattern as the two above.
        var notifModal = document.getElementById('notif-modal');
        var notifNameEl = document.getElementById('notif-modal-name');
        var notifPhoneInput = document.getElementById('notif-modal-phone-input');

        function openNotifModal(btn) {
            notifPhoneInput.value = btn.getAttribute('data-phone') || '';
            notifNameEl.textContent = btn.getAttribute('data-name') || '—';
            notifModal.classList.remove('hidden');
            notifModal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }
        function closeNotifModal() {
            notifModal.classList.add('hidden');
            notifModal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }
        document.addEventListener('click', function (e) {
            var notifBtn = e.target.closest('[data-send-notif]');
            if (notifBtn) { openNotifModal(notifBtn); return; }
            if (e.target.closest('[data-notif-close]')) closeNotifModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !notifModal.classList.contains('hidden')) closeNotifModal();
        });

        // Actions dropdown (client item 1's 9-item menu). One shared
        // fixed-position panel - repositioned under the clicked row's
        // "Actions" button and repopulated with that row's URLs/data every
        // time it opens, rather than one dropdown per row (which would get
        // clipped by the table's overflow-x-auto wrapper).
        var actionsBtn = null; // the row's trigger button currently driving the open menu
        var dropdown = document.getElementById('actions-dropdown');
        var adViewProfile = document.getElementById('ad-view-profile');
        var adWallet = document.getElementById('ad-wallet');
        var adTransactions = document.getElementById('ad-transactions');
        var adInvestments = document.getElementById('ad-investments');
        var adDeposits = document.getElementById('ad-deposits');
        var adWithdrawals = document.getElementById('ad-withdrawals');
        var adReferrals = document.getElementById('ad-referrals');
        var adNotify = document.getElementById('ad-notify');
        var adBan = document.getElementById('ad-ban');
        var adBanIcon = document.getElementById('ad-ban-icon');
        var adBanLabel = document.getElementById('ad-ban-label');
        var unbanForm = document.getElementById('unban-quick-form');

        function setLink(el, url, disabled) {
            el.href = url || '#';
            el.classList.toggle('is-disabled', !!disabled);
        }

        function openActionsMenu(btn) {
            actionsBtn = btn;
            var phone = btn.getAttribute('data-phone') || '';
            var name = btn.getAttribute('data-name') || '';
            var banned = btn.getAttribute('data-banned') === '1';
            var profileUrl = btn.getAttribute('data-profile-url') || '#';

            setLink(adViewProfile, profileUrl, false);
            setLink(adTransactions, btn.getAttribute('data-transactions-url'), !phone);
            setLink(adInvestments, profileUrl + '#investments', false);
            setLink(adDeposits, btn.getAttribute('data-deposits-url'), !phone);
            setLink(adWithdrawals, btn.getAttribute('data-withdrawals-url'), !phone);
            setLink(adReferrals, profileUrl + '#referrals', false);

            adWallet.setAttribute('data-phone', phone);
            adWallet.setAttribute('data-name', name);
            adWallet.setAttribute('data-balance', btn.getAttribute('data-balance') || '0.00');
            adWallet.classList.toggle('is-disabled', !phone);

            adNotify.setAttribute('data-phone', phone);
            adNotify.setAttribute('data-name', name);
            adNotify.classList.toggle('is-disabled', !phone);

            adBan.setAttribute('data-action', btn.getAttribute('data-toggle-ban-url') || '');
            adBan.setAttribute('data-name', name);
            adBan.setAttribute('data-banned', banned ? '1' : '0');
            adBanLabel.textContent = banned ? 'Unban user' : 'Ban user';
            adBanIcon.className = banned ? 'fa-solid fa-unlock' : 'fa-solid fa-ban';
            adBan.classList.toggle('is-danger', !banned);
            adBan.classList.toggle('is-success', banned);

            dropdown.classList.remove('hidden');
            // Position after it's visible so offsetWidth is real.
            var rect = btn.getBoundingClientRect();
            var menuWidth = dropdown.offsetWidth;
            var left = Math.max(8, Math.min(rect.right - menuWidth, window.innerWidth - menuWidth - 8));
            var top = rect.bottom + 6;
            if (top + dropdown.offsetHeight > window.innerHeight - 8) {
                top = Math.max(8, rect.top - dropdown.offsetHeight - 6);
            }
            dropdown.style.left = left + 'px';
            dropdown.style.top = top + 'px';
        }

        function closeActionsMenu() {
            dropdown.classList.add('hidden');
            actionsBtn = null;
        }

        document.addEventListener('click', function (e) {
            var toggleBtn = e.target.closest('[data-actions-toggle]');
            if (toggleBtn) {
                if (actionsBtn === toggleBtn) { closeActionsMenu(); return; }
                openActionsMenu(toggleBtn);
                return;
            }
            if (dropdown.contains(e.target)) {
                // Let ad-wallet/ad-notify (handled by their own delegated
                // listeners above) and ad-ban (below) do their thing, then
                // close the menu - links navigate away on their own.
                if (e.target.closest('#ad-wallet') || e.target.closest('#ad-notify') || e.target.closest('#ad-ban')) {
                    closeActionsMenu();
                }
                return;
            }
            closeActionsMenu();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && actionsBtn) closeActionsMenu();
        });
        window.addEventListener('scroll', closeActionsMenu, true);
        window.addEventListener('resize', closeActionsMenu);

        // Ban/Unban from the Actions menu: Ban opens the reason modal
        // (reuses openBanModal() above, passing #ad-ban itself since it now
        // carries the same data-action/data-name attributes); Unban has
        // nothing to justify, so it submits straight away behind a plain
        // confirm(), same as the old per-row Unban button did.
        adBan.addEventListener('click', function () {
            var banned = adBan.getAttribute('data-banned') === '1';
            if (banned) {
                if (!confirm('Unban this user? They will be able to log in again.')) return;
                unbanForm.action = adBan.getAttribute('data-action') || '';
                unbanForm.submit();
            } else {
                openBanModal(adBan);
            }
        });
    });
</script>

@endsection
