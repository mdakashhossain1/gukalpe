@props([
    'id',
    'title',
    'subtitle' => null,
    'points' => [],
    'series' => [],
    'valuePrefix' => '',
    'height' => 260,
])

{{--
    Plain-SVG line chart, no charting library - per the dataviz skill:
    form picked first (trend over time -> line), color assigned by job
    (categorical, fixed slot order from references/palette.md, validated via
    scripts/validate_palette.js), 2px lines / >=8px end-markers / hairline
    gridlines per marks-and-anatomy.md, and a crosshair+tooltip hover layer
    since an HTML chart is interactive by default, not an upgrade. A
    <table> twin ships in the same markup so every value is reachable
    without hovering. The component owns its own behavior (script at the
    bottom of this file, one IIFE per instance keyed by $id) - no separate
    bundled JS file to keep in sync with the markup.
--}}
@php
    $width = 760;
    $padL = 44; $padR = 16; $padT = 16; $padB = 30;
    $plotW = $width - $padL - $padR;
    $plotH = $height - $padT - $padB;
    $n = count($points);

    $maxRaw = 0;
    foreach ($points as $p) {
        foreach ($series as $s) {
            $maxRaw = max($maxRaw, (float) ($p['values'][$s['key']] ?? 0));
        }
    }
    // Round the axis ceiling to a clean step (1/2/5 * 10^k) so gridline
    // labels are round numbers, not the raw max.
    if ($maxRaw <= 0) {
        $niceMax = 4;
    } else {
        $magnitude = 10 ** floor(log10($maxRaw));
        $steps = [1, 2, 2.5, 5, 10];
        $niceMax = $magnitude * 10;
        foreach ($steps as $step) {
            if ($maxRaw <= $step * $magnitude) {
                $niceMax = $step * $magnitude;
                break;
            }
        }
    }

    $xFor = fn (int $i) => $n > 1 ? $padL + ($plotW * $i / ($n - 1)) : $padL + $plotW / 2;
    $yFor = fn (float $v) => $padT + $plotH - ($niceMax > 0 ? ($v / $niceMax) * $plotH : 0);

    $seriesPaths = [];
    foreach ($series as $s) {
        $coords = [];
        foreach ($points as $i => $p) {
            $coords[] = [$xFor($i), $yFor((float) ($p['values'][$s['key']] ?? 0))];
        }
        $seriesPaths[$s['key']] = $coords;
    }

    $labelEvery = max(1, (int) ceil($n / 6));
    $chartData = [
        'padL' => $padL, 'padT' => $padT, 'plotW' => $plotW, 'plotH' => $plotH,
        'niceMax' => $niceMax, 'n' => $n, 'valuePrefix' => $valuePrefix,
        'points' => array_map(fn ($p) => ['label' => $p['label']], $points),
        'series' => $series,
        'values' => array_map(fn ($p) => $p['values'], $points),
    ];
@endphp

