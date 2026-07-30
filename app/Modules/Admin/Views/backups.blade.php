@extends('layouts.admin')

@section('title', 'Backups')

@section('content')

@php
    $fmtSize = function ($bytes) {
        if ($bytes === null) return '—';
        $u = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($u) - 1) { $bytes /= 1024; $i++; }
        return round($bytes, 1).' '.$u[$i];
    };
@endphp

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="backups" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Backups" />

        <div class="px-6 md:px-10 py-8 md:py-10">

            <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Database backups</h1>
            <p class="text-[13.5px] text-[#64748B] mb-6">Create and download point-in-time copies of the database. Restore overwrites the live database (a pre-restore snapshot is saved automatically).</p>

            @if (session('success'))
                <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-[13px] font-semibold px-4 py-2.5">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-[13px] font-semibold px-4 py-2.5">{{ session('error') }}</div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                <div class="flex items-center gap-4 bg-white rounded-xl border border-[#E5E9EB] px-5 py-3.5">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-[#94A3B8]">Current database</p>
                        <p class="text-[15px] font-black text-[#0F172A] font-poppins">{{ $isSqlite ? $fmtSize($dbSize).' · SQLite' : 'MySQL / other' }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.backups.create') }}">
                    @csrf
                    <button type="submit" class="h-10 px-5 rounded-lg bg-[#0F172A] text-white font-semibold text-[13.5px] hover:bg-[#1E293B] transition-colors active:scale-[0.99] flex items-center gap-2">
                        <i class="fa-solid fa-plus text-[12px]"></i> Create backup now
                    </button>
                </form>
            </div>

            @unless ($isSqlite)
                <div class="mb-6 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-[12.5px] font-semibold px-4 py-2.5">This deployment isn't using SQLite. One-click backup/restore here supports SQLite only — use <code>mysqldump</code> for MySQL.</div>
            @endunless

            <div class="bg-white rounded-xl border border-[#E5E9EB] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[640px]">
                        <thead>
                            <tr class="bg-[#F8FAFC] border-b border-[#E5E9EB]">
                                <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Backup file</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Size</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B]">Created</th>
                                <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wide text-[#64748B] text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($backups as $b)
                                <tr class="border-b border-[#F1F5F9] last:border-0 hover:bg-[#F8FAFC] transition-colors">
                                    <td class="px-5 py-3 text-[12.5px] font-mono text-[#0F172A]">
                                        {{ $b['name'] }}
                                        @if (str_starts_with($b['name'], 'pre-restore-'))
                                            <span class="ml-1 text-[10px] font-bold uppercase text-amber-600">snapshot</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-[13px] text-[#334155] text-right whitespace-nowrap">{{ $fmtSize($b['size']) }}</td>
                                    <td class="px-4 py-3 text-[12.5px] text-[#64748B] whitespace-nowrap">{{ $b['modified']->format('d M Y, H:i') }}</td>
                                    <td class="px-5 py-3 text-right whitespace-nowrap">
                                        <div class="inline-flex gap-2 justify-end">
                                            <a href="{{ route('admin.backups.download', $b['name']) }}" class="h-8 px-3 rounded-lg border border-[#CBD5E1] text-[#334155] text-[12px] font-bold hover:bg-[#F1F5F9] transition-colors">Download</a>
                                            @if ($canRestore)
                                                <form method="POST" action="{{ route('admin.backups.restore', $b['name']) }}" onsubmit="return confirm('Restore this backup? It OVERWRITES the current database. A snapshot of the current DB will be saved first.');">
                                                    @csrf
                                                    <button type="submit" class="h-8 px-3 rounded-lg border border-amber-300 text-amber-700 text-[12px] font-bold hover:bg-amber-50 transition-colors">Restore</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('admin.backups.delete', $b['name']) }}" onsubmit="return confirm('Delete this backup file?');">
                                                @csrf
                                                <button type="submit" class="h-8 px-3 rounded-lg border border-red-200 text-red-600 text-[12px] font-bold hover:bg-red-50 transition-colors">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-8 text-center text-[13.5px] text-[#94A3B8] italic">No backups yet. Create your first one.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="text-[11.5px] text-[#94A3B8] mt-4"><i class="fa-solid fa-circle-info mr-1"></i> Scheduled (daily/weekly/monthly) backups can be automated via <code>php artisan schedule:run</code> — not enabled by default. Ask to wire this up.</p>

        </div>
    </main>
</div>

@endsection
