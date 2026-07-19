<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DepositRequest;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\PlanDuration;
use App\Models\WithdrawRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
            'plan' => new Plan(['is_active' => true, 'auto_mature' => true]),
            'categories' => $this->categoryOptions(),
            'categoryIcons' => PlanCategory::iconMap(),
            'requirablePlans' => Plan::orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['image' => ['required', 'image', 'max:4096']]);
        $this->resolveCategoryInput($request);

        $data = $this->validated($request);
        $data['image'] = $this->storeUploadedImage($request);

        $this->syncCategoryIcon($data['badge'], $data['badge_icon']);
        unset($data['badge_icon']);

        $plan = Plan::create($data);
        $this->syncDurations($plan, $request);

        Log::channel('admin_security')->info('Plan created', ['title' => $request->input('title')]);

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
        $request->validate(['image' => ['nullable', 'image', 'max:4096']]);
        $this->resolveCategoryInput($request);

        $data = $this->validated($request, $plan);
        // Only touch the image if a new file was actually uploaded - editing
        // a plan's price shouldn't force re-uploading its picture every time.
        if ($request->hasFile('image')) {
            $data['image'] = $this->storeUploadedImage($request);
        }

        $this->syncCategoryIcon($data['badge'], $data['badge_icon']);
        unset($data['badge_icon']);

        $plan->update($data);
        $this->syncDurations($plan, $request);

        Log::channel('admin_security')->info('Plan updated', ['plan_id' => $plan->id, 'title' => $plan->title]);

        return redirect()->route('admin.plans')->with('success', 'Plan updated.');
    }

    public function toggleActive(Plan $plan): RedirectResponse
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        Log::channel('admin_security')->info('Plan availability toggled', [
            'plan_id' => $plan->id,
            'title' => $plan->title,
            'is_active' => $plan->is_active,
        ]);

        return redirect()->route('admin.plans')
            ->with('success', "{$plan->title} is now ".($plan->is_active ? 'active' : 'disabled').'.');
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

    private function categoryOptions(): \Illuminate\Support\Collection
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
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100', $plan
                ? 'unique:plans,title,'.$plan->id
                : 'unique:plans,title'],
            'subtitle' => ['required', 'string', 'max:150'],
            'icon' => ['required', 'string', 'max:50'],
            'badge' => ['required', 'string', 'max:30'],
            'badge_icon' => ['nullable', 'string', 'max:50'],
            'growth_rate' => ['required', 'integer', 'min:0', 'max:100'],
            'lock_duration' => ['required', 'string', 'max:30'],
            'investment_amount' => ['required', 'numeric', 'min:1'],
            'min_investment_amount' => ['nullable', 'numeric', 'min:1'],
            'max_investment_amount' => ['nullable', 'numeric', 'min:1', 'gt:min_investment_amount'],
            'daily_profit' => ['required', 'numeric', 'min:0'],
            'total_return' => ['required', 'numeric', 'min:0'],
            'min_goal' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'plan_type' => ['nullable', 'in:trust_builder,growth'],
            'max_purchase_per_user' => ['nullable', 'integer', 'min:1'],
            'cooldown_days' => ['nullable', 'integer', 'min:0'],
            'requires_plan_id' => ['nullable', 'integer', $plan
                ? 'exists:plans,id|not_in:'.$plan->id
                : 'exists:plans,id'],
            'unlock_message' => ['nullable', 'string', 'max:2000'],
            'marketing_badge' => ['nullable', 'string', 'max:40'],
            'risk_level' => ['nullable', 'in:Low,Medium,High'],
            'max_slots' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'terms' => ['nullable', 'string', 'max:8000'],
            'highlights' => ['nullable', 'array'],
            'highlights.*' => ['nullable', 'string', 'max:60'],
            'faqs' => ['nullable', 'array'],
            'faqs.*.q' => ['nullable', 'string', 'max:200'],
            'faqs.*.a' => ['nullable', 'string', 'max:1000'],
        ]);

        // Checkboxes absent from the POST body simply mean false - not
        // something 'nullable'/'boolean' validation rules can express, so
        // they're read directly rather than through the rule set above.
        $validated['is_active'] = $request->boolean('is_active');
        $validated['unlock_enabled'] = $request->boolean('unlock_enabled');
        $validated['auto_mature'] = $request->boolean('auto_mature');
        $validated['early_close_allowed'] = $request->boolean('early_close_allowed');
        $validated['allow_topups'] = $request->boolean('allow_topups');

        $validated['highlights'] = collect($validated['highlights'] ?? [])
            ->map(fn ($h) => trim((string) $h))->filter()->values()->all() ?: null;

        $validated['faqs'] = collect($validated['faqs'] ?? [])
            ->map(fn ($f) => ['q' => trim((string) ($f['q'] ?? '')), 'a' => trim((string) ($f['a'] ?? ''))])
            ->filter(fn ($f) => $f['q'] !== '' && $f['a'] !== '')
            ->values()->all() ?: null;

        return $validated;
    }

    // Up to 4 durations per plan (plans.md's admin control). Each submitted
    // row is upserted by its own `id` field when present (existing row,
    // edited in place - keeps purchases' plan_duration_id snapshot valid)
    // or created fresh when absent; any existing row not resubmitted is
    // removed, so deleting a duration row in the form actually deletes it.
    private function syncDurations(Plan $plan, Request $request): void
    {
        $rows = collect($request->input('durations', []))
            ->filter(fn ($row) => trim((string) ($row['label'] ?? '')) !== '')
            ->take(4)
            ->values();

        // Radio value is the row's array index (always present, unlike `id`
        // which new rows don't have yet) - simplest stable key to compare
        // against regardless of whether the row is new or existing.
        $defaultIndex = $request->input('duration_default');

        $keptIds = [];

        foreach ($rows as $index => $row) {
            $attributes = [
                'plan_id' => $plan->id,
                'label' => trim((string) $row['label']),
                'duration_days' => max(1, (int) ($row['duration_days'] ?? 1)),
                'growth_rate' => max(0, (int) ($row['growth_rate'] ?? 0)),
                'daily_profit' => max(0, (float) ($row['daily_profit'] ?? 0)),
                'total_return' => max(0, (float) ($row['total_return'] ?? 0)),
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

    // Saved straight into public/assets/plans - deliberately NOT Laravel's
    // 'public' disk (storage/app/public + a storage:link symlink); this
    // app is served directly out of public/ via a custom index.php (see
    // SECURITY.md), and everything else image-like already lives in
    // public/assets/ with no symlink involved, so uploads follow the same
    // pattern rather than introducing a second, inconsistent one.
    private function storeUploadedImage(Request $request): string
    {
        $file = $request->file('image');
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $directory = public_path('assets/plans');

        // UploadedFile::move() does not create missing directories itself.
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file->move($directory, $filename);

        return 'assets/plans/'.$filename;
    }
}
