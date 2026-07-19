@extends('layouts.admin')

@section('title', 'Push notification')

@section('content')

<div class="flex min-h-screen">

    <x-admin-sidebar active="push-notification" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="Push notification" />

        <div class="px-6 md:px-10 py-8 md:py-10">

        <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Push notification</h1>
        <p class="text-[13.5px] text-[#64748B] mb-6">Sends straight to the bell on the user's Home page ({{ number_format($totalUsers) }} registered user{{ $totalUsers === 1 ? '' : 's' }} total) - real, not a demo.</p>

        <form method="POST" action="{{ route('admin.push-notification.send') }}" class="flex flex-col gap-3.5 bg-white rounded-2xl border border-[#E5E9EB] p-6">
            @csrf

            <div>
                <label class="block text-[12.5px] font-semibold text-[#334155] mb-2">Send to</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-center gap-2.5 h-11 px-3.5 rounded-lg border border-[#CBD5E1] has-[:checked]:border-brand has-[:checked]:bg-brand/5 cursor-pointer transition-colors">
                        <input type="radio" name="target" value="all" id="target-all" class="accent-brand" {{ old('target', 'all') === 'all' ? 'checked' : '' }}>
                        <span class="text-[13.5px] font-semibold text-[#0F172A]">All users</span>
                    </label>
                    <label class="flex items-center gap-2.5 h-11 px-3.5 rounded-lg border border-[#CBD5E1] has-[:checked]:border-brand has-[:checked]:bg-brand/5 cursor-pointer transition-colors">
                        <input type="radio" name="target" value="specific" id="target-specific" class="accent-brand" {{ old('target') === 'specific' ? 'checked' : '' }}>
                        <span class="text-[13.5px] font-semibold text-[#0F172A]">Specific user</span>
                    </label>
                </div>
            </div>

            <div id="phone-field" class="hidden">
                <label for="phone" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Phone number</label>
                <input type="tel" name="phone" id="phone" inputmode="numeric" pattern="[0-9]*" maxlength="10" placeholder="10-digit registered phone number" value="{{ old('phone') }}"
                    class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                @error('phone')
                    <p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="title" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Title</label>
                <input type="text" name="title" id="title" maxlength="120" placeholder="e.g. Scheduled maintenance tonight" value="{{ old('title') }}" required
                    class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15">
                @error('title')
                    <p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="body" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Message (optional)</label>
                <textarea name="body" id="body" rows="3" maxlength="500" placeholder="Extra detail shown under the title in the bell dropdown"
                    class="w-full rounded-lg border border-[#CBD5E1] px-3 py-2.5 text-[14px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15 resize-none">{{ old('body') }}</textarea>
                @error('body')
                    <p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="h-10 rounded-lg bg-brand text-white font-semibold text-[13.5px] hover:bg-brand-light transition-colors active:scale-[0.99] sm:w-fit sm:px-6 mt-1">
                Send notification
            </button>
        </form>

        </div>
    </main>
</div>

<script>
(function () {
    const targetAll = document.getElementById('target-all');
    const targetSpecific = document.getElementById('target-specific');
    const phoneField = document.getElementById('phone-field');
    if (!targetAll || !targetSpecific || !phoneField) return;

    function sync() {
        phoneField.classList.toggle('hidden', !targetSpecific.checked);
    }

    targetAll.addEventListener('change', sync);
    targetSpecific.addEventListener('change', sync);
    sync();
})();
</script>

@endsection
