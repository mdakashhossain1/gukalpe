@props(['label', 'value', 'icon' => 'fa-chart-simple', 'accent' => '#0A5C66'])

{{-- Stat tile contract per dataviz skill: label (sentence case, no trailing
     colon) + value (semibold, auto-compact). No delta/sparkline yet - this
     dataset is too young for a meaningful "vs last period" comparison; add
     one once there's enough history for it to mean something rather than
     noise. --}}
<div class="bg-white rounded-2xl border border-[#E5E9EB] p-5 flex items-center gap-3.5">
    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background-color: {{ $accent }}1a">
        <i class="fa-solid {{ $icon }} text-[15px]" style="color: {{ $accent }}"></i>
    </div>
    <div class="min-w-0">
        <p class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wide truncate">{{ $label }}</p>
        <p class="text-[19px] font-bold text-[#0F172A] font-poppins tracking-tight mt-0.5">{{ $value }}</p>
    </div>
</div>
