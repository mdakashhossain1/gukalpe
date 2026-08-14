@extends('layouts.admin')

@section('title', 'Banners')

@section('content')

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="banners" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Banners" />

        <div class="px-6 md:px-10 py-8 md:py-10">

            <div class="flex items-start justify-between gap-3 mb-6 flex-wrap">
                <div>
                    <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Banners</h1>
                    <p class="text-[13.5px] text-[#64748B]">Marketing banners shown across the app (Home, Offer, Explore, Popup). Higher priority shows first; inactive or out-of-schedule banners are hidden.</p>
                </div>
                <a href="{{ route('admin.banners.create') }}" class="h-10 px-5 rounded-lg bg-[#0F172A] text-white font-semibold text-[13.5px] hover:bg-[#1E293B] transition-colors active:scale-[0.99] flex items-center gap-2 shrink-0">
                    <i class="fa-solid fa-plus text-[12px]"></i> New banner
                </a>
            </div>

            @if (session('success'))
                <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-[13px] font-semibold px-4 py-2.5">{{ session('success') }}</div>
            @endif

            @if ($banners->isEmpty())
                <div class="bg-white rounded-xl border border-[#E5E9EB] p-10 text-center text-[13.5px] text-[#94A3B8] italic">No banners yet. Create your first one.</div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($banners as $banner)
                        @php
                            $now = now();
                            $started = ! $banner->start_date || $banner->start_date <= $now;
                            $ended = $banner->end_date && $banner->end_date < $now;
                            // Auto Status (client item 10.4): purely informational badge
                            // derived from is_active + the schedule window - does not
                            // change is_active itself, admins still control that toggle.
                            $autoStatus = ! $banner->is_active ? ['Hidden', 'bg-slate-200 text-slate-500']
                                : ($ended ? ['Expired', 'bg-red-100 text-red-700']
                                : (! $started ? ['Scheduled', 'bg-amber-100 text-amber-700']
                                : ['Active', 'bg-emerald-100 text-emerald-700']));
                        @endphp
                        <div class="bg-white rounded-xl border border-[#E5E9EB] overflow-hidden flex flex-col">
                            <button type="button" data-banner-preview data-image="{{ $banner->imageUrl() }}" data-title="{{ $banner->title ?: '(untitled)' }}"
                                class="relative bg-[#F1F5F9] aspect-[16/7] overflow-hidden block w-full cursor-zoom-in">
                                <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->title }}" class="w-full h-full object-cover">
                                <span class="absolute top-2 left-2 text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-white/90 text-[#334155] shadow-sm">{{ $banner->placementLabel() }}</span>
                                <span class="absolute top-2 right-2 text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full shadow-sm {{ $autoStatus[1] }}">{{ $autoStatus[0] }}</span>
                            </button>
                            <div class="p-4 flex flex-col gap-2 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-[13.5px] font-bold text-[#0F172A] truncate">{{ $banner->title ?: '(untitled)' }}</p>
                                    <span class="text-[11px] font-semibold text-[#94A3B8] shrink-0">Priority {{ $banner->priority }}</span>
                                </div>
                                @if ($banner->redirect_link)
                                    <p class="text-[11.5px] text-[#64748B] truncate"><i class="fa-solid fa-link text-[10px] mr-1"></i>{{ $banner->redirect_link }}</p>
                                @endif
                                @if ($banner->start_date || $banner->end_date)
                                    <p class="text-[11px] text-[#94A3B8]">{{ optional($banner->start_date)->format('d M Y') ?: 'Always' }} → {{ optional($banner->end_date)->format('d M Y') ?: 'No end' }}</p>
                                @endif
                                <div class="mt-auto pt-2 flex items-center gap-2 flex-wrap">
                                    <a href="{{ route('admin.banners.edit', $banner) }}" class="h-9 px-3.5 rounded-lg border border-[#CBD5E1] text-[#334155] text-[12.5px] font-bold hover:bg-[#F1F5F9] transition-colors">Edit</a>
                                    <form method="POST" action="{{ route('admin.banners.toggle-active', $banner) }}">
                                        @csrf
                                        <button type="submit" class="h-9 px-3.5 rounded-lg border border-[#CBD5E1] text-[#334155] text-[12.5px] font-bold hover:bg-[#F1F5F9] transition-colors">{{ $banner->is_active ? 'Hide' : 'Activate' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.banners.duplicate', $banner) }}">
                                        @csrf
                                        <button type="submit" class="h-9 px-3.5 rounded-lg border border-[#CBD5E1] text-[#334155] text-[12.5px] font-bold hover:bg-[#F1F5F9] transition-colors">Duplicate</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.banners.delete', $banner) }}" class="ml-auto" onsubmit="return confirm('Delete this banner?');">
                                        @csrf
                                        <button type="submit" class="h-9 px-3.5 rounded-lg border border-red-200 text-red-600 text-[12.5px] font-bold hover:bg-red-50 transition-colors">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </main>
</div>

{{-- Preview modal (client item 10 "Banner Actions -> Preview") - purely a
     bigger look at the same image already on the card, no server round-trip
     needed since a banner is just an image + redirect link. --}}
<div id="banner-preview-modal" class="hidden fixed inset-0 z-[600] items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/70" data-banner-preview-close></div>
    <div class="relative w-full max-w-2xl bg-white rounded-2xl border border-[#E5E9EB] shadow-xl overflow-hidden">
        <button type="button" data-banner-preview-close class="absolute top-3 right-3 z-10 w-9 h-9 rounded-lg bg-white/90 flex items-center justify-center text-[#64748B] hover:bg-white transition-colors" aria-label="Close">
            <i class="fa-solid fa-xmark text-[15px]"></i>
        </button>
        <img id="banner-preview-image" src="" alt="" class="w-full h-auto">
        <div class="p-4">
            <p id="banner-preview-title" class="text-[14px] font-bold text-[#0F172A]"></p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('banner-preview-modal');
        if (!modal) return;
        var imgEl = document.getElementById('banner-preview-image');
        var titleEl = document.getElementById('banner-preview-title');

        function open(btn) {
            imgEl.src = btn.getAttribute('data-image') || '';
            titleEl.textContent = btn.getAttribute('data-title') || '';
            modal.classList.remove('hidden'); modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }
        function close() {
            modal.classList.add('hidden'); modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-banner-preview]');
            if (btn) { open(btn); return; }
            if (e.target.closest('[data-banner-preview-close]')) close();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
        });
    });
</script>

@endsection
