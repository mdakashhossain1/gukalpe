<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\DepositRequest;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\PlanDuration;
use App\Models\WithdrawRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Real CRUD for the investment-plan catalog (App\Models\Plan) - the 5
 * plans used to be a hardcoded object literal in
 * resources/js/modules/animations.js with no way to add, edit, or disable
 * one without a code change. Kept as its own controller rather than folded
 * into AdminController, which was already large before this.
 */
class PlanManagementController extends Controller
{
    private function sidebarCounts(): array
    {
        return [
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
        ];
    }

    public function index(): View
    {
        return view('Admin::plans.index', [
            ...$this->sidebarCounts(),
            'plans' => Plan::ordered()->with(['durations', 'requiresPlan'])->get(),
        ]);
    }

    public function create(): View
    {
        return view('Admin::plans.form', [
            ...$this->sidebarCounts(),
            'plan' => new Plan(['is_active' => true, 'status' => Plan::STATUS_ACTIVE, 'auto_mature' => true]),
            'categories' => $this->categoryOptions(),
            'categoryIcons' => PlanCategory::iconMap(),
            'requirablePlans' => Plan::orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:4096'],
            'icon_image' => ['nullable', 'image', 'max:4096'],
        ]);
        $this->resolveCategoryInput($request);

        $data = $this->validated($request);
        $data['image'] = $this->storeUploadedImage($request, 'image', 'assets/plans');
        if ($request->hasFile('icon_image')) {
            $data['icon_image'] = $this->storeUploadedImage($request, 'icon_image', 'assets/plan-icons');
        }

        $this->syncCategoryIcon($data['badge'], $data['badge_icon']);
        unset($data['badge_icon']);

        $plan = Plan::create($data);
        $this->syncDurations($plan, $request);

        Log::channel('admin_security')->info('Plan created', ['title' => $request->input('title')]);
        AdminAuditLog::record($request, 'plan_created', $plan);

        return redirect()->route('admin.plans')->with('success', 'Plan created.');
    }

