@props(['text'])

{{-- Small "i" info icon with a hover tooltip explaining what the adjacent
     field does - added 2026-08-09 after repeated admin confusion about what
     each Plan form field is for. Uses the native title attribute rather
     than a custom JS tooltip so it needs zero extra markup/CSS, and because
     title is already one of x-i18n-engine's TRANSLATABLE_ATTRS - toggling
     the admin panel to Hindi (admin-topbar's new EN/हि button) translates
     these automatically as long as $text has a matching entry in
     public/lang/hi.json, same convention as every other translated string
     in this app (DESIGN.md's Internationalization section). --}}
<i class="bi bi-info-circle text-[11px] text-slate-400 hover:text-[#0A5C66] transition-colors cursor-help align-middle ml-1" title="{{ $text }}"></i>
