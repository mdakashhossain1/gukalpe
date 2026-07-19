{{-- Edit modals opened from Profile's list items. Preview/demo only - see
     DESIGN.md's "Settings" section. Shared shell classes
     (.settings-modal / .settings-modal-card) live in resources/css/app.css. --}}

<!-- Profile Information -->
<div id="modal-profile-info" class="settings-modal hidden" data-settings-modal>
    <div class="settings-modal-card">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-[18px] text-slate-800 font-poppins">Profile Information</h3>
            <button onclick="window.closeSettingsModal()" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="flex flex-col gap-3.5">
            <div>
                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">Full Name</label>
                <input type="text" id="settings-name" placeholder="Your name" class="w-full h-11 rounded-[12px] border border-slate-200 px-3.5 text-[14.5px] outline-none focus:border-[#0A5C66] focus:ring-1 focus:ring-[#0A5C66] transition-colors">
            </div>
            <div>
                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">Email</label>
                <input type="email" id="settings-email" placeholder="you@example.com" class="w-full h-11 rounded-[12px] border border-slate-200 px-3.5 text-[14.5px] outline-none focus:border-[#0A5C66] focus:ring-1 focus:ring-[#0A5C66] transition-colors">
            </div>
            <div>
                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">Phone Number</label>
                <input type="text" disabled value="Linked to your account — can't be changed here" class="w-full h-11 rounded-[12px] border border-slate-200 bg-slate-50 px-3.5 text-[13px] text-slate-400 font-medium outline-none">
            </div>
        </div>
        <button onclick="window.saveSettingsModal('profile-info')" class="w-full h-12 rounded-[14px] bg-[#0A5C66] text-white font-bold text-[14.5px] mt-5 hover:bg-[#0E7481] transition-colors active:scale-[0.98]">
            Save Changes
        </button>
    </div>
</div>

<!-- Security -->
<div id="modal-security" class="settings-modal hidden" data-settings-modal>
    <div class="settings-modal-card">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-[18px] text-slate-800 font-poppins">Security</h3>
            <button onclick="window.closeSettingsModal()" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="flex flex-col gap-3.5">
            <div>
                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">Current MPIN</label>
                <input type="password" maxlength="4" placeholder="••••" class="w-full h-11 rounded-[12px] border border-slate-200 px-3.5 text-[14.5px] tracking-[0.3em] outline-none focus:border-[#0A5C66] focus:ring-1 focus:ring-[#0A5C66] transition-colors">
            </div>
            <div>
                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">New MPIN</label>
                <input type="password" maxlength="4" placeholder="••••" class="w-full h-11 rounded-[12px] border border-slate-200 px-3.5 text-[14.5px] tracking-[0.3em] outline-none focus:border-[#0A5C66] focus:ring-1 focus:ring-[#0A5C66] transition-colors">
            </div>
            <label class="flex items-center justify-between py-2">
                <span class="text-[14px] font-bold text-slate-700">Biometric login</span>
                <input type="checkbox" checked class="settings-toggle">
            </label>
        </div>
        <button onclick="window.saveSettingsModal('security')" class="w-full h-12 rounded-[14px] bg-[#0A5C66] text-white font-bold text-[14.5px] mt-5 hover:bg-[#0E7481] transition-colors active:scale-[0.98]">
            Save Changes
        </button>
    </div>
</div>

<!-- Notifications -->
<div id="modal-notifications" class="settings-modal hidden" data-settings-modal>
    <div class="settings-modal-card">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-[18px] text-slate-800 font-poppins">Notifications</h3>
            <button onclick="window.closeSettingsModal()" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="flex flex-col divide-y divide-slate-100">
            <label class="flex items-center justify-between py-3">
                <span class="text-[14px] font-bold text-slate-700">Push notifications</span>
                <input type="checkbox" checked class="settings-toggle">
            </label>
            <label class="flex items-center justify-between py-3">
                <span class="text-[14px] font-bold text-slate-700">SMS alerts</span>
                <input type="checkbox" checked class="settings-toggle">
            </label>
            <label class="flex items-center justify-between py-3">
                <span class="text-[14px] font-bold text-slate-700">Email updates</span>
                <input type="checkbox" class="settings-toggle">
            </label>
        </div>
        <button onclick="window.saveSettingsModal('notifications')" class="w-full h-12 rounded-[14px] bg-[#0A5C66] text-white font-bold text-[14.5px] mt-5 hover:bg-[#0E7481] transition-colors active:scale-[0.98]">
            Save Changes
        </button>
    </div>
</div>

