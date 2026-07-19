    <div id="language-modal"
        class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-sm transform translate-y-full transition-transform duration-300 flex items-end justify-center">
        <div class="bg-white w-full max-w-md rounded-t-[32px] p-6 relative pb-10 max-h-[85vh] overflow-y-auto">
            <button onclick="document.getElementById('language-modal').classList.add('translate-y-full');"
                class="absolute top-4 right-5 w-8 h-8 bg-slate-100 rounded-full flex items-center justify-center text-slate-500 hover:bg-slate-200 sticky float-right z-10">
                <i class="fa-solid fa-times"></i>
            </button>

            <div class="flex justify-center mb-4 mt-2">
                <div class="flex relative">
                    <div
                        class="w-10 h-10 bg-slate-100 text-slate-800 font-black text-xl flex items-center justify-center">
                        A</div>
                    <div
                        class="w-10 h-10 bg-[#0A5C66] text-white font-black text-xl flex items-center justify-center absolute top-3 left-4 border-2 border-white shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
                        अ</div>
                </div>
            </div>

            <h2 class="text-center text-2xl font-black text-slate-900 mb-1">Choose app language</h2>
            <p class="text-center text-sm text-slate-500 font-medium mb-6">We will show the app in your selected
                language</p>

            <div class="grid grid-cols-2 gap-3 mb-6">
                <label
                    class="flex items-center p-3 border-2 border-[#0A5C66] bg-[#0A5C66]/5 rounded-[20px] cursor-pointer lang-option transition-all">
                    <input type="radio" name="app_lang" value="en" class="hidden" checked>
                    <div
                        class="w-5 h-5 rounded-full border-2 border-[#0A5C66] flex items-center justify-center mr-3 radio-indicator shrink-0">
                        <div class="w-2.5 h-2.5 bg-[#0A5C66] rounded-full"></div>
                    </div>
                    <span class="font-bold text-slate-800">English</span>
                </label>

                <label
                    class="flex items-center p-3 border-2 border-slate-100 rounded-[20px] cursor-pointer lang-option transition-all hover:border-slate-200">
                    <input type="radio" name="app_lang" value="hi" class="hidden">
                    <div
                        class="w-5 h-5 rounded-full border-2 border-slate-300 flex items-center justify-center mr-3 radio-indicator shrink-0">
                        <div class="w-2.5 h-2.5 bg-transparent rounded-full"></div>
                    </div>
                    <span class="font-bold text-slate-600">हिन्दी</span>
                </label>

                <label
                    class="flex items-center p-3 border-2 border-slate-100 rounded-[20px] cursor-pointer lang-option transition-all hover:border-slate-200">
                    <input type="radio" name="app_lang" value="mr" class="hidden">
                    <div
                        class="w-5 h-5 rounded-full border-2 border-slate-300 flex items-center justify-center mr-3 radio-indicator shrink-0">
                        <div class="w-2.5 h-2.5 bg-transparent rounded-full"></div>
                    </div>
                    <span class="font-bold text-slate-600">मराठी</span>
                </label>

                <label
                    class="flex items-center p-3 border-2 border-slate-100 rounded-[20px] cursor-pointer lang-option transition-all hover:border-slate-200">
                    <input type="radio" name="app_lang" value="bn" class="hidden">
                    <div
                        class="w-5 h-5 rounded-full border-2 border-slate-300 flex items-center justify-center mr-3 radio-indicator shrink-0">
                        <div class="w-2.5 h-2.5 bg-transparent rounded-full"></div>
                    </div>
                    <span class="font-bold text-slate-600">বাংলা</span>
                </label>

                <label
                    class="flex items-center p-3 border-2 border-slate-100 rounded-[20px] cursor-pointer lang-option transition-all hover:border-slate-200">
                    <input type="radio" name="app_lang" value="gu" class="hidden">
                    <div
                        class="w-5 h-5 rounded-full border-2 border-slate-300 flex items-center justify-center mr-3 radio-indicator shrink-0">
                        <div class="w-2.5 h-2.5 bg-transparent rounded-full"></div>
                    </div>
                    <span class="font-bold text-slate-600">ગુજરાતી</span>
                </label>

                <label
                    class="flex items-center p-3 border-2 border-slate-100 rounded-[20px] cursor-pointer lang-option transition-all hover:border-slate-200">
                    <input type="radio" name="app_lang" value="ta" class="hidden">
                    <div
                        class="w-5 h-5 rounded-full border-2 border-slate-300 flex items-center justify-center mr-3 radio-indicator shrink-0">
                        <div class="w-2.5 h-2.5 bg-transparent rounded-full"></div>
                    </div>
                    <span class="font-bold text-slate-600">தமிழ்</span>
                </label>

                <label
                    class="flex items-center p-3 border-2 border-slate-100 rounded-[20px] cursor-pointer lang-option transition-all hover:border-slate-200">
                    <input type="radio" name="app_lang" value="te" class="hidden">
                    <div
                        class="w-5 h-5 rounded-full border-2 border-slate-300 flex items-center justify-center mr-3 radio-indicator shrink-0">
                        <div class="w-2.5 h-2.5 bg-transparent rounded-full"></div>
                    </div>
                    <span class="font-bold text-slate-600">తెలుగు</span>
                </label>

                <label
                    class="flex items-center p-3 border-2 border-slate-100 rounded-[20px] cursor-pointer lang-option transition-all hover:border-slate-200">
                    <input type="radio" name="app_lang" value="kn" class="hidden">
                    <div
                        class="w-5 h-5 rounded-full border-2 border-slate-300 flex items-center justify-center mr-3 radio-indicator shrink-0">
                        <div class="w-2.5 h-2.5 bg-transparent rounded-full"></div>
                    </div>
                    <span class="font-bold text-slate-600">ಕನ್ನಡ</span>
                </label>
            </div>

            <button id="confirm-lang-btn"
                class="w-full bg-[#0A5C66] text-white font-bold py-4 rounded-[20px] flex items-center justify-center gap-2 active:scale-95 transition-transform shadow-lg shadow-teal-900/20">
                Confirm language <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </div>
