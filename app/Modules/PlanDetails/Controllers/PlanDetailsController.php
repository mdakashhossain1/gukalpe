<?php

namespace App\Modules\PlanDetails\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\UserPlan;
use App\Models\WalletBalance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PlanDetailsController extends Controller
{
    public function index(Plan $plan): View
    {
        $user = Auth::user();
        $phone = $user?->phone;

        // Real, server-computed unlock state - never guessed in the view -
        // used both for the 🔒/✅ badge and to decide whether the Progress
        // Timeline shows a live step or just a preview.
        $hasUnlocked = ! $plan->unlock_enabled || ! $plan->requires_plan_id || (
            $user && UserPlan::where('user_id', $user->id)->where('plan_id', $plan->requires_plan_id)->exists()
        );

        $existingHolding = $user
            ? UserPlan::where('user_id', $user->id)->where('plan_id', $plan->id)->latest('purchased_at')->first()
            : null;

        // The one ongoing pot this user can top up on this plan, if the
        // plan is in SIP-style top-up mode - null means their next
        // contribution opens a brand new pot rather than adding to one.
        $activePot = ($user && $plan->isTopupPot())
            ? UserPlan::activePotFor($user, $plan)?->load('topups')
            : null;

        return view('PlanDetails::plan-details', [
            'plan' => $plan,
            'p' => $plan->toLegacyArray(),
            'balance' => $phone ? WalletBalance::balanceFor($phone) : 0.0,
            'badgeIcons' => PlanCategory::iconMap(),
            'defaultBadgeIcon' => PlanCategory::DEFAULT_ICON,
            'hasUnlocked' => $hasUnlocked,
            'existingHolding' => $existingHolding,
            'activePot' => $activePot,
            'isFavorited' => $user ? $user->favoritePlans()->where('plan_id', $plan->id)->exists() : false,
        ]);
    }

    // Plain redirect+flash, no JSON - matches PlanPurchaseController's
    // pattern since this is a plain <form method="POST"> heart button too,
    // not a JS/fetch-driven toggle.
    public function toggleFavorite(Plan $plan): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login')->withErrors(['plan' => 'Please log in to save a plan.']);
        }

        Auth::user()->favoritePlans()->toggle($plan->id);

        return back();
    }
}
