@extends('layouts.simple')

@section('title', 'Payment Received')

@section('backRoute', route('home'))

@section('content')

    @php
        $formattedAmount = number_format($amount);
        $formattedDate = $submittedAt->format('d M Y, h:i A');
    @endphp

    <div class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="relative w-full max-w-[400px] bg-white rounded-[22px] shadow-2xl p-5 max-h-[92vh] overflow-y-auto">

            <a href="{{ route('home') }}" aria-label="Close"
                class="absolute top-4 right-4 w-9 h-9 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 hover:text-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0A5C66]/40 transition-colors shadow-sm">
                <i class="bi bi-x-lg text-[14px]"></i>
            </a>

            <div class="flex flex-col items-center text-center pt-2">
                <img src="{{ asset('assets/submit_banner.jpeg') }}" alt="" class="w-24 h-24 object-contain mb-3">
                <h1 class="text-[20px] font-black text-[#0F172A] font-poppins tracking-tight">Payment Received!</h1>
                <p class="text-[12.5px] text-slate-500 font-medium mt-1 mb-3">Thank you! We have received your payment details.</p>
                <span class="inline-flex items-center gap-1.5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11.5px] font-bold px-3.5 py-1.5 rounded-full">
                    <i class="bi bi-check-circle-fill text-[11px]"></i> Your payment is under verification
                </span>
            </div>

            <div class="bg-white border border-slate-100 rounded-[16px] p-4 mt-5 flex gap-4">
                <div class="relative w-[84px] h-[84px] shrink-0">
                    <svg viewBox="0 0 100 100" class="w-[84px] h-[84px] -rotate-90">
                        <circle cx="50" cy="50" r="42" fill="none" stroke="#E2E8F0" stroke-width="8"></circle>
                        <circle cx="50" cy="50" r="42" fill="none" stroke="#0A5C66" stroke-width="8" stroke-linecap="round" stroke-dasharray="264" stroke-dashoffset="185"></circle>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-0">
                        <span class="text-[6.5px] font-bold text-slate-400 uppercase tracking-wide">Verification in</span>
                        <span class="text-[17px] font-black text-[#0F172A] font-poppins leading-tight">5-10</span>
                        <span class="text-[8px] font-bold text-slate-400 uppercase tracking-wide">min</span>
                    </div>
                </div>

                <div class="flex-1 min-w-0 flex flex-col justify-center gap-2.5">
                    <div>
                        <p class="text-[12.5px] font-bold text-[#0F172A]">We're verifying your payment</p>
                        <p class="text-[10.5px] text-slate-500 leading-snug mt-0.5">Our team is checking your payment details. You will receive a notification once the amount is added to your wallet.</p>
                    </div>

                    <div class="flex items-start">
                        <div class="flex flex-col items-center gap-1 w-14 shrink-0">
                            <div class="w-7 h-7 rounded-full bg-[#0A5C66] text-white flex items-center justify-center shrink-0">
                                <i class="bi bi-file-earmark-check-fill text-[11px]"></i>
                            </div>
                            <span class="text-[7.5px] font-bold text-slate-500 text-center leading-tight">Details Received</span>
                        </div>
                        <div class="flex-1 border-t border-dashed border-slate-300 mt-3.5 mx-0.5"></div>
                        <div class="flex flex-col items-center gap-1 w-14 shrink-0">
                            <div class="w-7 h-7 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                                <i class="bi bi-search text-[11px]"></i>
                            </div>
                            <span class="text-[7.5px] font-bold text-slate-500 text-center leading-tight">Under Verification</span>
                        </div>
                        <div class="flex-1 border-t border-dashed border-slate-300 mt-3.5 mx-0.5"></div>
                        <div class="flex flex-col items-center gap-1 w-14 shrink-0">
                            <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center shrink-0">
                                <i class="bi bi-wallet2 text-[11px]"></i>
                            </div>
                            <span class="text-[7.5px] font-bold text-slate-500 text-center leading-tight">Amount Will be Added</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-100 rounded-[16px] p-4 mt-3.5">
                <div class="flex items-center gap-2 mb-3">
                    <i class="bi bi-receipt text-[#0A5C66] text-[15px]"></i>
                    <span class="text-[13px] font-bold text-[#0F172A]">Payment Summary</span>
                </div>
                <div class="grid grid-cols-2 gap-3.5">
                    <div>
                        <span class="block text-[9.5px] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Amount Paid</span>
                        <span class="text-[14px] font-black text-[#0A5C66]">₹{{ $formattedAmount }}</span>
                    </div>
                    <div>
                        <span class="block text-[9.5px] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Payment Method</span>
                        <span class="text-[13px] font-bold text-[#0F172A]">{{ $methodLabel }}</span>
                    </div>
                    <div class="min-w-0">
                        <span class="block text-[9.5px] font-bold text-slate-400 uppercase tracking-wide mb-0.5">UTR Number</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-[13px] font-bold text-[#0F172A] truncate">{{ $utr }}</span>
                            <button type="button" data-copy="{{ $utr }}" aria-label="Copy UTR number" class="copy-btn shrink-0 text-slate-400 hover:text-[#0A5C66] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0A5C66]/40 rounded transition-colors">
                                <i class="bi bi-copy text-[11px]"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <span class="block text-[9.5px] font-bold text-slate-400 uppercase tracking-wide mb-0.5">Date &amp; Time</span>
                        <span class="text-[13px] font-bold text-[#0F172A]">{{ $formattedDate }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-100 rounded-[14px] p-3.5 mt-3.5 flex items-start gap-2.5">
                <i class="bi bi-info-circle-fill text-blue-500 text-[14px] mt-0.5 shrink-0"></i>
                <div>
                    <p class="text-[12.5px] font-bold text-blue-700">What's Next?</p>
                    <p class="text-[11.5px] text-blue-600 leading-relaxed mt-0.5">Once your payment is verified, the amount will be added to your wallet instantly.</p>
                </div>
            </div>

            <div class="bg-amber-50 border border-amber-100 rounded-[14px] p-3.5 mt-3 flex items-start gap-2.5">
                <i class="bi bi-lightbulb-fill text-amber-500 text-[14px] mt-0.5 shrink-0"></i>
                <div>
                    <p class="text-[12.5px] font-bold text-amber-700">Important</p>
                    <p class="text-[11.5px] text-amber-600 leading-relaxed mt-0.5">Please do not make the payment again. Duplicate payments may take longer to verify.</p>
                </div>
            </div>

            <p class="flex items-center justify-center gap-1.5 text-[11.5px] font-semibold text-slate-500 mt-4">
                <i class="bi bi-bell-fill text-[11px] text-slate-400"></i> You will get a notification within 5-10 minutes
            </p>

            <a href="{{ route('home') }}" class="btn-shimmer-cta w-full h-[50px] rounded-[14px] bg-[#0A5C66] text-white font-bold text-[14px] hover:bg-[#0E7481] active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#0A5C66] transition-all shadow-md shadow-[#0A5C66]/20 flex items-center justify-center gap-2 mt-4">
                <i class="bi bi-house-fill text-[13px]"></i> Go to Dashboard
            </a>

            <div class="grid grid-cols-3 gap-2 pt-4 mt-4 border-t border-slate-100">
                <div class="flex flex-col items-center gap-1 text-center">
                    <div class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center">
                        <i class="bi bi-shield-check text-emerald-600 text-[13px]"></i>
                    </div>
                    <span class="text-[9px] font-bold text-slate-600">100% Secure</span>
                    <span class="text-[7.5px] text-slate-400 leading-tight">Your money is safe</span>
                </div>
                <div class="flex flex-col items-center gap-1 text-center">
                    <div class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center">
                        <i class="bi bi-patch-check-fill text-emerald-600 text-[13px]"></i>
                    </div>
                    <span class="text-[9px] font-bold text-slate-600">Manual Verification</span>
                    <span class="text-[7.5px] text-slate-400 leading-tight">Every payment is checked</span>
                </div>
                <div class="flex flex-col items-center gap-1 text-center">
                    <div class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center">
                        <i class="bi bi-headset text-emerald-600 text-[13px]"></i>
                    </div>
                    <span class="text-[9px] font-bold text-slate-600">24x7 Support</span>
                    <span class="text-[7.5px] text-slate-400 leading-tight">We are here to help you</span>
                </div>
            </div>

        </div>
    </div>

    <script>
    (function () {
        document.querySelectorAll('[data-copy]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var value = btn.getAttribute('data-copy');
                var original = btn.innerHTML;
                var restore = function () {
                    setTimeout(function () { btn.innerHTML = original; }, 1500);
                };
                var showCopied = function () {
                    btn.innerHTML = '<i class="bi bi-check-lg text-[11px]"></i>';
                    restore();
                };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(value).then(showCopied).catch(showCopied);
                } else {
                    showCopied();
                }
            });
        });
    })();
    </script>

@endsection
