{{--
    Real toast notifications, no JS - every page used to hand-roll its own
    always-visible colored banner for session('success')/session('error')/
    validation errors (Deposits, Withdrawals, Portfolio, PlanDetails,
    Notifications, and every admin page each had a near-identical but
    slightly different copy of this markup). This is one shared component:
    it reads the same flash data those banners did, renders each as a real
    toast that slides in, holds, then fades itself out via a CSS animation
    (see .toast-item in app.css) - auto-dismissing without a JS timer.
--}}
@php
    $toastItems = [];
    if (session('success')) {
        $toastItems[] = ['type' => 'success', 'message' => session('success')];
    }
    if (session('error')) {
        $toastItems[] = ['type' => 'error', 'message' => session('error')];
    }
    if ($errors->any()) {
        $toastItems[] = ['type' => 'error', 'message' => $errors->first()];
    }
@endphp

@if (count($toastItems) > 0)
    <div class="fixed top-4 inset-x-0 z-[500] flex flex-col items-center gap-2 px-4 pointer-events-none">
        @foreach ($toastItems as $i => $toast)
            <div class="toast-item w-full max-w-sm flex items-start gap-2.5 rounded-2xl px-4 py-3 shadow-lg border pointer-events-auto {{ $toast['type'] === 'success' ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200' }}"
                style="animation-delay: {{ $i * 0.15 }}s">
                <i class="bi {{ $toast['type'] === 'success' ? 'bi-check-circle-fill text-emerald-600' : 'bi-exclamation-circle-fill text-red-500' }} mt-0.5 shrink-0"></i>
                <p class="text-[13px] font-semibold leading-relaxed {{ $toast['type'] === 'success' ? 'text-emerald-800' : 'text-red-700' }}">{{ $toast['message'] }}</p>
            </div>
        @endforeach
    </div>
@endif
