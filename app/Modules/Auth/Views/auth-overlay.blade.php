    <div id="auth-overlay"
        class="fixed inset-0 z-[9999] bg-[#04242F]/65 backdrop-blur-md hidden items-center justify-center p-4 auth-slide-in overflow-y-auto w-full h-[100dvh]">

        <!-- Card Container -->
        <div class="relative w-full max-w-[400px] bg-white rounded-[28px] shadow-[0_20px_50px_rgba(10,92,102,0.22)] border border-slate-200/50 flex flex-col overflow-hidden max-h-[90vh]">
            
            <!-- Header bar inside card -->
            <div class="relative w-full bg-[#0A5C66]/5 shrink-0 pt-6 pb-5 flex flex-col items-center justify-center z-10 border-b border-slate-100">
                <!-- Back Button -->
                <button type="button" onclick="handleBackNavigation()" class="absolute top-1/2 -translate-y-1/2 left-4 w-8.5 h-8.5 rounded-full bg-white hover:bg-slate-50 text-slate-600 hover:text-[#0A5C66] flex items-center justify-center transition-all shadow-sm border border-slate-200/40 active:scale-95">
                    <i class="fa-solid fa-angle-left text-[15px] pr-0.5"></i>
                </button>

                <!-- Close Button -->
                <button type="button" onclick="window.closeAuth()" class="absolute top-1/2 -translate-y-1/2 right-4 w-8.5 h-8.5 rounded-full bg-white hover:bg-slate-50 text-slate-600 hover:text-[#0A5C66] flex items-center justify-center transition-all shadow-sm border border-slate-200/40 active:scale-95">
                    <i class="fa-solid fa-xmark text-[14px]"></i>
                </button>

                <div class="flex items-center gap-2 relative z-20">
                    <div class="w-8 h-8 bg-gradient-to-br from-[#0A5C66] to-[#0E7481] rounded-xl flex items-center justify-center shadow-md shadow-[#0A5C66]/10 text-white">
                        <i class="fa-solid fa-piggy-bank text-[15px]"></i>
                    </div>
                    <span class="text-[#0A5C66] text-[18px] font-black tracking-tight font-poppins">Gullak<span class="text-[#3CCF91]">Pe</span></span>
                </div>
            </div>

            <!-- Content Area -->
            <div class="px-6 pt-5 pb-4 flex-1 flex flex-col overflow-y-auto custom-scrollbar bg-white">

                <div id="step-phone"
                    class="block h-full flex-col flex-1 pb-safe overflow-y-auto overflow-x-hidden custom-scrollbar">

                    <h2 class="text-[26px] text-[#1a153a] font-bold mb-1 tracking-tight mt-2">Ready to get started?</h2>
                    <p class="text-[14px] text-slate-500 mb-6 leading-relaxed pr-2">Enter your number to help us set up
                        your investment account.</p>

                    <div class="flex gap-2.5 mb-5 relative z-50 h-[52px] shrink-0">
                        <div class="relative w-[90px] h-full shrink-0">
                            <button type="button"
                                class="w-full h-full rounded-[20px] bg-slate-100 flex items-center justify-center gap-2 outline-none">
                                <img src="https://flagcdn.com/w20/in.png"
                                    class="w-[20px] h-[14px] rounded-[2px] object-cover" alt="IN">
                                <span class="font-semibold text-slate-700 text-[16px]">+91</span>
                            </button>
                        </div>

                        <div class="relative flex-1 h-full">
                            <input type="tel" id="phone-input" inputmode="numeric" pattern="[0-9]*"
                                placeholder="Enter Phone Number"
                                class="w-full h-full border-1.5 border-slate-200 rounded-[20px] pl-4 pr-10 font-medium text-[18px] tracking-[0.15em] text-slate-800 placeholder:text-slate-400 placeholder:font-medium placeholder:tracking-normal outline-none focus:border-[#0A5C66] focus:ring-1 focus:ring-[#0A5C66] bg-white transition-all"
                                style="font-family: 'Roboto', sans-serif;">

                            <div id="phone-status-icon"
                                class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center justify-center opacity-0 transition-opacity duration-300">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between bg-transparent/80 border border-slate-200/60 rounded-[20px] px-3.5 py-2.5 mb-4 shrink-0 cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="const t = document.getElementById('save-login-toggle'); t.checked = !t.checked;">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-white rounded-full shadow-[0_2px_5px_rgba(0,0,0,0.04)] flex items-center justify-center text-[#0A5C66] border border-slate-100">
                                <i class="fa-solid fa-fingerprint text-[14px]"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[14px] font-bold text-[#1a153a] leading-tight font-poppins">Save Login</span>
                                <span class="text-[11px] font-medium text-slate-500 leading-tight mt-0.5">Stay logged in securely</span>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer pointer-events-none">
                            <input type="checkbox" id="save-login-toggle" value="" class="sr-only peer" checked>
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#0A5C66]"></div>
                        </label>
                    </div>

                    <label class="flex items-start gap-3 mb-6 cursor-pointer group shrink-0">
                        <div class="relative flex items-center justify-center mt-1 shrink-0">
                            <input type="checkbox" id="tc-checkbox" checked
                                class="peer appearance-none w-[18px] h-[18px] border-2 border-slate-300 rounded-[5px] bg-slate-100 checked:bg-[#0A5C66] transition-all cursor-pointer">
                            <i
                                class="fa-solid fa-check absolute text-white text-[10px] opacity-0 peer-checked:opacity-100 pointer-events-none"></i>
                        </div>
                        <p class="text-[13px] text-slate-600 font-medium leading-snug select-none">
                            By proceeding, you accept GullakPe's <a href="#"
                                class="text-slate-700 font-bold hover:underline">Terms of Use</a> and <a href="#"
                                class="text-slate-700 font-bold hover:underline">Privacy Policy</a>
                        </p>
                    </label>

                    <button id="continue-btn" disabled onclick="handleContinueClick()"
                        class="relative overflow-hidden w-full bg-[#0A5C66] text-white font-bold text-[16px] h-[52px] rounded-[20px] mb-3 transition-all disabled:bg-[#a5a3b2] disabled:text-white/90 disabled:cursor-not-allowed group shrink-0 flex items-center justify-center">
                        <div id="continue-shimmer"
                            class="absolute top-0 -inset-full h-full w-1/2 z-0 hidden transform -skew-x-12 bg-gradient-to-r from-transparent via-white/30 to-transparent pointer-events-none"
                            style="animation: magic-slide 2.5s infinite linear;"></div>
                        <span id="continue-text">Continue</span>
                    </button>

                    <button type="button" onclick="window.location.href='{{ route('auth.google') }}'"
                        class="w-full bg-white border border-slate-200 text-slate-600 font-bold text-[14px] h-[52px] rounded-[20px] flex items-center justify-center gap-3 shadow-[0_4px_20px_rgba(0,0,0,0.03)] shrink-0 mb-6 cursor-pointer hover:bg-transparent transition-colors">
                        <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-5 h-5"> Continue with
                        Google
                    </button>

                    <div class="pt-2 flex flex-col items-center justify-center shrink-0 mb-8">
                        <div class="flex items-center gap-2 mb-2.5">
                            <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-widest">Secured
                                by</span>
                            <div class="flex items-center gap-1.5 bg-[#111315] px-3 py-1.5 rounded-[7px] shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
                                <div class="w-[14px] h-[14px] bg-white rounded-[3px] flex items-center justify-center">
                                    <i
                                        class="fa-solid fa-location-arrow text-[#111315] text-[9px] -rotate-45 -ml-[1px] mt-[1px]"></i>
                                </div>
                                <span class="text-white text-[13px] font-bold tracking-wide font-poppins"
                                    style="font-family: 'Roboto', sans-serif;">stable bonds</span>
                            </div>
                        </div>
                        <p class="text-[12px] text-slate-500 font-medium tracking-wide">Start Goal in GullakPe and earn
                            <span class="text-[#0A5C66] font-bold italic">Higher returns</span>
                        </p>
                    </div>

                </div>

                <div id="step-otp"
                    class="hidden h-full flex-col flex-1 animate-slide-up pb-safe overflow-y-auto overflow-x-hidden custom-scrollbar relative">

                    <div class="w-full px-2 sm:px-0 mt-2 mb-5 text-left flex flex-col items-start">

                        <h2
                            class="text-[30px] sm:text-[32px] text-[#1a153a] font-black mb-1.5 tracking-tight leading-tight w-full">
                            Verify Phone Number</h2>

                        <p class="text-[15px] sm:text-[16px] text-slate-500 font-medium mb-3 w-full leading-relaxed">
                            Enter the 6-digit OTP sent to <br>
                            <span id="display-masked-phone" class="font-bold text-[#1a153a] tracking-wide">+91
                                ******0000</span>
                        </p>

                        <button type="button" onclick="goToStep('step-phone')"
                            class="group flex items-center justify-start gap-2 mt-1 py-1 text-[#0A5C66] text-[15px] font-bold transition-all cursor-pointer w-max font-poppins">
                            <div
                                class="w-6 h-6 rounded-full bg-[#0A5C66]/10 flex items-center justify-center group-hover:bg-[#0A5C66]/20 transition-colors">
                                <i class="fa-solid fa-pen text-[10px]"></i>
                            </div>
                            <span class="group-hover:underline">Edit Phone Number</span>
                        </button>

                    </div>

                    <div class="mb-5 mt-2 w-full px-2 sm:px-0">
                        <div class="flex justify-between items-center gap-2 sm:gap-3 w-full" id="otp-inputs-container">
                            <input type="tel" maxlength="1" inputmode="numeric" pattern="[0-9]*"
                                class="otp-input flex-1 h-[65px] sm:h-[72px] border-b-[4px] border-slate-200 focus:border-[#0A5C66] bg-transparent focus:bg-white rounded-t-xl text-center text-[34px] font-black text-[#1a153a] outline-none transition-all shadow-[0_4px_20px_rgba(0,0,0,0.03)] focus:premium-shadow caret-transparent pb-1.5 px-0 font-poppins">
                            <input type="tel" maxlength="1" inputmode="numeric" pattern="[0-9]*"
                                class="otp-input flex-1 h-[65px] sm:h-[72px] border-b-[4px] border-slate-200 focus:border-[#0A5C66] bg-transparent focus:bg-white rounded-t-xl text-center text-[34px] font-black text-[#1a153a] outline-none transition-all shadow-[0_4px_20px_rgba(0,0,0,0.03)] focus:premium-shadow caret-transparent pb-1.5 px-0 font-poppins">
                            <input type="tel" maxlength="1" inputmode="numeric" pattern="[0-9]*"
                                class="otp-input flex-1 h-[65px] sm:h-[72px] border-b-[4px] border-slate-200 focus:border-[#0A5C66] bg-transparent focus:bg-white rounded-t-xl text-center text-[34px] font-black text-[#1a153a] outline-none transition-all shadow-[0_4px_20px_rgba(0,0,0,0.03)] focus:premium-shadow caret-transparent pb-1.5 px-0 font-poppins">
                            <input type="tel" maxlength="1" inputmode="numeric" pattern="[0-9]*"
                                class="otp-input flex-1 h-[65px] sm:h-[72px] border-b-[4px] border-slate-200 focus:border-[#0A5C66] bg-transparent focus:bg-white rounded-t-xl text-center text-[34px] font-black text-[#1a153a] outline-none transition-all shadow-[0_4px_20px_rgba(0,0,0,0.03)] focus:premium-shadow caret-transparent pb-1.5 px-0 font-poppins">
                            <input type="tel" maxlength="1" inputmode="numeric" pattern="[0-9]*"
                                class="otp-input flex-1 h-[65px] sm:h-[72px] border-b-[4px] border-slate-200 focus:border-[#0A5C66] bg-transparent focus:bg-white rounded-t-xl text-center text-[34px] font-black text-[#1a153a] outline-none transition-all shadow-[0_4px_20px_rgba(0,0,0,0.03)] focus:premium-shadow caret-transparent pb-1.5 px-0 font-poppins">
                            <input type="tel" maxlength="1" inputmode="numeric" pattern="[0-9]*"
                                class="otp-input flex-1 h-[65px] sm:h-[72px] border-b-[4px] border-slate-200 focus:border-[#0A5C66] bg-transparent focus:bg-white rounded-t-xl text-center text-[34px] font-black text-[#1a153a] outline-none transition-all shadow-[0_4px_20px_rgba(0,0,0,0.03)] focus:premium-shadow caret-transparent pb-1.5 px-0 font-poppins">
                        </div>
                    </div>

                    <div id="otp-error-msg"
                        class="hidden flex items-start justify-start gap-2 mb-2 mt-2 w-full px-2 sm:px-0 mx-auto">
                        <span class="text-[14px] text-red-600 font-bold leading-snug">❌ Invalid OTP. Please try
                            again.</span>
                    </div>

                    <div id="timer-wrapper"
                        class="flex justify-between items-center w-full px-2 sm:px-0 mx-auto mt-4 mb-8">
                        <span class="text-[14px] font-medium text-slate-500 flex items-center gap-1.5"><i
                                class="fa-regular fa-clock text-[13px]"></i> <span id="otp-timer"
                                class="font-bold text-slate-800 tracking-wide">00:59s</span></span>
                        <button id="resend-otp-btn" disabled onclick="handleResendOTP()"
                            class="text-[14px] font-bold text-slate-400 cursor-not-allowed transition-all font-poppins">Resend
                            OTP</button>
                    </div>

                    <button id="verify-btn" disabled onclick="handleVerifyClick()"
                        class="relative overflow-hidden w-full bg-[#0A5C66] text-white font-bold text-[16px] h-[52px] rounded-[20px] transition-all disabled:bg-[#a5a3b2] disabled:text-white/90 disabled:cursor-not-allowed group shrink-0 flex items-center justify-center gap-2 mb-4">
                        <div id="verify-shimmer"
                            class="absolute top-0 -inset-full h-full w-1/2 z-0 hidden transform -skew-x-12 bg-gradient-to-r from-transparent via-white/30 to-transparent pointer-events-none"
                            style="animation: magic-slide 2.5s infinite linear;"></div>
                        <span id="verify-text">Verify OTP</span>
                    </button>

                </div>

                <div id="step-mpin" class="hidden h-full flex-col flex-1 animate-slide-up pb-safe overflow-y-auto overflow-x-hidden custom-scrollbar relative">
                
                <div class="w-full px-2 sm:px-0 mt-2 mb-4 text-left flex flex-col items-start">
                    <h2 class="text-[30px] sm:text-[32px] text-[#1a153a] font-black mb-1.5 tracking-tight leading-tight w-full">Set New MPIN</h2>
                    <p class="text-[15px] sm:text-[16px] text-slate-500 font-medium w-full leading-relaxed">
                        Set a 4-digit MPIN for fast and secure login without entering an OTP every time.
                    </p>
                </div>

                <div class="mb-5 w-full px-2 sm:px-0 mt-2">
                    <div class="flex justify-between items-center mb-3">
                        <label class="text-[15.5px] font-black text-[#1a153a] tracking-wide">Set New MPIN</label>
                        <button type="button" onclick="window.toggleMpinVisibility()" class="bg-slate-100 px-3 py-1.5 rounded-full text-[#0A5C66] text-[12px] font-bold flex items-center gap-1.5 hover:bg-slate-200 transition-colors shadow-[0_4px_20px_rgba(0,0,0,0.03)] border border-slate-200/60 active:scale-95 cursor-pointer font-poppins">
                            <i id="mpin-eye-icon" class="fa-regular fa-eye-slash"></i>
                            <span id="mpin-eye-text">Show</span>
                        </button>
                    </div>
                    
                    <div class="flex justify-between items-center gap-2 w-full">
                        <div class="grid grid-cols-4 gap-2 sm:gap-3 flex-1">
                            <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="mpin-input w-full h-[65px] sm:h-[70px] border-2 border-slate-200 focus:border-[#0A5C66] bg-transparent focus:bg-white rounded-[20px] text-center text-[32px] font-black text-[#1a153a] outline-none transition-all shadow-[0_4px_20px_rgba(0,0,0,0.03)] focus:premium-shadow caret-transparent px-0 font-poppins">
                            <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="mpin-input w-full h-[65px] sm:h-[70px] border-2 border-slate-200 focus:border-[#0A5C66] bg-transparent focus:bg-white rounded-[20px] text-center text-[32px] font-black text-[#1a153a] outline-none transition-all shadow-[0_4px_20px_rgba(0,0,0,0.03)] focus:premium-shadow caret-transparent px-0 font-poppins">
                            <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="mpin-input w-full h-[65px] sm:h-[70px] border-2 border-slate-200 focus:border-[#0A5C66] bg-transparent focus:bg-white rounded-[20px] text-center text-[32px] font-black text-[#1a153a] outline-none transition-all shadow-[0_4px_20px_rgba(0,0,0,0.03)] focus:premium-shadow caret-transparent px-0 font-poppins">
                            <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="mpin-input w-full h-[65px] sm:h-[70px] border-2 border-slate-200 focus:border-[#0A5C66] bg-transparent focus:bg-white rounded-[20px] text-center text-[32px] font-black text-[#1a153a] outline-none transition-all shadow-[0_4px_20px_rgba(0,0,0,0.03)] focus:premium-shadow caret-transparent px-0 font-poppins">
                        </div>
                        <div id="pin1-status" class="w-7 flex justify-center shrink-0"></div>
                    </div>
                </div>

                <div class="mb-2 w-full px-2 sm:px-0">
                    <div class="flex justify-between items-center mb-3">
                        <label class="text-[15.5px] font-black text-[#1a153a] tracking-wide">Re-enter New MPIN</label>
                    </div>
                    
                    <div class="flex justify-between items-center gap-2 w-full" id="mpin2-container">
                        <div class="grid grid-cols-4 gap-2 sm:gap-3 flex-1">
                            <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="mpin-confirm w-full h-[65px] sm:h-[70px] border-2 border-slate-200 focus:border-[#0A5C66] bg-transparent focus:bg-white rounded-[20px] text-center text-[32px] font-black text-[#1a153a] outline-none transition-all shadow-[0_4px_20px_rgba(0,0,0,0.03)] focus:premium-shadow caret-transparent px-0 font-poppins">
                            <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="mpin-confirm w-full h-[65px] sm:h-[70px] border-2 border-slate-200 focus:border-[#0A5C66] bg-transparent focus:bg-white rounded-[20px] text-center text-[32px] font-black text-[#1a153a] outline-none transition-all shadow-[0_4px_20px_rgba(0,0,0,0.03)] focus:premium-shadow caret-transparent px-0 font-poppins">
                            <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="mpin-confirm w-full h-[65px] sm:h-[70px] border-2 border-slate-200 focus:border-[#0A5C66] bg-transparent focus:bg-white rounded-[20px] text-center text-[32px] font-black text-[#1a153a] outline-none transition-all shadow-[0_4px_20px_rgba(0,0,0,0.03)] focus:premium-shadow caret-transparent px-0 font-poppins">
                            <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="mpin-confirm w-full h-[65px] sm:h-[70px] border-2 border-slate-200 focus:border-[#0A5C66] bg-transparent focus:bg-white rounded-[20px] text-center text-[32px] font-black text-[#1a153a] outline-none transition-all shadow-[0_4px_20px_rgba(0,0,0,0.03)] focus:premium-shadow caret-transparent px-0 font-poppins">
                        </div>
                        <div id="pin2-status" class="w-7 flex justify-center shrink-0"></div>
                    </div>
                </div>

                <div id="mpin-error" class="hidden flex items-start gap-2 w-full px-2 sm:px-0 mt-3 transition-opacity">
                    <span class="text-[13.5px] text-red-600 font-bold leading-snug">❌ MPINs do not match. Please try again.</span>
                </div>

                <div class="flex items-start gap-3 px-3 py-3.5 mx-2 sm:mx-0 mb-8 mt-5 bg-[#0A5C66]/5 rounded-[20px] border border-[#0A5C66]/10">
                    <div class="mt-[1px] shrink-0"><i class="fa-solid fa-shield-halved text-[#0A5C66] text-[18px]"></i></div>
                    <p class="text-[13.5px] text-slate-700 font-semibold leading-snug">Use your MPIN for secure login and transaction approval.</p>
                </div>

                <button id="set-mpin-btn" disabled onclick="handleSetMpinClick()" class="relative overflow-hidden w-full bg-[#0A5C66] text-white font-bold text-[16px] h-[52px] rounded-[20px] transition-all disabled:bg-[#a5a3b2] disabled:text-white/90 disabled:cursor-not-allowed group shrink-0 flex items-center justify-center gap-2 mb-3 shadow-[0_4px_12px_rgba(10,92,102,0.2)]">
                    <div id="mpin-shimmer" class="absolute top-0 -inset-full h-full w-1/2 z-0 hidden transform -skew-x-12 bg-gradient-to-r from-transparent via-white/30 to-transparent pointer-events-none" style="animation: magic-slide 2.5s infinite linear;"></div>
                    <span id="mpin-btn-text" class="relative z-10">Complete Setup</span>
                </button>

                <div class="text-center w-full mb-4">
                    <span class="text-[12px] font-bold text-slate-400 flex items-center justify-center gap-1.5 font-poppins"><i class="fa-solid fa-lock text-[10px]"></i> end-to-end encrypted.</span>
                </div>

            </div>

            <div id="step-login-mpin" class="hidden h-full flex-col flex-1 animate-slide-up pb-safe overflow-y-auto overflow-x-hidden custom-scrollbar relative">
                <div class="w-full px-2 sm:px-0 mt-2 mb-4 text-left flex flex-col items-start">
                    <h2 class="text-[30px] sm:text-[32px] text-[#1a153a] font-black mb-1.5 tracking-tight leading-tight w-full">Enter MPIN</h2>
                    <p class="text-[15px] sm:text-[16px] text-slate-500 font-medium w-full leading-relaxed">Enter your 4-digit MPIN to log in safely.</p>
                    <div class="flex items-center gap-3 mt-3 w-full">
                        <span id="login-display-masked-phone" class="font-bold text-[#1a153a] tracking-wide bg-slate-100 px-3 py-1 rounded-lg">+91 ******0000</span>
                        <button type="button" onclick="window.goToStep('step-phone')" class="text-[#0A5C66] text-[13px] font-bold hover:underline cursor-pointer font-poppins">Change</button>
                    </div>
                </div>
                <div class="mb-2 w-full px-2 sm:px-0 mt-2">
                    <div class="flex justify-between items-center gap-2 w-full" id="login-mpin-container">
                        <div class="grid grid-cols-4 gap-2 sm:gap-3 flex-1">
                            <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="login-mpin-input w-full h-[65px] sm:h-[70px] border-2 border-slate-200 focus:border-[#0A5C66] bg-transparent focus:bg-white rounded-[20px] text-center text-[32px] font-black text-[#1a153a] outline-none transition-all shadow-[0_4px_20px_rgba(0,0,0,0.03)] focus:premium-shadow caret-transparent px-0 font-poppins">
                            <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="login-mpin-input w-full h-[65px] sm:h-[70px] border-2 border-slate-200 focus:border-[#0A5C66] bg-transparent focus:bg-white rounded-[20px] text-center text-[32px] font-black text-[#1a153a] outline-none transition-all shadow-[0_4px_20px_rgba(0,0,0,0.03)] focus:premium-shadow caret-transparent px-0 font-poppins">
                            <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="login-mpin-input w-full h-[65px] sm:h-[70px] border-2 border-slate-200 focus:border-[#0A5C66] bg-transparent focus:bg-white rounded-[20px] text-center text-[32px] font-black text-[#1a153a] outline-none transition-all shadow-[0_4px_20px_rgba(0,0,0,0.03)] focus:premium-shadow caret-transparent px-0 font-poppins">
                            <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="login-mpin-input w-full h-[65px] sm:h-[70px] border-2 border-slate-200 focus:border-[#0A5C66] bg-transparent focus:bg-white rounded-[20px] text-center text-[32px] font-black text-[#1a153a] outline-none transition-all shadow-[0_4px_20px_rgba(0,0,0,0.03)] focus:premium-shadow caret-transparent px-0 font-poppins">
                        </div>
                    </div>
                </div>
                <div id="login-mpin-error" class="hidden flex items-start gap-2 w-full px-2 sm:px-0 mt-3 transition-opacity">
                    <span class="text-[13.5px] text-red-600 font-bold leading-snug">❌ Incorrect MPIN. Please try again.</span>
                </div>
                <div class="w-full px-2 sm:px-0 mt-4 mb-8 text-right">
                    <button type="button" onclick="window.startForgotMpinFlow()" class="text-[#0A5C66] text-[14px] font-bold hover:underline transition-all cursor-pointer font-poppins">Forgot MPIN?</button>
                </div>
                <button id="login-mpin-btn" disabled onclick="window.handleLoginMpinClick()" class="relative overflow-hidden w-full bg-[#0A5C66] text-white font-bold text-[16px] h-[52px] rounded-[20px] transition-all disabled:bg-[#a5a3b2] disabled:text-white/90 disabled:cursor-not-allowed group shrink-0 flex items-center justify-center gap-2 mb-3 premium-shadow">
                    <div id="login-mpin-shimmer" class="absolute top-0 -inset-full h-full w-1/2 z-0 hidden transform -skew-x-12 bg-gradient-to-r from-transparent via-white/30 to-transparent pointer-events-none" style="animation: magic-slide 2.5s infinite linear;"></div>
                    <span id="login-mpin-text" class="relative z-10">Login Securely</span>
                </button>
            </div>
            <div id="step-forgot-phone" class="hidden h-full flex-col flex-1 animate-slide-up pb-safe overflow-y-auto overflow-x-hidden custom-scrollbar relative">
                <div class="w-full px-2 sm:px-0 mt-2 mb-6 text-left flex flex-col items-start">
                    <button type="button" onclick="window.goToStep('step-login-mpin')" class="text-slate-400 hover:text-[#0A5C66] text-[14px] font-bold transition-all mb-4 flex items-center gap-1.5 cursor-pointer font-poppins"><i class="fa-solid fa-angle-left"></i> Back to Login</button>
                    <h2 class="text-[30px] sm:text-[32px] text-[#1a153a] font-black mb-1.5 tracking-tight leading-tight w-full">Reset MPIN</h2>
                    <p class="text-[15px] sm:text-[16px] text-slate-500 font-medium w-full leading-relaxed">Enter your registered phone number to receive an OTP.</p>
                </div>
                <div class="flex gap-2.5 mb-2 relative z-50 h-[52px] shrink-0 px-2 sm:px-0">
                    <div class="relative w-[90px] h-full shrink-0">
                        <div class="w-full h-full rounded-[20px] bg-slate-100 flex items-center justify-center gap-2 border border-slate-200">
                            <img src="https://flagcdn.com/w20/in.png" class="w-[20px] h-[14px] rounded-[2px] object-cover" alt="IN">
                            <span class="font-bold text-[#1a153a] text-[16px]">+91</span>
                        </div>
                    </div>
                    <div class="relative flex-1 h-full">
                        <input type="tel" id="forgot-phone-input" inputmode="numeric" pattern="[0-9]*" placeholder="Enter Phone Number" class="w-full h-full border-2 border-slate-200 rounded-[20px] pl-4 pr-10 font-black text-[18px] tracking-[0.1em] text-[#1a153a] placeholder:text-slate-300 placeholder:font-medium placeholder:tracking-normal outline-none focus:border-[#0A5C66] focus:ring-1 focus:ring-[#0A5C66] bg-transparent focus:bg-white transition-all shadow-[0_4px_20px_rgba(0,0,0,0.03)] focus:premium-shadow">
                        <div id="forgot-phone-status-icon" class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center justify-center opacity-0 transition-opacity duration-300"></div>
                    </div>
                </div>
                <div id="forgot-error-msg" class="hidden flex items-start gap-2 w-full px-2 sm:px-0 mt-2 mb-4 transition-opacity">
                    <span class="text-[13.5px] text-red-600 font-bold leading-snug">❌ Account not found. Please check the number.</span>
                </div>
                <button id="forgot-continue-btn" disabled onclick="window.handleForgotPhoneClick()" class="relative overflow-hidden w-full bg-[#0A5C66] text-white font-bold text-[16px] h-[52px] rounded-[20px] mb-3 transition-all disabled:bg-[#a5a3b2] disabled:text-white/90 disabled:cursor-not-allowed group shrink-0 flex items-center justify-center premium-shadow mt-4">
                    <div id="forgot-continue-shimmer" class="absolute top-0 -inset-full h-full w-1/2 z-0 hidden transform -skew-x-12 bg-gradient-to-r from-transparent via-white/30 to-transparent pointer-events-none" style="animation: magic-slide 2.5s infinite linear;"></div>
                    <span id="forgot-continue-text" class="relative z-10">Continue</span>
                </button>
            </div>


            </div>
        </div>
        <div id="max-attempts-modal"
            class="absolute inset-0 z-[10000] bg-[#1a153a]/40 backdrop-blur-sm hidden items-center justify-center p-4 transition-opacity duration-300 opacity-0">
            <div class="bg-white w-full max-w-[320px] rounded-[24px] p-6 sm:p-8 flex flex-col items-center text-center shadow-2xl transform scale-95 transition-transform duration-300"
                id="max-attempts-card">
                <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mb-5 shrink-0">
                    <i class="fa-solid fa-lock text-red-500 text-[26px]"></i>
                </div>

                <h3 class="text-[22px] font-black text-[#1a153a] mb-2 tracking-tight font-poppins">Verification Failed</h3>

                <p class="text-[14px] text-slate-500 font-medium leading-relaxed mb-8">
                    For your security, you have reached the maximum OTP verification attempts.<br><br>Please restart the
                    login process to request a new OTP.
                </p>

                <button type="button" onclick="resetToLogin()"
                    class="w-full bg-[#0A5C66] text-white font-bold text-[15px] h-[52px] rounded-[20px] hover:bg-[#084b53] active:scale-95 transition-all premium-shadow">
                    Back to Login
                </button>
            </div>
        </div>
    </div>


