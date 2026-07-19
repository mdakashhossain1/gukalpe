@extends('layouts.admin')

@section('title', 'Sign in')

@section('content')
<div class="min-h-screen flex items-center justify-center px-6">
    <div class="w-full max-w-[380px]">

        <div class="flex items-center gap-2.5 mb-8 justify-center">
            <div class="w-9 h-9 bg-brand rounded-xl flex items-center justify-center shadow-sm shrink-0">
                <i class="fa-solid fa-piggy-bank text-white text-[15px]"></i>
            </div>
            <span class="font-poppins font-extrabold text-[17px] tracking-tight text-[#0F172A]">GullakPe <span class="font-semibold text-[#64748B]">Ops</span></span>
        </div>

        <div class="bg-white rounded-2xl border border-[#E5E9EB] shadow-[0_1px_2px_rgba(15,23,42,0.04),0_8px_24px_-8px_rgba(15,23,42,0.08)] p-8">
            <h1 class="font-poppins font-bold text-[20px] text-[#0F172A] mb-1">Sign in</h1>
            <p class="text-[13.5px] text-[#64748B] mb-6">Restricted console. Authorized operators only.</p>


            <form method="POST" action="{{ route('admin.authenticate') }}" class="flex flex-col gap-4">
                @csrf

                <div>
                    <label for="password" class="block text-[13px] font-semibold text-[#334155] mb-1.5">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        autofocus
                        required
                        class="w-full h-11 rounded-lg border border-[#CBD5E1] px-3.5 text-[14.5px] text-[#0F172A] outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/15"
                    >
                </div>

                <button
                    type="submit"
                    class="h-11 rounded-lg bg-brand text-white font-semibold text-[14.5px] transition-colors hover:bg-brand-light active:scale-[0.99] transition-transform"
                >
                    Sign in
                </button>
            </form>
        </div>

        <p class="text-center text-[12px] text-[#94A3B8] mt-6">This page is intentionally unlisted. Do not share the URL.</p>
    </div>
</div>
@endsection