<!-- Language & Region -->
<div id="modal-language" class="settings-modal hidden" data-settings-modal>
    <div class="settings-modal-card">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-[18px] text-slate-800 font-poppins">Language & Region</h3>
            <button onclick="window.closeSettingsModal()" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="flex flex-col gap-2.5">
            <label class="flex items-center justify-between p-3.5 rounded-[12px] border border-slate-200 cursor-pointer has-[:checked]:border-[#0A5C66] has-[:checked]:bg-[#0A5C66]/5 transition-colors">
                <span class="text-[14.5px] font-bold text-slate-700">English</span>
                <input type="radio" name="settings-lang" value="en" checked class="accent-[#0A5C66] w-4 h-4">
            </label>
            <label class="flex items-center justify-between p-3.5 rounded-[12px] border border-slate-200 cursor-pointer has-[:checked]:border-[#0A5C66] has-[:checked]:bg-[#0A5C66]/5 transition-colors">
                <span class="text-[14.5px] font-bold text-slate-700 font-devanagari">हिन्दी (Hindi)</span>
                <input type="radio" name="settings-lang" value="hi" class="accent-[#0A5C66] w-4 h-4">
            </label>
        </div>
        <button onclick="window.saveSettingsModal('language')" class="w-full h-12 rounded-[14px] bg-[#0A5C66] text-white font-bold text-[14.5px] mt-5 hover:bg-[#0E7481] transition-colors active:scale-[0.98]">
            Save Changes
        </button>
    </div>
</div>

<!-- Payment Methods -->
<div id="modal-payment-methods" class="settings-modal hidden" data-settings-modal>
    <div class="settings-modal-card">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-[18px] text-slate-800 font-poppins">Payment Methods</h3>
            <button onclick="window.closeSettingsModal()" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="settings-payment-list" class="flex flex-col gap-2.5 mb-4">
            <div class="flex items-center justify-between p-3.5 rounded-[12px] border border-slate-200" data-payment-row>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-[10px] bg-slate-100 flex items-center justify-center text-slate-500"><i class="fa-solid fa-building-columns text-[14px]"></i></div>
                    <div>
                        <p class="text-[13.5px] font-bold text-slate-700 leading-tight">HDFC Bank</p>
                        <p class="text-[11.5px] text-slate-400 font-medium leading-tight">•••• 4821</p>
                    </div>
                </div>
                <button onclick="window.deletePaymentMethod(this)" class="w-8 h-8 rounded-full flex items-center justify-center text-red-500 hover:bg-red-50 transition-colors"><i class="fa-solid fa-trash text-[13px]"></i></button>
            </div>
            <div class="flex items-center justify-between p-3.5 rounded-[12px] border border-slate-200" data-payment-row>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-[10px] bg-slate-100 flex items-center justify-center text-slate-500"><i class="fa-solid fa-credit-card text-[14px]"></i></div>
                    <div>
                        <p class="text-[13.5px] font-bold text-slate-700 leading-tight">Visa Debit Card</p>
                        <p class="text-[11.5px] text-slate-400 font-medium leading-tight">•••• 7734</p>
                    </div>
                </div>
                <button onclick="window.deletePaymentMethod(this)" class="w-8 h-8 rounded-full flex items-center justify-center text-red-500 hover:bg-red-50 transition-colors"><i class="fa-solid fa-trash text-[13px]"></i></button>
            </div>
        </div>
        <button onclick="window.addPaymentMethod()" type="button" class="w-full h-11 rounded-[14px] border-2 border-dashed border-slate-200 text-slate-500 font-bold text-[13.5px] hover:border-[#0A5C66]/40 hover:text-[#0A5C66] transition-colors">
            <i class="fa-solid fa-plus mr-1.5"></i> Add Payment Method
        </button>
    </div>
</div>

<!-- Privacy & Data -->
<div id="modal-privacy" class="settings-modal hidden" data-settings-modal>
    <div class="settings-modal-card">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-[18px] text-slate-800 font-poppins">Privacy & Data</h3>
            <button onclick="window.closeSettingsModal()" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <label class="flex items-center justify-between py-3 mb-2 border-b border-slate-100">
            <span class="text-[14px] font-bold text-slate-700">Share usage data to improve GullakPe</span>
            <input type="checkbox" checked class="settings-toggle">
        </label>
        <button onclick="window.saveSettingsModal('privacy')" class="w-full h-12 rounded-[14px] bg-[#0A5C66] text-white font-bold text-[14.5px] mt-3 hover:bg-[#0E7481] transition-colors active:scale-[0.98]">
            Save Changes
        </button>
        <button onclick="window.deleteAccountDemo()" type="button" class="w-full h-11 rounded-[14px] text-red-600 font-bold text-[13.5px] mt-2 hover:bg-red-50 transition-colors">
            Delete Account
        </button>
    </div>
</div>