{{-- min-w-0: this card is a grid item (Overview's charts grid). Grid/flex
     items default to min-width:auto, which refuses to shrink a track below
     its content's intrinsic size - since the SVG below is min-w-[600px],
     without this the whole grid track (and therefore main, body, the page)
     got forced to at least 600px wide even in the single-column mobile
     layout, causing page-wide horizontal scroll instead of the chart's own
     internal overflow-x-auto containing it. --}}
<div class="bg-white rounded-2xl border border-[#E5E9EB] p-6 min-w-0">
    <div class="flex items-start justify-between gap-3 mb-1">
        <div>
            <h2 class="text-[14px] font-bold text-[#0F172A]">{{ $title }}</h2>
            @if ($subtitle)
                <p class="text-[12px] text-[#94A3B8] mt-0.5">{{ $subtitle }}</p>
            @endif
        </div>
        <button type="button" data-chart-table-toggle="{{ $id }}" class="text-[11.5px] font-semibold text-[#64748B] hover:text-[#0F172A] transition-colors shrink-0">
            Table view
        </button>
    </div>

    @if (count($series) >= 2)
        <div class="flex items-center gap-4 mb-3 mt-3">
            @foreach ($series as $s)
                <span class="flex items-center gap-1.5 text-[11.5px] font-semibold text-[#334155]">
                    <span class="inline-block w-3 h-[2px] rounded-full" style="background-color: {{ $s['color'] }}"></span>
                    {{ $s['label'] }}
                </span>
            @endforeach
        </div>
    @endif

    {{-- overflow-x-auto + min-w on the SVG: shrinking a 14-point chart to
         fit a narrow phone by width alone would scale the 10px axis/label
         text down to ~4-5px, unreadable. Below ~600px this scrolls
         horizontally at a legible size instead of squeezing text past
         reading size - the same "don't just scale pixels" treatment as a
         data table on mobile. --}}
    <div class="relative mt-2 overflow-x-auto" data-chart-root="{{ $id }}">
        <svg id="{{ $id }}" viewBox="0 0 {{ $width }} {{ $height }}" class="w-full h-auto min-w-[600px]" role="img" aria-label="{{ $title }}">
            {{-- gridlines --}}
            @for ($g = 0; $g <= 3; $g++)
                @php $gy = $padT + $plotH * $g / 3; $gv = $niceMax * (3 - $g) / 3; @endphp
                <line x1="{{ $padL }}" y1="{{ $gy }}" x2="{{ $width - $padR }}" y2="{{ $gy }}" stroke="#e1e0d9" stroke-width="1" />
                <text x="{{ $padL - 8 }}" y="{{ $gy + 3 }}" text-anchor="end" font-size="10" fill="#898781">{{ $valuePrefix }}{{ number_format($gv) }}</text>
            @endfor

            {{-- baseline --}}
            <line x1="{{ $padL }}" y1="{{ $padT + $plotH }}" x2="{{ $width - $padR }}" y2="{{ $padT + $plotH }}" stroke="#c3c2b7" stroke-width="1" />

            {{-- x-axis labels --}}
            @foreach ($points as $i => $p)
                @if ($i % $labelEvery === 0 || $i === $n - 1)
                    <text x="{{ $xFor($i) }}" y="{{ $height - 8 }}" text-anchor="middle" font-size="10" fill="#898781">{{ $p['label'] }}</text>
                @endif
            @endforeach

            {{-- series lines --}}
            @foreach ($series as $s)
                @php $coords = $seriesPaths[$s['key']]; $path = 'M '.implode(' L ', array_map(fn ($c) => $c[0].','.$c[1], $coords)); @endphp
                <path d="{{ $path }}" fill="none" stroke="{{ $s['color'] }}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
                @php $last = end($coords); @endphp
                <circle cx="{{ $last[0] }}" cy="{{ $last[1] }}" r="4" fill="{{ $s['color'] }}" stroke="#fff" stroke-width="2" />
            @endforeach

            {{-- crosshair (hidden until hover, driven by admin.js) --}}
            <line data-chart-crosshair="{{ $id }}" x1="0" y1="{{ $padT }}" x2="0" y2="{{ $padT + $plotH }}" stroke="#c3c2b7" stroke-width="1" class="hidden" />

            {{-- hit layer --}}
            <rect data-chart-hitlayer="{{ $id }}" x="{{ $padL }}" y="{{ $padT }}" width="{{ $plotW }}" height="{{ $plotH }}" fill="transparent" class="cursor-crosshair" />
        </svg>

        <div data-chart-tooltip="{{ $id }}" class="hidden absolute z-10 pointer-events-none bg-[#0F172A] text-white rounded-lg px-3 py-2 text-[11.5px] shadow-lg min-w-[130px]"></div>
    </div>

    {{-- Table twin - every value in the chart is reachable here without hovering. --}}
    <div data-chart-table="{{ $id }}" class="hidden mt-4 overflow-x-auto">
        <table class="w-full text-[12px] border-collapse">
            <thead>
                <tr class="border-b border-[#E5E9EB]">
                    <th class="text-left font-semibold text-[#64748B] py-2 pr-3">Date</th>
                    @foreach ($series as $s)
                        <th class="text-right font-semibold text-[#64748B] py-2 pl-3">{{ $s['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($points as $p)
                    <tr class="border-b border-[#F1F5F9]">
                        <td class="py-1.5 pr-3 text-[#334155]">{{ $p['label'] }}</td>
                        @foreach ($series as $s)
                            <td class="py-1.5 pl-3 text-right font-mono text-[#0F172A]">{{ $valuePrefix }}{{ number_format((float) ($p['values'][$s['key']] ?? 0), $valuePrefix ? 2 : 0) }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    const id = @json($id);
    const data = @json($chartData);

    const svg = document.getElementById(id);
    const hitLayer = svg?.querySelector(`[data-chart-hitlayer="${id}"]`);
    const crosshair = svg?.querySelector(`[data-chart-crosshair="${id}"]`);
    const tooltip = document.querySelector(`[data-chart-tooltip="${id}"]`);
    const tableToggle = document.querySelector(`[data-chart-table-toggle="${id}"]`);
    const chartRoot = document.querySelector(`[data-chart-root="${id}"]`);
    const table = document.querySelector(`[data-chart-table="${id}"]`);
    if (!svg || !hitLayer || !crosshair || !tooltip) return;

    const { padL, plotW, n, valuePrefix, points, series, values } = data;

    function indexForClientX(clientX) {
        const rect = svg.getBoundingClientRect();
        const scale = rect.width / 760;
        const svgX = (clientX - rect.left) / scale;
        const ratio = n > 1 ? (svgX - padL) / plotW : 0;
        return Math.min(n - 1, Math.max(0, Math.round(ratio * (n - 1))));
    }

    function showAt(index) {
        const x = n > 1 ? padL + (plotW * index / (n - 1)) : padL + plotW / 2;
        crosshair.setAttribute('x1', x);
        crosshair.setAttribute('x2', x);
        crosshair.classList.remove('hidden');

        const rowValues = values[index] || {};
        const rows = series.map((s) => {
            const v = rowValues[s.key] ?? 0;
            const formatted = valuePrefix ? `${valuePrefix}${Number(v).toLocaleString('en-IN', { maximumFractionDigits: 2 })}` : Number(v).toLocaleString('en-IN');
            return { label: s.label, color: s.color, formatted };
        });

        tooltip.innerHTML = '';
        const dateEl = document.createElement('div');
        dateEl.className = 'font-semibold text-white/70 mb-1.5';
        dateEl.textContent = points[index]?.label ?? '';
        tooltip.appendChild(dateEl);

        rows.forEach((row) => {
            const rowEl = document.createElement('div');
            rowEl.className = 'flex items-center gap-1.5 justify-between';

            const keyEl = document.createElement('span');
            keyEl.className = 'flex items-center gap-1.5 text-white/80';
            const swatch = document.createElement('span');
            swatch.className = 'inline-block w-2.5 h-[2px] rounded-full';
            swatch.style.backgroundColor = row.color;
            const labelEl = document.createElement('span');
            labelEl.textContent = row.label;
            keyEl.appendChild(swatch);
            keyEl.appendChild(labelEl);

            const valEl = document.createElement('span');
            valEl.className = 'font-bold text-white';
            valEl.textContent = row.formatted;

            rowEl.appendChild(keyEl);
            rowEl.appendChild(valEl);
            tooltip.appendChild(rowEl);
        });

        tooltip.classList.remove('hidden');
        const rect = svg.getBoundingClientRect();
        const scale = rect.width / 760;
        const px = padL + (n > 1 ? (plotW * index / (n - 1)) : plotW / 2);
        let left = px * scale + 12;
        if (left + 140 > rect.width) left = px * scale - 152;
        tooltip.style.left = `${left}px`;
        tooltip.style.top = '4px';
    }

    function hide() {
        crosshair.classList.add('hidden');
        tooltip.classList.add('hidden');
    }

    hitLayer.addEventListener('pointermove', (e) => showAt(indexForClientX(e.clientX)));
    hitLayer.addEventListener('pointerleave', hide);
    hitLayer.addEventListener('pointerdown', (e) => showAt(indexForClientX(e.clientX)));

    if (tableToggle && chartRoot && table) {
        tableToggle.addEventListener('click', () => {
            const showingTable = !table.classList.contains('hidden');
            table.classList.toggle('hidden', showingTable);
            chartRoot.classList.toggle('hidden', !showingTable);
            tableToggle.textContent = showingTable ? 'Table view' : 'Chart view';
        });
    }
})();
</script>
