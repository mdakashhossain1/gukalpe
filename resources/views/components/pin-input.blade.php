@props(['name', 'length' => 4, 'autofocus' => false, 'ariaLabel' => null, 'autoSubmit' => false])

{{-- Segmented PIN entry - one box per digit instead of a single masked
     input, with auto-advance-on-type, backspace-to-previous, and paste
     support. Submits as a single concatenated value via the hidden input,
     so server-side validation (digits:{{ $length }}) is untouched. Multiple
     instances on one page (set-mpin.blade.php has two) work independently -
     the delegated script below scopes every lookup to the box's own
     [data-pin-group] ancestor. When autoSubmit is set, filling the last box
     pulses the group and submits its form automatically - used for MPIN/OTP
     entry where "type the last digit" IS the user's intent to proceed. --}}
<div class="flex items-center gap-3" data-pin-group @if ($autoSubmit) data-auto-submit @endif role="group" @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif>
    @for ($i = 0; $i < $length; $i++)
        <input type="password" inputmode="numeric" pattern="[0-9]*" maxlength="1"
            data-pin-box
            @if ($autofocus && $i === 0) autofocus @endif
            class="flex-1 min-w-0 h-[64px] rounded-[16px] border-none bg-slate-100 text-center text-[26px] font-black text-slate-800 outline-none focus:ring-2 focus:ring-[#0A5C66]/30 transition-all">
    @endfor
    <input type="hidden" name="{{ $name }}" data-pin-value>
</div>

@once
    <script>
        (function () {
            function syncGroup(group) {
                var boxes = group.querySelectorAll('[data-pin-box]');
                var hidden = group.querySelector('[data-pin-value]');
                var value = '';
                boxes.forEach(function (box) { value += box.value; });
                hidden.value = value;
            }

            function maybeAutoSubmit(group) {
                if (!group.hasAttribute('data-auto-submit')) return;
                var boxes = group.querySelectorAll('[data-pin-box]');
                var hidden = group.querySelector('[data-pin-value]');
                if (hidden.value.length !== boxes.length) return;
                var form = group.closest('form');
                if (!form) return;
                group.classList.add('pin-group-complete');
                boxes.forEach(function (b) { b.blur(); });
                setTimeout(function () {
                    if (typeof form.requestSubmit === 'function') form.requestSubmit();
                    else form.submit();
                }, 220);
            }

            document.addEventListener('input', function (e) {
                var box = e.target.closest('[data-pin-box]');
                if (!box) return;
                box.value = box.value.replace(/\D/g, '').slice(0, 1);
                var group = box.closest('[data-pin-group]');
                syncGroup(group);
                if (box.value && box.nextElementSibling && box.nextElementSibling.hasAttribute('data-pin-box')) {
                    box.nextElementSibling.focus();
                }
                maybeAutoSubmit(group);
            });

            document.addEventListener('keydown', function (e) {
                var box = e.target.closest('[data-pin-box]');
                if (!box || e.key !== 'Backspace' || box.value) return;
                if (box.previousElementSibling && box.previousElementSibling.hasAttribute('data-pin-box')) {
                    box.previousElementSibling.focus();
                }
            });

            document.addEventListener('paste', function (e) {
                var box = e.target.closest('[data-pin-box]');
                if (!box) return;
                var group = box.closest('[data-pin-group]');
                var boxes = Array.prototype.slice.call(group.querySelectorAll('[data-pin-box]'));
                var text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                if (!text) return;
                e.preventDefault();
                boxes.forEach(function (b, i) { b.value = text[i] || ''; });
                syncGroup(group);
                var next = boxes[Math.min(text.length, boxes.length - 1)];
                if (next) next.focus();
                maybeAutoSubmit(group);
            });
        })();
    </script>
@endonce
