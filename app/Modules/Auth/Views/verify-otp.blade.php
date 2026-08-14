@extends('layouts.auth-hero')

@section('title', 'Verify your phone number')

@section('content')

    <div class="h-full flex flex-col bg-white overflow-hidden">

        <x-auth-hero :back="route('login')" :compact="true" />

        <!-- Sheet -->
        <div class="relative -mt-4 flex-1 min-h-0 bg-white rounded-t-[28px] overflow-y-auto">
            <div class="px-6 sm:px-7 pt-6 sm:pt-7 pb-4">
                <h1 class="text-[clamp(24px,6vw,29px)] font-black text-[#111827] tracking-tight font-poppins leading-tight">Verify phone number</h1>
                <p class="text-[clamp(13px,3.4vw,14px)] text-slate-500 font-medium mt-1.5 sm:mt-2 leading-relaxed max-w-[300px]">
                    <span>Enter the 6-digit OTP sent to</span> <span class="font-bold text-[#111827]">+91 ******{{ substr($phone, -4) }}</span>
                </p>

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

                    <x-pin-input name="otp" :length="6" :autofocus="true" aria-label="6-digit OTP" :auto-submit="true" />
                    @error('otp')
                        <p class="text-[12px] font-semibold text-red-500 -mt-3">{{ $message }}</p>
                    @enderror
                </form>

                <form method="POST" action="{{ route('login.resend-otp') }}" class="mt-4 text-center">
                    @csrf
                    <button type="submit" data-loading-text="Resending..." class="text-[14px] font-bold text-[#0A5C66] hover:underline">Resend OTP</button>
                </form>
            </div>
        </div>

        <!-- Bottom action bar -->
        <div class="shrink-0 bg-white border-t border-slate-100 px-6 sm:px-7 pt-4 pb-6">
            <button type="submit" form="verify-otp-form" data-loading-text="Verifying OTP..." class="w-full h-[52px] rounded-2xl bg-[#0A5C66] text-white font-bold text-[15px] hover:bg-[#0E7481] active:scale-[0.98] transition-all flex items-center justify-center gap-2 shadow-md">
                Verify OTP <i class="fa-solid fa-arrow-right text-[14px]"></i>
            </button>
        </div>
    </div>

@endsection
