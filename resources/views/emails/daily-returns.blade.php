@php
    $formattedDate = now()->format('d M Y');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Daily Returns</title>
</head>
<body style="margin:0; padding:0; background-color:#F4F6F7; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F4F6F7; padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 2px 12px rgba(15,23,42,0.06);">

                <!-- Header -->
                <tr>
                    <td style="background-color:#0A5C66; padding:28px 32px; text-align:center;">
                        <span style="font-size:20px; font-weight:800; color:#ffffff; letter-spacing:-0.02em;">
                            Gullak<span style="color:#3FEA8A;">Pe</span>
                        </span>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:32px; text-align:center;">
                        <h1 style="margin:0 0 8px; font-size:19px; font-weight:800; color:#0F172A; letter-spacing:-0.01em;">Your Investments Grew Today</h1>
                        <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#64748B;">
                            Hi {{ $user->name }}, here's your daily growth summary for {{ $formattedDate }}.
                        </p>
                        <span style="display:inline-block; background-color:#ECFDF5; border:1px solid #A7F3D0; color:#047857; font-size:16px; font-weight:800; padding:9px 20px; border-radius:20px; margin-bottom:24px;">
                            +&#8377;{{ number_format($totalDailyReturn, 2) }} today
                        </span>

                        <!-- Per-plan breakdown -->
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; border:1px solid #E5E9EB; border-radius:12px; margin-top:8px; margin-bottom:24px; text-align:left;">
                            <tr>
                                <td style="padding:18px 20px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                        @foreach ($holdings as $i => $holding)
                                            <tr>
                                                <td style="padding-bottom:{{ $loop->last ? '0' : '14px' }}; {{ $i > 0 ? 'border-top:1px solid #E5E9EB; padding-top:14px;' : '' }}">
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td style="font-size:13.5px; font-weight:700; color:#0F172A;">{{ $holding['title'] }}</td>
                                                            <td align="right" style="font-size:14px; font-weight:800; color:#19B36B;">+&#8377;{{ number_format($holding['amount'], 2) }}</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <!-- Portfolio value -->
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#EFF6FF; border:1px solid #DBEAFE; border-radius:12px; margin-bottom:12px; text-align:left;">
                            <tr>
                                <td style="padding:14px 16px;">
                                    <p style="margin:0 0 4px; font-size:12.5px; font-weight:700; color:#1D4ED8;">Total Portfolio Value</p>
                                    <p style="margin:0; font-size:18px; font-weight:800; color:#1D4ED8;">&#8377;{{ number_format($portfolioValue, 2) }}</p>
                                </td>
                            </tr>
                        </table>

                        <a href="{{ route('portfolio') }}" style="display:inline-block; background-color:#0A5C66; color:#ffffff; font-size:13.5px; font-weight:700; padding:12px 28px; border-radius:12px; text-decoration:none; margin-top:8px;">
                            View Portfolio
                        </a>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="padding:20px 32px; background-color:#F8FAFC; border-top:1px solid #E5E9EB;">
                        <p style="margin:0; font-size:11.5px; line-height:1.6; color:#94A3B8; text-align:center;">
                            This is an automated daily summary from GullakPe. Returns shown are estimated values based on your active plans.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
