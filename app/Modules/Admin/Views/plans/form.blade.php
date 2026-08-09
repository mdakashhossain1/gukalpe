@extends('layouts.admin')

@section('title', $plan->exists ? 'Edit plan' : 'Add plan')

@section('content')

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="plans" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="{{ $plan->exists ? 'Edit plan' : 'Add plan' }}" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <a href="{{ route('admin.plans') }}" class="inline-flex items-center gap-1.5 text-[13px] font-bold text-slate-400 hover:text-[#0A5C66] transition-colors mb-4">
            <i class="fa-solid fa-arrow-left text-[12px]"></i> Back to plans
        </a>

        <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">{{ $plan->exists ? 'Edit '.$plan->title : 'Add plan' }}</h1>
        <p class="text-[13.5px] text-[#64748B] mb-6">{{ $plan->exists ? 'Existing holders keep the amount/rate they already bought at - only new purchases use these numbers.' : 'Appears on Explore/Home immediately once saved as active.' }}</p>

        <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-6 items-start">
        <form method="POST" action="{{ $plan->exists ? route('admin.plans.update', $plan) : route('admin.plans.store') }}" enctype="multipart/form-data" class="flex flex-col gap-3.5 bg-white rounded-2xl border border-[#E5E9EB] p-6 min-w-0">
            @csrf

            {{-- ===== Step 1: Select Plan Type Switcher ===== --}}
            <div class="mb-4 p-4 bg-[#F8FAFC] rounded-xl border border-[#E5E9EB]">
                <label class="block text-[12.5px] font-semibold text-[#334155] mb-2.5">
                    <i class="bi bi-toggles mr-1"></i>Step 1 — Select Plan Type
                </label>
                <div class="inline-flex rounded-xl border border-[#CBD5E1] overflow-hidden" id="plan-type-switcher">
                    <button type="button" id="btn-fixed" onclick="window.setPlanMode('fixed')"
                        class="px-5 py-2.5 text-[13.5px] font-bold transition-colors">
                        <i class="bi bi-lock-fill mr-1.5"></i>Fixed Investment Plan
                    </button>
                    <button type="button" id="btn-flexible" onclick="window.setPlanMode('flexible')"
                        class="px-5 py-2.5 text-[13.5px] font-bold transition-colors">
                        <i class="bi bi-sliders mr-1.5"></i>Flexible Investment Plan
                    </button>
                </div>
                <input type="hidden" name="investment_mode" id="investment_mode"
                    value="{{ (old('min_investment_amount', $plan->min_investment_amount) !== null) ? 'flexible' : 'fixed' }}">
                <p class="text-[11px] text-[#94A3B8] mt-2">
                    <strong>Fixed:</strong> Single fixed investment amount (e.g. ₹199). &nbsp;
                    <strong>Flexible:</strong> User selects amount from a range slider (e.g. ₹100–₹1,000).
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label for="title" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Title</label>
                    <input type="text" name="title" id="title" maxlength="100" value="{{ old('title', $plan->title) }}" required
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                    @error('title')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    @php
                        $currentBadge = old('badge', $plan->badge);
                        $isCustomBadge = $currentBadge === '__custom__' || ($currentBadge && ! $categories->contains($currentBadge));
                    @endphp
                    <label for="badge-select" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Category</label>
                    <select name="badge" id="badge-select" required
                        onchange="document.getElementById('badge-custom-wrap').classList.toggle('hidden', this.value !== '__custom__'); document.getElementById('badge-custom').required = (this.value === '__custom__');"
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        <option value="" disabled {{ $currentBadge ? '' : 'selected' }}>Select a category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" {{ ! $isCustomBadge && $currentBadge === $category ? 'selected' : '' }}>{{ $category }}</option>
                        @endforeach
                        <option value="__custom__" {{ $isCustomBadge ? 'selected' : '' }}>+ New category&hellip;</option>
                    </select>
                    @error('badge')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror

                    <div id="badge-custom-wrap" class="mt-2 {{ $isCustomBadge ? '' : 'hidden' }}">
                        <input type="text" name="badge_custom" id="badge-custom" maxlength="30" placeholder="New category name"
                            value="{{ old('badge_custom', $isCustomBadge && $currentBadge !== '__custom__' ? $currentBadge : '') }}"
                            {{ $isCustomBadge ? 'required' : '' }}
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        @error('badge_custom')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    @php
                        $currentBadgeIcon = old('badge_icon', $categoryIcons[$currentBadge] ?? '');
                    @endphp
                    <label for="badge-icon-input" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5 mt-2.5">Category Icon (Bootstrap Icons class)</label>
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-lg bg-[#0A5C66]/5 border border-[#E5E9EB] flex items-center justify-center shrink-0">
                            <i id="badge-icon-preview" class="bi {{ $currentBadgeIcon ?: 'bi-tag-fill' }} text-[16px] text-[#0A5C66]"></i>
                        </div>
                        <input type="text" name="badge_icon" id="badge-icon-input" maxlength="50" placeholder="e.g. bi-fire"
                            value="{{ $currentBadgeIcon }}"
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        <button type="button" data-icon-picker-target="badge-icon-input" data-icon-picker-preview="badge-icon-preview" class="icon-picker-open shrink-0 h-10 px-3 rounded-lg border border-[#CBD5E1] text-[12.5px] font-semibold text-[#334155] hover:border-brand hover:text-brand transition-colors whitespace-nowrap">
                            <i class="bi bi-grid-3x3-gap"></i> Browse
                        </button>
                    </div>
                    <p class="text-[11px] text-[#94A3B8] mt-1">Shared by every plan in this category - changing it updates the badge icon everywhere.</p>
                    @error('badge_icon')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="subtitle" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Subtitle</label>
                <input type="text" name="subtitle" id="subtitle" maxlength="150" value="{{ old('subtitle', $plan->subtitle) }}" required
                    class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                @error('subtitle')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label for="image" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Thumbnail image {{ $plan->exists ? '(leave empty to keep current)' : '' }}</label>
                    @if ($plan->exists && $plan->image)
                        <div class="mb-2 flex items-center gap-2.5">
                            <img src="{{ $plan->imageUrl() }}" alt="{{ $plan->title }}" class="w-14 h-14 rounded-lg object-cover border border-[#E5E9EB]">
                            <span class="text-[11.5px] text-[#94A3B8]">Current thumbnail</span>
                        </div>
                    @endif
                    <input type="file" name="image" id="image" accept="image/png,image/jpeg,image/webp" {{ $plan->exists ? '' : 'required' }}
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[13px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15 file:mr-3 file:h-full file:border-0 file:bg-[#0A5C66]/10 file:text-[#0A5C66] file:font-semibold file:px-3 file:rounded-l-lg file:cursor-pointer">
                    <p class="text-[11px] text-[#94A3B8] mt-1">Photo shown on <strong>Plan Details</strong> &amp; the admin lists (not on Explore). JPG, PNG, or WebP · up to 4MB.</p>
                    @error('image')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="icon_image" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Plan Icon {{ $plan->exists && $plan->icon_image ? '(leave empty to keep current)' : '(shown on Explore, Plan Details & Portfolio)' }}</label>
                    @if ($plan->exists && $plan->iconImageUrl())
                        <div class="mb-2 flex items-center gap-2.5">
                            <div class="w-14 h-14 rounded-lg bg-[#0A5C66]/5 border border-[#E5E9EB] flex items-center justify-center overflow-hidden shrink-0">
                                <img src="{{ $plan->iconImageUrl() }}" alt="{{ $plan->title }}" class="w-full h-full object-contain">
                            </div>
                            <span class="text-[11.5px] text-[#94A3B8]">Current icon</span>
                        </div>
                    @endif
                    <input type="file" name="icon_image" id="icon_image" accept="image/png,image/jpeg,image/webp"
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[13px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15 file:mr-3 file:h-full file:border-0 file:bg-[#0A5C66]/10 file:text-[#0A5C66] file:font-semibold file:px-3 file:rounded-l-lg file:cursor-pointer">
                    <p class="text-[11px] text-[#94A3B8] mt-1">Shown in the <strong>Explore</strong> and <strong>Portfolio</strong> card circles. PNG, JPG, or WebP · up to 4MB · saved to public/assets/plan-icons.</p>
                    @error('icon_image')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-2 pt-3 border-t border-[#E5E9EB]">
                <div class="flex items-center justify-between gap-2 mb-2.5">
                    <h2 class="font-poppins font-bold text-[14px] text-[#0F172A]">Investment &amp; returns</h2>
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-[#0A5C66] bg-[#0A5C66]/8 px-2.5 py-1 rounded-full"><i class="bi bi-magic text-[11px]"></i> Auto-calculated</span>
                </div>

                <div id="fixed-investment-section" class="rounded-xl border border-[#0A5C66]/20 bg-[#0A5C66]/[0.03] p-3.5 mb-3.5">
                    <p class="text-[11px] font-bold text-[#0A5C66] uppercase tracking-wide mb-2.5 flex items-center gap-1.5"><i class="bi bi-lock-fill"></i> Fixed Plan Amount</p>
                    <div class="max-w-xs">
                        <label for="investment_amount" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Investment (₹, one-time)</label>
                        <input type="number" name="investment_amount" id="investment_amount" min="1" step="0.01" value="{{ old('investment_amount', $plan->investment_amount) }}" required
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        <p class="text-[11px] text-[#94A3B8] mt-1">Every buyer pays exactly this amount. Only used in Fixed mode - ignored (and cleared) when Flexible is selected above.</p>
                        @error('investment_amount')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Growth rate/Term days/Daily-Total preview apply to BOTH modes - Fixed
                     uses them directly; Flexible uses them as the rate each Duration row
                     below inherits, and only the Investment field above is Fixed-only. --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label for="growth_rate" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Growth rate (%/yr)</label>
                        <div class="mb-2">
                            <span class="block text-[11px] font-semibold text-[#94A3B8] uppercase tracking-wide mb-1.5">Quick presets</span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ([1, 2, 3, 5, 8, 10, 12, 15, 20] as $rp)
                                    <button type="button" class="rate-preset px-2.5 py-0.5 rounded-full border border-[#CBD5E1] text-[11px] font-bold text-[#334155] hover:border-[#0A5C66] hover:text-[#0A5C66] transition-all"
                                        data-rate="{{ $rp }}" onclick="window.applyRatePreset({{ $rp }})">{{ $rp }}%</button>
                                @endforeach
                                <button type="button" class="rate-preset px-2.5 py-0.5 rounded-full border border-[#CBD5E1] text-[11px] font-bold text-[#334155] hover:border-[#0A5C66] hover:text-[#0A5C66] transition-all"
                                    data-rate="custom" onclick="window.applyRatePreset(null)">Custom %</button>
                            </div>
                        </div>
                        <input type="number" name="growth_rate" id="growth_rate" min="0" max="100" value="{{ old('growth_rate', $plan->growth_rate) }}" required
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        @error('growth_rate')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="term_days_calc" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Term (days)</label>
                        {{-- Not saved on the plan (there is no plan-level days column - only
                             durations have one); purely the multiplier that turns the yearly
                             rate above into the headline Daily/Total figures below. --}}
                        <input type="number" name="term_days" id="term_days_calc" min="1" placeholder="e.g. 365" value="{{ old('term_days', $plan->term_days) }}"
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        <p class="text-[11px] text-[#94A3B8] mt-1">Length used to compute the numbers below. Required when there are no duration options.</p>
                        @error('term_days')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 mt-3.5">
                    <div>
                        <label for="daily_profit" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Daily profit (₹) <span class="text-[10.5px] font-normal text-[#94A3B8]">· auto, system-computed</span></label>
                        <input type="number" id="daily_profit" min="0" step="0.01" value="{{ old('daily_profit', $plan->daily_profit) }}" disabled
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#94A3B8] bg-[#F8FAFC] outline-none">
                    </div>
                    <div>
                        <label for="total_return" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Total return (₹) <span class="text-[10.5px] font-normal text-[#94A3B8]">· auto, system-computed</span></label>
                        <input type="number" id="total_return" min="0" step="0.01" value="{{ old('total_return', $plan->total_return) }}" disabled
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#94A3B8] bg-[#F8FAFC] outline-none">
                    </div>
                </div>
                <p class="text-[11px] text-[#94A3B8] mt-2">Formula: <span class="font-semibold text-[#64748B]">Total = Investment × (1 + Rate%⁄yr × Days⁄365)</span>, and <span class="font-semibold text-[#64748B]">Daily = (Total − Investment) ⁄ Days</span>. Computed by the system on save - not editable.</p>
            </div>

            <div id="flexible-investment-section" class="rounded-xl border border-[#0A5C66]/20 bg-[#0A5C66]/[0.03] p-3.5">
                <p class="text-[11px] font-bold text-[#0A5C66] uppercase tracking-wide mb-2.5 flex items-center gap-1.5"><i class="bi bi-sliders"></i> Flexible Plan Range</p>
                <p class="text-[11.5px] text-[#64748B] mb-3">Customer drags a slider between Min and Max to pick their own amount. <strong class="text-[#334155]">Both Min and Max are required</strong> in this mode - the plan won't save without a real range, so it can never silently fall back to acting like a Fixed plan.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label for="min_investment_amount" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Min investment (₹)</label>
                    <input type="number" name="min_investment_amount" id="min_investment_amount" min="1" step="0.01" value="{{ old('min_investment_amount', $plan->min_investment_amount) }}"
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                    @error('min_investment_amount')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="max_investment_amount" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Max investment (₹)</label>
                    <input type="number" name="max_investment_amount" id="max_investment_amount" min="1" step="0.01" value="{{ old('max_investment_amount', $plan->max_investment_amount) }}"
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                    @error('max_investment_amount')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="slider_step" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Slider step (₹, optional)</label>
                    <input type="number" name="slider_step" id="slider_step" min="0.01" step="0.01" placeholder="e.g. 100 - leave blank to auto-space ~50 steps" value="{{ old('slider_step', $plan->slider_step) }}"
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                    @error('slider_step')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div id="range-preview" class="sm:col-span-2 rounded-xl border border-[#E2E8F0] bg-white px-3.5 py-3" hidden>
                    <p class="text-[10.5px] font-bold text-[#94A3B8] uppercase tracking-wide mb-2.5">Customer will see a slider like this on Plan Details</p>
                    <div class="flex items-center gap-2.5">
                        <span id="range-preview-min" class="text-[12.5px] font-black text-[#0F172A] whitespace-nowrap">₹0</span>
                        <div class="relative flex-1 h-2 bg-slate-200 rounded-full">
                            <div class="absolute inset-0 bg-[#0A5C66] rounded-full"></div>
                            <div class="absolute top-1/2 left-0 -translate-x-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-white border-2 border-[#0A5C66] shadow"></div>
                            <div class="absolute top-1/2 right-0 translate-x-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-white border-2 border-[#0A5C66] shadow"></div>
                        </div>
                        <span id="range-preview-max" class="text-[12.5px] font-black text-[#0F172A] whitespace-nowrap">₹0</span>
                    </div>
                    <p id="range-preview-step" class="text-[10.5px] text-[#94A3B8] mt-2"></p>
                </div>

                <p class="text-[11px] text-[#94A3B8] sm:col-span-2 -mt-1.5">The return is computed live from each Duration row's growth rate below, proportional to whatever amount the customer actually picks. <strong class="text-[#334155]">Requires at least one Duration option below</strong> - the plan won't save without one, since without a duration there's no rate to compute a proportional return from.</p>

                <label class="sm:col-span-2 flex items-center gap-2.5 h-11 px-3.5 rounded-lg border border-[#CBD5E1] bg-white has-[:checked]:border-brand has-[:checked]:bg-brand/5 cursor-pointer transition-colors w-fit">
                    <input type="checkbox" name="allow_topups" value="1" class="accent-brand" {{ old('allow_topups', $plan->allow_topups) ? 'checked' : '' }}>
                    <span class="text-[13.5px] font-semibold text-[#0F172A]">Allow top-ups (SIP-style pot)</span>
                </label>
                <p class="text-[11px] text-[#94A3B8] sm:col-span-2 -mt-1.5">A user's first contribution opens one ongoing pot; every later contribution adds to that SAME pot (one shared maturity date) up to Max investment, instead of each investment being a separate independent purchase.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label for="lock_duration" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Lock duration</label>
                    <input type="text" name="lock_duration" id="lock_duration" maxlength="30" placeholder="e.g. Flexible, 12 Months, 36 Months" value="{{ old('lock_duration', $plan->lock_duration) }}" required
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                    @error('lock_duration')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="sort_order" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Sort order</label>
                    <input type="number" name="sort_order" id="sort_order" min="0" value="{{ old('sort_order', $plan->sort_order) }}"
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                    @error('sort_order')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="max-w-xs">
                <label for="status" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Availability</label>
                @php $currentStatus = old('status', $plan->status ?? 'active'); @endphp
                <select name="status" id="status" class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                    <option value="draft" {{ $currentStatus === 'draft' ? 'selected' : '' }}>Draft (admin-only, not purchasable)</option>
                    <option value="active" {{ $currentStatus === 'active' ? 'selected' : '' }}>Active (listed &amp; purchasable)</option>
                    <option value="hidden" {{ $currentStatus === 'hidden' ? 'selected' : '' }}>Hidden (purchasable via direct link only)</option>
                    <option value="expired" {{ $currentStatus === 'expired' ? 'selected' : '' }}>Expired (not listed, not purchasable)</option>
                </select>
                @error('status')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
            </div>

            {{-- ================= Trust Builder / Growth Plan unlock system ================= --}}
            <div class="pt-4 mt-1 border-t border-[#E5E9EB]">
                <h2 class="font-poppins font-bold text-[14px] text-[#0F172A] mb-3">Unlock system</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <div>
                        <label for="plan_type" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Plan type</label>
                        <select name="plan_type" id="plan_type" class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                            <option value="" {{ old('plan_type', $plan->plan_type) === null ? 'selected' : '' }}>Regular plan</option>
                            <option value="trust_builder" {{ old('plan_type', $plan->plan_type) === 'trust_builder' ? 'selected' : '' }}>Trust Builder Plan</option>
                            <option value="growth" {{ old('plan_type', $plan->plan_type) === 'growth' ? 'selected' : '' }}>Growth Plan</option>
                        </select>
                        @error('plan_type')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="max_purchase_per_user" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Max purchases per user</label>
                        <input type="number" name="max_purchase_per_user" id="max_purchase_per_user" min="1" placeholder="Unlimited" value="{{ old('max_purchase_per_user', $plan->max_purchase_per_user) }}"
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        @error('max_purchase_per_user')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="cooldown_days" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Cooldown (days)</label>
                        <input type="number" name="cooldown_days" id="cooldown_days" min="0" placeholder="None" value="{{ old('cooldown_days', $plan->cooldown_days) }}"
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        @error('cooldown_days')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                </div>

                <label class="flex items-center gap-2.5 h-11 px-3.5 rounded-lg border border-[#CBD5E1] has-[:checked]:border-brand has-[:checked]:bg-brand/5 cursor-pointer transition-colors w-fit mt-3">
                    <input type="checkbox" name="unlock_enabled" value="1" class="accent-brand" {{ old('unlock_enabled', $plan->unlock_enabled) ? 'checked' : '' }}>
                    <span class="text-[13.5px] font-semibold text-[#0F172A]">Require another plan to unlock this one</span>
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 mt-3">
                    <div>
                        <label for="requires_plan_id" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Requires purchase of</label>
                        <select name="requires_plan_id" id="requires_plan_id" class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                            <option value="">— None —</option>
                            @foreach ($requirablePlans as $requirable)
                                <option value="{{ $requirable->id }}" {{ (int) old('requires_plan_id', $plan->requires_plan_id) === $requirable->id ? 'selected' : '' }}>{{ $requirable->title }}</option>
                            @endforeach
                        </select>
                        @error('requires_plan_id')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="unlock_message" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Unlock popup message</label>
                        <input type="text" name="unlock_message" id="unlock_message" maxlength="2000" placeholder="To unlock this plan, please activate a Growth Plan first." value="{{ old('unlock_message', $plan->unlock_message) }}"
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        @error('unlock_message')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- ================= Marketing & availability ================= --}}
            <div class="pt-4 mt-1 border-t border-[#E5E9EB]">
                <h2 class="font-poppins font-bold text-[14px] text-[#0F172A] mb-3">Marketing & availability</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                    <div>
                        <label for="marketing_badge" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Marketing badge</label>
                        <input type="text" name="marketing_badge" id="marketing_badge" maxlength="40" placeholder="Most Popular" value="{{ old('marketing_badge', $plan->marketing_badge) }}"
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        <p class="text-[11px] text-[#94A3B8] mt-1">Plain text now - pick the star/fire/etc. icon separately below instead of typing an emoji.</p>
                        @error('marketing_badge')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="marketing-badge-icon-input" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Badge icon (optional)</label>
                        <div class="flex items-center gap-2.5">
                            <div class="w-10 h-10 rounded-lg bg-[#0A5C66]/5 border border-[#E5E9EB] flex items-center justify-center shrink-0">
                                <i id="marketing-badge-icon-preview" class="bi {{ old('marketing_badge_icon', $plan->marketing_badge_icon) ?: 'bi-star-fill' }} text-[16px] text-[#0A5C66]"></i>
                            </div>
                            <input type="text" name="marketing_badge_icon" id="marketing-badge-icon-input" maxlength="50" placeholder="e.g. bi-star-fill" value="{{ old('marketing_badge_icon', $plan->marketing_badge_icon) }}"
                                class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                            <button type="button" data-icon-picker-target="marketing-badge-icon-input" data-icon-picker-preview="marketing-badge-icon-preview" class="icon-picker-open shrink-0 h-10 px-3 rounded-lg border border-[#CBD5E1] text-[12.5px] font-semibold text-[#334155] hover:border-brand hover:text-brand transition-colors whitespace-nowrap">
                                <i class="bi bi-grid-3x3-gap"></i> Browse
                            </button>
                        </div>
                        @error('marketing_badge_icon')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="risk_level" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Risk level</label>
                        <select name="risk_level" id="risk_level" class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                            <option value="">— Not set —</option>
                            @foreach (['Low', 'Medium', 'High'] as $risk)
                                <option value="{{ $risk }}" {{ old('risk_level', $plan->risk_level) === $risk ? 'selected' : '' }}>{{ $risk }}</option>
                            @endforeach
                        </select>
                        @error('risk_level')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                </div>

                @php
                    $currentBadgeColor = old('marketing_badge_color', $plan->marketing_badge_color) ?: 'amber';
                    $badgeColorDots = [
                        'amber' => '#F59E0B', 'teal' => '#0A5C66', 'green' => '#10B981',
                        'rose' => '#F43F5E', 'violet' => '#8B5CF6', 'slate' => '#64748B',
                    ];
                @endphp
                <div class="mt-3.5">
                    <label class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Badge colour</label>
                    <div class="flex flex-wrap items-center gap-2.5">
                        @foreach (\App\Models\Plan::MARKETING_BADGE_COLORS as $colorKey => $colorClasses)
                            <label class="group cursor-pointer" title="{{ ucfirst($colorKey) }}">
                                <input type="radio" name="marketing_badge_color" value="{{ $colorKey }}" class="hidden" {{ $currentBadgeColor === $colorKey ? 'checked' : '' }}>
                                <span class="flex w-8 h-8 rounded-full border-2 border-transparent items-center justify-center transition-all group-has-[:checked]:border-[#0F172A] group-has-[:checked]:scale-110" style="background-color: {{ $badgeColorDots[$colorKey] }}">
                                    <i class="bi bi-check-lg text-white text-[13px] hidden group-has-[:checked]:block"></i>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-[#94A3B8] mt-1.5">Controls the badge's background/text colour on Explore and Plan Details.</p>
                    @error('marketing_badge_color')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 mt-3.5">
                    <div>
                        <label for="start_date" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Start date</label>
                        <input type="date" name="start_date" id="start_date" value="{{ old('start_date', optional($plan->start_date)->format('Y-m-d')) }}"
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        @error('start_date')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="end_date" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">End date</label>
                        <input type="date" name="end_date" id="end_date" value="{{ old('end_date', optional($plan->end_date)->format('Y-m-d')) }}"
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        @error('end_date')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 mt-3">
                    <label class="flex items-center gap-2.5 h-11 px-3.5 rounded-lg border border-[#CBD5E1] has-[:checked]:border-brand has-[:checked]:bg-brand/5 cursor-pointer transition-colors w-fit">
                        <input type="checkbox" name="auto_mature" value="1" class="accent-brand" {{ old('auto_mature', $plan->auto_mature) ? 'checked' : '' }}>
                        <span class="text-[13.5px] font-semibold text-[#0F172A]">Auto-mature (credit wallet automatically)</span>
                    </label>
                </div>

                {{-- Catalog-wide purchase limit --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 mt-3.5 border-t border-[#E5E9EB] pt-3.5">
                    <div>
                        <label for="max_purchases" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">
                            Max total purchases (catalog-wide, optional)
                        </label>
                        <input type="number" name="max_purchases" id="max_purchases" min="1" placeholder="Unlimited"
                            value="{{ old('max_purchases', $plan->max_purchases) }}"
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        <p class="text-[11px] text-[#94A3B8] mt-1">Leave blank for unlimited. Plan automatically blocks purchases when this limit is reached.</p>
                        @error('max_purchases')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Sales count (read-only)</label>
                        <div class="h-10 flex items-center px-3 rounded-lg bg-[#F8FAFC] border border-[#E5E9EB] text-[14px] font-bold text-[#0F172A]">
                            {{ number_format($plan->total_purchases_count ?? 0) }}
                            @if ($plan->max_purchases)
                                <span class="text-[#94A3B8] font-normal ml-1.5">/ {{ number_format($plan->max_purchases) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================= Multiple durations (max 4) ================= --}}
            <div class="pt-4 mt-1 border-t border-[#E5E9EB]">
                <h2 class="font-poppins font-bold text-[14px] text-[#0F172A] mb-1">Duration options (max 4)</h2>

                <div id="trust-builder-duration-notice" style="display: none;" class="mb-3 flex items-center gap-2 px-3.5 py-2.5 rounded-lg bg-[#0A5C66]/8 border border-[#0A5C66]/20">
                    <i class="bi bi-lock-fill text-[#0A5C66] text-[13px]"></i>
                    <span class="text-[12.5px] font-semibold text-[#0A5C66]">Locked: 1 Day, auto-mature. Trust Builder plans always run for exactly 1 day and credit profit automatically - the options below don't apply.</span>
                </div>

                <div id="flexible-duration-required-notice" style="display: none;" class="mb-3 flex items-center gap-2 px-3.5 py-2.5 rounded-lg bg-amber-50 border border-amber-200">
                    <i class="bi bi-exclamation-triangle-fill text-amber-600 text-[13px]"></i>
                    <span class="text-[12.5px] font-semibold text-amber-700">Required for Flexible plans: at least one row below, or the plan won't save. Flexible purchases compute their return from a Duration row's rate - without one there's nothing to compute against.</span>
                </div>

                <div id="duration-rows-section">
                    <p class="text-[12px] text-[#64748B] mb-3">Leave a row's label blank to skip it. When set, users pick one of these on Plan Details instead of the single duration/return above. Mark one row as the default. <span class="text-[#0A5C66] font-semibold">Enter Days + Rate % and each row's Daily ₹ / Total ₹ fill in automatically</span> from the Investment above.</p>

                @php
                    $existingDurations = old('durations') ? collect() : ($plan->durations ?? collect())->values();
                    $defaultDurationIndex = old('duration_default', (string) $existingDurations->search(fn ($d) => $d->is_default));
                    if ($defaultDurationIndex === '' || $defaultDurationIndex === false) {
                        $defaultDurationIndex = $existingDurations->isEmpty() ? '0' : (string) $existingDurations->keys()->first();
                    }
                @endphp
                <div id="duration-rows" class="flex flex-col gap-3">
                    @for ($i = 0; $i < 4; $i++)
                        @php $d = $existingDurations[$i] ?? null; @endphp
                        <div data-duration-row class="grid grid-cols-2 sm:grid-cols-6 gap-2.5 items-end p-3 rounded-lg border border-[#E5E9EB]">
                            <input type="hidden" name="durations[{{ $i }}][id]" value="{{ old("durations.$i.id", $d?->id) }}">
                            <div class="col-span-2 sm:col-span-1">
                                <label class="block text-[10.5px] font-semibold text-[#64748B] mb-1">Default</label>
                                <input type="radio" name="duration_default" value="{{ $i }}" class="accent-brand w-4 h-4"
                                    {{ (string) $i === (string) $defaultDurationIndex ? 'checked' : '' }}>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <label class="block text-[10.5px] font-semibold text-[#64748B] mb-1">Label</label>
                                <input type="text" name="durations[{{ $i }}][label]" maxlength="30" placeholder="e.g. 3 Months" value="{{ old("durations.$i.label", $d?->label) }}"
                                    class="w-full h-9 rounded-lg border border-[#CBD5E1] px-2.5 text-[13px] text-[#0F172A] outline-none focus:border-brand focus:ring-2 focus:ring-brand/15">
                            </div>
                            <div>
                                <label class="block text-[10.5px] font-semibold text-[#64748B] mb-1">Days</label>
                                <input type="number" name="durations[{{ $i }}][duration_days]" min="1" value="{{ old("durations.$i.duration_days", $d?->duration_days) }}"
                                    class="w-full h-9 rounded-lg border border-[#CBD5E1] px-2.5 text-[13px] text-[#0F172A] outline-none focus:border-brand focus:ring-2 focus:ring-brand/15">
                            </div>
                            <div>
                                <label class="block text-[10.5px] font-semibold text-[#64748B] mb-1">Rate %</label>
                                <input type="number" name="durations[{{ $i }}][growth_rate]" min="0" value="{{ old("durations.$i.growth_rate", $d?->growth_rate) }}"
                                    class="w-full h-9 rounded-lg border border-[#CBD5E1] px-2.5 text-[13px] text-[#0F172A] outline-none focus:border-brand focus:ring-2 focus:ring-brand/15">
                            </div>
                            <div>
                                <label class="block text-[10.5px] font-semibold text-[#64748B] mb-1">Daily ₹ <span class="font-normal text-[#94A3B8]">· auto</span></label>
                                <input type="number" id="duration-daily-{{ $i }}" min="0" step="0.01" value="{{ old("durations.$i.daily_profit", $d?->daily_profit) }}" disabled
                                    class="w-full h-9 rounded-lg border border-[#CBD5E1] px-2.5 text-[13px] text-[#94A3B8] bg-[#F8FAFC] outline-none">
                            </div>
                            <div>
                                <label class="block text-[10.5px] font-semibold text-[#64748B] mb-1">Total ₹ <span class="font-normal text-[#94A3B8]">· auto</span></label>
                                <input type="number" id="duration-total-{{ $i }}" min="0" step="0.01" value="{{ old("durations.$i.total_return", $d?->total_return) }}" disabled
                                    class="w-full h-9 rounded-lg border border-[#CBD5E1] px-2.5 text-[13px] text-[#94A3B8] bg-[#F8FAFC] outline-none">
                            </div>
                        </div>
                    @endfor
                </div>
                </div>
            </div>

            {{-- ================= Content: highlights, terms, FAQs ================= --}}
            <div class="pt-4 mt-1 border-t border-[#E5E9EB]">
                <h2 class="font-poppins font-bold text-[14px] text-[#0F172A] mb-3">Highlights, terms & FAQs</h2>

                <label class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Highlight chips (up to 6)</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 mb-3.5">
                    @php $existingHighlights = old('highlights', $plan->highlights ?? []); @endphp
                    @for ($i = 0; $i < 6; $i++)
                        <input type="text" name="highlights[{{ $i }}]" maxlength="60" placeholder="e.g. 24x7 Support" value="{{ $existingHighlights[$i] ?? '' }}"
                            class="w-full h-9 rounded-lg border border-[#CBD5E1] px-2.5 text-[13px] text-[#0F172A] outline-none focus:border-brand focus:ring-2 focus:ring-brand/15">
                    @endfor
                </div>

                <div class="mb-3.5">
                    <label for="terms" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Terms</label>
                    <textarea name="terms" id="terms" rows="3" maxlength="8000" placeholder="Standard GullakPe investment terms apply..."
                        class="w-full rounded-lg border border-[#CBD5E1] px-3 py-2 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">{{ old('terms', $plan->terms) }}</textarea>
                    @error('terms')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>

                <label class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">FAQs (up to 4)</label>
                <div class="flex flex-col gap-2.5">
                    @php $existingFaqs = old('faqs', $plan->faqs ?? []); @endphp
                    @for ($i = 0; $i < 4; $i++)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <input type="text" name="faqs[{{ $i }}][q]" maxlength="200" placeholder="Question" value="{{ $existingFaqs[$i]['q'] ?? '' }}"
                                class="w-full h-9 rounded-lg border border-[#CBD5E1] px-2.5 text-[13px] text-[#0F172A] outline-none focus:border-brand focus:ring-2 focus:ring-brand/15">
                            <input type="text" name="faqs[{{ $i }}][a]" maxlength="1000" placeholder="Answer" value="{{ $existingFaqs[$i]['a'] ?? '' }}"
                                class="w-full h-9 rounded-lg border border-[#CBD5E1] px-2.5 text-[13px] text-[#0F172A] outline-none focus:border-brand focus:ring-2 focus:ring-brand/15">
                        </div>
                    @endfor
                </div>
            </div>

            <button type="submit" class="h-10 rounded-lg bg-brand text-white font-semibold text-[13.5px] hover:bg-brand-light transition-colors active:scale-[0.99] sm:w-fit sm:px-6 mt-1">
                {{ $plan->exists ? 'Save changes' : 'Create plan' }}
            </button>
        </form>

        {{-- ================= Live Preview (plan.md Section 13) ================= --}}
        <aside class="lg:sticky lg:top-6 flex flex-col gap-2">
            <p class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wide flex items-center gap-1.5">
                <i class="bi bi-eye"></i> Live preview
            </p>
            <div class="bg-white rounded-2xl border border-[#E5E9EB] shadow-sm p-4">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <span id="lp-marketing-badge" class="hidden items-center gap-1 text-[9.5px] font-extrabold uppercase tracking-wide px-2 py-0.5 rounded-full">
                        <i id="lp-marketing-badge-icon" class="bi"></i>
                        <span id="lp-marketing-badge-text"></span>
                    </span>
                    <span id="lp-category" class="text-[9.5px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">—</span>
                </div>
                <div class="flex items-center gap-3 mb-3.5">
                    <div class="w-14 h-14 rounded-full bg-[#F2F7F8] border border-[#E4EFEF] flex items-center justify-center overflow-hidden shrink-0">
                        <img id="lp-icon-img" class="hidden w-full h-full object-contain" alt="">
                        <i id="lp-icon-fallback" class="bi bi-piggy-bank text-[24px] text-[#0A5C66]"></i>
                    </div>
                    <div class="min-w-0">
                        <h4 id="lp-title" class="text-[14.5px] font-extrabold text-[#0D1F3C] font-poppins truncate">Plan title</h4>
                        <p id="lp-subtitle" class="text-[11px] text-slate-500 truncate">Subtitle appears here</p>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-1.5 text-center mb-3 py-2.5 border-y border-slate-100">
                    <div>
                        <p class="text-[8.5px] text-slate-400 uppercase font-bold tracking-wide">Rate</p>
                        <p id="lp-rate" class="text-[12.5px] font-black text-[#0A5C66]">0%</p>
                    </div>
                    <div>
                        <p class="text-[8.5px] text-slate-400 uppercase font-bold tracking-wide">Daily</p>
                        <p id="lp-daily" class="text-[12.5px] font-black text-emerald-600">₹0</p>
                    </div>
                    <div>
                        <p class="text-[8.5px] text-slate-400 uppercase font-bold tracking-wide">Total</p>
                        <p id="lp-total" class="text-[12.5px] font-black text-[#0F172A]">₹0</p>
                    </div>
                </div>
                <p id="lp-amount" class="text-[13px] font-bold text-[#0F172A] text-center">Invest ₹0</p>
            </div>
            <p class="text-[10.5px] text-[#94A3B8]">Mirrors the Explore card + auto profit calculator - updates as you type. Not exactly pixel-identical to the live site's card, but reflects the same values.</p>
        </aside>
        </div>

        </div>
    </main>
</div>

<!-- Icon Picker Modal - browse/search the full real Bootstrap Icons set
     (public/assets/bootstrap-icons-list.json, generated from the actual
     installed npm package - see PlanManagementController) instead of
     admins having to guess exact class names blind. -->
<div id="icon-picker-modal" class="hidden fixed inset-0 z-[300] bg-slate-900/50 backdrop-blur-sm items-center justify-center p-4">
    <div class="w-full max-w-2xl max-h-[80vh] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-[#E5E9EB]">
        <div class="p-4 border-b border-[#E5E9EB] flex items-center gap-3 shrink-0">
            <div class="relative flex-1">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-[#94A3B8] text-[13px]"></i>
                <input type="text" id="icon-picker-search" placeholder="Search icons by name..." autocomplete="off"
                    class="w-full h-10 pl-9 pr-3 rounded-lg border border-[#CBD5E1] text-[14px] text-[#0F172A] outline-none focus:border-brand focus:ring-2 focus:ring-brand/15">
            </div>
            <button type="button" id="icon-picker-close" class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center text-[#64748B] hover:bg-[#F1F5F9] transition-colors">
                <i class="bi bi-x-lg text-[14px]"></i>
            </button>
        </div>
        <div id="icon-picker-grid" class="flex-1 overflow-y-auto p-3 grid grid-cols-5 sm:grid-cols-6 gap-2 content-start"></div>
        <div id="icon-picker-empty" class="hidden flex-1 items-center justify-center p-8 text-center">
            <p class="text-[13px] text-[#94A3B8] font-medium">No icons match that search.</p>
        </div>
        <div class="p-2.5 border-t border-[#E5E9EB] text-center shrink-0">
            <span id="icon-picker-count" class="text-[11px] text-[#94A3B8] font-semibold"></span>
        </div>
    </div>
</div>

<script>
(function () {
    var categoryIcons = @json($categoryIcons);
    var select = document.getElementById('badge-select');
    var iconInput = document.getElementById('badge-icon-input');
    var iconPreview = document.getElementById('badge-icon-preview');

    function setIcon(icon) {
        var cls = icon || 'bi-tag-fill';
        iconInput.value = icon || '';
        iconPreview.className = 'bi ' + cls + ' text-[16px] text-[#0A5C66]';
    }

    select.addEventListener('change', function () {
        setIcon(this.value === '__custom__' ? '' : (categoryIcons[this.value] || 'bi-tag-fill'));
    });

    iconInput.addEventListener('input', function () {
        iconPreview.className = 'bi ' + (this.value || 'bi-tag-fill') + ' text-[16px] text-[#0A5C66]';
    });
})();

(function () {
    var minInput = document.getElementById('min_investment_amount');
    var maxInput = document.getElementById('max_investment_amount');
    var stepInput = document.getElementById('slider_step');
    var preview = document.getElementById('range-preview');
    var previewMin = document.getElementById('range-preview-min');
    var previewMax = document.getElementById('range-preview-max');
    var previewStep = document.getElementById('range-preview-step');

    function formatRupees(n) {
        return '₹' + Math.round(n).toLocaleString('en-IN');
    }

    function updatePreview() {
        var min = parseFloat(minInput.value);
        var max = parseFloat(maxInput.value);
        if (!isNaN(min) && !isNaN(max) && max > min) {
            previewMin.textContent = formatRupees(min);
            previewMax.textContent = formatRupees(max);
            var step = parseFloat(stepInput.value);
            if (!(step > 0)) step = Math.max(1, Math.round((max - min) / 50));
            previewStep.textContent = 'Step: ₹' + step.toLocaleString('en-IN') + ' (~' + Math.round((max - min) / step) + ' stops)';
            preview.hidden = false;
        } else {
            preview.hidden = true;
        }
    }

    minInput.addEventListener('input', updatePreview);
    maxInput.addEventListener('input', updatePreview);
    stepInput.addEventListener('input', updatePreview);
    updatePreview();
})();

(function () {
    var modal = document.getElementById('icon-picker-modal');
    var searchInput = document.getElementById('icon-picker-search');
    var grid = document.getElementById('icon-picker-grid');
    var emptyState = document.getElementById('icon-picker-empty');
    var countLabel = document.getElementById('icon-picker-count');
    var closeBtn = document.getElementById('icon-picker-close');

    var allIcons = null;
    var iconsUrl = '{{ asset('assets/bootstrap-icons-list.json') }}';
    var activeTargetInput = null;
    var activeTargetPreview = null;

    // Shown only for the instant the modal opens, before the real ~2000-icon
    // list has finished loading - not a cap on what's browsable. Once
    // fetched, the full list (or the live search results against it)
    // replaces this immediately.
    var STARTER_ICONS = [
        'piggy-bank', 'wallet2', 'bank2', 'bank', 'cash-coin', 'cash-stack', 'coin',
        'graph-up-arrow', 'graph-up', 'bar-chart-fill', 'pie-chart-fill', 'currency-rupee',
        'shield-check', 'shield-lock', 'lock-fill', 'check-circle-fill', 'star-fill',
        'gem', 'gift-fill', 'trophy-fill', 'award-fill', 'house-heart', 'house-door-fill',
        'car-front-fill', 'airplane-fill', 'phone-fill', 'laptop-fill', 'watch',
        'mortarboard-fill', 'heart-fill', 'umbrella-fill', 'flower1', 'tree-fill',
        'lightning-charge-fill', 'fire', 'speedometer2', 'rocket-takeoff-fill',
        'calendar2-check', 'clock-fill', 'hourglass-split', 'briefcase-fill',
        'building', 'globe2', 'compass-fill', 'flag-fill', 'tag-fill', 'ticket-perforated-fill',
    ];

    function iconGridItem(name) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'flex flex-col items-center justify-center gap-1.5 py-3 rounded-lg border border-[#E5E9EB] hover:border-brand hover:bg-brand/5 transition-colors';
        btn.title = name;
        btn.innerHTML = '<i class="bi bi-' + name + ' text-[19px] text-[#334155]"></i>' +
            '<span class="text-[9px] text-[#94A3B8] font-medium truncate w-full text-center px-1">' + name + '</span>';
        btn.addEventListener('click', function () {
            if (!activeTargetInput) return;
            activeTargetInput.value = 'bi-' + name;
            activeTargetInput.dispatchEvent(new Event('input', { bubbles: true }));
            if (activeTargetPreview) {
                activeTargetPreview.className = 'bi bi-' + name + ' text-[16px] text-[#0A5C66]';
            }
            closeModal();
        });
        return btn;
    }

    // Renders every icon in `list` - no slicing/cap. ~2000 simple buttons is
    // well within what a browser handles fine in an on-demand modal.
    function render(list, label) {
        grid.innerHTML = '';
        var frag = document.createDocumentFragment();
        list.forEach(function (name) {
            frag.appendChild(iconGridItem(name));
        });
        grid.appendChild(frag);
        var hasResults = list.length > 0;
        grid.classList.toggle('hidden', !hasResults);
        emptyState.classList.toggle('hidden', hasResults);
        emptyState.classList.toggle('flex', !hasResults);
        countLabel.textContent = label;
    }

    function runSearch() {
        var q = searchInput.value.trim().toLowerCase();
        if (!allIcons) {
            // Still loading - filter the starter set so search doesn't look
            // dead in the brief window before the full list arrives.
            var starterMatches = q === '' ? STARTER_ICONS : STARTER_ICONS.filter(function (n) { return n.indexOf(q) !== -1; });
            render(starterMatches, 'Loading full icon library...');
            return;
        }
        if (q === '') {
            render(allIcons, allIcons.length + ' icons - browse or search by name');
            return;
        }
        var matches = allIcons.filter(function (name) { return name.indexOf(q) !== -1; });
        render(matches, matches.length + ' match' + (matches.length === 1 ? '' : 'es') + ' for "' + q + '"');
    }

    function ensureIconsLoaded(callback) {
        if (allIcons) { callback(); return; }
        fetch(iconsUrl).then(function (res) { return res.json(); }).then(function (data) {
            allIcons = data;
            callback();
        }).catch(function () {
            countLabel.textContent = 'Could not load the full icon list - showing suggestions only.';
        });
    }

    function openModal(targetInput, targetPreview) {
        activeTargetInput = targetInput;
        activeTargetPreview = targetPreview;
        searchInput.value = '';
        render(allIcons || STARTER_ICONS, allIcons ? (allIcons.length + ' icons - browse or search by name') : 'Loading full icon library...');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        searchInput.focus();
        ensureIconsLoaded(function () {
            // Only replace the grid if the admin hasn't already typed a
            // search while this was loading - runSearch() re-checks
            // allIcons itself so a mid-load query still resolves correctly.
            runSearch();
        });
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        activeTargetInput = null;
        activeTargetPreview = null;
    }

    document.querySelectorAll('.icon-picker-open').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetInput = document.getElementById(btn.getAttribute('data-icon-picker-target'));
            var targetPreview = document.getElementById(btn.getAttribute('data-icon-picker-preview'));
            openModal(targetInput, targetPreview);
        });
    });

    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });
    searchInput.addEventListener('input', runSearch);
})();

// Returns auto-calculator - stops the admin from computing Daily/Total by hand
// in a separate tool. Same formula the purchase engine AND PlanManagementController
// use server-side (the source of truth - these fields are disabled/display-only,
// purely a live preview of what the server will actually save):
//   total = amount * (1 + (rate/100) * days/365);  daily = (total - amount)/days
(function () {
    function computeReturn(amount, ratePct, days) {
        if (!(amount > 0) || !(ratePct >= 0) || !(days > 0)) return null;
        var total = amount * (1 + (ratePct / 100) * (days / 365));
        var daily = (total - amount) / days;
        return { total: Math.round(total * 100) / 100, daily: Math.round(daily * 100) / 100 };
    }

    function num(el) {
        if (!el) return NaN;
        var v = parseFloat(el.value);
        return isNaN(v) ? NaN : v;
    }

    function fill(outEl, value) {
        if (outEl) outEl.value = value;
    }

    var investmentEl = document.getElementById('investment_amount');

    // --- Plan-level headline figures (Investment + Growth rate + Term days) ---
    var rateEl = document.getElementById('growth_rate');
    var termEl = document.getElementById('term_days_calc');
    var dailyEl = document.getElementById('daily_profit');
    var totalEl = document.getElementById('total_return');

    function recalcPlanLevel() {
        var r = computeReturn(num(investmentEl), num(rateEl), num(termEl));
        if (!r) return;
        fill(dailyEl, r.daily);
        fill(totalEl, r.total);
    }
    [investmentEl, rateEl, termEl].forEach(function (el) {
        if (el) el.addEventListener('input', recalcPlanLevel);
    });

    // --- Duration rows (Days + Rate %, against the plan's Investment) ---
    var rows = Array.prototype.slice.call(document.querySelectorAll('#duration-rows [data-duration-row]'));
    var rowState = rows.map(function (row, i) {
        var daysEl = row.querySelector('input[name$="[duration_days]"]');
        var rRateEl = row.querySelector('input[name$="[growth_rate]"]');
        var rDailyEl = document.getElementById('duration-daily-' + i);
        var rTotalEl = document.getElementById('duration-total-' + i);

        function recalcRow() {
            var r = computeReturn(num(investmentEl), num(rRateEl), num(daysEl));
            if (!r) return;
            fill(rDailyEl, r.daily);
            fill(rTotalEl, r.total);
        }
        [daysEl, rRateEl].forEach(function (el) {
            if (el) el.addEventListener('input', recalcRow);
        });
        return recalcRow;
    });

    // A change to the shared Investment cascades into every duration row too.
    if (investmentEl) {
        investmentEl.addEventListener('input', function () {
            rowState.forEach(function (recalc) { recalc(); });
        });
    }

    // --- Duration Type (Trust Builder locks to 1 Day, hides the multi-duration builder) ---
    var planTypeEl = document.getElementById('plan_type');
    var durationRowsSection = document.getElementById('duration-rows-section');
    var trustBuilderNotice = document.getElementById('trust-builder-duration-notice');
    var autoMatureCheckbox = document.querySelector('input[name="auto_mature"]');

    function applyDurationTypeUI() {
        var isTrustBuilder = planTypeEl && planTypeEl.value === 'trust_builder';
        if (durationRowsSection) durationRowsSection.style.display = isTrustBuilder ? 'none' : '';
        if (trustBuilderNotice) trustBuilderNotice.style.display = isTrustBuilder ? '' : 'none';
        if (autoMatureCheckbox) {
            if (isTrustBuilder) {
                autoMatureCheckbox.checked = true;
                autoMatureCheckbox.disabled = true;
            } else {
                autoMatureCheckbox.disabled = false;
            }
        }
    }
    if (planTypeEl) {
        planTypeEl.addEventListener('change', applyDurationTypeUI);
        applyDurationTypeUI();
    }

    // --- Live Preview (plan.md Section 13) - mirrors the Explore card +
    // profit calculator, reading straight off the same fields/computed
    // values above rather than a second copy of the formula. ---
    (function () {
        var badgeColorMap = @json(\App\Models\Plan::MARKETING_BADGE_COLORS);
        var existingIconUrl = @json($plan->iconImageUrl());

        var titleEl = document.getElementById('title');
        var subtitleEl = document.getElementById('subtitle');
        var badgeSelectEl = document.getElementById('badge-select');
        var badgeCustomEl = document.getElementById('badge-custom');
        var mBadgeTextEl = document.getElementById('marketing_badge');
        var mBadgeIconEl = document.getElementById('marketing-badge-icon-input');
        var minInvestEl = document.getElementById('min_investment_amount');
        var maxInvestEl = document.getElementById('max_investment_amount');
        var iconFileEl = document.getElementById('icon_image');

        var lpTitle = document.getElementById('lp-title');
        var lpSubtitle = document.getElementById('lp-subtitle');
        var lpCategory = document.getElementById('lp-category');
        var lpMBadge = document.getElementById('lp-marketing-badge');
        var lpMBadgeIcon = document.getElementById('lp-marketing-badge-icon');
        var lpMBadgeText = document.getElementById('lp-marketing-badge-text');
        var lpIconImg = document.getElementById('lp-icon-img');
        var lpIconFallback = document.getElementById('lp-icon-fallback');
        var lpRate = document.getElementById('lp-rate');
        var lpDaily = document.getElementById('lp-daily');
        var lpTotal = document.getElementById('lp-total');
        var lpAmount = document.getElementById('lp-amount');

        function currentBadgeColorRadio() {
            var checked = document.querySelector('input[name="marketing_badge_color"]:checked');
            return checked ? checked.value : 'amber';
        }

        function setIconPreview(src) {
            if (src) {
                lpIconImg.src = src;
                lpIconImg.classList.remove('hidden');
                lpIconFallback.classList.add('hidden');
            } else {
                lpIconImg.classList.add('hidden');
                lpIconFallback.classList.remove('hidden');
            }
        }

        function updateLivePreview() {
            lpTitle.textContent = (titleEl.value || '').trim() || 'Plan title';
            lpSubtitle.textContent = (subtitleEl.value || '').trim() || 'Subtitle appears here';

            var category = badgeSelectEl.value === '__custom__' ? badgeCustomEl.value : badgeSelectEl.value;
            lpCategory.textContent = (category || '').trim() || '—';

            var mText = (mBadgeTextEl.value || '').trim();
            if (mText) {
                var colors = badgeColorMap[currentBadgeColorRadio()] || badgeColorMap.amber;
                lpMBadge.className = 'inline-flex items-center gap-1 text-[9.5px] font-extrabold uppercase tracking-wide px-2 py-0.5 rounded-full '
                    + colors.bg + ' ' + colors.text + ' border ' + colors.border;
                lpMBadgeIcon.className = 'bi ' + (mBadgeIconEl.value || 'bi-star-fill');
                lpMBadgeText.textContent = mText;
            } else {
                lpMBadge.className = 'hidden';
            }

            lpRate.textContent = (num(rateEl) || 0) + '%';
            lpDaily.textContent = '₹' + (dailyEl.value || '0');
            lpTotal.textContent = '₹' + (totalEl.value || '0');

            var isFlexible = num(minInvestEl) > 0 && num(maxInvestEl) > num(minInvestEl);
            lpAmount.textContent = isFlexible
                ? 'Invest ₹' + Math.round(num(minInvestEl)).toLocaleString('en-IN') + '–₹' + Math.round(num(maxInvestEl)).toLocaleString('en-IN')
                : 'Invest ₹' + Math.round(num(investmentEl) || 0).toLocaleString('en-IN');
        }

        if (iconFileEl) {
            iconFileEl.addEventListener('change', function () {
                if (iconFileEl.files && iconFileEl.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function (e) { setIconPreview(e.target.result); };
                    reader.readAsDataURL(iconFileEl.files[0]);
                } else {
                    setIconPreview(existingIconUrl);
                }
            });
        }
        setIconPreview(existingIconUrl);

        [titleEl, subtitleEl, badgeCustomEl, mBadgeTextEl, mBadgeIconEl, minInvestEl, maxInvestEl].forEach(function (el) {
            if (el) el.addEventListener('input', updateLivePreview);
        });
        document.querySelectorAll('input[name="marketing_badge_color"]').forEach(function (el) {
            el.addEventListener('change', updateLivePreview);
        });
        badgeSelectEl.addEventListener('change', updateLivePreview);
        // investment_amount/growth_rate/term_days_calc drive the auto-calc
        // (recalcPlanLevel sets daily/total programmatically, no native
        // 'input' event of its own) - one shared listener covers both.
        [investmentEl, rateEl, termEl].forEach(function (el) {
            if (el) el.addEventListener('input', updateLivePreview);
        });

        updateLivePreview();
    })();

    // --- Plan Type Switcher (Fixed vs Flexible) ---
    window.setPlanMode = function(mode) {
        var modeInput = document.getElementById('investment_mode');
        if (modeInput) modeInput.value = mode;
        var isFixed = mode === 'fixed';
        var fixedEl = document.getElementById('fixed-investment-section');
        var flexEl  = document.getElementById('flexible-investment-section');
        var btnFixed = document.getElementById('btn-fixed');
        var btnFlex  = document.getElementById('btn-flexible');

        if (fixedEl) fixedEl.style.display = isFixed ? '' : 'none';
        if (flexEl)  flexEl.style.display  = isFixed ? 'none' : '';

        // A hidden `required` field is barred from being focused, so an empty
        // one silently blocks form submission with no visible error. Only keep
        // investment_amount required while its section is actually visible.
        var amountInput = document.getElementById('investment_amount');
        if (amountInput) amountInput.required = isFixed;

        // Disabled fields don't get submitted at all - the inactive mode's
        // values can no longer reach the server regardless of what's typed
        // in them, matching PlanManagementController's own server-side reset.
        // (The server still resets them independently too - this is belt and
        // suspenders, not the only guard, since a direct/tampered POST
        // bypasses JS entirely.)
        ['min_investment_amount', 'max_investment_amount', 'slider_step'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                el.disabled = isFixed;
                el.required = !isFixed;
            }
        });
        var topupsInput = document.querySelector('input[name="allow_topups"]');
        if (topupsInput) topupsInput.disabled = isFixed;

        var flexDurationNotice = document.getElementById('flexible-duration-required-notice');
        if (flexDurationNotice) flexDurationNotice.style.display = isFixed ? 'none' : 'flex';

        var activeClass   = 'px-5 py-2.5 text-[13.5px] font-bold transition-colors bg-[#0A5C66] text-white';
        var inactiveClass = 'px-5 py-2.5 text-[13.5px] font-bold transition-colors bg-white text-[#64748B]';
        if (btnFixed) btnFixed.className = isFixed ? activeClass : inactiveClass;
        if (btnFlex)  btnFlex.className  = isFixed ? inactiveClass : activeClass;
    };

    // --- Interest Rate Presets ---
    window.applyRatePreset = function(value) {
        var rateInput = document.getElementById('growth_rate');
        if (rateInput && value !== null) {
            rateInput.value = value;
            rateInput.dispatchEvent(new Event('input'));
        }
        document.querySelectorAll('.rate-preset').forEach(function(btn) {
            var active = value !== null ? btn.dataset.rate === String(value) : btn.dataset.rate === 'custom';
            btn.classList.toggle('bg-[#0A5C66]', active);
            btn.classList.toggle('text-white', active);
            btn.classList.toggle('border-[#0A5C66]', active);
        });
    };

    // Initialize switcher state on load
    var currentMode = (document.getElementById('investment_mode') || {}).value || 'fixed';
    window.setPlanMode(currentMode);
})();
</script>

@endsection
