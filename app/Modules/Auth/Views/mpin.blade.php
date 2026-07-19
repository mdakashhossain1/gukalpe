@extends('layouts.auth-hero')

@section('title', 'Enter MPIN')

@section('content')

    <div class="h-screen flex flex-col bg-white overflow-hidden">

        <x-auth-hero :back="route('login')" />

        <!-- Sheet -->
        <div class="relative -mt-10 flex-1 min-h-0 bg-white rounded-t-[36px] overflow-y-auto">
            <div class="px-6 pt-6 pb-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h1 class="text-[27px] font-black text-[#1a153a] tracking-tight font-poppins leading-tight">Enter MPIN</h1>
                        <p class="text-[14px] text-slate-500 font-medium mt-2 leading-relaxed max-w-[300px]">Enter your 4-digit MPIN to log in safely.</p>
                    </div>
                    <button type="button" onclick="window.toggleLanguage && window.toggleLanguage()" aria-label="Switch language"
                        class="relative w-9 h-9 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 shrink-0 mt-1 active:scale-95 transition-all hover:text-[#0A5C66] hover:border-[#0A5C66]/25">
                        <i class="bi bi-translate text-[14px]"></i>
                        <span data-current-lang class="absolute -bottom-1 -right-1 bg-[#0A5C66] text-white text-[7px] font-black px-1 rounded-full leading-tight border-2 border-white">EN</span>
                    </button>
                </div>

                <div class="flex items-center gap-3 mt-4">
                    <span class="font-bold text-[#1a153a] text-[13px] bg-slate-100 px-3 py-1.5 rounded-lg">+91 ******{{ substr($phone, -4) }}</span>
                    <a href="{{ route('login') }}" class="text-[#0A5C66] text-[13px] font-bold hover:underline">Change</a>
                </div>

                <form id="mpin-form" method="POST" action="{{ route('login.mpin.submit') }}" class="flex flex-col gap-6 mt-7">
                    @csrf

                    <div>
                        <x-pin-input name="mpin" :length="4" :autofocus="true" aria-label="MPIN" :auto-submit="true" />
                        @error('mpin')
                            <p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="text-right -mt-3">
                        <a href="{{ route('login.forgot-mpin') }}" class="text-[#0A5C66] text-[13.5px] font-bold hover:underline">Forgot MPIN?</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bottom action bar -->
        <div class="shrink-0 bg-white border-t border-slate-100 px-6 pt-4 pb-6">
            <button type="submit" form="mpin-form" data-loading-text="Verifying..." class="btn-shimmer-cta w-full h-[56px] rounded-full bg-[#0A5C66] text-white font-bold text-[16px] hover:bg-[#0E7481] active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                Login Securely <i class="fa-solid fa-arrow-right text-[14px]"></i>
            </button>
        </div>
    </div>

@endsection
