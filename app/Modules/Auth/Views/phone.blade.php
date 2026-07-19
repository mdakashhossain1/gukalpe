@extends('layouts.auth-hero')

@section('title', 'Log in or sign up')

@section('content')

    <div class="h-screen flex flex-col bg-white overflow-hidden">

        <x-auth-hero :back="route('home')" />

        <!-- Sheet -->
        <div class="relative -mt-10 flex-1 min-h-0 bg-white rounded-t-[36px] overflow-y-auto">
            <div class="px-6 pt-9 pb-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h1 class="text-[27px] font-black text-[#1a153a] tracking-tight font-poppins leading-tight">Ready to get started?</h1>
                        <p class="text-[14px] text-slate-500 font-medium mt-2 leading-relaxed max-w-[300px]">Enter your number to help us set up your investment account.</p>
                    </div>
                    <button type="button" onclick="window.toggleLanguage && window.toggleLanguage()" aria-label="Switch language"
                        class="relative w-9 h-9 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 shrink-0 mt-1 active:scale-95 transition-all hover:text-[#0A5C66] hover:border-[#0A5C66]/25">
                        <i class="bi bi-translate text-[14px]"></i>
                        <span data-current-lang class="absolute -bottom-1 -right-1 bg-[#0A5C66] text-white text-[7px] font-black px-1 rounded-full leading-tight border-2 border-white">EN</span>
                    </button>
                </div>

                <form id="phone-form" method="POST" action="{{ route('login.submit') }}" class="flex flex-col gap-6 mt-8">
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

        <!-- Bottom action bar - every primary/secondary action anchored here,
             not trailing after the form fields, so it's always reachable
             without scrolling regardless of content height. -->
        <div class="shrink-0 bg-white border-t border-slate-100 px-6 pt-4 pb-6 flex flex-col gap-3">
            <div>
                <label class="flex items-start gap-3 cursor-pointer group">
                    <input type="checkbox" name="terms" value="1" checked required form="phone-form"
                        class="mt-0.5 w-[19px] h-[19px] rounded-[6px] border-2 border-slate-300 accent-[#0A5C66] shrink-0">
                    <p class="text-[13.5px] text-slate-700 font-medium leading-snug">
                        I agree to receive communications on my mobile number registered with GullakPe
                    </p>
                </label>
                @error('terms')
                    <p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <p class="text-[12px] text-slate-400 font-medium text-center leading-relaxed">
                By proceeding, you accept GullakPe's <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="text-slate-600 font-bold hover:underline">Terms of Use</a> and <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener" class="text-slate-600 font-bold hover:underline">Privacy Policy</a>
            </p>

            <button type="submit" form="phone-form" class="btn-shimmer-cta w-full h-[56px] rounded-full bg-[#0A5C66] text-white font-bold text-[16px] hover:bg-[#0E7481] active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                Continue <i class="fa-solid fa-arrow-right text-[14px]"></i>
            </button>

            <a href="{{ route('auth.google') }}" class="w-full h-[52px] rounded-full border border-slate-200 text-slate-500 font-semibold text-[13.5px] flex items-center justify-center gap-2.5 hover:bg-slate-50 transition-colors">
                <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-[18px] h-[18px]"> Continue with Google
            </a>
        </div>
    </div>

@endsection
