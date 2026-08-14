@extends('layouts.auth-hero')

@section('title', 'Log in or sign up')

@section('content')

    <div class="h-full flex flex-col bg-white overflow-hidden">

        <x-auth-hero :back="route('home')" />

        <!-- Sheet -->
        <div class="relative -mt-10 flex-1 min-h-0 bg-white rounded-t-[36px] overflow-y-auto">
            <div class="px-6 sm:px-7 pt-6 sm:pt-7 pb-4">
                <h1 class="text-[clamp(24px,6vw,29px)] font-black text-[#111827] tracking-tight font-poppins leading-tight">Ready to get started?</h1>
                <p class="text-[clamp(13px,3.4vw,14px)] text-slate-500 font-normal mt-1.5 sm:mt-2 leading-relaxed max-w-[320px]">Enter your number to help us set up your investment account.</p>

                <form id="phone-form" method="POST" action="{{ route('login.submit') }}" class="flex flex-col gap-4 sm:gap-5 mt-6 sm:mt-7">
                    @csrf

                    <div>
                        <label for="phone" class="sr-only">Phone number</label>
                        <div class="flex gap-2.5 h-[46px]">
                            <div class="w-[58px] h-full rounded-2xl border border-slate-200 bg-white px-2.5 flex items-center justify-center shrink-0">
                                <span class="font-bold text-slate-700 text-[13.5px]">+91</span>
                            </div>
                            <div class="relative flex-1 h-full">
                                <input type="tel" id="phone" name="phone" inputmode="numeric" maxlength="10" required autofocus
                                    placeholder="Enter Phone Number" value="{{ old('phone') }}"
                                    class="w-full h-full rounded-2xl border border-slate-200 bg-white pl-4 pr-11 font-semibold text-[13.5px] text-slate-800 outline-none focus:border-[#0A5C66] focus:ring-2 focus:ring-[#0A5C66]/10 transition-all placeholder:text-slate-400 placeholder:font-normal">
                                {{-- Plays once the number reaches 10 digits - same validity check
                                     driving the Continue button below, just a lighter-weight visual
                                     confirmation right where the user is typing. --}}
                                <div id="phone-valid-tick" class="hidden absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 pointer-events-none"></div>
                            </div>
                        </div>
                        @error('phone')
                            <p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-0.5">
                        <label class="flex items-start gap-2 cursor-pointer group">
                            <input type="checkbox" id="terms-checkbox" name="terms" value="1"
                                class="mt-0.5 w-[15px] h-[15px] rounded-[4px] border-2 border-slate-300 accent-[#0A5C66] text-[#0A5C66] shrink-0 focus:ring-0 cursor-pointer">
                            <p class="text-[11.5px] text-slate-700 font-medium leading-snug select-none">
                                I agree to receive communications on my mobile number registered with GullakPe
                            </p>
                        </label>
                        @error('terms')
                            <p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </form>
            </div>
        </div>

        <!-- Bottom action bar -->
        <div class="shrink-0 bg-white border-t border-slate-100 px-6 sm:px-7 pt-4 pb-6 flex flex-col gap-3 sm:gap-3.5">
            <p class="text-[clamp(11px,2.8vw,12px)] text-slate-400 font-medium text-center leading-relaxed">
                By proceeding, you accept GullakPe's <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="text-[#0A5C66] font-bold hover:underline">Terms of Use</a> and <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener" class="text-[#0A5C66] font-bold hover:underline">Privacy Policy</a>
            </p>

            <button type="submit" id="continue-btn" form="phone-form" disabled data-loading-text="Please wait..."
                class="w-full h-[52px] rounded-2xl bg-[#CBD5E1] text-white font-bold text-[15px] cursor-not-allowed transition-all flex items-center justify-center gap-2">
                Continue <i class="fa-solid fa-arrow-right text-[14px]"></i>
            </button>

            <!-- Divider -->
            <div class="relative flex items-center py-0.5">
                <div class="flex-grow border-t border-slate-200"></div>
                <span class="flex-shrink mx-3 text-[clamp(12px,3vw,13px)] text-slate-400 font-medium">or</span>
                <div class="flex-grow border-t border-slate-200"></div>
            </div>

            <a href="{{ route('auth.google') }}" class="w-full h-[48px] sm:h-[50px] rounded-2xl border border-slate-200 bg-white text-slate-700 font-semibold text-[clamp(13px,3.4vw,14px)] flex items-center justify-center gap-2.5 hover:bg-slate-50 hover:border-slate-300 active:scale-[0.99] transition-all">
                <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-[18px] h-[18px]" alt="Google"> Continue with Google
            </a>
        </div>
    </div>

    <script src="{{ asset('libs/lottie/lottie_light.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const phoneInput = document.getElementById('phone');
            const termsCheckbox = document.getElementById('terms-checkbox');
            const continueBtn = document.getElementById('continue-btn');
            const tickEl = document.getElementById('phone-valid-tick');

            // Lazy-loaded once on the first valid entry, then just
            // stop/play again on repeat toggles - no need to re-fetch the
            // animation JSON every keystroke.
            let tickAnim = null;
            let wasValid = false;

            function playTick() {
                tickEl.classList.remove('hidden');
                if (!tickAnim) {
                    tickAnim = lottie.loadAnimation({
                        container: tickEl,
                        renderer: 'svg',
                        loop: false,
                        autoplay: false,
                        path: '{{ asset('assets/lottie/phone-verified-tick.json') }}',
                    });
                }
                tickAnim.stop();
                tickAnim.play();
            }

            function hideTick() {
                tickEl.classList.add('hidden');
                if (tickAnim) {
                    tickAnim.stop();
                }
            }

            function updateBtnState() {
                const phoneVal = (phoneInput.value || '').replace(/\D/g, '');
                if (phoneInput.value !== phoneVal) {
                    phoneInput.value = phoneVal;
                }
                const isValid = phoneVal.length === 10;

                if (isValid) {
                    continueBtn.disabled = false;
                    continueBtn.className = 'w-full h-[52px] rounded-2xl bg-[#0A5C66] text-white font-bold text-[15px] hover:bg-[#0E7481] active:scale-[0.98] transition-all flex items-center justify-center gap-2 shadow-md cursor-pointer';
                } else {
                    continueBtn.disabled = true;
                    continueBtn.className = 'w-full h-[52px] rounded-2xl bg-[#CBD5E1] text-white font-bold text-[15px] cursor-not-allowed transition-all flex items-center justify-center gap-2';
                }

                if (isValid && !wasValid) {
                    playTick();
                } else if (!isValid && wasValid) {
                    hideTick();
                }
                wasValid = isValid;
            }

            phoneInput.addEventListener('input', updateBtnState);
            termsCheckbox.addEventListener('change', updateBtnState);

            updateBtnState();
        });
    </script>

@endsection
