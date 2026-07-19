    <div id="plan-detail-modal" class="fixed inset-0 z-[9999] bg-[#F8FAFC] hidden flex-col h-[100dvh] w-full auth-slide-in overflow-hidden">
        
        <!-- Header / Cover -->
        <div class="relative w-full h-[320px] shrink-0 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-t from-[#0A5C66] via-[#0A5C66]/80 to-transparent z-10"></div>
            <div class="absolute inset-0 bg-[#0A5C66]/20 mix-blend-overlay z-10 animate-pulse"></div>
            <img id="plan-detail-img" src="https://images.unsplash.com/photo-1558981403-c5f9899a28bc?q=80&w=600&auto=format&fit=crop" class="w-full h-full object-cover transform scale-105" alt="Plan Cover" style="animation: slow-pan 20s infinite alternate linear;">
            
            <div class="absolute top-5 left-5 right-5 z-20 flex items-center justify-between">
                <button type="button" onclick="closePlanDetail()" class="w-10 h-10 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center text-white border border-white/30 hover:bg-white/30 active:scale-95 transition-all shadow-lg">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-full border-2 border-white/40 bg-white/20 backdrop-blur-md flex items-center justify-center overflow-hidden shadow-lg shadow-black/20">
                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix" alt="Profile" class="w-full h-full">
                    </div>
                    <div class="bg-gradient-to-r from-amber-400 to-orange-500 border border-white/40 text-white text-[11px] font-black px-3 py-1.5 rounded-full uppercase tracking-wider flex items-center gap-1.5 shadow-[0_0_15px_rgba(251,191,36,0.6)]">
                        <i class="fa-solid fa-fire text-white animate-pulse"></i> <span id="plan-detail-badge">HOT</span>
                    </div>
                </div>
            </div>

            <div class="absolute bottom-6 left-6 right-6 z-20 flex items-end justify-between">
                <div class="flex-1 pr-4">
                    <h2 id="plan-detail-title" class="text-white text-[32px] font-black leading-tight tracking-tight font-poppins mb-1 shadow-black/50 drop-shadow-lg">Dream Superbike</h2>
                    <div class="flex items-center gap-2 mb-2">
                        <div class="h-2 w-2 rounded-full bg-green-400 animate-ping"></div>
                        <p id="plan-detail-desc" class="text-white/90 text-[14px] font-medium shadow-black/50 drop-shadow-md">Live Growth Active</p>
                    </div>
                </div>
                
                <!-- Goal Progress Ring -->
                <div class="relative w-20 h-20 shrink-0 flex items-center justify-center bg-white/10 backdrop-blur-xl rounded-full border border-white/20 shadow-2xl">
                    <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" fill="transparent" stroke="rgba(255,255,255,0.2)" stroke-width="8" />
                        <circle cx="50" cy="50" r="45" fill="transparent" stroke="#3FEA8A" stroke-width="8" stroke-dasharray="282.7" stroke-dashoffset="70" class="drop-shadow-[0_0_8px_rgba(63,234,138,0.8)]" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center flex-col">
                        <i id="plan-detail-icon" class="fa-solid fa-motorcycle text-[20px] text-white"></i>
                        <span class="text-white text-[10px] font-black mt-0.5">75%</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar relative z-30 -mt-6 bg-[#F8FAFC] rounded-t-[32px] shadow-[0_-10px_40px_rgba(0,0,0,0.1)]">
            
            <!-- Summary Card Sticky -->
            <div class="sticky top-0 z-40 bg-white/90 backdrop-blur-xl border-b border-slate-200/50 p-5 shadow-[0_10px_30px_rgba(0,0,0,0.04)] transition-all">
                <div class="grid grid-cols-3 gap-y-4 gap-x-2">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider font-poppins mb-0.5">Plan Amount</span>
                        <span class="text-[15px] font-black text-[#1a153a] font-poppins" id="summary-amt">₹50,000</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider font-poppins mb-0.5">Est. Growth</span>
                        <span id="plan-detail-return" class="text-[15px] font-black text-[#0A5C66] font-poppins flex items-center gap-1">12% <i class="fa-solid fa-arrow-trend-up text-[10px]"></i></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider font-poppins mb-0.5">Lock Duration</span>
                        <span id="plan-detail-duration" class="text-[15px] font-black text-[#1a153a] font-poppins flex items-center gap-1.5"><i class="fa-regular fa-clock text-[#0A5C66]"></i> 12 Mo</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider font-poppins mb-0.5">Withdrawal</span>
                        <span class="text-[13px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded inline-block w-max">Flexible</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider font-poppins mb-0.5">VIP Eligible</span>
                        <span class="text-[13px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded inline-block w-max"><i class="fa-solid fa-crown"></i> Yes</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-slate-400/80 uppercase tracking-wider font-poppins mb-0.5">Daily Profit</span>
                        <span class="text-[13px] font-black text-indigo-600" id="summary-daily">+₹16.43</span>
                    </div>
                </div>
            </div>

            <div class="p-5 pb-32 space-y-8">
                <!-- Smart Profit Simulator -->
                <div class="bg-white rounded-[28px] p-6 shadow-[0_15px_40px_-10px_rgba(0,0,0,0.06)] border border-slate-100 relative overflow-hidden group">
                    <div class="absolute -right-10 -top-10 w-32 h-32 bg-indigo-50 rounded-full blur-3xl group-hover:bg-indigo-100 transition-colors duration-500 pointer-events-none"></div>
                    <h3 class="font-black text-[#1a153a] text-[20px] mb-6 flex items-center gap-2 font-poppins relative z-10">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600"><i class="fa-solid fa-calculator text-[14px]"></i></div>
                        Smart Profit Simulator
                    </h3>
                    
                    <div class="mb-8 relative z-10">
                        <div class="flex justify-between items-end mb-4">
                            <span class="text-[14px] font-bold text-slate-500">I want to start with</span>
                            <span id="sim-amount-display" class="text-[28px] font-black text-[#1a153a] font-poppins tracking-tight">₹50,000</span>
                        </div>
                        <div class="relative w-full h-3 bg-slate-100 rounded-full">
                            <div id="sim-slider-fill" class="absolute top-0 left-0 h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full w-[10%]"></div>
                            <input type="range" id="sim-slider" min="1000" max="500000" step="1000" value="50000" class="absolute top-0 left-0 w-full h-full appearance-none bg-transparent cursor-pointer z-20">
                            <!-- Custom thumb styling handled in css -->
                        </div>
                        <div class="flex justify-between mt-3 text-[12px] font-bold text-slate-400">
                            <span>₹1K</span>
                            <span>₹5L</span>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-[20px] p-5 text-white relative overflow-hidden shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/40 transition-shadow">
                        <div class="absolute right-0 bottom-0 opacity-10 transform translate-x-4 translate-y-4">
                            <i class="fa-solid fa-chart-line text-[80px]"></i>
                        </div>
                        <div class="relative z-10 flex justify-between items-center mb-3">
                            <span class="text-[13px] font-medium text-indigo-100">Total Est. Returns</span>
                            <span id="sim-return-display" class="text-[24px] font-black text-white font-poppins">+₹6,000</span>
                        </div>
                        <div class="relative z-10 flex justify-between items-center pt-3 border-t border-white/20">
                            <span class="text-[13px] font-medium text-indigo-100">Total Value</span>
                            <span id="sim-total-display" class="text-[20px] font-black text-white font-poppins">₹56,000</span>
                        </div>
                    </div>
                </div>

                <!-- Daily Growth Preview -->
                <div class="bg-gradient-to-r from-[#0A5C66] to-[#148e9e] rounded-[28px] p-6 text-white relative overflow-hidden shadow-[0_20px_40px_rgba(10,92,102,0.25)] hover:shadow-[0_20px_50px_rgba(10,92,102,0.35)] transition-shadow">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/20 rounded-full blur-3xl animate-pulse"></div>
                    <div class="relative z-10 flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-[12px] font-bold text-white/70 uppercase tracking-widest mb-1 font-poppins flex items-center gap-1.5"><div class="w-1.5 h-1.5 rounded-full bg-[#3FEA8A] animate-ping"></div> Live Earning Preview</span>
                            <div class="flex items-center gap-2 mt-1">
                                <span id="daily-growth-display" class="text-[32px] font-black font-poppins tracking-tight">+₹16.43</span>
                                <span class="text-[14px] font-bold text-white/60 mt-2">/ day</span>
                            </div>
                        </div>
                        <div class="w-14 h-14 rounded-[16px] bg-white/10 flex items-center justify-center backdrop-blur-xl border border-white/20 shadow-inner">
                            <i class="fa-solid fa-coins text-white text-[24px] group-hover:rotate-12 transition-transform drop-shadow-[0_0_10px_rgba(255,255,255,0.5)]"></i>
                        </div>
                    </div>
                </div>

                <!-- Plan Benefits -->
                <div>
                    <h3 class="font-black text-[#1a153a] text-[20px] mb-5 font-poppins tracking-tight">Premium Benefits</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <div class="bg-white p-4 rounded-[20px] border border-slate-100 shadow-[0_5px_15px_rgba(0,0,0,0.02)] flex flex-col items-center text-center gap-3 hover:border-[#0A5C66]/40 hover:shadow-[0_10px_20px_rgba(10,92,102,0.08)] transition-all group hover:-translate-y-1">
                            <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center text-green-500 group-hover:scale-110 transition-transform group-hover:bg-green-500 group-hover:text-white shadow-sm"><i class="fa-solid fa-shield-halved text-[18px]"></i></div>
                            <span class="text-[13px] font-bold text-[#1a153a]">Safe & Secure</span>
                        </div>
                        <div class="bg-white p-4 rounded-[20px] border border-slate-100 shadow-[0_5px_15px_rgba(0,0,0,0.02)] flex flex-col items-center text-center gap-3 hover:border-[#0A5C66]/40 hover:shadow-[0_10px_20px_rgba(10,92,102,0.08)] transition-all group hover:-translate-y-1">
                            <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-blue-500 group-hover:scale-110 transition-transform group-hover:bg-blue-500 group-hover:text-white shadow-sm"><i class="fa-solid fa-bolt text-[18px]"></i></div>
                            <span class="text-[13px] font-bold text-[#1a153a]">Fast Processing</span>
                        </div>
                        <div class="bg-white p-4 rounded-[20px] border border-slate-100 shadow-[0_5px_15px_rgba(0,0,0,0.02)] flex flex-col items-center text-center gap-3 hover:border-[#0A5C66]/40 hover:shadow-[0_10px_20px_rgba(10,92,102,0.08)] transition-all group hover:-translate-y-1">
                            <div class="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-500 group-hover:scale-110 transition-transform group-hover:bg-indigo-500 group-hover:text-white shadow-sm"><i class="fa-solid fa-chart-line text-[18px]"></i></div>
                            <span class="text-[13px] font-bold text-[#1a153a]">Smart Growth</span>
                        </div>
                        <div class="bg-white p-4 rounded-[20px] border border-slate-100 shadow-[0_5px_15px_rgba(0,0,0,0.02)] flex flex-col items-center text-center gap-3 hover:border-[#0A5C66]/40 hover:shadow-[0_10px_20px_rgba(10,92,102,0.08)] transition-all group hover:-translate-y-1">
                            <div class="w-12 h-12 rounded-full bg-teal-50 flex items-center justify-center text-teal-500 group-hover:scale-110 transition-transform group-hover:bg-teal-500 group-hover:text-white shadow-sm"><i class="fa-solid fa-circle-check text-[18px]"></i></div>
                            <span class="text-[13px] font-bold text-[#1a153a]">Verified Plan</span>
                        </div>
                        <div class="bg-white p-4 rounded-[20px] border border-slate-100 shadow-[0_5px_15px_rgba(0,0,0,0.02)] flex flex-col items-center text-center gap-3 hover:border-[#0A5C66]/40 hover:shadow-[0_10px_20px_rgba(10,92,102,0.08)] transition-all group hover:-translate-y-1">
                            <div class="w-12 h-12 rounded-full bg-amber-50 flex items-center justify-center text-amber-500 group-hover:scale-110 transition-transform group-hover:bg-amber-500 group-hover:text-white shadow-sm"><i class="fa-solid fa-crown text-[18px]"></i></div>
                            <span class="text-[13px] font-bold text-[#1a153a]">VIP Benefits</span>
                        </div>
                        <div class="bg-white p-4 rounded-[20px] border border-slate-100 shadow-[0_5px_15px_rgba(0,0,0,0.02)] flex flex-col items-center text-center gap-3 hover:border-[#0A5C66]/40 hover:shadow-[0_10px_20px_rgba(10,92,102,0.08)] transition-all group hover:-translate-y-1">
                            <div class="w-12 h-12 rounded-full bg-rose-50 flex items-center justify-center text-rose-500 group-hover:scale-110 transition-transform group-hover:bg-rose-500 group-hover:text-white shadow-sm"><i class="fa-solid fa-gift text-[18px]"></i></div>
                            <span class="text-[13px] font-bold text-[#1a153a]">Flexible Rewards</span>
                        </div>
                    </div>
                </div>

                <!-- Timeline Section -->
                <div class="bg-white rounded-[28px] p-6 shadow-[0_15px_40px_-10px_rgba(0,0,0,0.05)] border border-slate-100">
                    <h3 class="font-black text-[#1a153a] text-[20px] mb-6 font-poppins tracking-tight">Journey Timeline</h3>
                    <div class="relative pl-7 space-y-7">
                        <div class="absolute left-[13px] top-2 bottom-2 w-[3px] bg-gradient-to-b from-[#0A5C66] via-indigo-200 to-slate-100 rounded-full"></div>
                        
                        <div class="relative z-10 flex gap-5 group">
                            <div class="w-7 h-7 rounded-full bg-[#0A5C66] border-[4px] border-white shadow-md flex items-center justify-center flex-shrink-0 -ml-[19px] mt-0.5 group-hover:scale-110 transition-transform"><i class="fa-solid fa-check text-white text-[8px]"></i></div>
                            <div>
                                <h4 class="text-[15px] font-bold text-[#1a153a] mb-1">Goal Activated</h4>
                                <p class="text-[13px] font-medium text-slate-500">Funds securely processed in vault</p>
                            </div>
                        </div>
                        <div class="relative z-10 flex gap-5 group">
                            <div class="w-7 h-7 rounded-full bg-indigo-500 border-[4px] border-white shadow-md flex items-center justify-center flex-shrink-0 -ml-[19px] mt-0.5 group-hover:scale-110 transition-transform animate-pulse"><div class="w-2 h-2 bg-white rounded-full"></div></div>
                            <div>
                                <h4 class="text-[15px] font-bold text-[#1a153a] mb-1">Growth Started</h4>
                                <p class="text-[13px] font-medium text-slate-500">Daily smart returns begin compounding</p>
                            </div>
                        </div>
                        <div class="relative z-10 flex gap-5 group">
                            <div class="w-7 h-7 rounded-full bg-slate-200 border-[4px] border-white shadow-md flex-shrink-0 -ml-[19px] mt-0.5 group-hover:scale-110 transition-transform"></div>
                            <div>
                                <h4 class="text-[15px] font-bold text-[#1a153a] mb-1 opacity-60">Reward Unlock</h4>
                                <p class="text-[13px] font-medium text-slate-400">Milestone achievements reached</p>
                            </div>
                        </div>
                        <div class="relative z-10 flex gap-5 group">
                            <div class="w-7 h-7 rounded-full bg-slate-200 border-[4px] border-white shadow-md flex-shrink-0 -ml-[19px] mt-0.5 group-hover:scale-110 transition-transform"></div>
                            <div>
                                <h4 class="text-[15px] font-bold text-[#1a153a] mb-1 opacity-60">Withdraw Available</h4>
                                <p class="text-[13px] font-medium text-slate-400">After goal lock-in period ends</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Sticky Bottom Action: Liquid Swipe to Buy -->
        <div class="fixed bottom-0 left-0 right-0 p-5 bg-white/90 backdrop-blur-xl border-t border-slate-200/50 z-50 rounded-t-[32px] shadow-[0_-15px_40px_rgba(0,0,0,0.08)] pb-safe">
            <div id="swipe-btn-container" class="relative w-full h-[68px] bg-slate-100/80 rounded-full overflow-hidden shadow-inner border border-slate-200/80 group">
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <span class="text-[#0A5C66] font-black text-[16px] tracking-wide font-poppins opacity-70 ml-10">Swipe to Activate <i class="fa-solid fa-angles-right ml-2 animate-pulse"></i></span>
                </div>
                <!-- Track Progress Liquid Glow -->
                <div id="swipe-progress" class="absolute top-0 left-0 bottom-0 w-0 bg-gradient-to-r from-[#0A5C66] via-[#148e9e] to-[#3CCF91] opacity-40 shadow-[0_0_20px_rgba(63,234,138,0.5)]"></div>
                <!-- Draggable Thumb -->
                <div id="swipe-thumb" class="absolute top-1.5 left-1.5 bottom-1.5 w-[56px] bg-gradient-to-r from-[#0A5C66] to-[#148e9e] rounded-full flex items-center justify-center text-white shadow-[0_5px_15px_rgba(10,92,102,0.5)] cursor-grab active:cursor-grabbing z-10 transition-transform touch-none border border-white/20">
                    <i class="fa-solid fa-arrow-right text-[20px] group-active:rotate-12 transition-transform"></i>
                </div>
            </div>
        </div>

    </div>

    <!-- ================= PREMIUM POPUPS ================= -->
    
    <!-- Success Popup -->
    <div id="popup-success" class="popup-overlay">
        <div class="popup-card relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-[#19B36B]"></div>
            <div class="w-16 h-16 bg-[#19B36B]/10 rounded-full flex items-center justify-center mb-5 border border-[#19B36B]/20 shadow-inner success-glow-pulse">
                <i class="fa-solid fa-circle-check text-[#19B36B] text-[32px] checkmark-animate"></i>
            </div>
            <h3 class="text-[#0A5C66] text-[22px] font-black leading-tight mb-1.5 font-poppins tracking-tight">Investment Activated</h3>
            <p class="text-slate-500 text-[13.5px] font-medium mb-5 leading-relaxed">Your goal plan is now active successfully.</p>
            
            <div class="w-full bg-[#0A5C66]/5 rounded-2xl p-4 border border-[#0A5C66]/10 text-left space-y-2.5 mb-6">
                <div class="flex justify-between items-center text-[12px]">
                    <span class="text-slate-400 font-semibold font-poppins">PLAN NAME</span>
                    <span id="pop-success-name" class="text-[#0A5C66] font-bold font-poppins">-</span>
                </div>
                <div class="flex justify-between items-center text-[12px]">
                    <span class="text-slate-400 font-semibold font-poppins">INVESTMENT</span>
                    <span id="pop-success-amount" class="text-[#0A5C66] font-bold font-poppins">-</span>
                </div>
                <div class="flex justify-between items-center text-[12px]">
                    <span class="text-slate-400 font-semibold font-poppins">EXPECTED RETURN</span>
                    <span id="pop-success-return" class="text-[#19B36B] font-bold font-poppins">-</span>
                </div>
                <div class="flex justify-between items-center text-[12px]">
                    <span class="text-slate-400 font-semibold font-poppins">DURATION</span>
                    <span id="pop-success-duration" class="text-[#0A5C66] font-bold font-poppins">-</span>
                </div>
            </div>

            <div class="w-full flex flex-col gap-2.5">
                <button onclick="closePopups(); switchTab('portfolio');" class="w-full bg-[#0E7680] text-white font-bold text-[15px] h-[48px] rounded-full active:scale-95 transition-all shadow-md shadow-[#0E7680]/20">View Portfolio</button>
                <button onclick="closePopups();" class="w-full bg-slate-100/80 text-slate-600 font-bold text-[14px] h-[44px] rounded-full hover:bg-slate-200/80 active:scale-95 transition-all">Continue Exploring</button>
            </div>
        </div>
    </div>

    <!-- Error/Insufficient Popup -->
    <div id="popup-error" class="popup-overlay">
        <div class="popup-card relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-amber-400 to-orange-500"></div>
            <div class="w-16 h-16 bg-amber-500/10 rounded-full flex items-center justify-center mb-5 border border-amber-500/20 shadow-inner amber-glow-pulse">
                <i class="fa-solid fa-wallet text-amber-500 text-[28px]"></i>
            </div>
            <h3 class="text-[#0A5C66] text-[22px] font-black leading-tight mb-1.5 font-poppins tracking-tight">Insufficient Balance</h3>
            <p class="text-slate-500 text-[13.5px] font-medium mb-5 leading-relaxed">Your wallet balance is too low to activate this plan.</p>
            
            <div class="w-full bg-amber-500/5 rounded-2xl p-4 border border-amber-500/10 text-left space-y-2.5 mb-6">
                <div class="flex justify-between items-center text-[12px]">
                    <span class="text-slate-400 font-semibold font-poppins">REQUIRED AMOUNT</span>
                    <span id="pop-error-required" class="text-slate-700 font-bold font-poppins">-</span>
                </div>
                <div class="flex justify-between items-center text-[12px]">
                    <span class="text-slate-400 font-semibold font-poppins">AVAILABLE BALANCE</span>
                    <span id="pop-error-available" class="text-amber-600 font-bold font-poppins">-</span>
                </div>
            </div>

            <div class="w-full flex flex-col gap-2.5">
                <button onclick="window.goToAddMoneyFromPopup();" class="w-full bg-amber-500 text-white font-bold text-[15px] h-[48px] rounded-full active:scale-95 transition-all shadow-md shadow-amber-500/20">Add Money</button>
                <button onclick="closePopups();" class="w-full bg-slate-100/80 text-slate-600 font-bold text-[14px] h-[44px] rounded-full hover:bg-slate-200/80 active:scale-95 transition-all">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Activation Failed Popup -->
    <div id="popup-failed" class="popup-overlay">
        <div class="popup-card relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-red-400 to-rose-500"></div>
            <div class="w-16 h-16 bg-red-500/10 rounded-full flex items-center justify-center mb-5 border border-red-500/20 shadow-inner red-glow-pulse">
                <i class="fa-solid fa-shield-halved text-red-500 text-[28px]"></i>
            </div>
            <h3 class="text-[#0A5C66] text-[22px] font-black leading-tight mb-1.5 font-poppins tracking-tight">Activation Failed</h3>
            <p class="text-slate-500 text-[13.5px] font-medium mb-6 leading-relaxed">Something interrupted the activation process.</p>

            <div class="w-full flex flex-col gap-2.5">
                <button onclick="closePopups(); resetSlideToInvest(true);" class="w-full bg-red-500 text-white font-bold text-[15px] h-[48px] rounded-full active:scale-95 transition-all shadow-md shadow-red-500/20">Try Again</button>
                <button onclick="closePopups();" class="w-full bg-slate-100/80 text-slate-600 font-bold text-[14px] h-[44px] rounded-full hover:bg-slate-200/80 active:scale-95 transition-all">Back</button>
            </div>
        </div>
    </div>

    <!-- Claim Commission Success Popup -->
    <div id="popup-claim-commission" class="popup-overlay">
        <div class="popup-card relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-[#19B36B]"></div>
            <div class="w-16 h-16 bg-[#19B36B]/10 rounded-full flex items-center justify-center mb-5 border border-[#19B36B]/20 shadow-inner success-glow-pulse">
                <i class="fa-solid fa-circle-check text-[#19B36B] text-[32px] checkmark-animate"></i>
            </div>
            <h3 class="text-[#0A5C66] text-[22px] font-black leading-tight mb-1.5 font-poppins tracking-tight">Bonus Claimed Successfully</h3>
            <p id="claim-commission-success-amount" class="text-slate-500 text-[15.5px] font-bold mb-6 font-poppins">₹0.00 added to your Reward Balance.</p>
            
            <div class="w-full flex flex-col gap-2.5">
                <button onclick="closePopups();" class="w-full bg-[#0A5C66] text-white font-bold text-[15px] h-[48px] rounded-full active:scale-95 transition-all shadow-md shadow-[#0A5C66]/20 cursor-pointer">Awesome</button>
            </div>
        </div>
    </div>

    <!-- Claim Cashback Success Popup -->
    <div id="popup-claim-cashback" class="popup-overlay">
        <div class="popup-card relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-[#19B36B]"></div>
            <div class="w-16 h-16 bg-[#19B36B]/10 rounded-full flex items-center justify-center mb-5 border border-[#19B36B]/20 shadow-inner success-glow-pulse">
                <i class="fa-solid fa-circle-check text-[#19B36B] text-[32px] checkmark-animate"></i>
            </div>
            <h3 class="text-[#0A5C66] text-[22px] font-black leading-tight mb-1.5 font-poppins tracking-tight">Cashback Added</h3>
            <p id="claim-cashback-success-amount" class="text-slate-500 text-[15.5px] font-bold mb-6 font-poppins">₹0.00 added successfully</p>
            
            <div class="w-full flex flex-col gap-2.5">
                <button onclick="closePopups();" class="w-full bg-[#0A5C66] text-white font-bold text-[15px] h-[48px] rounded-full active:scale-95 transition-all shadow-md shadow-[#0A5C66]/20 cursor-pointer">Okay</button>
            </div>
        </div>
    </div>

    <!-- Login Required Popup -->
    <div id="popup-login" class="popup-overlay">
        <div class="popup-card relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-[#0E7680] to-[#1199A5]"></div>
            <div class="w-16 h-16 bg-[#0E7680]/10 rounded-full flex items-center justify-center mb-5 border border-[#0E7680]/20 shadow-inner teal-glow-pulse">
                <i class="fa-solid fa-lock text-[#0E7680] text-[26px]"></i>
            </div>
            <h3 class="text-[#0A5C66] text-[22px] font-black leading-tight mb-1.5 font-poppins tracking-tight">Login Required</h3>
            <p class="text-slate-500 text-[13.5px] font-medium mb-6 leading-relaxed">Please login or create your account to activate this investment plan.</p>

            <div class="w-full flex flex-col gap-2.5">
                <button onclick="closePopups(); if (window.openAuth) { window.openAuth(); } else { const auth = document.getElementById('auth-overlay'); if (auth) auth.classList.remove('hidden'); }" class="w-full bg-[#0E7680] text-white font-bold text-[15px] h-[48px] rounded-full active:scale-95 transition-all shadow-md shadow-[#0E7680]/20">Login / Sign Up</button>
                <button onclick="closePopups();" class="w-full bg-slate-100/80 text-slate-600 font-bold text-[14px] h-[44px] rounded-full hover:bg-slate-200/80 active:scale-95 transition-all">Later</button>
            </div>
        </div>
    </div>
    
    <!-- Plan Already Active Popup -->
    <div id="popup-already-active" class="popup-overlay">
        <div class="popup-card relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-400 to-indigo-500"></div>
            <div class="w-16 h-16 bg-blue-500/10 rounded-full flex items-center justify-center mb-5 border border-blue-500/20 shadow-inner amber-glow-pulse">
                <i class="fa-solid fa-circle-info text-blue-500 text-[28px]"></i>
            </div>
            <h3 class="text-[#0A5C66] text-[22px] font-black leading-tight mb-1.5 font-poppins tracking-tight">Plan Already Active</h3>
            <p class="text-slate-500 text-[13.5px] font-medium mb-6 leading-relaxed">This investment plan is already active in your account.</p>
            <button onclick="closePopups(); switchTab('portfolio');" class="w-full bg-[#0E7680] text-white font-bold text-[15px] h-[48px] rounded-full active:scale-95 transition-all shadow-md shadow-[#0E7680]/20">View My Plans</button>
        </div>
    </div>

    <!-- Transaction History Popup -->
    <div id="popup-history" class="popup-overlay">
        <div class="popup-card relative overflow-hidden max-w-[360px] h-[480px] flex flex-col justify-start">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-[#0E7680] to-[#1199A5]"></div>
            
            <div class="flex justify-between items-center w-full mb-4 pb-2 border-b border-[#0A5C66]/10 shrink-0">
                <h3 class="text-[#0A5C66] text-[18px] font-black font-poppins tracking-tight">Transaction History</h3>
                <button onclick="closePopups();" class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:text-slate-600 active:scale-90 transition-all shrink-0">
                    <i class="fa-solid fa-xmark text-[16px]"></i>
                </button>
            </div>
            
            <!-- List Scroll Container -->
            <div id="history-list-container" class="flex-1 w-full overflow-y-auto pr-1 space-y-3 custom-scrollbar text-left">
                <!-- Transactions rendered dynamically -->
            </div>
        </div>
    </div>
    
    <!-- Starter Plan Required Popup -->
    <div id="popup-starter" class="popup-overlay">
        <div class="popup-card relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-amber-400 to-orange-500"></div>
            <div class="w-16 h-16 bg-amber-500/10 rounded-full flex items-center justify-center mb-5 border border-amber-500/20 shadow-inner">
                <i class="fa-solid fa-lock text-amber-500 text-[28px]"></i>
            </div>
            <h3 class="text-[#0A5C66] text-[22px] font-black leading-tight mb-1.5 font-poppins tracking-tight">VIP Plan Locked</h3>
            <p class="text-slate-500 text-[13.5px] font-medium mb-6 leading-relaxed">You must complete a Starter or Smart Growth plan before accessing VIP Premium plans.</p>
            <div class="w-full flex flex-col gap-2.5">
                <button onclick="closePopups();" class="w-full bg-amber-500 text-white font-bold text-[15px] h-[48px] rounded-full active:scale-95 transition-all shadow-md shadow-amber-500/20">View Starter Plans</button>
                <button onclick="closePopups();" class="w-full bg-slate-100/80 text-slate-600 font-bold text-[14px] h-[44px] rounded-full hover:bg-slate-200/80 active:scale-95 transition-all">Cancel</button>
            </div>
        </div>
    </div>
    
    <!-- Withdrawal Success Popup -->
    <div id="popup-withdraw" class="popup-overlay">
        <div class="popup-card relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-400 to-purple-500"></div>
            <div class="w-16 h-16 bg-indigo-500/10 rounded-full flex items-center justify-center mb-5 border border-indigo-500/20 shadow-inner">
                <i class="fa-solid fa-building-columns text-indigo-500 text-[28px] animate-bounce"></i>
            </div>
            <h3 class="text-[#0A5C66] text-[22px] font-black leading-tight mb-1.5 font-poppins tracking-tight">Withdrawal Success!</h3>
            <p class="text-slate-500 text-[13.5px] font-medium mb-6 leading-relaxed">₹<span id="popup-withdraw-amount" class="font-bold text-slate-700">12,500</span> has been transferred to your registered bank account instantly.</p>
            <button onclick="closePopups()" class="w-full bg-indigo-500 text-white font-bold text-[15px] h-[48px] rounded-full active:scale-95 transition-all shadow-md shadow-indigo-500/20">Awesome</button>
        </div>
    </div>
