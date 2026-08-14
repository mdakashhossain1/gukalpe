@extends('layouts.auth-hero')

@section('title', 'Reset MPIN')

@section('content')

    <div class="h-full flex flex-col bg-white overflow-hidden">

        <x-auth-hero :back="route('login.mpin')" :compact="true" />

        <!-- Sheet -->
        <div class="relative -mt-4 flex-1 min-h-0 bg-white rounded-t-[28px] overflow-y-auto">
            <div class="px-6 sm:px-7 pt-6 sm:pt-7 pb-4">
                <h1 class="text-[clamp(24px,6vw,29px)] font-black text-[#111827] tracking-tight font-poppins leading-tight">Reset MPIN</h1>
                <p class="text-[clamp(13px,3.4vw,14px)] text-slate-500 font-medium mt-1.5 sm:mt-2 leading-relaxed max-w-[300px]">Enter your registered phone number to receive an OTP.</p>

                <form id="forgot-mpin-form" method="POST" action="{{ route('login.forgot-mpin.submit') }}" class="flex flex-col gap-6 mt-8">
                    @csrf

                    <div>
                        <label for="phone" class="sr-only">Phone number</label>
                        <div class="flex gap-2.5 h-[46px]">
                            <div class="w-[58px] h-full rounded-2xl border border-slate-200 bg-white px-2.5 flex items-center justify-center shrink-0">
                                <span class="font-bold text-slate-700 text-[13.5px]">+91</span>
                            </div>
                            <input type="tel" id="phone" name="phone" inputmode="numeric" maxlength="10" required autofocus
                                placeholder="Enter Phone Number" value="{{ old('phone') }}"
                                class="flex-1 h-full rounded-2xl border border-slate-200 bg-white px-4 font-semibold text-[13.5px] text-slate-800 outline-none focus:border-[#0A5C66] focus:ring-2 focus:ring-[#0A5C66]/10 transition-all placeholder:text-slate-400 placeholder:font-normal">
                        </div>
                        @error('phone')
                            <p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </form>
            </div>
        </div>

        <!-- Bottom action bar -->
        <div class="shrink-0 bg-white border-t border-slate-100 px-6 sm:px-7 pt-4 pb-6">
            <button type="submit" form="forgot-mpin-form" data-loading-text="Sending OTP..." class="w-full h-[52px] rounded-2xl bg-[#0A5C66] text-white font-bold text-[15px] hover:bg-[#0E7481] active:scale-[0.98] transition-all flex items-center justify-center gap-2 shadow-md">
                Continue <i class="fa-solid fa-arrow-right text-[14px]"></i>
            </button>
        </div>
    </div>

@endsection
