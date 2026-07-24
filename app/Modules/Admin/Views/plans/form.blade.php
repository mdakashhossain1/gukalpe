@extends('layouts.admin')

@section('title', $plan->exists ? 'Edit plan' : 'Add plan')

@section('content')

<div class="flex min-h-screen">

    <x-admin-sidebar active="plans" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="{{ $plan->exists ? 'Edit plan' : 'Add plan' }}" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <a href="{{ route('admin.plans') }}" class="inline-flex items-center gap-1.5 text-[13px] font-bold text-slate-400 hover:text-[#0A5C66] transition-colors mb-4">
            <i class="fa-solid fa-arrow-left text-[12px]"></i> Back to plans
        </a>

        <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">{{ $plan->exists ? 'Edit '.$plan->title : 'Add plan' }}</h1>
        <p class="text-[13.5px] text-[#64748B] mb-6">{{ $plan->exists ? 'Existing holders keep the amount/rate they already bought at - only new purchases use these numbers.' : 'Appears on Explore/Home immediately once saved as active.' }}</p>

        <form method="POST" action="{{ $plan->exists ? route('admin.plans.update', $plan) : route('admin.plans.store') }}" enctype="multipart/form-data" class="flex flex-col gap-3.5 bg-white rounded-2xl border border-[#E5E9EB] p-6">
            @csrf

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
                    <label for="image" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Image {{ $plan->exists ? '(leave empty to keep current)' : '' }}</label>
                    @if ($plan->exists && $plan->image)
                        <div class="mb-2 flex items-center gap-2.5">
                            <img src="{{ $plan->imageUrl() }}" alt="{{ $plan->title }}" class="w-14 h-14 rounded-lg object-cover border border-[#E5E9EB]">
                            <span class="text-[11.5px] text-[#94A3B8]">Current image</span>
                        </div>
                    @endif
                    <input type="file" name="image" id="image" accept="image/png,image/jpeg,image/webp" {{ $plan->exists ? '' : 'required' }}
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[13px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15 file:mr-3 file:h-full file:border-0 file:bg-[#0A5C66]/10 file:text-[#0A5C66] file:font-semibold file:px-3 file:rounded-l-lg file:cursor-pointer">
                    <p class="text-[11px] text-[#94A3B8] mt-1">JPG, PNG, or WebP · up to 4MB · saved to public/assets/plans</p>
                    @error('image')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="icon" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Icon (Bootstrap Icons class)</label>
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-lg bg-[#0A5C66]/5 border border-[#E5E9EB] flex items-center justify-center shrink-0">
                            <i id="icon-preview" class="bi {{ old('icon', $plan->icon) ?: 'bi-tag-fill' }} text-[16px] text-[#0A5C66]"></i>
                        </div>
                        <input type="text" name="icon" id="icon" maxlength="50" placeholder="e.g. bi-piggy-bank" value="{{ old('icon', $plan->icon) }}" required
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        <button type="button" data-icon-picker-target="icon" data-icon-picker-preview="icon-preview" class="icon-picker-open shrink-0 h-10 px-3 rounded-lg border border-[#CBD5E1] text-[12.5px] font-semibold text-[#334155] hover:border-brand hover:text-brand transition-colors whitespace-nowrap">
                            <i class="bi bi-grid-3x3-gap"></i> Browse
                        </button>
                    </div>
                    @error('icon')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 mt-2 pt-3 border-t border-[#E5E9EB]">
                <div>
                    <label for="investment_amount" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Investment (₹, one-time)</label>
                    <input type="number" name="investment_amount" id="investment_amount" min="1" step="0.01" value="{{ old('investment_amount', $plan->investment_amount) }}" required
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                    @error('investment_amount')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="growth_rate" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Growth rate (%/yr)</label>
                    <input type="number" name="growth_rate" id="growth_rate" min="0" max="100" value="{{ old('growth_rate', $plan->growth_rate) }}" required
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                    @error('growth_rate')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="daily_profit" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Daily profit (₹)</label>
                    <input type="number" name="daily_profit" id="daily_profit" min="0" step="0.01" value="{{ old('daily_profit', $plan->daily_profit) }}" required
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                    @error('daily_profit')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="total_return" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Total return (₹)</label>
                    <input type="number" name="total_return" id="total_return" min="0" step="0.01" value="{{ old('total_return', $plan->total_return) }}" required
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                    @error('total_return')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label for="min_investment_amount" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Min investment (₹, optional)</label>
                    <input type="number" name="min_investment_amount" id="min_investment_amount" min="1" step="0.01" placeholder="Leave both blank for a fixed amount" value="{{ old('min_investment_amount', $plan->min_investment_amount) }}"
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                    @error('min_investment_amount')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="max_investment_amount" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Max investment (₹, optional)</label>
                    <input type="number" name="max_investment_amount" id="max_investment_amount" min="1" step="0.01" placeholder="Leave both blank for a fixed amount" value="{{ old('max_investment_amount', $plan->max_investment_amount) }}"
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                    @error('max_investment_amount')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div id="range-preview" class="sm:col-span-2 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] px-3.5 py-3" hidden>
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
                </div>

                <p class="text-[11px] text-[#94A3B8] sm:col-span-2 -mt-1.5">Set both (max &gt; min) to show a real drag-slider on Plan Details letting the user invest any amount in this range - the return is then computed live from each duration's growth rate above rather than the fixed Investment amount. Requires at least one duration option below.</p>

                <label class="sm:col-span-2 flex items-center gap-2.5 h-11 px-3.5 rounded-lg border border-[#CBD5E1] has-[:checked]:border-brand has-[:checked]:bg-brand/5 cursor-pointer transition-colors w-fit">
                    <input type="checkbox" name="allow_topups" value="1" class="accent-brand" {{ old('allow_topups', $plan->allow_topups) ? 'checked' : '' }}>
                    <span class="text-[13.5px] font-semibold text-[#0F172A]">Allow top-ups (SIP-style pot)</span>
                </label>
                <p class="text-[11px] text-[#94A3B8] sm:col-span-2 -mt-1.5">Only applies with a real Min/Max range above. A user's first contribution opens one ongoing pot; every later contribution adds to that SAME pot (one shared maturity date) up to Max investment, instead of each investment being a separate independent purchase.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                <div>
                    <label for="min_goal" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Min goal (₹)</label>
                    <input type="number" name="min_goal" id="min_goal" min="0" step="0.01" value="{{ old('min_goal', $plan->min_goal) }}" required
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                    @error('min_goal')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
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

            <label class="flex items-center gap-2.5 h-11 px-3.5 rounded-lg border border-[#CBD5E1] has-[:checked]:border-brand has-[:checked]:bg-brand/5 cursor-pointer transition-colors w-fit mt-1">
                <input type="checkbox" name="is_active" value="1" class="accent-brand" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}>
                <span class="text-[13.5px] font-semibold text-[#0F172A]">Active (visible to users)</span>
            </label>

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
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
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
                    <div>
                        <label for="max_slots" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Max slots</label>
                        <input type="number" name="max_slots" id="max_slots" min="0" placeholder="Unlimited" value="{{ old('max_slots', $plan->max_slots) }}"
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                        @error('max_slots')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
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
                    <label class="flex items-center gap-2.5 h-11 px-3.5 rounded-lg border border-[#CBD5E1] has-[:checked]:border-brand has-[:checked]:bg-brand/5 cursor-pointer transition-colors w-fit">
                        <input type="checkbox" name="early_close_allowed" value="1" class="accent-brand" {{ old('early_close_allowed', $plan->early_close_allowed) ? 'checked' : '' }}>
                        <span class="text-[13.5px] font-semibold text-[#0F172A]">Allow early close</span>
                    </label>
                </div>
            </div>

            {{-- ================= Multiple durations (max 4) ================= --}}
            <div class="pt-4 mt-1 border-t border-[#E5E9EB]">
                <h2 class="font-poppins font-bold text-[14px] text-[#0F172A] mb-1">Duration options (max 4)</h2>
                <p class="text-[12px] text-[#64748B] mb-3">Leave a row's label blank to skip it. When set, users pick one of these on Plan Details instead of the single duration/return above. Mark one row as the default.</p>

                @php
                    $existingDurations = old('durations') ? collect() : ($plan->durations ?? collect())->values();
                    $defaultDurationIndex = old('duration_default', (string) $existingDurations->search(fn ($d) => $d->is_default));
                    if ($defaultDurationIndex === '' || $defaultDurationIndex === false) {
                        $defaultDurationIndex = $existingDurations->isEmpty() ? '0' : (string) $existingDurations->keys()->first();
                    }
                @endphp
                <div class="flex flex-col gap-3">
                    @for ($i = 0; $i < 4; $i++)
                        @php $d = $existingDurations[$i] ?? null; @endphp
                        <div class="grid grid-cols-2 sm:grid-cols-6 gap-2.5 items-end p-3 rounded-lg border border-[#E5E9EB]">
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
                                <label class="block text-[10.5px] font-semibold text-[#64748B] mb-1">Daily ₹</label>
                                <input type="number" name="durations[{{ $i }}][daily_profit]" min="0" step="0.01" value="{{ old("durations.$i.daily_profit", $d?->daily_profit) }}"
                                    class="w-full h-9 rounded-lg border border-[#CBD5E1] px-2.5 text-[13px] text-[#0F172A] outline-none focus:border-brand focus:ring-2 focus:ring-brand/15">
                            </div>
                            <div>
                                <label class="block text-[10.5px] font-semibold text-[#64748B] mb-1">Total ₹</label>
                                <input type="number" name="durations[{{ $i }}][total_return]" min="0" step="0.01" value="{{ old("durations.$i.total_return", $d?->total_return) }}"
                                    class="w-full h-9 rounded-lg border border-[#CBD5E1] px-2.5 text-[13px] text-[#0F172A] outline-none focus:border-brand focus:ring-2 focus:ring-brand/15">
                            </div>
                        </div>
                    @endfor
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
    var preview = document.getElementById('range-preview');
    var previewMin = document.getElementById('range-preview-min');
    var previewMax = document.getElementById('range-preview-max');

    function formatRupees(n) {
        return '₹' + Math.round(n).toLocaleString('en-IN');
    }

    function updatePreview() {
        var min = parseFloat(minInput.value);
        var max = parseFloat(maxInput.value);
        if (!isNaN(min) && !isNaN(max) && max > min) {
            previewMin.textContent = formatRupees(min);
            previewMax.textContent = formatRupees(max);
            preview.hidden = false;
        } else {
            preview.hidden = true;
        }
    }

    minInput.addEventListener('input', updatePreview);
    maxInput.addEventListener('input', updatePreview);
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
</script>

@endsection
