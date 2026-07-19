@extends('layouts.simple')

@section('title', 'Terms of Use')

@section('backRoute', route('home'))

@section('content')

    @php
        $sections = [
            [
                'heading' => 'Acceptance of Terms',
                'paragraphs' => [
                    'By creating a GullakPe account or using any part of the app, you agree to be bound by these Terms of Use and our Privacy Policy.',
                    'If you do not agree to these terms, please do not register for or use GullakPe.',
                ],
            ],
            [
                'heading' => 'Eligibility',
                'paragraphs' => [
                    'You must be at least 18 years old and legally capable of entering into a binding contract to use GullakPe.',
                    'You are responsible for ensuring the phone number and details you provide during registration are accurate and belong to you.',
                ],
            ],
            [
                'heading' => 'Your Account',
                'paragraphs' => [
                    'Access to GullakPe is secured using your phone number, a one-time password (OTP), and an MPIN you set during onboarding, or by signing in with your Google account.',
                    'You are responsible for keeping your MPIN confidential and for all activity that happens under your account. Notify us immediately if you suspect unauthorized access.',
                ],
            ],
            [
                'heading' => 'Wallet, Deposits & Withdrawals',
                'paragraphs' => [
                    'Money you add to GullakPe is credited to your in-app wallet after we verify your UPI payment reference. Verification is typically completed within a few hours but may take longer in some cases.',
                    'Withdrawal requests are processed to your registered bank account after review. GullakPe may set minimum or maximum limits on deposits and withdrawals from time to time.',
                ],
            ],
            [
                'heading' => 'Investment Plans & Risk Disclosure',
                'paragraphs' => [
                    'GullakPe offers savings and investment plans, including fixed-return, gold-linked, and market-linked options. Except where a plan explicitly states a guaranteed return, all investments carry risk and returns are not guaranteed.',
                    'Some plans have a lock-in duration. Withdrawing before the lock-in period ends may reduce your returns or affect eligibility for certain benefits. Past performance shown in the app is illustrative and not a guarantee of future results.',
                ],
            ],
            [
                'heading' => 'Referral & Rewards Program',
                'paragraphs' => [
                    'GullakPe may offer referral cashback and commission rewards for inviting others to join. Reward amounts, eligibility, and the referral program itself may be changed, paused, or discontinued at any time at our discretion.',
                ],
            ],
            [
                'heading' => 'Prohibited Conduct',
                'paragraphs' => [
                    'You agree not to misuse GullakPe, including attempting to access other users\' accounts, interfering with the app\'s normal operation, or using the referral program fraudulently.',
                ],
            ],
            [
                'heading' => 'Limitation of Liability',
                'paragraphs' => [
                    'GullakPe is provided on an "as available" basis. To the maximum extent permitted by law, GullakPe is not liable for indirect or consequential losses arising from your use of the app, including investment losses arising from normal market risk.',
                ],
            ],
            [
                'heading' => 'Changes to These Terms',
                'paragraphs' => [
                    'We may update these Terms of Use from time to time. Continuing to use GullakPe after changes are posted means you accept the updated terms.',
                ],
            ],
            [
                'heading' => 'Governing Law & Contact',
                'paragraphs' => [
                    'These terms are governed by the laws of India. If you have questions about these terms, please reach out to us through the support options available in the app.',
                ],
            ],
        ];
    @endphp

    <div class="mt-6 mb-8">
        <h1 class="text-[26px] font-black text-[#1a153a] font-poppins tracking-tight">Terms of Use</h1>
        <p class="text-[13px] text-slate-400 font-semibold mt-1">Last updated 18 July 2026</p>
    </div>

    <div class="flex flex-col gap-7 pb-16">
        @foreach ($sections as $i => $section)
            <div>
                <h2 class="text-[16px] font-black text-[#0A5C66] font-poppins tracking-tight mb-2">
                    <span class="text-slate-300 mr-1.5">{{ $i + 1 }}.</span>{{ $section['heading'] }}
                </h2>
                <div class="flex flex-col gap-2.5">
                    @foreach ($section['paragraphs'] as $paragraph)
                        <p class="text-[13.5px] text-slate-600 font-medium leading-relaxed">{{ $paragraph }}</p>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="flex items-start gap-2.5 bg-slate-50 border border-slate-100 rounded-[14px] p-4 mt-2">
            <i class="fa-solid fa-circle-info text-[13px] text-slate-400 mt-0.5"></i>
            <p class="text-[12px] text-slate-500 font-medium leading-relaxed">This page is a general summary provided for transparency and does not constitute financial or legal advice.</p>
        </div>
    </div>

@endsection
