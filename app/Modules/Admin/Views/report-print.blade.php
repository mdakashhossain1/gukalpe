<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GullakPe {{ $label }} Report</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: #0F172A; padding: 40px; max-width: 800px; margin: 0 auto; }
        .head { border-bottom: 3px solid #0A5C66; padding-bottom: 16px; margin-bottom: 24px; }
        .head h1 { font-size: 22px; }
        .head p { color: #64748B; font-size: 13px; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { text-align: left; padding: 11px 14px; border-bottom: 1px solid #E5E9EB; font-size: 13.5px; }
        th { background: #F8FAFC; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: #64748B; }
        td.num, th.num { text-align: right; }
        td.metric { font-weight: 600; }
        td.amt { font-weight: 700; }
        .foot { margin-top: 28px; color: #94A3B8; font-size: 11px; }
        .actions { margin-bottom: 20px; }
        .actions button { background: #0F172A; color: #fff; border: 0; border-radius: 8px; padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer; }
        @media print { .actions { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="actions"><button onclick="window.print()">Print / Save as PDF</button></div>

    <div class="head">
        <h1>GullakPe — {{ $label }} Report</h1>
        <p>{{ $from->format('d M Y, H:i') }} — {{ $to->format('d M Y, H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr><th>Metric</th><th class="num">Count</th><th class="num">Amount (₹)</th></tr>
        </thead>
        <tbody>
            @foreach ($metrics as $m)
                <tr>
                    <td class="metric">{{ $m['label'] }}</td>
                    <td class="num">{{ $m['count'] !== null ? number_format($m['count']) : '—' }}</td>
                    <td class="num amt">{{ $m['amount'] !== null ? number_format($m['amount'], 2) : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="foot">Generated {{ now()->format('d M Y, H:i') }} · GullakPe Admin</p>

    <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
</body>
</html>
