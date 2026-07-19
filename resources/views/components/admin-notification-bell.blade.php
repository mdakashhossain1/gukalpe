@props(['id' => 'admin-notif-bell'])

{{-- Polled via inline AJAX (script at the bottom of this file) against
     GET admin.notifications.poll / POST admin.notifications.read - see
     App\Models\AdminNotification. Rendered twice - once inside admin-topbar
     (desktop, hidden on mobile) and once inside admin-sidebar's mobile
     header (hidden on desktop) - so the bell is reachable at every
     breakpoint, not just desktop. Ids are scoped by the $id prop (same
     pattern as admin-line-chart) so the two instances never collide in the
     DOM despite only one being visible at a time. --}}
<div id="{{ $id }}" class="relative" data-poll-url="{{ route('admin.notifications.poll') }}" data-read-url="{{ route('admin.notifications.read') }}">
    <button type="button" id="{{ $id }}-toggle" class="relative w-11 h-11 rounded-full bg-white border border-[#E5E9EB] shadow-[0_1px_2px_rgba(15,23,42,0.04),0_8px_24px_-8px_rgba(15,23,42,0.12)] flex items-center justify-center hover:bg-[#F8FAFC] transition-colors">
        <i class="fa-solid fa-bell text-[15px] text-[#334155]"></i>
        <span id="{{ $id }}-badge" class="hidden absolute -top-1 -right-1 bg-[#DC2626] text-white text-[10px] font-bold h-[18px] min-w-[18px] px-1 rounded-full items-center justify-center">0</span>
    </button>

    {{-- w-[min(320px,calc(100vw-2rem))]: a fixed 320px panel anchored
         right-0 would spill off the left edge of the viewport on phones
         narrower than ~350px (320px panel + 24px right inset > 320px
         screen). Capping to the viewport width minus a comfortable margin
         keeps it fully on-screen at any width instead of causing
         horizontal scroll/clipping. --}}
    <div id="{{ $id }}-panel" class="hidden absolute right-0 mt-2 w-[min(320px,calc(100vw-2rem))] max-h-[400px] overflow-y-auto bg-white rounded-2xl border border-[#E5E9EB] shadow-[0_8px_24px_-8px_rgba(15,23,42,0.2)]">
        <div class="px-4 py-3 border-b border-[#F1F5F9]">
            <span class="text-[13px] font-bold text-[#0F172A]">Notifications</span>
        </div>
        <div id="{{ $id }}-list" class="flex flex-col divide-y divide-[#F1F5F9]">
            <p class="px-4 py-6 text-[12.5px] text-[#94A3B8] italic text-center">Loading…</p>
        </div>
    </div>
</div>

<script>
(function () {
    const id = @json($id);
    const root = document.getElementById(id);
    if (!root) return;

    const toggle = document.getElementById(id + '-toggle');
    const panel = document.getElementById(id + '-panel');
    const badge = document.getElementById(id + '-badge');
    const list = document.getElementById(id + '-list');
    const pollUrl = root.dataset.pollUrl;
    const readUrl = root.dataset.readUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    let lastRenderKey = null;

    const icons = {
        user_registered: 'fa-user-plus text-[#0A5C66]',
        withdrawal_request: 'fa-money-bill-transfer text-amber-600',
    };

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    function render(items) {
        if (!items.length) {
            list.innerHTML = '<p class="px-4 py-6 text-[12.5px] text-[#94A3B8] italic text-center">No notifications yet.</p>';
            return;
        }

        list.innerHTML = items.map((n) => `
            <div class="flex items-start gap-2.5 px-4 py-3 ${n.unread ? 'bg-[#0A5C66]/[0.03]' : ''}">
                <div class="w-7 h-7 rounded-full bg-[#F1F5F9] flex items-center justify-center shrink-0 mt-0.5">
                    <i class="fa-solid ${icons[n.type] || 'fa-circle-info text-[#64748B]'} text-[11px]"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[12.5px] font-bold text-[#0F172A] leading-snug">${escapeHtml(n.title)}</p>
                    ${n.body ? `<p class="text-[11.5px] text-[#64748B] leading-snug mt-0.5">${escapeHtml(n.body)}</p>` : ''}
                    <p class="text-[10.5px] text-[#94A3B8] mt-1">${escapeHtml(n.created_at)}</p>
                </div>
            </div>
        `).join('');
    }

    async function poll() {
        try {
            const res = await fetch(pollUrl, { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();

            // Skip the DOM entirely when nothing changed since the last
            // tick - reassigning list.innerHTML and toggling badge classes
            // on every poll (even when identical) visibly flickered the
            // dropdown if it happened to be open when a poll landed.
            const items = data.items || [];
            const renderKey = JSON.stringify([data.unread_count, items]);
            if (renderKey === lastRenderKey) return;
            lastRenderKey = renderKey;

            if (data.unread_count > 0) {
                badge.textContent = data.unread_count > 99 ? '99+' : String(data.unread_count);
                badge.classList.remove('hidden');
                badge.classList.add('flex');
            } else {
                badge.classList.add('hidden');
                badge.classList.remove('flex');
            }

            render(items);
        } catch (err) {
            // Silent - a failed poll just means the badge stays stale until the next tick.
        }
    }

    async function markRead() {
        try {
            await fetch(readUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            });
            badge.classList.add('hidden');
            badge.classList.remove('flex');
        } catch (err) {
            // Non-fatal - next poll just shows the same unread state.
        }
    }

    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const isHidden = panel.classList.contains('hidden');
        panel.classList.toggle('hidden', !isHidden);
        if (isHidden) markRead();
    });

    document.addEventListener('click', (e) => {
        if (!root.contains(e.target)) panel.classList.add('hidden');
    });

    poll();
    setInterval(poll, 12000);
})();
</script>
