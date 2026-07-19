@extends('layouts.app')

@section('content')
    <div id="tab-profile" class="flex min-h-[100dvh] w-full flex-col flex-1 bg-[#F8FAFC] pb-28 pt-safe overflow-y-auto custom-scrollbar">

        @if ($user)
            <!-- Header -->
            <div class="px-5 pt-6 pb-2 flex items-center gap-2.5">
                <img src="{{ asset('assets/logo.png') }}" alt="GullakPe" class="w-8 h-8 rounded-full object-cover">
                <span class="font-black text-[#0A5C66] text-[16px] leading-none tracking-tight font-poppins">GullakPe</span>
            </div>

            <div class="px-5 pt-3 flex flex-col flex-1">
                <!-- Hero User Card -->
                <div class="bg-gradient-to-br from-[#0A5C66] to-[#04242F] rounded-[22px] p-5 text-white shadow-[0_15px_40px_-15px_rgba(10,92,102,0.6)] relative overflow-hidden mb-6">
                    <div class="absolute -top-16 -right-16 w-[160px] h-[160px] bg-[#3FEA8A]/[0.08] rounded-full blur-[40px] pointer-events-none"></div>
                    <div class="relative z-10 flex items-center gap-3.5">
                        <img src="{{ $user->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed='.urlencode($user->name) }}" alt="{{ $user->name }}" class="w-14 h-14 rounded-full border-2 border-white/20 object-cover bg-white/10 shrink-0">
                        <div class="min-w-0">
                            <h2 class="text-[16px] font-black tracking-tight font-poppins truncate">{{ $user->name }}</h2>
                            <p class="text-[11.5px] font-semibold text-white/60 mt-0.5">Joined {{ $user->created_at->format('d M \'y') }}</p>
                            @if ($user->phone)
                                <p class="text-[11.5px] font-semibold text-white/60">+91 {{ $user->phone }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-2 gap-3 mb-7">
                    <a href="{{ route('portfolio') }}" class="flex flex-col items-center justify-center gap-2 bg-white rounded-[18px] border border-slate-100 shadow-[0_1px_2px_rgba(15,23,42,0.04)] py-5 transition-all active:scale-[0.98] hover:border-[#0A5C66]/20">
                        <div class="w-10 h-10 rounded-full bg-[#0A5C66]/8 flex items-center justify-center text-[#0A5C66]">
                            <i class="bi bi-person-circle text-[18px]"></i>
                        </div>
                        <div class="text-center">
                            <span class="block text-[13px] font-black text-[#0F172A] font-poppins leading-tight">My Account</span>
                            <span class="block text-[10.5px] text-slate-400 font-semibold mt-0.5">Portfolio &amp; holdings</span>
                        </div>
                    </a>
                    <a href="{{ route('portfolio') }}" class="flex flex-col items-center justify-center gap-2 bg-white rounded-[18px] border border-slate-100 shadow-[0_1px_2px_rgba(15,23,42,0.04)] py-5 transition-all active:scale-[0.98] hover:border-[#0A5C66]/20">
                        <div class="w-10 h-10 rounded-full bg-[#19B36B]/10 flex items-center justify-center text-[#19B36B]">
                            <i class="bi bi-clock-history text-[18px]"></i>
                        </div>
                        <div class="text-center">
                            <span class="block text-[13px] font-black text-[#0F172A] font-poppins leading-tight">History</span>
                            <span class="block text-[10.5px] text-slate-400 font-semibold mt-0.5">See transactions</span>
                        </div>
                    </a>
                </div>

                <!-- Manage -->
                <span class="text-[11px] font-bold text-slate-400 tracking-wider uppercase px-1 font-poppins mb-2.5 block">Manage</span>
                <div class="flex flex-col gap-2.5 mb-7">
                    <a href="{{ route('rewards') }}" class="w-full bg-white p-4 rounded-[16px] border border-slate-100 shadow-[0_1px_2px_rgba(15,23,42,0.04)] flex items-center justify-between transition-all active:scale-[0.98] hover:border-[#0A5C66]/20">
                        <div class="flex items-center gap-3.5">
                            <i class="bi bi-gift text-[17px] text-[#0A5C66] w-5 text-center"></i>
                            <span class="text-[13.5px] font-bold text-[#0F172A] font-poppins">My Rewards</span>
                        </div>
                        <i class="bi bi-chevron-right text-[12px] text-slate-300"></i>
                    </a>
                    <a href="{{ route('notifications') }}" class="w-full bg-white p-4 rounded-[16px] border border-slate-100 shadow-[0_1px_2px_rgba(15,23,42,0.04)] flex items-center justify-between transition-all active:scale-[0.98] hover:border-[#0A5C66]/20">
                        <div class="flex items-center gap-3.5">
                            <i class="bi bi-bell text-[17px] text-[#0A5C66] w-5 text-center"></i>
                            <span class="text-[13.5px] font-bold text-[#0F172A] font-poppins">Notifications</span>
                        </div>
                        <i class="bi bi-chevron-right text-[12px] text-slate-300"></i>
                    </a>
                    <div class="w-full bg-white p-4 rounded-[16px] border border-slate-100 shadow-[0_1px_2px_rgba(15,23,42,0.04)] flex items-center justify-between opacity-60">
                        <div class="flex items-center gap-3.5">
                            <i class="bi bi-bank2 text-[17px] text-slate-400 w-5 text-center"></i>
                            <span class="text-[13.5px] font-bold text-slate-500 font-poppins">Bank Account</span>
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Soon</span>
                    </div>
                    <div class="w-full bg-white p-4 rounded-[16px] border border-slate-100 shadow-[0_1px_2px_rgba(15,23,42,0.04)] flex items-center justify-between opacity-60">
                        <div class="flex items-center gap-3.5">
                            <i class="bi bi-file-earmark-text text-[17px] text-slate-400 w-5 text-center"></i>
                            <span class="text-[13.5px] font-bold text-slate-500 font-poppins">Statements</span>
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Soon</span>
                    </div>
                </div>

                <!-- Support & Others -->
                <span class="text-[11px] font-bold text-slate-400 tracking-wider uppercase px-1 font-poppins mb-2.5 block">Support &amp; Others</span>
                <div class="flex flex-col gap-2.5 mb-6 pb-safe">
                    <a href="{{ route('legal.faq') }}" class="w-full bg-white p-4 rounded-[16px] border border-slate-100 shadow-[0_1px_2px_rgba(15,23,42,0.04)] flex items-center justify-between transition-all active:scale-[0.98] hover:border-[#0A5C66]/20">
                        <div class="flex items-center gap-3.5">
                            <i class="bi bi-question-circle text-[17px] text-[#0A5C66] w-5 text-center"></i>
                            <span class="text-[13.5px] font-bold text-[#0F172A] font-poppins">Frequently Asked Questions</span>
                        </div>
                        <i class="bi bi-chevron-right text-[12px] text-slate-300"></i>
                    </a>
                    <button type="button" onclick="window.toggleLanguage && window.toggleLanguage()"
                        class="w-full bg-white p-4 rounded-[16px] border border-slate-100 shadow-[0_1px_2px_rgba(15,23,42,0.04)] flex items-center justify-between transition-all active:scale-[0.98] hover:border-[#0A5C66]/20">
                        <div class="flex items-center gap-3.5">
                            <i class="bi bi-globe2 text-[17px] text-[#0A5C66] w-5 text-center"></i>
                            <span class="text-[13.5px] font-bold text-[#0F172A] font-poppins">Language &amp; Region</span>
                        </div>
                        <span class="flex items-center gap-1.5 text-[11px] font-bold text-slate-400">
                            <span data-current-lang>EN</span>
                            <i class="bi bi-arrow-left-right text-[11px] text-slate-300"></i>
                        </span>
                    </button>
                    @foreach ([
                        ['icon' => 'file-earmark-ruled', 'label' => 'Documents'],
                        ['icon' => 'person-vcard', 'label' => 'Profile Information'],
                        ['icon' => 'shield-lock', 'label' => 'Security'],
                        ['icon' => 'credit-card', 'label' => 'Payment Methods'],
                    ] as $item)
                        <div class="w-full bg-white p-4 rounded-[16px] border border-slate-100 shadow-[0_1px_2px_rgba(15,23,42,0.04)] flex items-center justify-between opacity-60">
                            <div class="flex items-center gap-3.5">
                                <i class="bi bi-{{ $item['icon'] }} text-[17px] text-slate-400 w-5 text-center"></i>
                                <span class="text-[13.5px] font-bold text-slate-500 font-poppins">{!! $item['label'] !!}</span>
                            </div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Soon</span>
                        </div>
                    @endforeach

                    <a href="{{ route('legal.privacy') }}" class="w-full bg-white p-4 rounded-[16px] border border-slate-100 shadow-[0_1px_2px_rgba(15,23,42,0.04)] flex items-center justify-between transition-all active:scale-[0.98] hover:border-[#0A5C66]/20">
                        <div class="flex items-center gap-3.5">
                            <i class="bi bi-lock text-[17px] text-[#0A5C66] w-5 text-center"></i>
                            <span class="text-[13.5px] font-bold text-[#0F172A] font-poppins">Privacy Policy</span>
                        </div>
                        <i class="bi bi-chevron-right text-[12px] text-slate-300"></i>
                    </a>
                    <a href="{{ route('legal.terms') }}" class="w-full bg-white p-4 rounded-[16px] border border-slate-100 shadow-[0_1px_2px_rgba(15,23,42,0.04)] flex items-center justify-between transition-all active:scale-[0.98] hover:border-[#0A5C66]/20">
                        <div class="flex items-center gap-3.5">
                            <i class="bi bi-file-earmark-text text-[17px] text-[#0A5C66] w-5 text-center"></i>
                            <span class="text-[13.5px] font-bold text-[#0F172A] font-poppins">Terms of Use</span>
                        </div>
                        <i class="bi bi-chevron-right text-[12px] text-slate-300"></i>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full bg-white p-4 rounded-[16px] border border-red-100 shadow-[0_1px_2px_rgba(15,23,42,0.04)] flex items-center justify-between transition-all active:scale-[0.98] hover:border-red-200">
                            <div class="flex items-center gap-3.5">
                                <i class="bi bi-box-arrow-right text-[18px] text-red-500 w-5 text-center"></i>
                                <span class="text-[13.5px] font-bold text-red-600 font-poppins">Logout</span>
                            </div>
                            <i class="bi bi-chevron-right text-[12px] text-red-200"></i>
                        </button>
                    </form>
                </div>
            </div>
        @else
            <!-- Guest -->
            <div class="flex-1 flex flex-col items-center justify-center py-16 px-6 text-center min-h-[100dvh]">
                <img src="{{ asset('assets/logo.png') }}" alt="GullakPe" class="w-16 h-16 rounded-2xl object-cover mb-5 shadow-sm">
                <div class="w-16 h-16 bg-[#0A5C66]/5 rounded-[20px] flex items-center justify-center mb-5 text-[#0A5C66] border border-[#0A5C66]/10">
                    <i class="bi bi-person-lock text-[26px]"></i>
                </div>
                <h3 class="text-[17px] font-black text-[#0F172A] font-poppins">Please log in to access your profile</h3>
                <p class="text-[12.5px] text-slate-400 font-semibold mt-2 max-w-[260px] leading-relaxed">
                    Log in to GullakPe to access your bank details, withdrawals, statements, settings, and more.
                </p>
                <a href="{{ route('login') }}" class="mt-6 w-full max-w-[200px] h-[48px] rounded-2xl bg-gradient-to-r from-[#0A5C66] to-[#0d7380] text-white font-bold text-[14px] shadow-md hover:shadow-lg active:scale-95 transition-all flex items-center justify-center">
                    Login / Sign Up
                </a>
            </div>
        @endif

    </div>
@endsection
