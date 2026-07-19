@extends('layouts.simple')

@section('title', 'Frequently Asked Questions')

@section('backRoute', route('profile'))

@section('content')

    @php
        $faqGroups = [
            [
                'heading' => 'Account & Security',
                'items' => [
                    ['q' => 'How do I create a GullakPe account?', 'a' => 'Enter your phone number, verify it with a one-time password (OTP), then set a 4-digit MPIN. You can also sign in with Google.'],
                    ['q' => 'What if I forget my MPIN?', 'a' => 'Tap "Forgot MPIN?" on the login screen. We\'ll verify your phone number with an OTP and let you set a new one.'],
                    ['q' => 'Is my money and personal data safe?', 'a' => 'Your MPIN is stored in encrypted form and is never visible to anyone, including GullakPe. Login is protected by OTP verification and progressive lockouts after repeated failed attempts.'],
                ],
            ],
            [
                'heading' => 'Deposits & Withdrawals',
                'items' => [
                    ['q' => 'How do I add money to my wallet?', 'a' => 'Go to Add Money, enter an amount, pay via UPI, then submit your payment reference number. Once verified, it\'s credited to your wallet - usually within a few hours.'],
                    ['q' => 'How do I withdraw money?', 'a' => 'Go to Withdraw Money and request a withdrawal to your registered bank account. Requests are processed after a quick review.'],
                ],
            ],
            [
                'heading' => 'Investments',
                'items' => [
                    ['q' => 'How do investment plans work?', 'a' => 'Choose a plan, invest an amount from your wallet, and watch your returns grow daily based on that plan\'s rate. Some plans have a lock-in period before you can withdraw.'],
                    ['q' => 'Are my returns guaranteed?', 'a' => 'Only where a plan explicitly states a guaranteed return. Otherwise, all investments carry risk and past performance shown in the app is illustrative, not a promise of future results.'],
                ],
            ],
            [
                'heading' => 'Refer & Earn',
                'items' => [
                    ['q' => 'How do I earn a commission?', 'a' => 'Share your referral link or QR code. When someone signs up through it and makes their first investment, you automatically earn a percentage of that investment (set by GullakPe) - credited straight to your wallet.'],
                    ['q' => 'When do I get paid?', 'a' => 'Instantly. The moment your friend\'s first investment is confirmed, the commission is credited to your GullakPe wallet - no waiting, no claim step.'],
                ],
            ],
            [
                'heading' => 'Language & Support',
                'items' => [
                    ['q' => 'Can I use the app in Hindi?', 'a' => 'Yes. Tap the translate icon at the top of most screens to switch between English and Hindi at any time.'],
                    ['q' => 'How do I contact support?', 'a' => 'Reach out through the support options available in the app, or refer to our Terms of Use and Privacy Policy pages for more details on how GullakPe operates.'],
                ],
            ],
        ];
    @endphp

    <div class="mt-6 mb-8">
        <h1 class="text-[26px] font-black text-[#1a153a] font-poppins tracking-tight">Frequently Asked Questions</h1>
        <p class="text-[13px] text-slate-400 font-semibold mt-1">Answers to common questions about GullakPe</p>
    </div>

    <div class="flex flex-col gap-6 pb-16">
        @foreach ($faqGroups as $group)
            <div>
                <h2 class="text-[13px] font-black text-[#0A5C66] font-poppins tracking-tight uppercase mb-2.5">{{ $group['heading'] }}</h2>
                <div class="flex flex-col gap-2.5" data-faq-group>
                    @foreach ($group['items'] as $faq)
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
        @endforeach
    </div>

    <script>
        (function () {
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
        })();
    </script>

@endsection
