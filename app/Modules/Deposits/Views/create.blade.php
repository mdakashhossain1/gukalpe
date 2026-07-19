@extends('layouts.simple')

@section('title', 'Add Money')

@section('backRoute', route('home'))

@section('content')

    @php
        $payToLabel = $activeMethod === 'upi'
            ? ($upiAccount->display_name ?: $upiAccount->upi_id)
            : ($bankAccount->bank_name.' '.substr($bankAccount->account_number, -4));
        $shareText = $activeMethod === 'upi'
            ? "Pay via UPI to {$upiAccount->upi_id} for your GullakPe deposit."
            : "Pay via Bank Transfer to {$bankAccount->account_holder_name}, {$bankAccount->bank_name} (A/C {$bankAccount->account_number}, IFSC {$bankAccount->ifsc_code}) for your GullakPe deposit.";

        $howItWorksSteps = $activeMethod === 'upi'
            ? [
                ['title' => 'Scan & Pay', 'description' => 'Scan the QR code above using any UPI app'],
                ['title' => 'Make Payment', 'description' => 'Pay the exact amount shown above'],
                ['title' => 'Get UTR Number', 'description' => 'After payment, note the UTR / reference number from your UPI app'],
                ['title' => 'Submit Details', 'description' => 'Enter the UTR below and submit this form'],
                ['title' => 'Verification', 'description' => 'We\'ll verify and credit your wallet, usually within a few hours'],
            ]
            : [
                ['title' => 'Copy Bank Details', 'description' => 'Copy the account number and IFSC code shown above'],
                ['title' => 'Make Payment', 'description' => 'Transfer the exact amount via NEFT, IMPS, or RTGS'],
                ['title' => 'Get Reference Number', 'description' => 'After payment, note the UTR / reference number from your bank'],
                ['title' => 'Submit Details', 'description' => 'Enter the reference number below and submit this form'],
                ['title' => 'Verification', 'description' => 'We\'ll verify and credit your wallet, usually within a few hours'],
            ];

        // "upi://pay" is the NPCI-standard intent every compliant UPI app
        // registers as a handler for, used for the generic "Any UPI App"
        // tile. The three named tiles (PhonePe/GPay/Paytm) just launch that
        // app via its bare scheme - no prefilled amount/payee, the user
        // completes the payment inside the app itself.
        if ($activeMethod === 'upi') {
            $upiIntentQuery = http_build_query([
                'pa' => $upiAccount->upi_id,
                'pn' => $upiAccount->display_name ?: 'GullakPe',
                'am' => $amount,
                'cu' => 'INR',
                'tn' => 'GullakPe Deposit',
            ]);

            $upiApps = [
                ['name' => 'PhonePe', 'file' => 'phonepe.png', 'scheme' => 'phonepe://'],
                ['name' => 'Google Pay', 'file' => 'gpay.png', 'scheme' => 'tez://'],
                ['name' => 'Paytm', 'file' => 'paytm.jpg', 'scheme' => 'paytmmp://'],
            ];
        }
    @endphp

    <div class="mt-2 mb-6">
        <h1 class="text-[22px] font-black text-[#0A5C66] font-poppins tracking-tight">Add Money</h1>
        <p class="text-[13.5px] text-slate-500 font-medium mt-1">Pay manually, then submit your reference number for verification.</p>
    </div>

    <form method="POST" action="{{ route('deposits.store') }}" class="flex flex-col gap-5 pb-10">
        @csrf
        <input type="hidden" name="method" value="{{ $activeMethod }}">
        <input type="hidden" name="pay_to_label" value="{{ $payToLabel }}">
        <input type="hidden" name="amount" value="{{ $amount }}">

        @if ($activeMethod === 'upi')
            <!-- UPI QR hero card -->
            <div class="premium-card overflow-hidden">
                <div class="flex items-center justify-between gap-3 bg-gradient-to-br from-[#0A5C66] via-[#0A5C66] to-[#04242F] px-5 py-3.5">
                    <div class="flex items-center gap-2 min-w-0">
                        <i class="bi bi-clock text-amber-400 text-[15px] shrink-0"></i>
                        <span class="text-[12.5px] font-semibold text-white/95 truncate">Complete your payment within</span>
                    </div>
                    <div class="flex items-baseline gap-1 shrink-0">
                        <span id="payment-countdown" class="text-[19px] font-black text-amber-400 font-poppins tracking-tight">15:00</span>
                        <span class="text-[11.5px] font-semibold text-white/80">min</span>
                    </div>
                </div>

                <div class="p-6 flex flex-col items-center text-center gap-1">
                    <h2 class="text-[16px] font-black text-[#1a153a] font-poppins">Scan QR Code to Pay</h2>
                    <p class="text-[12.5px] text-slate-500 font-medium mb-4">Use any UPI app on your phone</p>

                    <div class="p-3 rounded-[18px] border-2 border-slate-100 bg-white">
                        <img id="upi-qr-image" src="{{ $upiAccount->qrImageUrl() }}" alt="{{ $upiAccount->upi_id }}" class="w-[220px] h-[220px] object-contain">
                    </div>

                    <div class="w-full mt-5 bg-slate-50 border border-slate-100 rounded-[14px] p-4">
                        <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-1">Amount to Pay</span>
                        <span class="text-[26px] font-black text-[#1a153a] font-poppins tracking-tight">₹{{ number_format($amount) }}</span>
                        <p class="text-[12px] text-slate-500 font-medium mt-0.5">Meri Gullak Deposit</p>
                    </div>
                </div>

                <div class="px-5 pb-2 flex flex-col gap-3">
                    <div class="flex items-center justify-between bg-white border border-slate-100 rounded-[14px] px-4 h-14">
                        <div class="flex flex-col text-left min-w-0">
                            <span class="text-[10.5px] font-bold text-slate-500 uppercase tracking-wide">UPI ID</span>
                            <span class="text-[14px] font-bold text-[#1a153a] truncate">{{ $upiAccount->upi_id }}</span>
                        </div>
                        <button type="button" data-copy="{{ $upiAccount->upi_id }}" class="copy-btn shrink-0 h-9 px-3 rounded-[10px] border border-slate-200 text-[12px] font-bold text-slate-600 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0A5C66]/40 transition-colors flex items-center gap-1.5">
                            <i class="bi bi-copy text-[11px]"></i> Copy
                        </button>
                    </div>

                    @if ($upiAccount->mobile_number)
                        <div class="flex items-center justify-between bg-white border border-slate-100 rounded-[14px] px-4 h-14">
                            <div class="flex flex-col text-left min-w-0">
                                <span class="text-[10.5px] font-bold text-slate-500 uppercase tracking-wide">Mobile Number</span>
                                <span class="text-[14px] font-bold text-[#1a153a] truncate">{{ $upiAccount->mobile_number }}</span>
                            </div>
                            <button type="button" data-copy="{{ $upiAccount->mobile_number }}" class="copy-btn shrink-0 h-9 px-3 rounded-[10px] border border-slate-200 text-[12px] font-bold text-slate-600 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0A5C66]/40 transition-colors flex items-center gap-1.5">
                                <i class="bi bi-copy text-[11px]"></i> Copy
                            </button>
                        </div>
                    @endif
                </div>

                <div class="px-5 pt-3 pb-1">
                    <p class="text-center text-[11.5px] font-bold text-slate-500 uppercase tracking-wide mb-3">Pay using any UPI App</p>
                    <div class="grid grid-cols-4 gap-2.5">
                        @foreach ($upiApps as $app)
                            @php $logoPath = 'assets/'.$app['file']; @endphp
                            <a href="{{ $app['scheme'] }}" title="{{ $app['name'] }}" aria-label="Open {{ $app['name'] }}"
                                class="flex flex-col items-center gap-1.5 py-2.5 px-1 rounded-[14px] border border-slate-100 active:scale-95 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0A5C66]/40 transition-transform">
                                <div class="w-9 h-9 rounded-[10px] flex items-center justify-center shrink-0 {{ file_exists(public_path($logoPath)) ? '' : 'bg-slate-100' }}">
                                    @if (file_exists(public_path($logoPath)))
                                        <img src="{{ asset($logoPath) }}" alt="{{ $app['name'] }}" class="w-9 h-9 rounded-[10px] object-contain">
                                    @else
                                        <span class="text-[11px] font-black text-slate-500">{{ Illuminate\Support\Str::of($app['name'])->explode(' ')->map(fn ($w) => $w[0])->take(2)->implode('') }}</span>
                                    @endif
                                </div>
                                <span class="text-[9px] font-semibold text-slate-500 text-center leading-tight truncate w-full">{{ $app['name'] }}</span>
                            </a>
                        @endforeach
                        <a href="upi://pay?{{ $upiIntentQuery }}" title="Any UPI App" aria-label="Pay with any UPI app"
                            class="flex flex-col items-center gap-1.5 py-2.5 px-1 rounded-[14px] border border-slate-100 active:scale-95 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0A5C66]/40 transition-transform">
                            <div class="w-9 h-9 rounded-[10px] bg-slate-100 flex items-center justify-center shrink-0">
                                <i class="bi bi-three-dots text-slate-500 text-[16px]"></i>
                            </div>
                            <span class="text-[9px] font-semibold text-slate-500 text-center leading-tight">Any UPI App</span>
                        </a>
                    </div>
                    <p class="text-center text-[10.5px] text-slate-500 font-medium mt-3">Opens the app - scan the QR code above or enter the UPI ID to pay</p>
                </div>

                <div class="p-5 grid grid-cols-2 gap-3">
                    <a id="download-qr-btn" href="{{ $upiAccount->qrImageUrl() }}" download class="h-11 rounded-[12px] border border-slate-200 text-[13px] font-bold text-slate-600 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0A5C66]/40 transition-colors flex items-center justify-center gap-2">
                        <i class="bi bi-download text-[13px]"></i> Download QR
                    </a>
                    <button type="button" id="share-payment-btn" data-share-text="{{ $shareText }}" class="h-11 rounded-[12px] border border-slate-200 text-[13px] font-bold text-slate-600 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0A5C66]/40 transition-colors flex items-center justify-center gap-2">
                        <i class="bi bi-share-fill text-[12px]"></i> Share Payment
                    </button>
                </div>
            </div>
        @else
            <!-- Bank transfer details card -->
            <div class="premium-card overflow-hidden">
                <div class="flex items-center justify-between gap-3 bg-gradient-to-br from-[#0A5C66] via-[#0A5C66] to-[#04242F] px-5 py-3.5">
                    <div class="flex items-center gap-2 min-w-0">
                        <i class="bi bi-clock text-amber-400 text-[15px] shrink-0"></i>
                        <span class="text-[12.5px] font-semibold text-white/95 truncate">Complete your payment within</span>
                    </div>
                    <div class="flex items-baseline gap-1 shrink-0">
                        <span id="payment-countdown" class="text-[19px] font-black text-amber-400 font-poppins tracking-tight">15:00</span>
                        <span class="text-[11.5px] font-semibold text-white/80">min</span>
                    </div>
                </div>

                <div class="p-6 flex flex-col items-center text-center gap-1">
                    <div class="w-14 h-14 rounded-full bg-[#0A5C66]/10 flex items-center justify-center mb-2">
                        <i class="bi bi-bank2 text-[22px] text-[#0A5C66]"></i>
                    </div>
                    <h2 class="text-[16px] font-black text-[#1a153a] font-poppins">Bank Transfer Details</h2>
                    <p class="text-[12.5px] text-slate-500 font-medium mb-4">Use NEFT / IMPS / RTGS from your bank app</p>

                    <div class="w-full bg-slate-50 border border-slate-100 rounded-[14px] p-4">
                        <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-1">Amount to Pay</span>
                        <span class="text-[26px] font-black text-[#1a153a] font-poppins tracking-tight">₹{{ number_format($amount) }}</span>
                        <p class="text-[12px] text-slate-500 font-medium mt-0.5">Meri Gullak Deposit</p>
                    </div>
                </div>

                <div class="px-5 pb-5 flex flex-col gap-3">
                    <div class="bg-white border border-slate-100 rounded-[14px] px-4 h-14 flex items-center justify-between">
                        <div class="flex flex-col text-left min-w-0">
                            <span class="text-[10.5px] font-bold text-slate-500 uppercase tracking-wide">Account Holder</span>
                            <span class="text-[14px] font-bold text-[#1a153a] truncate">{{ $bankAccount->account_holder_name }}</span>
                        </div>
                        <button type="button" data-copy="{{ $bankAccount->account_holder_name }}" class="copy-btn shrink-0 h-9 px-3 rounded-[10px] border border-slate-200 text-[12px] font-bold text-slate-600 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0A5C66]/40 transition-colors flex items-center gap-1.5">
                            <i class="bi bi-copy text-[11px]"></i> Copy
                        </button>
                    </div>

                    <div class="bg-white border border-slate-100 rounded-[14px] px-4 h-14 flex items-center justify-between">
                        <div class="flex flex-col text-left min-w-0">
                            <span class="text-[10.5px] font-bold text-slate-500 uppercase tracking-wide">Account Number</span>
                            <span class="text-[14px] font-bold text-[#1a153a] truncate">{{ $bankAccount->account_number }}</span>
                        </div>
                        <button type="button" data-copy="{{ $bankAccount->account_number }}" class="copy-btn shrink-0 h-9 px-3 rounded-[10px] border border-slate-200 text-[12px] font-bold text-slate-600 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0A5C66]/40 transition-colors flex items-center gap-1.5">
                            <i class="bi bi-copy text-[11px]"></i> Copy
                        </button>
                    </div>

                    <div class="bg-white border border-slate-100 rounded-[14px] px-4 h-14 flex items-center justify-between">
                        <div class="flex flex-col text-left min-w-0">
                            <span class="text-[10.5px] font-bold text-slate-500 uppercase tracking-wide">IFSC Code</span>
                            <span class="text-[14px] font-bold text-[#1a153a] truncate">{{ $bankAccount->ifsc_code }}</span>
                        </div>
                        <button type="button" data-copy="{{ $bankAccount->ifsc_code }}" class="copy-btn shrink-0 h-9 px-3 rounded-[10px] border border-slate-200 text-[12px] font-bold text-slate-600 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0A5C66]/40 transition-colors flex items-center gap-1.5">
                            <i class="bi bi-copy text-[11px]"></i> Copy
                        </button>
                    </div>

                    <div class="bg-white border border-slate-100 rounded-[14px] px-4 h-14 flex items-center justify-between">
                        <div class="flex flex-col text-left min-w-0">
                            <span class="text-[10.5px] font-bold text-slate-500 uppercase tracking-wide">Bank{{ $bankAccount->branch_name ? ' & Branch' : '' }}</span>
                            <span class="text-[14px] font-bold text-[#1a153a] truncate">{{ $bankAccount->bank_name }}{{ $bankAccount->branch_name ? ', '.$bankAccount->branch_name : '' }}</span>
                        </div>
                        <button type="button" data-copy="{{ $bankAccount->bank_name }}{{ $bankAccount->branch_name ? ', '.$bankAccount->branch_name : '' }}" class="copy-btn shrink-0 h-9 px-3 rounded-[10px] border border-slate-200 text-[12px] font-bold text-slate-600 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0A5C66]/40 transition-colors flex items-center gap-1.5">
                            <i class="bi bi-copy text-[11px]"></i> Copy
                        </button>
                    </div>
                </div>

                <div class="px-5 pb-5">
                    <button type="button" id="share-payment-btn" data-share-text="{{ $shareText }}" class="w-full h-11 rounded-[12px] border border-slate-200 text-[13px] font-bold text-slate-600 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0A5C66]/40 transition-colors flex items-center justify-center gap-2">
                        <i class="bi bi-share-fill text-[12px]"></i> Share Payment Details
                    </button>
                </div>
            </div>
        @endif

        <!-- UTR / Reference -->
        <div>
            <label for="utr" class="block text-[12px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">
                {{ $activeMethod === 'upi' ? 'UTR / UPI Reference Number' : 'Bank Transaction Reference Number (UTR)' }}
            </label>
            <input type="text" id="utr" name="utr" maxlength="{{ $activeMethod === 'upi' ? 12 : 30 }}" required
                inputmode="{{ $activeMethod === 'upi' ? 'numeric' : 'text' }}"
                placeholder="{{ $activeMethod === 'upi' ? 'e.g. 402819473625' : 'e.g. N123240718123456' }}" value="{{ old('utr') }}"
                class="w-full h-12 rounded-[14px] border border-slate-200 px-4 text-[15px] font-bold tracking-wider text-slate-800 outline-none focus:border-[#0A5C66] focus:ring-1 focus:ring-[#0A5C66] transition-colors">
            <p class="text-[11.5px] text-slate-500 font-medium mt-1.5">Copy this from your {{ $activeMethod === 'upi' ? 'UPI app\'s' : 'bank\'s' }} payment confirmation, once you've paid the amount above.</p>
            @error('utr')
                <p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn-shimmer-cta w-full h-[52px] rounded-[16px] bg-[#0A5C66] text-white font-bold text-[15px] hover:bg-[#0E7481] active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#0A5C66] transition-all shadow-md shadow-[#0A5C66]/20">
            Submit for Verification
        </button>

        <div class="flex items-start gap-2.5 bg-slate-50 border border-slate-100 rounded-[14px] p-3.5">
            <i class="bi bi-shield-check text-[13px] text-slate-400 mt-0.5"></i>
            <p class="text-[11.5px] text-slate-500 font-medium leading-relaxed">Deposits are verified manually and usually credited within a few hours.</p>
        </div>

        <div class="rounded-[20px] bg-gradient-to-b from-slate-50 to-white border border-slate-100 px-6 py-5 flex items-center justify-center gap-6">
            <div class="flex flex-col items-center gap-2">
                <div class="w-14 h-14 rounded-full bg-white ring-1 ring-slate-200/80 shadow-[0_4px_14px_-4px_rgba(15,23,42,0.12)] flex items-center justify-center transition-transform hover:-translate-y-0.5 hover:shadow-[0_8px_18px_-4px_rgba(15,23,42,0.16)]">
                    <i class="bi bi-lock-fill text-slate-400 text-[20px]"></i>
                </div>
                <span class="text-[9px] font-semibold text-slate-500 uppercase tracking-wide text-center leading-tight max-w-[68px]">256-bit Encryption</span>
            </div>
            <div class="w-px h-12 bg-gradient-to-b from-transparent via-slate-200 to-transparent"></div>
            <div class="flex flex-col items-center gap-2">
                <div class="w-14 h-14 rounded-full bg-white ring-1 ring-slate-200/80 shadow-[0_4px_14px_-4px_rgba(15,23,42,0.12)] flex items-center justify-center transition-transform hover:-translate-y-0.5 hover:shadow-[0_8px_18px_-4px_rgba(15,23,42,0.16)]">
                    <i class="bi bi-shield-fill-check text-slate-400 text-[20px]"></i>
                </div>
                <span class="text-[9px] font-semibold text-slate-500 uppercase tracking-wide text-center leading-tight max-w-[68px]">{{ $activeMethod === 'upi' ? 'UPI Protected' : 'Bank-Grade Security' }}</span>
            </div>
            <div class="w-px h-12 bg-gradient-to-b from-transparent via-slate-200 to-transparent"></div>
            <div class="flex flex-col items-center gap-2">
                <div class="w-14 h-14 rounded-full bg-white ring-1 ring-slate-200/80 shadow-[0_4px_14px_-4px_rgba(15,23,42,0.12)] flex items-center justify-center transition-transform hover:-translate-y-0.5 hover:shadow-[0_8px_18px_-4px_rgba(15,23,42,0.16)]">
                    <i class="bi bi-check-circle-fill text-slate-400 text-[20px]"></i>
                </div>
                <span class="text-[9px] font-semibold text-slate-500 uppercase tracking-wide text-center leading-tight max-w-[68px]">Secure Transactions</span>
            </div>
        </div>
    </form>

    <!-- Floating "How It Works" button - gently bobs to draw attention -->
    <button type="button" id="how-it-works-btn" aria-label="How it works"
        class="how-it-works-fab fixed bottom-6 right-5 z-40 w-14 h-14 rounded-full bg-[#0A5C66] text-white shadow-lg shadow-[#0A5C66]/30 flex items-center justify-center active:scale-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#0A5C66] transition-transform">
        <i class="bi bi-question-lg text-[22px]"></i>
    </button>

    <!-- How It Works popup -->
    <div id="how-it-works-modal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm items-center justify-center p-5">
        <div id="how-it-works-card" class="w-full max-w-[380px] bg-white rounded-[22px] shadow-2xl p-5 max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-[10px] bg-[#0A5C66]/10 flex items-center justify-center shrink-0">
                        <i class="bi bi-journal-bookmark-fill text-[16px] text-[#0A5C66]"></i>
                    </div>
                    <h2 class="text-[16px] font-black text-[#0F172A] font-poppins">How It Works</h2>
                </div>
                <button type="button" id="how-it-works-close" aria-label="Close"
                    class="w-8 h-8 rounded-full flex items-center justify-center text-slate-500 hover:bg-slate-50 hover:text-slate-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0A5C66]/40 transition-colors">
                    <i class="bi bi-x-lg text-[14px]"></i>
                </button>
            </div>

            <div class="flex flex-col gap-4">
                @foreach ($howItWorksSteps as $index => $step)
                    <div class="how-it-works-step flex items-start gap-3" style="animation-delay: {{ $index * 90 }}ms">
                        <div class="shrink-0 w-7 h-7 rounded-full bg-[#0A5C66] text-white text-[12.5px] font-black flex items-center justify-center">{{ $index + 1 }}</div>
                        <div class="flex flex-col gap-0.5 pt-0.5">
                            <span class="text-[14px] font-bold text-[#0F172A]">{{ $step['title'] }}</span>
                            <span class="text-[12.5px] text-slate-500 font-medium leading-snug">{{ $step['description'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <style>
        /* Gentle up/down bob, distinct from the shimmer sweep used on CTAs -
           this button needs to read as "tap me for help", not "primary
           action", so a slow float instead of a shine. */
        @keyframes how-it-works-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        .how-it-works-fab {
            animation: how-it-works-float 2.4s ease-in-out infinite;
        }
        /* fadeInUp/slideUpFade keyframes already exist globally
           (resources/css/app.css, "Splash Screen Animations") - reused here
           rather than redefined. Toggling the modal's `hidden` class is
           enough to replay them each time it opens, same mechanism the
           splash screen itself relies on. */
        #how-it-works-card {
            animation: slideUpFade 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .how-it-works-step {
            opacity: 0;
            animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* The perpetual FAB bob and the modal's entrance choreography are
           both purely decorative - neither carries information a static
           state doesn't already convey, so both must yield to reduced
           motion rather than keep animating regardless. */
        @media (prefers-reduced-motion: reduce) {
            .how-it-works-fab {
                animation: none;
            }
            #how-it-works-card,
            .how-it-works-step {
                animation: none;
                opacity: 1;
                transform: none;
            }
        }
    </style>

    <script>
    (function () {
        // Copy-to-clipboard - covers every [data-copy] button on the page
        // (UPI ID, mobile, account number, IFSC).
        document.querySelectorAll('[data-copy]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var value = btn.getAttribute('data-copy');
                var original = btn.innerHTML;
                var restore = function () {
                    setTimeout(function () { btn.innerHTML = original; }, 1500);
                };
                var showCopied = function () {
                    btn.innerHTML = '<i class="bi bi-check-lg text-[11px]"></i> Copied';
                    restore();
                };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(value).then(showCopied).catch(showCopied);
                } else {
                    showCopied();
                }
            });
        });

        // Cosmetic countdown only - there is no server-side payment-session
        // expiry to enforce here, this just mirrors the urgency prompt shown
        // in the reference design.
        var countdownEl = document.getElementById('payment-countdown');
        if (countdownEl) {
            var seconds = 15 * 60;
            var tick = function () {
                seconds = Math.max(0, seconds - 1);
                var m = Math.floor(seconds / 60);
                var s = seconds % 60;
                countdownEl.textContent = m + ':' + (s < 10 ? '0' : '') + s;
                if (seconds <= 0) clearInterval(timer);
            };
            var timer = setInterval(tick, 1000);
        }

        var shareBtn = document.getElementById('share-payment-btn');
        if (shareBtn) {
            shareBtn.addEventListener('click', function () {
                var text = shareBtn.getAttribute('data-share-text');
                if (navigator.share) {
                    navigator.share({ title: 'GullakPe Payment', text: text }).catch(function () {});
                } else if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text);
                    alert('Payment details copied to clipboard.');
                }
            });
        }

        // How It Works popup - same open/close/click-outside/Escape pattern
        // as the icon-picker modal in Admin::plans.form.
        var howItWorksBtn = document.getElementById('how-it-works-btn');
        var howItWorksModal = document.getElementById('how-it-works-modal');
        var howItWorksClose = document.getElementById('how-it-works-close');
        if (howItWorksBtn && howItWorksModal && howItWorksClose) {
            var openHowItWorks = function () {
                howItWorksModal.classList.remove('hidden');
                howItWorksModal.classList.add('flex');
            };
            var closeHowItWorks = function () {
                howItWorksModal.classList.add('hidden');
                howItWorksModal.classList.remove('flex');
            };
            howItWorksBtn.addEventListener('click', openHowItWorks);
            howItWorksClose.addEventListener('click', closeHowItWorks);
            howItWorksModal.addEventListener('click', function (e) {
                if (e.target === howItWorksModal) closeHowItWorks();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !howItWorksModal.classList.contains('hidden')) closeHowItWorks();
            });
        }
    })();
    </script>

@endsection
