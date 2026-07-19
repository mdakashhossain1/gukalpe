@extends('layouts.auth-hero')

@section('title', $isNewUser ? 'Complete your account' : 'Reset your MPIN')

@section('content')

    <div class="h-screen flex flex-col bg-white overflow-hidden">

        <x-auth-hero :back="route('login')" />

        <!-- Sheet -->
        <div class="relative -mt-10 flex-1 min-h-0 bg-white rounded-t-[36px] overflow-y-auto">
            <div class="px-6 pt-9 pb-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h1 class="text-[27px] font-black text-[#1a153a] tracking-tight font-poppins leading-tight">{{ $isNewUser ? 'Complete your account' : 'Set a new MPIN' }}</h1>
                        <p class="text-[14px] text-slate-500 font-medium mt-2 leading-relaxed max-w-[300px]">
                            @if ($isNewUser)
                                Set a 4-digit MPIN for fast and secure login without entering an OTP every time.
                            @else
                                Your phone number is verified. Choose a new 4-digit MPIN.
                            @endif
                        </p>
                    </div>
                    <button type="button" onclick="window.toggleLanguage && window.toggleLanguage()" aria-label="Switch language"
                        class="relative w-9 h-9 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 shrink-0 mt-1 active:scale-95 transition-all hover:text-[#0A5C66] hover:border-[#0A5C66]/25">
                        <i class="bi bi-translate text-[14px]"></i>
                        <span data-current-lang class="absolute -bottom-1 -right-1 bg-[#0A5C66] text-white text-[7px] font-black px-1 rounded-full leading-tight border-2 border-white">EN</span>
                    </button>
                </div>

                <form id="set-mpin-form" method="POST" action="{{ route('login.set-mpin.submit') }}" class="flex flex-col gap-6 mt-7">
                    @csrf

                    @if ($isNewUser)
                        <div>
                            <label for="name" class="sr-only">Full name</label>
                            <input type="text" id="name" name="name" required autofocus
                                placeholder="Your full name" value="{{ old('name') }}"
                                class="w-full h-[56px] rounded-[16px] border-none bg-slate-100 px-4 font-bold text-[15px] text-slate-800 outline-none focus:ring-2 focus:ring-[#0A5C66]/30 transition-colors placeholder:text-slate-400 placeholder:font-medium">
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
                        <x-pin-input name="mpin_confirmation" :length="4" aria-label="Re-enter MPIN" />
                    </div>
                    @error('mpin')
                        <p class="text-[12px] font-semibold text-red-500 -mt-3">{{ $message }}</p>
                    @enderror

                    <div class="flex items-start gap-2.5 bg-[#0A5C66]/5 rounded-[16px] p-3.5 border border-[#0A5C66]/10">
                        <i class="fa-solid fa-shield-halved text-[13px] text-[#0A5C66] mt-0.5"></i>
                        <p class="text-[12.5px] text-slate-700 font-semibold leading-relaxed">Use your MPIN for secure login and transaction approval.</p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bottom action bar -->
        <div class="shrink-0 bg-white border-t border-slate-100 px-6 pt-4 pb-6">
            <button type="submit" form="set-mpin-form" class="btn-shimmer-cta w-full h-[56px] rounded-full bg-[#0A5C66] text-white font-bold text-[16px] hover:bg-[#0E7481] active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                {{ $isNewUser ? 'Complete Setup' : 'Save New MPIN' }} <i class="fa-solid fa-arrow-right text-[14px]"></i>
            </button>
        </div>
    </div>

@endsection
