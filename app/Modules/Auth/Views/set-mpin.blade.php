@extends('layouts.auth-hero')

@section('title', $isNewUser ? 'Complete your account' : 'Reset your MPIN')

@section('content')

    <div class="h-full flex flex-col bg-white overflow-hidden">

        <x-auth-hero :back="route('login')" :compact="true" />

        <!-- Sheet -->
        <div class="relative -mt-4 flex-1 min-h-0 bg-white rounded-t-[28px] overflow-y-auto">
            <div class="px-6 sm:px-7 pt-6 sm:pt-7 pb-4">
                <h1 class="text-[clamp(24px,6vw,29px)] font-black text-[#111827] tracking-tight font-poppins leading-tight">{{ $isNewUser ? 'Complete your account' : 'Set a new MPIN' }}</h1>
                <p class="text-[clamp(13px,3.4vw,14px)] text-slate-500 font-medium mt-1.5 sm:mt-2 leading-relaxed max-w-[300px]">
                    @if ($isNewUser)
                        Set a 4-digit MPIN for fast and secure login without entering an OTP every time.
                    @else
                        Your phone number is verified. Choose a new 4-digit MPIN.
                    @endif
                </p>

                <form id="set-mpin-form" method="POST" action="{{ route('login.set-mpin.submit') }}" class="flex flex-col gap-6 mt-7">
                    @csrf

                    @if ($isNewUser)
                        <div>
                            <label for="name" class="sr-only">Full name</label>
                            <input type="text" id="name" name="name" required autofocus
                                placeholder="Your full name" value="{{ old('name') }}"
                                class="w-full h-[52px] rounded-2xl border border-slate-200 bg-white px-4 font-semibold text-[15px] text-slate-800 outline-none focus:border-[#0A5C66] focus:ring-2 focus:ring-[#0A5C66]/10 transition-all placeholder:text-slate-400 placeholder:font-normal">
                            @error('name')
                                <p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div>
                        <span class="block text-[12px] font-bold text-slate-500 uppercase tracking-wide mb-2">New MPIN</span>
                        <x-pin-input name="mpin" :length="4" :autofocus="! $isNewUser" aria-label="New MPIN" />
                    </div>

                    <div>
                        <span class="block text-[12px] font-bold text-slate-500 uppercase tracking-wide mb-2">Re-enter MPIN</span>
                        <x-pin-input name="mpin_confirmation" :length="4" aria-label="Re-enter MPIN" :auto-submit="true" />
                    </div>
                    @error('mpin')
                        <p class="text-[12px] font-semibold text-red-500 -mt-3">{{ $message }}</p>
                    @enderror

                    <div class="flex items-start gap-2.5 bg-[#0A5C66]/5 rounded-2xl p-3.5 border border-[#0A5C66]/10">
                        <i class="fa-solid fa-shield-halved text-[13px] text-[#0A5C66] mt-0.5"></i>
                        <p class="text-[12.5px] text-slate-700 font-semibold leading-relaxed">Use your MPIN for secure login and transaction approval.</p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bottom action bar -->
        <div class="shrink-0 bg-white border-t border-slate-100 px-6 sm:px-7 pt-4 pb-6">
            <button type="submit" form="set-mpin-form" data-loading-text="{{ $isNewUser ? 'Setting up...' : 'Saving...' }}" class="w-full h-[52px] rounded-2xl bg-[#0A5C66] text-white font-bold text-[15px] hover:bg-[#0E7481] active:scale-[0.98] transition-all flex items-center justify-center gap-2 shadow-md">
                {{ $isNewUser ? 'Complete Setup' : 'Save New MPIN' }} <i class="fa-solid fa-arrow-right text-[14px]"></i>
            </button>
        </div>
    </div>

@endsection
