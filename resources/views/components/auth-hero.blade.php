@props(['back'])

{{-- Shared hero banner for the phone/OTP/MPIN auth flow - teal gradient,
     faint grid texture, back button, and the real logo mark. Every screen
     in the flow renders this identically; only the back-button destination
     changes per screen. --}}
<div class="relative overflow-hidden shrink-0 h-[300px] bg-gradient-to-br from-[#0A5C66] via-[#0A5C66] to-[#04242F]">
    <div class="absolute inset-0 opacity-[0.07]" style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 48px 48px;"></div>
    <div class="absolute -top-16 -right-10 w-[220px] h-[220px] bg-[#3FEA8A]/[0.12] rounded-full blur-[60px] pointer-events-none"></div>

    <a href="{{ $back }}" aria-label="Back"
        class="absolute top-6 left-5 z-10 w-11 h-11 rounded-2xl bg-white flex items-center justify-center text-[#0A5C66] shadow-md active:scale-95 transition-all">
        <i class="fa-solid fa-chevron-left text-[16px]"></i>
    </a>

    <div class="absolute left-5 bottom-16 z-10 flex items-center gap-3">
        <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-md shrink-0 p-1.5">
            <img src="{{ asset('assets/logo.png') }}" alt="GullakPe" class="w-full h-full object-contain">
        </div>
        <span class="font-poppins font-black text-[32px] tracking-tight text-white leading-none">Gullak<span class="text-[#3FEA8A]">Pe</span></span>
    </div>
</div>
