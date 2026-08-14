@extends('layouts.auth-hero')

@section('title', 'Enter MPIN')

@section('content')

    <div class="h-full flex flex-col bg-white overflow-hidden">

        <x-auth-hero :back="route('login')" :compact="true" />

        <!-- Sheet -->
        <div class="relative -mt-4 flex-1 min-h-0 bg-white rounded-t-[28px] overflow-y-auto">
            <div class="px-6 sm:px-7 pt-6 sm:pt-7 pb-4">
                <h1 class="text-[clamp(24px,6vw,29px)] font-black text-[#111827] tracking-tight font-poppins leading-tight">Enter MPIN</h1>
                <p class="text-[clamp(13px,3.4vw,14px)] text-slate-500 font-medium mt-1.5 sm:mt-2 leading-relaxed max-w-[300px]">Enter your 4-digit MPIN to log in safely.</p>

                <div class="flex items-center gap-3 mt-4">
                    <span class="font-bold text-[#111827] text-[13px] bg-slate-100 px-3 py-1.5 rounded-lg">+91 ******{{ substr($phone, -4) }}</span>
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
        <div class="shrink-0 bg-white border-t border-slate-100 px-6 sm:px-7 pt-4 pb-6">
            <button type="submit" form="mpin-form" data-loading-text="Verifying..." class="w-full h-[52px] rounded-2xl bg-[#0A5C66] text-white font-bold text-[15px] hover:bg-[#0E7481] active:scale-[0.98] transition-all flex items-center justify-center gap-2 shadow-md">
                Login Securely <i class="fa-solid fa-arrow-right text-[14px]"></i>
            </button>
        </div>
    </div>

@endsection
