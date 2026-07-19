{{--
    "Complete Your Starter Journey" unlock popup (plans.md's Premium Unlock
    Popup) - shown when a purchase is attempted against a plan whose
    unlock_enabled + requires_plan_id gate isn't satisfied yet
    (PlanPurchaseController::purchase()). Pure CSS: session flash sets the
    checkbox `checked`, closed via a label, no JS.
--}}
@php
    $lockedPlanId = session('open_unlock_popup');
    $lockedPlan = $lockedPlanId ? \App\Models\Plan::find($lockedPlanId) : null;
@endphp

@if ($lockedPlan)
    <input type="checkbox" id="unlock-required-popup-toggle" class="hidden peer" checked>
    <div class="hidden peer-checked:flex fixed inset-0 z-[250] bg-slate-900/60 backdrop-blur-md items-center justify-center p-4">
        <div class="relative w-full max-w-[360px] bg-white rounded-[28px] shadow-2xl p-7 flex flex-col items-center text-center overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-[#0E7680] to-[#1199A5]"></div>

            <div class="w-16 h-16 bg-[#0E7680]/10 rounded-full flex items-center justify-center mb-5 border border-[#0E7680]/20 shadow-inner teal-glow-pulse">
                <i class="bi bi-lock-fill text-[#0E7680] text-[26px]"></i>
            </div>

            <h3 class="text-[#0A5C66] text-[20px] font-black leading-tight mb-1.5 font-poppins tracking-tight">Complete Your Starter Journey</h3>
            <p class="text-slate-500 text-[13.5px] font-medium mb-5 leading-relaxed">
                {{ $lockedPlan->unlock_message ?: 'To unlock the Trust Builder Plan, please activate a Growth Plan first.' }}
            </p>

            @php $highlights = $lockedPlan->highlights ?: ['Unlock Trust Builder Plan', 'Access Premium Investment Features', 'Daily Portfolio Tracking', 'Priority Verification', 'Better Investment Experience']; @endphp
            <div class="w-full bg-[#0A5C66]/5 rounded-2xl p-4 border border-[#0A5C66]/10 text-left space-y-2 mb-6">
                @foreach ($highlights as $highlight)
                    <div class="flex items-center gap-2 text-[12.5px] font-semibold text-[#0A5C66]">
                        <i class="bi bi-check-circle-fill text-[#3CCF91] text-[13px] shrink-0"></i> {{ $highlight }}
                    </div>
                @endforeach
            </div>

            <div class="w-full flex flex-col gap-2.5">
                <a href="{{ route('explore', $lockedPlan->requires_plan_id ? ['badge' => optional($lockedPlan->requiresPlan)->badge] : []) }}"
                    class="w-full bg-[#0E7680] text-white font-bold text-[15px] h-[48px] rounded-full active:scale-95 transition-all shadow-md shadow-[#0E7680]/20 flex items-center justify-center">
                    View Growth Plans
                </a>
                <label for="unlock-required-popup-toggle" class="w-full bg-slate-100/80 text-slate-600 font-bold text-[14px] h-[44px] rounded-full hover:bg-slate-200/80 active:scale-95 transition-all cursor-pointer flex items-center justify-center">Maybe Later</label>
            </div>
        </div>
    </div>
@endif
