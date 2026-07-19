@extends('layouts.auth-hero')

@section('title', 'Verify your phone number')

@section('content')

    <div class="h-screen flex flex-col bg-white overflow-hidden">

        <x-auth-hero :back="route('login')" />

        <!-- Sheet -->
        <div class="relative -mt-10 flex-1 min-h-0 bg-white rounded-t-[36px] overflow-y-auto">
            <div class="px-6 pt-9 pb-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h1 class="text-[27px] font-black text-[#1a153a] tracking-tight font-poppins leading-tight">Verify phone number</h1>
                        <p class="text-[14px] text-slate-500 font-medium mt-2 leading-relaxed max-w-[300px]">
                            <span>Enter the 6-digit OTP sent to</span> <span class="font-bold text-[#1a153a]">+91 ******{{ substr($phone, -4) }}</span>
                        </p>
                    </div>
                    <button type="button" onclick="window.toggleLanguage && window.toggleLanguage()" aria-label="Switch language"
                        class="relative w-9 h-9 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 shrink-0 mt-1 active:scale-95 transition-all hover:text-[#0A5C66] hover:border-[#0A5C66]/25">
                        <i class="bi bi-translate text-[14px]"></i>
                        <span data-current-lang class="absolute -bottom-1 -right-1 bg-[#0A5C66] text-white text-[7px] font-black px-1 rounded-full leading-tight border-2 border-white">EN</span>
                    </button>
                </div>

                <div class="mt-4">
                    <a href="{{ route('login') }}" class="text-[#0A5C66] text-[13px] font-bold hover:underline">Edit phone number</a>
                </div>

                @if (session('demo_otp'))
                    <div class="flex items-start gap-2.5 bg-amber-50 border border-amber-200 rounded-[16px] p-4 mt-4">
                        <i class="fa-solid fa-flask text-amber-600 mt-0.5"></i>
                        <p class="text-[13px] text-amber-800 font-semibold leading-relaxed">
                            <span>Demo mode &mdash; no SMS gateway is configured, so here's the code directly:</span> <span class="font-black tracking-widest">{{ session('demo_otp') }}</span>
                        </p>
                    </div>
                @endif

                <form id="verify-otp-form" method="POST" action="{{ route('login.verify-otp.submit') }}" class="flex flex-col gap-6 mt-7">
                    @csrf

                    <x-pin-input name="otp" :length="6" :autofocus="true" aria-label="6-digit OTP" />
                    @error('otp')
                        <p class="text-[12px] font-semibold text-red-500 -mt-3">{{ $message }}</p>
                    @enderror
                </form>

                <form method="POST" action="{{ route('login.resend-otp') }}" class="mt-4 text-center">
                    @csrf
                    <button type="submit" class="text-[14px] font-bold text-[#0A5C66] hover:underline">Resend OTP</button>
                </form>
            </div>
        </div>

        <!-- Bottom action bar -->
        <div class="shrink-0 bg-white border-t border-slate-100 px-6 pt-4 pb-6">
            <button type="submit" form="verify-otp-form" class="btn-shimmer-cta w-full h-[56px] rounded-full bg-[#0A5C66] text-white font-bold text-[16px] hover:bg-[#0E7481] active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                Verify OTP <i class="fa-solid fa-arrow-right text-[14px]"></i>
            </button>
        </div>
    </div>

@endsection
