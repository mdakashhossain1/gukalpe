@extends('layouts.admin')

@section('title', $banner->exists ? 'Edit banner' : 'New banner')

@section('content')

<div class="flex flex-col md:flex-row min-h-screen">

    <x-admin-sidebar active="banners" :pending-deposit-count="$pendingDepositCount" :pending-withdrawal-count="$pendingWithdrawalCount" />

    <main class="flex-1 min-w-0 flex flex-col min-h-screen">
        <x-admin-topbar title="{{ $banner->exists ? 'Edit banner' : 'New banner' }}" />

        <div class="px-6 md:px-10 py-8 md:py-10">

            <a href="{{ route('admin.banners') }}" class="text-[13px] font-semibold text-[#64748B] hover:text-[#0F172A] mb-4 inline-flex items-center gap-1.5"><i class="fa-solid fa-arrow-left text-[11px]"></i> Back to banners</a>
            <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-6">{{ $banner->exists ? 'Edit banner' : 'New banner' }}</h1>

            <form method="POST" action="{{ $banner->exists ? route('admin.banners.update', $banner) : route('admin.banners.store') }}" enctype="multipart/form-data" class="flex flex-col gap-4 bg-white rounded-2xl border border-[#E5E9EB] p-6">
                @csrf

                <div>
                    <label for="placement" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Placement</label>
                    <select name="placement" id="placement" class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none focus:border-brand focus:ring-2 focus:ring-brand/15">
                        @foreach ($placements as $key => $label)
                            <option value="{{ $key }}" {{ old('placement', $banner->placement) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('placement')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="title" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Title <span class="text-[#94A3B8] font-normal">(admin label, optional)</span></label>
                    <input type="text" name="title" id="title" maxlength="100" value="{{ old('title', $banner->title) }}"
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none focus:border-brand focus:ring-2 focus:ring-brand/15">
                    @error('title')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="image" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Banner image {{ $banner->exists ? '(leave blank to keep current)' : '' }}</label>
                    @if ($banner->exists && $banner->image)
                        <img src="{{ $banner->imageUrl() }}" alt="" class="w-full max-w-sm rounded-lg border border-[#E5E9EB] mb-2">
                    @endif
                    <input type="file" name="image" id="image" accept="image/*" class="w-full text-[13px] text-[#334155] file:mr-3 file:h-9 file:px-3 file:rounded-lg file:border-0 file:bg-[#0F172A] file:text-white file:text-[12.5px] file:font-semibold">
                    <p class="text-[11px] text-[#94A3B8] mt-1">Recommended wide/landscape image, up to 4 MB.</p>
                    @error('image')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="redirect_link" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Redirect link <span class="text-[#94A3B8] font-normal">(optional)</span></label>
                    <input type="text" name="redirect_link" id="redirect_link" maxlength="255" placeholder="/explore or https://…" value="{{ old('redirect_link', $banner->redirect_link) }}"
                        class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none focus:border-brand focus:ring-2 focus:ring-brand/15">
                    @error('redirect_link')<p class="text-[12px] font-semibold text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="priority" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Priority</label>
                        <input type="number" name="priority" id="priority" min="0" value="{{ old('priority', $banner->priority ?? 0) }}"
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none focus:border-brand focus:ring-2 focus:ring-brand/15">
                    </div>
                    <div>
                        <label for="start_date" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">Start date</label>
                        <input type="date" name="start_date" id="start_date" value="{{ old('start_date', optional($banner->start_date)->format('Y-m-d')) }}"
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none focus:border-brand focus:ring-2 focus:ring-brand/15">
                    </div>
                    <div>
                        <label for="end_date" class="block text-[12.5px] font-semibold text-[#334155] mb-1.5">End date</label>
                        <input type="date" name="end_date" id="end_date" value="{{ old('end_date', optional($banner->end_date)->format('Y-m-d')) }}"
                            class="w-full h-10 rounded-lg border border-[#CBD5E1] px-3 text-[14px] text-[#0F172A] outline-none focus:border-brand focus:ring-2 focus:ring-brand/15">
                    </div>
                </div>
                @error('end_date')<p class="text-[12px] font-semibold text-red-500 -mt-2">{{ $message }}</p>@enderror

                <label for="is_active" class="flex items-center gap-3 py-2 px-3 rounded-lg border border-[#E5E9EB] cursor-pointer w-fit">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $banner->is_active ?? true) ? 'checked' : '' }} class="w-5 h-5 rounded accent-brand">
                    <span class="text-[13px] font-semibold text-[#0F172A]">Active (visible to users)</span>
                </label>

                <button type="submit" class="h-10 rounded-lg bg-[#0F172A] text-white font-semibold text-[13.5px] hover:bg-[#1E293B] transition-colors active:scale-[0.99] mt-1 sm:w-fit sm:px-6">
                    {{ $banner->exists ? 'Save banner' : 'Create banner' }}
                </button>
            </form>

        </div>
    </main>
</div>

@endsection
