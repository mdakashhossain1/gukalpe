@php
    $formattedAmount = number_format((float) $deposit->amount, 2);
    $formattedDate = $deposit->submitted_at->format('d M Y, h:i A');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Received</title>
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
                        <h1 style="margin:0 0 8px; font-size:19px; font-weight:800; color:#0F172A; letter-spacing:-0.01em;">Payment Received!</h1>
                        <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#64748B;">
                            Thank you! We've received your payment details and they're being verified.
                        </p>
                        <span style="display:inline-block; background-color:#ECFDF5; border:1px solid #A7F3D0; color:#047857; font-size:11.5px; font-weight:700; padding:7px 16px; border-radius:20px; margin-bottom:24px;">
                            &#10003; Your payment is under verification
                        </span>

                        <!-- Details card -->
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; border:1px solid #E5E9EB; border-radius:12px; margin-top:8px; margin-bottom:24px; text-align:left;">
                            <tr>
                                <td style="padding:18px 20px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="padding-bottom:14px;">
                                                <span style="display:block; font-size:10.5px; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:2px;">Amount Paid</span>
                                                <span style="font-size:18px; font-weight:800; color:#0A5C66;">&#8377;{{ $formattedAmount }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding-bottom:14px; border-top:1px solid #E5E9EB; padding-top:14px;">
                                                <span style="display:block; font-size:10.5px; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:2px;">Payment Method</span>
                                                <span style="font-size:14px; font-weight:700; color:#0F172A;">{{ $deposit->method_label }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding-bottom:14px; border-top:1px solid #E5E9EB; padding-top:14px;">
                                                <span style="display:block; font-size:10.5px; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:2px;">UTR Number</span>
                                                <span style="font-size:14px; font-weight:700; color:#0F172A; letter-spacing:0.02em;">{{ $deposit->utr }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="border-top:1px solid #E5E9EB; padding-top:14px;">
                                                <span style="display:block; font-size:10.5px; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:2px;">Date &amp; Time</span>
                                                <span style="font-size:14px; font-weight:700; color:#0F172A;">{{ $formattedDate }}</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <!-- What's Next -->
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#EFF6FF; border:1px solid #DBEAFE; border-radius:12px; margin-bottom:12px; text-align:left;">
                            <tr>
                                <td style="padding:14px 16px;">
                                    <p style="margin:0 0 4px; font-size:12.5px; font-weight:700; color:#1D4ED8;">What's Next?</p>
                                    <p style="margin:0; font-size:12px; line-height:1.6; color:#2563EB;">Once your payment is verified, the amount will be added to your wallet instantly. This usually takes 5-10 minutes.</p>
                                </td>
                            </tr>
                        </table>

                        <!-- Important -->
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FFFBEB; border:1px solid #FDE68A; border-radius:12px; text-align:left;">
                            <tr>
                                <td style="padding:14px 16px;">
                                    <p style="margin:0 0 4px; font-size:12.5px; font-weight:700; color:#B45309;">Important</p>
                                    <p style="margin:0; font-size:12px; line-height:1.6; color:#B45309;">Please do not make the payment again. Duplicate payments may take longer to verify.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="padding:20px 32px; background-color:#F8FAFC; border-top:1px solid #E5E9EB;">
                        <p style="margin:0; font-size:11.5px; line-height:1.6; color:#94A3B8; text-align:center;">
                            This is an automated confirmation from GullakPe. If you didn't make this request, please contact support.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
