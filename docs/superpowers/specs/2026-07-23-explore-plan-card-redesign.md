# Explore Plan Card Redesign Specification

## Overview
Redesign the plan card layout on the Explore Goals page (`app/Modules/Explore/Views/explore.blade.php`) to exactly match the provided screenshot design.

## UI/UX Requirements
1. **Badges**:
   - **Marketing Badge (Top Left)**: Soft pastel pill badge with icon and text.
     - "MOST POPULAR": `#FFF4E5` bg, `#D97706` text/icon (Orange).
     - "HIGH RETURNS": `#ECFDF5` bg, `#059669` text/icon (Green).
     - "STEADY GROWTH": `#F3E8FF` bg, `#7C3AED` text/icon (Purple).
   - **Tier Badge (Top Right)**: Soft pastel pill badge.
     - "STARTER": `#DCFCE7` bg, `#16A34A` text.
     - "BEGINNER": `#EFF6FF` bg, `#2563EB` text.
     - "PREMIUM": `#FEF3C7` bg, `#D97706` text.

2. **Card Grid & Layout**:
   - **Container**: White background card with smooth rounded corners (`rounded-2xl` / `rounded-3xl`), light border (`border border-slate-100`), soft shadow (`shadow-sm hover:shadow-md transition-shadow`).
   - **Left Icon Pod**: Large circular soft background pod (`w-24 h-24 sm:w-28 sm:h-28 rounded-full bg-slate-50 flex items-center justify-center shrink-0`).
   - **Center Main Details**:
     - **Header**: Large bold title (`text-slate-900 font-extrabold text-[18px] sm:text-[20px]`), subtitle (`text-slate-500 text-[13px] sm:text-[14px]`).
     - **3-Column Metrics Section**:
       - Divider lines: `border-r border-slate-200/80` between columns.
       - Column 1: Label "Interest Rate (Yearly)", Value in bold green text (`text-[#19B36B] font-extrabold text-[20px] sm:text-[22px]`).
       - Column 2: Label "Total Return", Value in bold green text (`text-[#19B36B] font-extrabold text-[20px] sm:text-[22px]`).
       - Column 3: Label "Duration", Value in bold dark font (`text-slate-800 font-extrabold text-[16px]`) or stylized duration selector dropdown when options exist (`3 Months ˅`, `6 Months ˅`).
     - **Trust Indicators Row**:
       - `🔒 End-to-End Encryption` (Dark teal lock icon with label).
       - `🛡️ 100% Trusted & Secure` (Green shield checkmark icon with label).
   - **Right Side Section**:
     - **Price**: Large price font (`text-[26px] sm:text-[30px] font-black text-slate-900 leading-tight`), caption `One-Time Investment` or `Flexible Amount`.
     - **CTA Button**: Solid deep teal button (`bg-[#0A5C66] text-white hover:bg-[#07474f] font-bold text-[14px] px-6 py-2.5 rounded-xl inline-flex items-center justify-center gap-2 shadow-sm transition-all`).

3. **Responsiveness**:
   - Desktop & Large Tablet (`md:` breakpoint): 3-part horizontal layout (Icon | Details | Price & CTA).
   - Mobile: Stacks cleanly into vertical sections with high contrast, keeping text, divider lines, and buttons proportional and legible.

4. **Integration**:
   - Preserves all Blade directives, dynamic plan model properties (`$plan`, `$cp`), routing (`route('plan-details', $plan)`), and translation keys.
