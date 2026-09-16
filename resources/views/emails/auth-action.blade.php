<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#f0f4fa;color:#142338;direction:rtl;font-family:Tahoma,'Segoe UI',Arial,sans-serif;-webkit-text-size-adjust:100%;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">{{ $intro }}</div>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#f0f4fa;">
    <tr>
        <td align="center" style="padding:32px 14px;">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:600px;border-collapse:separate;background:#ffffff;border:1px solid #dfe6ef;border-radius:22px;overflow:hidden;box-shadow:0 18px 55px rgba(31,53,84,.11);">
                <tr>
                    <td style="padding:30px 34px;background:#102d51;background-image:linear-gradient(115deg,#0c1e36 0%,#102d51 58%,#1769e0 140%);text-align:right;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td valign="middle" style="text-align:right;color:#ffffff;">
                                    <div style="font-size:21px;line-height:1.5;font-weight:800;">Ausbildung Match</div>
                                    <div style="margin-top:4px;color:#cbd8e8;font-size:12px;line-height:1.8;">مسیر حرفه‌ای تو در آلمان</div>
                                </td>
                                <td width="52" valign="middle" align="left">
                                    <div style="width:48px;height:48px;line-height:48px;text-align:center;border-radius:14px;background:#1769e0;color:#ffffff;font-size:23px;font-weight:900;box-shadow:0 9px 22px rgba(23,105,224,.28);">A</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:42px 38px 32px;text-align:right;">
                        <div style="display:inline-block;margin-bottom:15px;padding:7px 11px;border-radius:999px;background:#eaf3ff;color:#1769e0;font-size:12px;font-weight:700;">{{ $eyebrow }}</div>
                        <h1 style="margin:0 0 15px;color:#10233f;font-size:27px;line-height:1.65;font-weight:900;">{{ $title }}</h1>
                        <p style="margin:0;color:#66758a;font-size:15px;line-height:2.1;">{{ $intro }}</p>
                        <p style="margin:12px 0 0;color:#8290a2;font-size:12px;line-height:1.8;direction:ltr;text-align:right;">{{ $email }}</p>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:28px 0;">
                            <tr>
                                <td align="center">
                                    <a href="{{ $actionUrl }}" target="_blank" rel="noopener" style="display:inline-block;padding:14px 28px;border-radius:11px;background:#1769e0;color:#ffffff;text-decoration:none;font-size:15px;font-weight:800;line-height:1.4;box-shadow:0 8px 18px rgba(23,105,224,.19);">{{ $actionLabel }}</a>
                                </td>
                            </tr>
                        </table>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:separate;background:#fff8e9;border:1px solid #f3dfb6;border-radius:12px;">
                            <tr>
                                <td style="padding:15px 17px;color:#74541a;font-size:12px;line-height:2;">🔒 {{ $notice }}</td>
                            </tr>
                        </table>

                        <p style="margin:25px 0 7px;color:#66758a;font-size:12px;line-height:1.9;">اگر دکمه کار نکرد، نشانی زیر را در مرورگر کپی کن:</p>
                        <p style="margin:0;padding:12px 14px;border-radius:10px;background:#f4f6f9;color:#1769e0;font-family:Consolas,'Courier New',monospace;font-size:11px;line-height:1.8;direction:ltr;text-align:left;word-break:break-all;">{{ $actionUrl }}</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 34px;border-top:1px solid #edf1f6;background:#f9fbfd;color:#8290a2;text-align:center;font-size:11px;line-height:1.9;">
                        این پیام به‌صورت خودکار از Ausbildung Match ارسال شده است.<br>
                        لطفاً به این ایمیل پاسخ نده.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
