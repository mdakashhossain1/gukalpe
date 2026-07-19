@extends('layouts.app')

@section('content')
    <div class="flex min-h-[100dvh] flex-col flex-1 bg-[#F8FAFC] pb-28 pt-safe overflow-y-auto custom-scrollbar w-full">
        <!-- Sticky Top App Bar -->
        <div class="sticky top-0 z-[100] w-full h-[64px] bg-white border-b border-black/[0.05] flex items-center px-4 justify-between shrink-0">
            <a href="{{ route('home') }}" class="w-10 h-10 rounded-full bg-white border border-[#0B5C63]/20 flex items-center justify-center shadow-[0_2px_8px_rgba(11,92,99,0.08)] active:scale-95 transition-all">
                <i class="fa-solid fa-arrow-left text-[#0B5C63] text-[16px]"></i>
            </a>
            <h1 class="text-[18px] font-black text-slate-800 font-poppins absolute left-1/2 transform -translate-x-1/2">Notifications</h1>
            <div class="w-10"></div>
        </div>

        <div class="px-5 pt-5 pb-6">
            @if ($notifications->isNotEmpty())
                <div class="flex items-center justify-end mb-4">
                    <form method="POST" action="{{ route('notifications.read') }}">
                        @csrf
                        <button type="submit" class="text-[12.5px] font-bold text-[#0A5C66] hover:underline">Mark all as read</button>
                    </form>
                </div>

                <div class="space-y-3">
                    @foreach ($notifications as $n)
                        <div class="bg-white rounded-2xl border {{ $n['unread'] ? 'border-[#0A5C66]/20' : 'border-slate-100' }} shadow-sm p-4 flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $n['unread'] ? 'bg-[#0A5C66]/10 text-[#0A5C66]' : 'bg-slate-50 text-slate-400' }}">
                                <i class="fa-solid {{ $n['icon'] }}"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <h4 class="text-[13.5px] font-black text-[#1a153a] font-poppins leading-tight">{{ $n['title'] }}</h4>
                                    @if ($n['unread'])
                                        <span class="w-2 h-2 rounded-full bg-[#3CCF91] shrink-0 mt-1"></span>
                                    @endif
                                </div>
                                @if ($n['body'])
                                    <p class="text-[12px] text-slate-500 font-medium mt-1 leading-relaxed">{{ $n['body'] }}</p>
                                @endif
                                <p class="text-[10.5px] text-slate-400 font-semibold mt-1.5">{{ $n['createdAt'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center text-center py-20 px-6">
                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mb-5 border border-slate-100 shadow-sm">
                        <i class="fa-regular fa-bell text-[28px] text-slate-300"></i>
                    </div>
                    <h3 class="text-[16px] font-black text-[#1a153a] mb-1.5 font-poppins">No notifications yet</h3>
                    <p class="text-[13px] text-slate-500 font-medium leading-relaxed max-w-[240px]">We'll let you know here when something needs your attention.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
