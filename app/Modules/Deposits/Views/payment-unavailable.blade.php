@extends('layouts.simple')

@section('title', $title)

@section('backRoute', route('home'))

@section('content')

    <div class="premium-card p-8 flex flex-col items-center text-center gap-3 mt-6">
        <div class="w-14 h-14 rounded-full bg-[#0A5C66]/10 flex items-center justify-center">
            <i class="fa-solid fa-clock text-[22px] text-[#0A5C66]"></i>
        </div>
        <h1 class="text-[18px] font-black text-[#0A5C66] font-poppins tracking-tight">{{ $title }}</h1>
        <p class="text-[13.5px] text-slate-500 font-medium">{{ $message }}</p>
        <a href="{{ route('home') }}" class="mt-3 h-11 px-6 rounded-[14px] bg-[#0A5C66] text-white font-bold text-[14px] hover:bg-[#0E7481] active:scale-[0.98] transition-all flex items-center justify-center">
            Back to Home
        </a>
    </div>

@endsection
