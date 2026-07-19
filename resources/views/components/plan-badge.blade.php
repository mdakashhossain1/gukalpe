@props(['plan', 'unlocked' => true, 'justUnlocked' => false])

{{--
    🔒 Unlock Required / ✅ Unlocked pill (plans.md's "Trust Builder Plan
    Badge") - only rendered for plans that actually have unlock_enabled set,
    so ordinary plans render nothing extra. $unlocked is a real, caller-
    computed boolean (has the viewing user purchased plan.requires_plan_id),
    never guessed here. $justUnlocked plays the one-shot scale-in animation
    only in the single request right after the qualifying purchase - not a
    persistent pulsing glow, so it stays honest even though this specific
    moment is allowed a celebratory touch.
--}}
@if ($plan->unlock_enabled)
    <span @class([
        'inline-flex items-center gap-1 text-[9.5px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border',
        'bg-emerald-50 text-emerald-700 border-emerald-200' => $unlocked,
        'bg-slate-100 text-slate-500 border-slate-200' => ! $unlocked,
        'plan-badge-unlock-pop' => $justUnlocked,
    ])>
        @if ($unlocked)
            <i class="bi bi-check-circle-fill text-[9px]"></i> Unlocked
        @else
            <i class="bi bi-lock-fill text-[9px]"></i> Unlock Required
        @endif
    </span>
@endif
