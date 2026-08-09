@props(['title' => ''])

{{-- Standard dashboard chrome: sidebar (admin-sidebar) on the left, this
     bar pinned to the top of the content column on the right. Every
     authenticated admin page renders this instead of jumping straight from
     the sidebar into page content - the notification bell lives here, not
     floated on its own.

     Desktop-only (hidden md:flex): on mobile, admin-sidebar's own mobile
     header already provides the top chrome (logo, sign out, section nav) -
     stacking this bar underneath it as well duplicated the page title and
     wasted a full extra row of height for no new information. Its bell
     moves into that mobile header instead (see admin-sidebar.blade.php),
     not dropped - hiding it outright would be losing functionality on
     mobile, not adapting for it. --}}
<header class="hidden md:flex sticky top-0 z-30 h-16 shrink-0 items-center justify-between gap-4 px-6 md:px-10 bg-white/95 backdrop-blur border-b border-[#E5E9EB]">
    <span class="text-[14.5px] font-bold text-[#0F172A] truncate">{{ $title }}</span>

    <div class="flex items-center gap-3 shrink-0">
        {{-- Admin panel's own EN/Hindi toggle - same window.toggleLanguage()
             engine the customer-facing app uses, added 2026-08-09 so the
             new field info-tips (x-info-tip) below can actually be read in
             Hindi, not just English. --}}
        <button type="button" onclick="window.toggleLanguage && window.toggleLanguage()" aria-label="Switch language" title="Switch language / भाषा बदलें"
            class="relative w-9 h-9 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-500 transition-all active:scale-95 hover:border-[#0A5C66]/25 hover:text-[#0A5C66]">
            <i class="bi bi-translate text-[15px]"></i>
            <span data-current-lang class="absolute -bottom-1 -right-1 bg-[#0A5C66] text-white text-[8px] font-black px-1 rounded-full leading-tight border-2 border-white">EN</span>
        </button>
        <x-admin-notification-bell id="admin-notif-bell-desktop" />
    </div>
</header>
