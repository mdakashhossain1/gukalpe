@props(['back', 'compact' => false])

{{-- Shared hero banner for the phone/OTP/MPIN auth flow - dark teal gradient,
     faint grid texture. Two variants, extracted from the two real reference
     screenshots rather than one style stretched over both:
       - Expanded (default, the phone-entry/login page): boxed back button,
         bigger centered logo lockup on its own row below it, taller hero.
       - Compact ($compact=true, every screen after login - OTP/MPIN/etc):
         a plain chevron with no button box, a small logo lockup sharing the
         SAME row top-right, much shorter hero. The reference for these
         inner screens shows no language toggle in the hero at all, so it's
         only rendered in the expanded variant. --}}
@if ($compact)
    <div class="relative overflow-hidden shrink-0 h-[92px] bg-gradient-to-br from-[#063e47] via-[#0A5C66] to-[#04242F]">
        <div class="absolute inset-0 opacity-[0.06]" style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 32px 32px;"></div>

        <div class="relative z-10 h-full flex items-center justify-between px-5">
            <a href="{{ $back }}" aria-label="Back" class="w-7 h-7 flex items-center justify-center text-white active:scale-95 transition-all shrink-0">
                <i class="fa-solid fa-chevron-left text-[18px]"></i>
            </a>

            <div class="flex items-center gap-2 shrink-0">
                <div class="w-7 h-7 bg-white rounded-lg flex items-center justify-center shadow-sm shrink-0 p-1">
                    <img src="{{ asset('assets/logo.png') }}" alt="GullakPe" class="w-full h-full object-contain">
                </div>
                <span class="font-poppins font-black text-[16px] tracking-tight text-white leading-none">Gullak<span class="text-[#F59E0B]">Pe</span></span>
            </div>
        </div>
    </div>
@else
    <div class="relative overflow-hidden shrink-0 h-[210px] bg-gradient-to-br from-[#063e47] via-[#0A5C66] to-[#04242F]">
        {{-- Subtle grid background pattern --}}
        <div class="absolute inset-0 opacity-[0.06]" style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 32px 32px;"></div>
        {{-- Top-right ambient glow --}}
        <div class="absolute -top-14 -right-10 w-[200px] h-[200px] bg-[#3FEA8A]/[0.10] rounded-full blur-[50px] pointer-events-none"></div>

        {{-- Top-left back button --}}
        <a href="{{ $back }}" aria-label="Back"
            class="absolute top-6 left-5 z-10 w-11 h-11 rounded-2xl bg-white shadow-sm flex items-center justify-center text-slate-800 active:scale-95 transition-all hover:shadow-md">
            <i class="fa-solid fa-chevron-left text-[14px]"></i>
        </a>

        {{-- Top-right language toggle - only the expanded/entry-page hero has
             room for it; not present in the compact inner-page reference. --}}
        <button type="button" onclick="window.toggleLanguage && window.toggleLanguage()" aria-label="Switch language"
            class="absolute top-6 right-5 z-10 w-11 h-11 rounded-2xl bg-white shadow-sm flex items-center justify-center text-slate-800 active:scale-95 transition-all hover:shadow-md">
            <i class="bi bi-translate text-[15px]"></i>
            <span data-current-lang class="absolute -bottom-1 -right-1 bg-[#F59E0B] text-white text-[8px] font-black px-1 rounded-full leading-tight border-2 border-white">EN</span>
        </button>

        {{-- Horizontally Centered Brand Logo & Typography --}}
        <div class="absolute inset-x-0 bottom-12 sm:bottom-14 z-10 flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="w-12 h-12 sm:w-[54px] sm:h-[54px] bg-white rounded-2xl flex items-center justify-center shadow-md shrink-0 p-1.5">
                <img src="{{ asset('assets/logo.png') }}" alt="GullakPe" class="w-full h-full object-contain">
            </div>
            <span class="font-poppins font-black text-[clamp(26px,7vw,31px)] tracking-tight text-white leading-none">Gullak<span class="text-[#F59E0B]">Pe</span></span>
        </div>
    </div>
@endif
