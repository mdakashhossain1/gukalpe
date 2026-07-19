@extends('layouts.app')

@section('content')
    <div id="tab-rewards" class="flex min-h-[100dvh] flex-col flex-1 bg-[#F8FAFC] pb-28 pt-safe overflow-y-auto custom-scrollbar w-full relative">
        <!-- Sticky Top App Bar -->
        <div class="sticky top-0 z-[100] w-full h-[64px] bg-white border-b border-black/[0.05] flex items-center px-4 justify-between shrink-0">
            <a href="{{ route('home') }}" class="w-10 h-10 rounded-full bg-white border border-[#0B5C63]/20 flex items-center justify-center shadow-[0_2px_8px_rgba(11,92,99,0.08)] active:scale-95 transition-all">
                <i class="fa-solid fa-arrow-left text-[#0B5C63] text-[16px]"></i>
            </a>
            <h1 class="text-[18px] font-black text-slate-800 font-poppins absolute left-1/2 transform -translate-x-1/2">
                Refer &amp; Earn
            </h1>
            <button type="button" onclick="window.toggleLanguage && window.toggleLanguage()" aria-label="Switch language"
                class="relative w-10 h-10 rounded-full bg-white border border-[#0B5C63]/20 flex items-center justify-center text-[#0B5C63] shadow-[0_2px_8px_rgba(11,92,99,0.08)] active:scale-95 transition-all">
                <i class="bi bi-translate text-[15px]"></i>
                <span data-current-lang class="absolute -bottom-1 -right-1 bg-[#0A5C66] text-white text-[8px] font-black px-1 rounded-full leading-tight border-2 border-white">EN</span>
            </button>
        </div>

        @if (! $user)
            <!-- Guest CTA -->
            <div class="flex-1 flex flex-col items-center justify-center text-center px-8 py-16">
                <div class="w-20 h-20 rounded-full bg-[#0A5C66]/10 flex items-center justify-center mb-5">
                    <i class="bi bi-gift-fill text-[32px] text-[#0A5C66]"></i>
                </div>
                <h2 class="text-[20px] font-black text-[#0F172A] font-poppins tracking-tight mb-2">Refer &amp; Earn</h2>
                <p class="text-[14px] text-slate-500 font-medium leading-relaxed max-w-[280px] mb-8">Sign in to get your personal referral link and start earning when your friends invest.</p>
                <a href="{{ route('login') }}" class="w-full max-w-[240px] h-[52px] bg-gradient-to-r from-[#0A5C66] to-[#148e9e] text-white font-bold text-[15px] rounded-[20px] shadow-[0_10px_20px_rgba(10,92,102,0.2)] active:scale-95 transition-all flex items-center justify-center gap-2">
                    Login / Sign Up
                </a>
            </div>
        @else
            <div class="px-5 pt-5 pb-24 flex flex-col gap-5">
                <!-- 1. Reward Balance -->
                <div class="bg-gradient-to-br from-[#0A5C66] to-[#052A30] rounded-[20px] px-5 py-4 text-white shadow-md relative overflow-hidden flex items-center justify-between gap-3">
                    <div class="absolute -right-8 -top-8 w-28 h-28 bg-[#3FEA8A]/10 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="absolute -left-8 -bottom-8 w-28 h-28 bg-[#0A5C66]/30 rounded-full blur-2xl pointer-events-none"></div>

                    <div class="relative z-10 min-w-0">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-white/50 block">Reward Balance</span>
                        <p class="text-[10px] text-white/40 font-semibold leading-snug mt-1 max-w-[170px]"><span>Credited instantly to your wallet -</span> {{ $commissionPercent }}% <span>of every friend's first investment</span></p>
                    </div>
                    <div class="relative z-10 text-[26px] font-black text-[#3FEA8A] font-poppins tracking-tight shrink-0">₹{{ number_format($totalCommission, 2) }}</div>
                </div>

                <!-- 2. Commission Center -->
                <div class="bg-white rounded-[20px] border border-slate-100 shadow-[0_1px_2px_rgba(15,23,42,0.04)] px-4 py-3.5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[13.5px] font-black text-[#0F172A] font-poppins tracking-tight">Commission Center</h3>
                        <span class="text-[9.5px] font-bold text-[#19B36B] bg-[#19B36B]/10 px-2 py-0.5 rounded-full">Instant payout</span>
                    </div>

                    <div class="grid grid-cols-3 divide-x divide-slate-100">
                        <div class="flex flex-col items-center text-center px-1">
                            <div class="w-7 h-7 rounded-[9px] bg-[#19B36B]/10 flex items-center justify-center text-[#19B36B] mb-1">
                                <i class="bi bi-piggy-bank-fill text-[12px]"></i>
                            </div>
                            <span class="text-[9px] font-semibold text-slate-400 uppercase tracking-wide">Total Earned</span>
                            <span class="text-[13px] font-black text-[#0F172A] font-poppins mt-0.5 truncate max-w-full">₹{{ number_format($totalCommission, 2) }}</span>
                        </div>
                        <div class="flex flex-col items-center text-center px-1">
                            <div class="w-7 h-7 rounded-[9px] bg-[#0A5C66]/10 flex items-center justify-center text-[#0A5C66] mb-1">
                                <i class="bi bi-wallet2 text-[12px]"></i>
                            </div>
                            <span class="text-[9px] font-semibold text-slate-400 uppercase tracking-wide">Wallet</span>
                            <span class="text-[13px] font-black text-[#0F172A] font-poppins mt-0.5 truncate max-w-full">₹{{ number_format($walletBalance, 2) }}</span>
                        </div>
                        <div class="flex flex-col items-center text-center px-1">
                            <div class="w-7 h-7 rounded-[9px] bg-amber-500/10 flex items-center justify-center text-amber-500 mb-1">
                                <i class="bi bi-percent text-[12px]"></i>
                            </div>
                            <span class="text-[9px] font-semibold text-slate-400 uppercase tracking-wide">Your Rate</span>
                            <span class="text-[13px] font-black text-[#0F172A] font-poppins mt-0.5 truncate max-w-full">{{ $commissionPercent }}%</span>
                        </div>
                    </div>
                </div>

                <!-- 3. Referral Statistics -->
                <div>
                    <h3 class="text-[15px] font-black text-[#0F172A] font-poppins tracking-tight mb-3">Referral Statistics</h3>
                    <div class="grid grid-cols-4 gap-2.5">
                        <div class="bg-white rounded-[16px] border border-slate-100 p-3 text-center">
                            <span class="block text-[17px] font-black text-[#0F172A] font-poppins">{{ $totalInvites }}</span>
                            <span class="block text-[10px] font-semibold text-slate-400 mt-0.5">Total Invites</span>
                        </div>
                        <div class="bg-white rounded-[16px] border border-slate-100 p-3 text-center">
                            <span class="block text-[17px] font-black text-[#0F172A] font-poppins">{{ $totalRegistered }}</span>
                            <span class="block text-[10px] font-semibold text-slate-400 mt-0.5">Registered</span>
                        </div>
                        <div class="bg-white rounded-[16px] border border-slate-100 p-3 text-center">
                            <span class="block text-[17px] font-black text-[#0F172A] font-poppins">{{ $totalDeposited }}</span>
                            <span class="block text-[10px] font-semibold text-slate-400 mt-0.5">Deposited</span>
                        </div>
                        <div class="bg-white rounded-[16px] border border-slate-100 p-3 text-center">
                            <span class="block text-[17px] font-black text-[#19B36B] font-poppins">{{ $totalInvested }}</span>
                            <span class="block text-[10px] font-semibold text-slate-400 mt-0.5">Invested</span>
                        </div>
                    </div>
                </div>

                <!-- 4. Referral link + QR -->
                <div class="bg-white rounded-[24px] border border-slate-100 shadow-[0_4px_20px_rgba(15,23,42,0.05)] overflow-hidden">
                    <div class="p-5 pb-0">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-full bg-[#0A5C66]/10 flex items-center justify-center text-[#0A5C66] shrink-0">
                                <i class="bi bi-link-45deg text-[17px]"></i>
                            </div>
                            <h3 class="text-[15px] font-black text-[#0F172A] font-poppins tracking-tight">Your Referral Link</h3>
                        </div>

                        <!-- Full link - the only thing that's ever shared. There is
                             deliberately no separate "code" shown anywhere on this
                             page: registration only counts when it happens through
                             this exact link, so nothing shorter or separately
                             copyable is exposed. -->
                        <div class="rounded-[18px] bg-gradient-to-br from-[#0A5C66]/[0.05] to-[#3FEA8A]/[0.08] border border-dashed border-[#0A5C66]/25 p-4">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-11 rounded-[12px] bg-white border border-[#0A5C66]/10 flex items-center px-3.5 min-w-0">
                                    <input type="text" id="referral-link-input" readonly value="{{ $referralLink }}"
                                        class="w-full bg-transparent outline-none text-[12px] font-semibold text-slate-500 truncate">
                                </div>
                                <button type="button" id="referral-copy-btn"
                                    class="shrink-0 h-11 px-4 rounded-[12px] bg-[#0A5C66] text-white text-[13px] font-bold active:scale-95 transition-all">
                                    Copy
                                </button>
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-400 font-semibold mt-2.5 leading-relaxed">Only friends who sign up through this link count as your referral.</p>
                    </div>

                    <!-- QR Code -->
                    <div class="flex flex-col items-center mt-5 py-6 bg-slate-50/60 border-y border-slate-50">
                        <div class="relative w-[160px] h-[160px] rounded-[20px] bg-white shadow-[0_8px_24px_rgba(15,23,42,0.08)] p-3">
                            <img id="referral-qr-img" src="{{ $qrCodeUrl }}" alt="Referral QR code" class="w-full h-full object-contain rounded-[8px]">
                            <div class="absolute -top-1.5 -left-1.5 w-5 h-5 border-t-2 border-l-2 border-[#0A5C66] rounded-tl-[10px]"></div>
                            <div class="absolute -top-1.5 -right-1.5 w-5 h-5 border-t-2 border-r-2 border-[#0A5C66] rounded-tr-[10px]"></div>
                            <div class="absolute -bottom-1.5 -left-1.5 w-5 h-5 border-b-2 border-l-2 border-[#0A5C66] rounded-bl-[10px]"></div>
                            <div class="absolute -bottom-1.5 -right-1.5 w-5 h-5 border-b-2 border-r-2 border-[#0A5C66] rounded-br-[10px]"></div>
                        </div>
                        <p class="text-[11px] text-slate-400 font-semibold mt-3">Scan to open your referral link</p>
                        <button type="button" id="referral-qr-download-btn"
                            class="mt-1.5 text-[12.5px] font-bold text-[#0A5C66] hover:underline inline-flex items-center gap-1.5">
                            <i class="bi bi-download text-[11px]"></i> Download QR Code
                        </button>
                    </div>

                    @php
                        $shareMessage = 'Join me on GullakPe and start growing your money! '.$referralLink;
                    @endphp
                    <div class="px-5 pb-5">
                        <span class="text-[11px] font-semibold text-slate-400 block mb-3">Share via</span>
                        <div class="grid grid-cols-4 gap-1.5">
                            <a href="https://wa.me/?text={{ urlencode($shareMessage) }}" target="_blank" rel="noopener"
                                class="flex flex-col items-center gap-1 py-2 rounded-[12px] active:scale-95 transition-all">
                                <div class="w-9 h-9 rounded-[11px] bg-[#25D366] flex items-center justify-center text-white"><i class="fa-brands fa-whatsapp text-[16px]"></i></div>
                                <span class="text-[9.5px] font-bold text-slate-600">WhatsApp</span>
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($referralLink) }}" target="_blank" rel="noopener"
                                class="flex flex-col items-center gap-1 py-2 rounded-[12px] active:scale-95 transition-all">
                                <div class="w-9 h-9 rounded-[11px] bg-[#1877F2] flex items-center justify-center text-white"><i class="fa-brands fa-facebook-f text-[14px]"></i></div>
                                <span class="text-[9.5px] font-bold text-slate-600">Facebook</span>
                            </a>
                            <a href="https://twitter.com/intent/tweet?text={{ urlencode($shareMessage) }}" target="_blank" rel="noopener"
                                class="flex flex-col items-center gap-1 py-2 rounded-[12px] active:scale-95 transition-all">
                                <div class="w-9 h-9 rounded-[11px] bg-black flex items-center justify-center text-white"><i class="fa-brands fa-twitter text-[14px]"></i></div>
                                <span class="text-[9.5px] font-bold text-slate-600">X</span>
                            </a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($referralLink) }}" target="_blank" rel="noopener"
                                class="flex flex-col items-center gap-1 py-2 rounded-[12px] active:scale-95 transition-all">
                                <div class="w-9 h-9 rounded-[11px] bg-[#0A66C2] flex items-center justify-center text-white"><i class="fa-brands fa-linkedin-in text-[14px]"></i></div>
                                <span class="text-[9.5px] font-bold text-slate-600">LinkedIn</span>
                            </a>
                            <a href="mailto:?subject={{ urlencode('Join me on GullakPe') }}&body={{ urlencode($shareMessage) }}"
                                class="flex flex-col items-center gap-1 py-2 rounded-[12px] active:scale-95 transition-all">
                                <div class="w-9 h-9 rounded-[11px] bg-slate-500 flex items-center justify-center text-white"><i class="fa-solid fa-envelope text-[14px]"></i></div>
                                <span class="text-[9.5px] font-bold text-slate-600">Email</span>
                            </a>
                            <a href="https://t.me/share/url?url={{ urlencode($referralLink) }}&text={{ urlencode('Join me on GullakPe and start growing your money!') }}" target="_blank" rel="noopener"
                                class="flex flex-col items-center gap-1 py-2 rounded-[12px] active:scale-95 transition-all">
                                <div class="w-9 h-9 rounded-[11px] bg-[#26A5E4] flex items-center justify-center text-white"><i class="fa-brands fa-telegram text-[16px]"></i></div>
                                <span class="text-[9.5px] font-bold text-slate-600">Telegram</span>
                            </a>
                            <a href="sms:?body={{ urlencode($shareMessage) }}"
                                class="flex flex-col items-center gap-1 py-2 rounded-[12px] active:scale-95 transition-all">
                                <div class="w-9 h-9 rounded-[11px] bg-[#19B36B] flex items-center justify-center text-white"><i class="fa-solid fa-comment-sms text-[14px]"></i></div>
                                <span class="text-[9.5px] font-bold text-slate-600">SMS</span>
                            </a>
                            <button type="button" id="referral-native-share-btn"
                                class="hidden flex-col items-center gap-1 py-2 rounded-[12px] active:scale-95 transition-all">
                                <div class="w-9 h-9 rounded-[11px] bg-slate-700 flex items-center justify-center text-white"><i class="fa-solid fa-ellipsis text-[16px]"></i></div>
                                <span class="text-[9.5px] font-bold text-slate-600">More</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 5. Referral History -->
                <div>
                    <h3 class="text-[15px] font-black text-[#0F172A] font-poppins tracking-tight mb-3">Referral History</h3>

                    @if ($referralHistory->isEmpty())
                        <div class="bg-white rounded-[22px] border border-slate-100 p-8 text-center">
                            <i class="bi bi-person-plus text-[28px] text-slate-300"></i>
                            <p class="text-[13px] text-slate-400 font-semibold mt-3">No referrals yet. Share your link to start earning.</p>
                        </div>
                    @else
                        <div class="flex flex-col gap-3">
                            @foreach ($referralHistory as $entry)
                                <div class="bg-white rounded-[18px] border border-slate-100 shadow-[0_1px_2px_rgba(15,23,42,0.04)] p-4 flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 shrink-0">
                                        <i class="bi bi-person-fill text-[16px]"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-[13.5px] font-black text-[#1a153a] font-poppins truncate">{{ $entry['name'] }}</h4>
                                        <p class="text-[11px] text-slate-400 font-medium truncate">{{ $entry['maskedPhone'] }} · {{ $entry['joinedAt']->format('d M Y') }}</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        @if ($entry['hasInvested'])
                                            <span class="inline-block text-[10px] font-bold text-[#19B36B] bg-[#19B36B]/10 px-2 py-0.5 rounded-full">Invested</span>
                                        @else
                                            <span class="inline-block text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">Registered</span>
                                        @endif
                                        @if ($entry['commissionEarned'])
                                            <div class="text-[12.5px] font-black text-[#0A5C66] font-poppins mt-1">+₹{{ number_format($entry['commissionEarned'], 2) }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- 6. FAQ -->
                <div>
                    <h3 class="text-[15px] font-black text-[#0F172A] font-poppins tracking-tight mb-3">Frequently Asked Questions</h3>
                    <div class="flex flex-col gap-2.5" id="rewards-faq">
                        @php
                            $faqs = [
                                ['q' => 'How do I earn a commission?', 'a' => 'Share your referral link or QR code. When someone signs up through it and makes their first investment, you automatically earn a percentage of that investment (set by GullakPe) - credited straight to your wallet.'],
                                ['q' => 'Is there a limit to how much I can earn?', 'a' => 'No. You earn a commission for every friend you refer who invests for the first time - there is no cap on the number of referrals.'],
                                ['q' => 'When do I get paid?', 'a' => 'Instantly. The moment your friend\'s first investment is confirmed, the commission is credited to your GullakPe wallet - no waiting, no claim step.'],
                                ['q' => 'Do I earn from my friend\'s future investments too?', 'a' => 'No, the commission applies only to their first investment on GullakPe.'],
                                ['q' => 'What if the referral program is paused?', 'a' => 'GullakPe may enable, disable, or adjust the commission rate at any time. Any commission already credited to your wallet is yours to keep.'],
                            ];
                        @endphp
                        @foreach ($faqs as $i => $faq)
                            <div class="bg-white rounded-[16px] border border-slate-100 overflow-hidden" data-faq-item>
                                <button type="button" data-faq-toggle class="w-full flex items-center justify-between gap-3 p-4 text-left">
                                    <span class="text-[13px] font-bold text-[#1a153a]">{{ $faq['q'] }}</span>
                                    <i class="bi bi-chevron-down text-[12px] text-slate-400 shrink-0 transition-transform" data-faq-chevron></i>
                                </button>
                                <div class="hidden px-4 pb-4" data-faq-body>
                                    <p class="text-[12.5px] text-slate-500 font-medium leading-relaxed">{{ $faq['a'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <script>
                (function () {
                    var btn = document.getElementById('referral-copy-btn');
                    var input = document.getElementById('referral-link-input');
                    if (btn && input) {
                        btn.addEventListener('click', function () {
                            navigator.clipboard.writeText(input.value).then(function () {
                                var original = btn.textContent;
                                btn.textContent = 'Copied!';
                                setTimeout(function () { btn.textContent = original; }, 1500);
                            });
                        });
                    }

                    // The QR image lives on api.qrserver.com, not this app's
                    // own origin - a plain <a download> is silently ignored by
                    // every browser for cross-origin URLs (a security
                    // restriction, not a bug in the markup), so it just opened
                    // the image instead of saving it. Fetching it ourselves and
                    // downloading from the resulting same-origin blob: URL is
                    // the real fix; this only works because that API happens to
                    // send Access-Control-Allow-Origin: * (confirmed before
                    // shipping this), otherwise the fetch itself would be
                    // blocked by CORS too.
                    var qrDownloadBtn = document.getElementById('referral-qr-download-btn');
                    var qrImg = document.getElementById('referral-qr-img');
                    if (qrDownloadBtn && qrImg) {
                        qrDownloadBtn.addEventListener('click', function () {
                            fetch(qrImg.src)
                                .then(function (res) { return res.blob(); })
                                .then(function (blob) {
                                    var blobUrl = URL.createObjectURL(blob);
                                    var link = document.createElement('a');
                                    link.href = blobUrl;
                                    link.download = 'gullakpe-referral-qr.png';
                                    document.body.appendChild(link);
                                    link.click();
                                    link.remove();
                                    URL.revokeObjectURL(blobUrl);
                                })
                                .catch(function () {
                                    // Fallback if the fetch itself fails (offline,
                                    // API down, etc.) - at least let the user get
                                    // to the image and save it manually.
                                    window.open(qrImg.src, '_blank', 'noopener');
                                });
                        });
                    }

                    document.querySelectorAll('[data-faq-toggle]').forEach(function (toggle) {
                        toggle.addEventListener('click', function () {
                            var item = toggle.closest('[data-faq-item]');
                            var body = item.querySelector('[data-faq-body]');
                            var chevron = item.querySelector('[data-faq-chevron]');
                            var isOpen = !body.classList.contains('hidden');

                            body.classList.toggle('hidden', isOpen);
                            chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
                        });
                    });

                    // Instagram has no web share-intent URL (unlike the others
                    // above), so the only real way to reach it from a web page
                    // is the OS's own share sheet - only show this button where
                    // that API actually exists rather than showing a dead one.
                    var nativeShareBtn = document.getElementById('referral-native-share-btn');
                    if (nativeShareBtn && input && navigator.share) {
                        nativeShareBtn.classList.remove('hidden');
                        nativeShareBtn.classList.add('flex');
                        nativeShareBtn.addEventListener('click', function () {
                            navigator.share({
                                title: 'GullakPe',
                                text: 'Join me on GullakPe and start growing your money!',
                                url: input.value
                            }).catch(function () {});
                        });
                    }
                })();
            </script>
        @endif
    </div>
@endsection
