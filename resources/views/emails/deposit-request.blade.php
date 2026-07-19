@php
    $formattedAmount = number_format((float) $deposit->amount, 2);
    $formattedDate = $deposit->submitted_at->format('d M Y, h:i A');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New Deposit Request</title>
</head>
<body style="margin:0; padding:0; background-color:#F4F6F7; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F4F6F7; padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 2px 12px rgba(15,23,42,0.06);">

                <!-- Header -->
                <tr>
                    <td style="background-color:#0A5C66; padding:28px 32px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="font-size:20px; font-weight:800; color:#ffffff; letter-spacing:-0.02em;">
                                    Gullak<span style="color:#3FEA8A;">Pe</span>
                                </td>
                                <td align="right" style="font-size:11px; font-weight:700; color:rgba(255,255,255,0.75); text-transform:uppercase; letter-spacing:0.04em;">
                                    Ops Notification
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:32px;">
                        <table role="presentation" width="1" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                            <tr>
                                <td style="background-color:#FEF3C7; border-radius:20px; padding:6px 14px; white-space:nowrap;">
                                    <span style="font-size:11px; font-weight:700; color:#B45309; text-transform:uppercase; letter-spacing:0.03em;">Action needed</span>
                                </td>
                            </tr>
                        </table>

                        <h1 style="margin:0 0 8px; font-size:19px; font-weight:800; color:#0F172A; letter-spacing:-0.01em;">New deposit request submitted</h1>
                        <p style="margin:0 0 24px; font-size:14px; line-height:1.6; color:#64748B;">
                            A user has submitted a manual payment reference and is waiting on your review. Please verify the transaction before approving.
                        </p>

                        <!-- Details card -->
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; border:1px solid #E5E9EB; border-radius:12px; margin-bottom:24px;">
                            <tr>
                                <td style="padding:18px 20px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="padding-bottom:14px;">
                                                <span style="display:block; font-size:10.5px; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:2px;">Amount</span>
                                                <span style="font-size:18px; font-weight:800; color:#0A5C66;">&#8377;{{ $formattedAmount }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding-bottom:14px; border-top:1px solid #E5E9EB; padding-top:14px;">
                                                <span style="display:block; font-size:10.5px; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:2px;">Phone</span>
                                                <span style="font-size:14px; font-weight:700; color:#0F172A;">{{ $deposit->phone }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding-bottom:14px; border-top:1px solid #E5E9EB; padding-top:14px;">
                                                <span style="display:block; font-size:10.5px; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:2px;">Method</span>
                                                <span style="font-size:14px; font-weight:700; color:#0F172A;">{{ $deposit->method_label }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding-bottom:14px; border-top:1px solid #E5E9EB; padding-top:14px;">
                                                <span style="display:block; font-size:10.5px; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:2px;">UTR / Reference Number</span>
                                                <span style="font-size:14px; font-weight:700; color:#0F172A; letter-spacing:0.02em;">{{ $deposit->utr }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="border-top:1px solid #E5E9EB; padding-top:14px;">
                                                <span style="display:block; font-size:10.5px; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:2px;">Submitted</span>
                                                <span style="font-size:14px; font-weight:700; color:#0F172A;">{{ $formattedDate }}</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <!-- CTA -->
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="center">
                                    <a href="{{ route('admin.deposits') }}" target="_blank" style="display:inline-block; background-color:#0A5C66; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none; padding:13px 28px; border-radius:12px;">
                                        Review in Admin Panel
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="padding:20px 32px; background-color:#F8FAFC; border-top:1px solid #E5E9EB;">
                        <p style="margin:0; font-size:11.5px; line-height:1.6; color:#94A3B8; text-align:center;">
                            This is an automated notification from GullakPe Ops. Please do not reply to this email.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
