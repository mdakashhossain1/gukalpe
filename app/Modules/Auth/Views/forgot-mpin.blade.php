@extends('layouts.auth-hero')

@section('title', 'Reset MPIN')

@section('content')

    <div class="h-screen flex flex-col bg-white overflow-hidden">

        <x-auth-hero :back="route('login.mpin')" />

        <!-- Sheet -->
        <div class="relative -mt-10 flex-1 min-h-0 bg-white rounded-t-[36px] overflow-y-auto">
            <div class="px-6 pt-9 pb-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h1 class="text-[27px] font-black text-[#1a153a] tracking-tight font-poppins leading-tight">Reset MPIN</h1>
                        <p class="text-[14px] text-slate-500 font-medium mt-2 leading-relaxed max-w-[300px]">Enter your registered phone number to receive an OTP.</p>
                    </div>
                    <button type="button" onclick="window.toggleLanguage && window.toggleLanguage()" aria-label="Switch language"
                        class="relative w-9 h-9 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 shrink-0 mt-1 active:scale-95 transition-all hover:text-[#0A5C66] hover:border-[#0A5C66]/25">
                        <i class="bi bi-translate text-[14px]"></i>
                        <span data-current-lang class="absolute -bottom-1 -right-1 bg-[#0A5C66] text-white text-[7px] font-black px-1 rounded-full leading-tight border-2 border-white">EN</span>
                    </button>
                </div>

                <form id="forgot-mpin-form" method="POST" action="{{ route('login.forgot-mpin.submit') }}" class="flex flex-col gap-6 mt-8">
                    @csrf

                    <div>
                        <label for="phone" class="sr-only">Phone number</label>
                        <div class="flex gap-2.5 h-[56px]">
                            <div class="w-[72px] h-full rounded-[16px] bg-slate-100 flex items-center justify-center gap-1.5 shrink-0">
                                <img src="https://flagcdn.com/w20/in.png" class="w-[18px] h-[13px] rounded-[2px] object-cover" alt="IN">
                                <span class="font-bold text-slate-700 text-[15px]">+91</span>
                            </div>
                            <input type="tel" id="phone" name="phone" inputmode="numeric" maxlength="10" required autofocus
                                placeholder="10-digit phone number" value="{{ old('phone') }}"
                                class="flex-1 h-full border-none rounded-[16px] px-4 font-bold text-[16px] tracking-wide text-slate-800 outline-none focus:ring-2 focus:ring-[#0A5C66]/30 transition-colors bg-slate-100 placeholder:text-slate-400 placeholder:font-medium">
                        </div>
                        @error('phone')
                            <p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </form>
            </div>
        </div>

        <!-- Bottom action bar -->
        <div class="shrink-0 bg-white border-t border-slate-100 px-6 pt-4 pb-6">
            <button type="submit" form="forgot-mpin-form" class="btn-shimmer-cta w-full h-[56px] rounded-full bg-[#0A5C66] text-white font-bold text-[16px] hover:bg-[#0E7481] active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                Continue <i class="fa-solid fa-arrow-right text-[14px]"></i>
            </button>
        </div>
    </div>

@endsection