    public function edit(Plan $plan): View
    {
        return view('Admin::plans.form', [
            ...$this->sidebarCounts(),
            'plan' => $plan,
            'categories' => $this->categoryOptions(),
            'categoryIcons' => PlanCategory::iconMap(),
            'requirablePlans' => Plan::where('id', '!=', $plan->id)->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $request->validate([
            'image' => ['nullable', 'image', 'max:4096'],
            'icon_image' => ['nullable', 'image', 'max:4096'],
        ]);
        $this->resolveCategoryInput($request);

        $data = $this->validated($request, $plan);
        // Only touch the image if a new file was actually uploaded - editing
        // a plan's price shouldn't force re-uploading its picture every time.
        if ($request->hasFile('image')) {
            $data['image'] = $this->storeUploadedImage($request, 'image', 'assets/plans');
        }
        // Same for the custom icon image - left untouched unless replaced.
        if ($request->hasFile('icon_image')) {
            $data['icon_image'] = $this->storeUploadedImage($request, 'icon_image', 'assets/plan-icons');
        }

        $this->syncCategoryIcon($data['badge'], $data['badge_icon']);
        unset($data['badge_icon']);

        $plan->update($data);
        $this->syncDurations($plan, $request);

        Log::channel('admin_security')->info('Plan updated', ['plan_id' => $plan->id, 'title' => $plan->title]);
        AdminAuditLog::record($request, 'plan_updated', $plan);

        return redirect()->route('admin.plans')->with('success', 'Plan updated.');
    }

    // Quick active <-> hidden flip for the common case (both are purchasable,
    // so this never blocks anyone with an existing direct link) - Draft and
    // Expired are set from the full edit form only, not this one-click toggle.
    public function toggleActive(Request $request, Plan $plan): RedirectResponse
    {
        $newStatus = $plan->status === Plan::STATUS_ACTIVE ? Plan::STATUS_HIDDEN : Plan::STATUS_ACTIVE;
        $plan->update([
            'status' => $newStatus,
            'is_active' => in_array($newStatus, [Plan::STATUS_ACTIVE, Plan::STATUS_HIDDEN], true),
        ]);

        Log::channel('admin_security')->info('Plan availability toggled', [
            'plan_id' => $plan->id,
            'title' => $plan->title,
            'status' => $plan->status,
        ]);
        AdminAuditLog::record($request, 'plan_toggled', $plan, null, ['status' => $plan->status]);

        return redirect()->route('admin.plans')
            ->with('success', "{$plan->title} is now {$plan->status}.");
    }

    // Only safe to hard-delete a plan nobody has ever bought - a purchased
    // plan has UserPlan rows (and possibly PlanTopup/ReferralCommission rows
    // chained off those) that reference plan_id, so deleting it would either
    // orphan a real user's holding history or cascade-destroy it. Every
    // other case (draft, misconfigured, leftover test data) goes through
    // toggleActive() -> Hidden instead.
    public function destroy(Request $request, Plan $plan): RedirectResponse
    {
        if ($plan->userPlans()->exists()) {
            return redirect()->route('admin.plans')
                ->withErrors(['plan' => "{$plan->title} has real purchases and can't be deleted - hide it instead."]);
        }

        $title = $plan->title;
        AdminAuditLog::record($request, 'plan_deleted', $plan, null, ['title' => $title]);
        $plan->durations()->delete();
        $plan->delete();

        Log::channel('admin_security')->info('Plan deleted', ['title' => $title]);

        return redirect()->route('admin.plans')->with('success', "{$title} deleted.");
    }

    // The "Category" field is a <select> built from every known
    // PlanCategory, plus a "+ New category" option (value __custom__) that
    // reveals a plain text input (badge_custom) on the same form. This
    // resolves that pair back into the single 'badge' value the form would
    // have submitted if it had always just been a text field, before
    // validation runs.
    private function resolveCategoryInput(Request $request): void
    {
        if ($request->input('badge') === '__custom__') {
            $request->merge(['badge' => trim((string) $request->input('badge_custom'))]);
        }
    }

    // Badge icons are shared across every plan using that category, so they
    // live on their own PlanCategory row rather than on the plan itself -
    // this keeps that row in sync with whatever the admin picked/typed.
    private function syncCategoryIcon(string $badge, ?string $icon): void
    {
        PlanCategory::updateOrCreate(
            ['name' => $badge],
            ['icon' => $icon !== null && trim($icon) !== '' ? trim($icon) : PlanCategory::DEFAULT_ICON]
        );
    }

    private function categoryOptions(): Collection
    {
        return PlanCategory::query()->pluck('name')
            ->merge(Plan::query()->whereNotNull('badge')->distinct()->pluck('badge'))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    private function validated(Request $request, ?Plan $plan = null): array
    {
        $hasDurationRows = collect($request->input('durations', []))
            ->contains(fn ($row) => trim((string) ($row['label'] ?? '')) !== '');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100', $plan
                ? 'unique:plans,title,'.$plan->id
                : 'unique:plans,title'],
            'subtitle' => ['required', 'string', 'max:150'],
            // Optional now that admins can upload a custom icon image instead
            // (icon_image, handled separately). Kept as a fallback class.
            'icon' => ['nullable', 'string', 'max:50'],
            'badge' => ['required', 'string', 'max:30'],
            'badge_icon' => ['nullable', 'string', 'max:50'],
            'growth_rate' => $hasDurationRows ? ['nullable', 'integer', 'min:0', 'max:100'] : ['required', 'integer', 'min:0', 'max:100'],
            'lock_duration' => ['required', 'string', 'max:30'],
            'investment_mode' => ['required', 'in:fixed,flexible'],
            // Only truly required in Fixed mode - Flexible plans derive this
            // from min_investment_amount below (see the investment_mode
            // branch further down), so the field is legitimately empty
            // whenever Flexible is selected.
            'investment_amount' => ['nullable', 'required_if:investment_mode,fixed', 'numeric', 'min:1'],
            // required_if (not just nullable) is what actually makes the Step 1
            // switcher mean something server-side - previously investment_mode
            // was submitted but never read, so a Flexible-looking plan saved
            // with only Min filled in (Max left blank) silently fell back to a
            // Fixed plan with no error at all. See MEMORY.md 2026-08-09 "Premium
            // Plan" incident.
            'min_investment_amount' => ['nullable', 'required_if:investment_mode,flexible', 'numeric', 'min:1'],
            'max_investment_amount' => ['nullable', 'required_if:investment_mode,flexible', 'numeric', 'min:1', 'gt:min_investment_amount'],
            'slider_step' => ['nullable', 'numeric', 'min:0.01'],
            'term_days' => $hasDurationRows ? ['nullable', 'integer', 'min:1'] : ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:'.implode(',', Plan::STATUSES)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'plan_type' => ['nullable', 'in:trust_builder,growth'],
            'max_purchase_per_user' => ['nullable', 'integer', 'min:1'],
            'max_purchases' => ['nullable', 'integer', 'min:1'],
            'cooldown_days' => ['nullable', 'integer', 'min:0'],
            'requires_plan_id' => ['nullable', 'integer', $plan
                ? 'exists:plans,id|not_in:'.$plan->id
                : 'exists:plans,id'],
            'unlock_message' => ['nullable', 'string', 'max:2000'],
            'marketing_badge' => ['nullable', 'string', 'max:40'],
            'marketing_badge_icon' => ['nullable', 'string', 'max:50'],
            'marketing_badge_color' => ['nullable', 'in:'.implode(',', array_keys(Plan::MARKETING_BADGE_COLORS))],
            'risk_level' => ['nullable', 'in:Low,Medium,High'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'terms' => ['nullable', 'string', 'max:8000'],
            'highlights' => ['nullable', 'array'],
            'highlights.*' => ['nullable', 'string', 'max:60'],
            'faqs' => ['nullable', 'array'],
            'faqs.*.q' => ['nullable', 'string', 'max:200'],
            'faqs.*.a' => ['nullable', 'string', 'max:1000'],
        ]);

        // Blank input -> "" -> null under the 'nullable' rule, and an
        // explicit null in the insert overrides the column's ->default(0)
        // (defaults only apply when the column is omitted entirely), which
        // trips the NOT NULL constraint. Coalesce back to 0 here instead.
        $validated['sort_order'] ??= 0;

        // The Bootstrap-class icon field was replaced on the form by an icon
        // image upload, so it no longer arrives in the POST body. Keep the
        // column (still NOT NULL, and a fallback wherever no icon image is set)
        // populated: preserve the plan's existing class on edit, default on
        // create.
        $validated['icon'] ??= $plan?->icon ?? 'bi-piggy-bank';

        // Checkboxes absent from the POST body simply mean false - not
        // something 'nullable'/'boolean' validation rules can express, so
        // they're read directly rather than through the rule set above.
        // is_active is derived from status (not its own checkbox anymore) -
        // Active and Hidden both keep purchase-eligibility on; only Draft
        // and Expired turn it off. This is the ONLY place is_active is set,
        // so every other call site's existing meaning stays intact.
        $validated['is_active'] = in_array($validated['status'], [Plan::STATUS_ACTIVE, Plan::STATUS_HIDDEN], true);
        $validated['unlock_enabled'] = $request->boolean('unlock_enabled');
        $validated['auto_mature'] = $request->boolean('auto_mature');
        $validated['allow_topups'] = $request->boolean('allow_topups');

        // investment_mode itself isn't a Plan column - it's what actually
        // decides which of the two branches below applies, then gets
        // discarded. Fixed mode: always exactly one rate/term (the top-level
        // Growth rate + Term days fields) - wipe any stray Min/Max/step/
        // top-ups values still sitting in the request (e.g. the admin
        // toggled to Flexible, typed a Min, then toggled back to Fixed
        // before saving - the hidden fields aren't disabled, so their values
        // still POST) so the saved row can never end up in the ambiguous
        // "has a Min but no Max" state that made Premium Plan silently
        // behave as Fixed while its own subtitle promised a slider. Any
        // Duration rows submitted are discarded server-side too (see
        // syncDurations()) - Fixed plans never offer a per-term choice.
        // Flexible mode: a real Min+Max range is already guaranteed by the
        // required_if rules above; also require at least one Duration row -
        // Flexible is where the per-term choice (3mo/6mo/1yr, each its own
        // rate) actually lives, and PlanPurchaseController's
        // proportionalReturn() only proportions the return to the amount the
        // user actually invests when a duration is present, so without one a
        // Flexible plan would pay every buyer the same flat profit
        // regardless of how much they invested.
        if ($validated['investment_mode'] === 'fixed') {
            $validated['min_investment_amount'] = null;
            $validated['max_investment_amount'] = null;
            $validated['slider_step'] = null;
            $validated['allow_topups'] = false;
        } else {
            if (! $hasDurationRows) {
                throw ValidationException::withMessages([
                    'investment_mode' => 'Flexible plans need at least one Duration option below - without one, every buyer would get the same flat return regardless of how much they invest.',
                ]);
            }

            // The base Investment field only ever feeds the plan-level
            // preview/legacy display for Flexible plans (real purchases
            // compute their own proportional return from whatever the user
            // drags the slider to) - pin it to Min so there's one fewer
            // number an admin has to keep in sync by hand.
            $validated['investment_amount'] = $validated['min_investment_amount'];
        }

        unset($validated['investment_mode']);

        // Trust Builder is always "buy today, mature in 1 day, auto-credit" -
        // never trust a submitted checkbox to turn that off for this type.
        // Nullable rule means the key is absent from $validated entirely when
        // the request omits it (e.g. a raw API call bypassing the <select>).
        if (($validated['plan_type'] ?? null) === 'trust_builder') {
            $validated['auto_mature'] = true;
        }

        $validated['highlights'] = collect($validated['highlights'] ?? [])
            ->map(fn ($h) => trim((string) $h))->filter()->values()->all() ?: null;

        $validated['faqs'] = collect($validated['faqs'] ?? [])
            ->map(fn ($f) => ['q' => trim((string) ($f['q'] ?? '')), 'a' => trim((string) ($f['a'] ?? ''))])
            ->filter(fn ($f) => $f['q'] !== '' && $f['a'] !== '')
            ->values()->all() ?: null;

        if ($hasDurationRows) {
            $firstDur = collect($request->input('durations', []))
                ->first(fn ($r) => trim((string) ($r['label'] ?? '')) !== '');
            if (($validated['growth_rate'] ?? null) === null) {
                $validated['growth_rate'] = (int) ($firstDur['growth_rate'] ?? $plan?->growth_rate ?? 0);
            }
            if (($validated['term_days'] ?? null) === null) {
                $validated['term_days'] = (int) ($firstDur['duration_days'] ?? $plan?->term_days ?? 365);
            }
        }

        [$validated['daily_profit'], $validated['total_return']] = $this->computeReturns(
            (float) $validated['investment_amount'],
            (float) ($validated['growth_rate'] ?? 0),
            (int) ($validated['term_days'] ?? $plan?->term_days ?? 365)
        );

        return $validated;
    }

    // Same formula the purchase engine uses for flexible amounts
    // (PlanPurchaseController::proportionalReturn) and the admin form's own
    // JS preview - kept in exactly one place server-side now that the
    // client-submitted daily_profit/total_return are never trusted.
    private function computeReturns(float $amount, float $ratePct, int $days): array
    {
        if ($amount <= 0 || $days <= 0) {
            return [0.0, 0.0];
        }

        $total = $amount * (1 + ($ratePct / 100) * ($days / 365));
        $daily = ($total - $amount) / $days;

        return [round($daily, 2), round($total, 2)];
    }

    // Up to 4 durations per plan (plans.md's admin control). Each submitted
    // row is upserted by its own `id` field when present (existing row,
    // edited in place - keeps purchases' plan_duration_id snapshot valid)
    // or created fresh when absent; any existing row not resubmitted is
    // removed, so deleting a duration row in the form actually deletes it.
    //
    // Trust Builder plans (plan_type = trust_builder) and Fixed-amount plans
    // both ignore whatever rows were submitted and always collapse to
    // exactly one system-defined row - this is enforced here (not just
    // hidden in the UI) so neither can ever end up with a real multi-duration
    // option, even via a direct/tampered request. Trust Builder's row is a
    // fixed 1 day; Fixed's row mirrors the plan's own top-level
    // growth_rate/term_days, since a Fixed plan always offers exactly one
    // rate/term (set via those fields), never a per-term choice - that
    // choice is what Flexible mode is for. The purchase flow and Plan
    // Details slider both still resolve their rate through a PlanDuration
    // row, so one is created here rather than changing those read paths.
    private function syncDurations(Plan $plan, Request $request): void
    {
        $forcesSingleRow = $plan->plan_type === 'trust_builder' || ! $plan->isFlexibleAmount();

        if ($forcesSingleRow) {
            // Reuse whichever existing row is around (rather than delete +
            // recreate) so an already-purchased holding's plan_duration_id
            // stays pointed at a live row instead of nulling out.
            $existingId = $plan->durations()->value('id');

            $rows = $plan->plan_type === 'trust_builder'
                ? collect([[
                    'id' => $existingId,
                    'label' => '1 Day',
                    'duration_days' => 1,
                    'growth_rate' => (int) $plan->growth_rate,
                ]])
                : collect([[
                    'id' => $existingId,
                    'label' => $plan->lock_duration,
                    'duration_days' => max(1, (int) $plan->term_days),
                    'growth_rate' => (int) $plan->growth_rate,
                ]]);
        } else {
            $rows = collect($request->input('durations', []))
                ->filter(fn ($row) => trim((string) ($row['label'] ?? '')) !== '')
                ->take(4)
                ->values();
        }

        // Radio value is the row's array index (always present, unlike `id`
        // which new rows don't have yet) - simplest stable key to compare
        // against regardless of whether the row is new or existing.
        $defaultIndex = $forcesSingleRow ? '0' : $request->input('duration_default');

        $keptIds = [];

        foreach ($rows as $index => $row) {
            $durationDays = max(1, (int) ($row['duration_days'] ?? 1));
            $growthRate = max(0, (int) ($row['growth_rate'] ?? 0));
            [$rowDaily, $rowTotal] = $this->computeReturns((float) $plan->investment_amount, $growthRate, $durationDays);

            $attributes = [
                'plan_id' => $plan->id,
                'label' => trim((string) $row['label']),
                'duration_days' => $durationDays,
                'growth_rate' => $growthRate,
                'daily_profit' => $rowDaily,
                'total_return' => $rowTotal,
                'is_default' => (string) $defaultIndex === (string) $index,
                'sort_order' => $index,
            ];

            $duration = ! empty($row['id'])
                ? PlanDuration::where('plan_id', $plan->id)->find($row['id'])
                : null;

            if ($duration) {
                $duration->update($attributes);
            } else {
                $duration = PlanDuration::create($attributes);
            }

            $keptIds[] = $duration->id;
        }

        PlanDuration::where('plan_id', $plan->id)->whereNotIn('id', $keptIds)->delete();

        // Guarantee exactly one default when at least one duration exists,
        // even if the admin's radio selection didn't resolve to a real row.
        if ($keptIds !== [] && ! PlanDuration::where('plan_id', $plan->id)->where('is_default', true)->exists()) {
            PlanDuration::where('id', $keptIds[0])->update(['is_default' => true]);
        }
    }

    // Saved straight into public/assets/<subdir> - deliberately NOT Laravel's
    // 'public' disk (storage/app/public + a storage:link symlink); this
    // app is served directly out of public/ via a custom index.php (see
    // SECURITY.md), and everything else image-like already lives in
    // public/assets/ with no symlink involved, so uploads follow the same
    // pattern rather than introducing a second, inconsistent one.
    private function storeUploadedImage(Request $request, string $field = 'image', string $relativeDir = 'assets/plans'): string
    {
        $file = $request->file($field);
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $directory = public_path($relativeDir);

        // UploadedFile::move() does not create missing directories itself.
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file->move($directory, $filename);

        return $relativeDir.'/'.$filename;
    }
}
