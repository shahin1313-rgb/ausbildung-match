<!doctype html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f3f6fb;font-family:Tahoma,Arial,sans-serif;color:#172033">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:28px 12px"><tr><td align="center">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#fff;border-radius:18px;overflow:hidden">
<tr><td style="background:#175cd3;padding:30px;color:#fff"><h1 style="margin:0 0 8px;font-size:24px">فرصت‌های تازه آوسبیلدونگ</h1><p style="margin:0;line-height:1.8">{{ $opportunities->count() }} فرصت جدید در هفت روز گذشته</p></td></tr>
<tr><td style="padding:24px">
@forelse($opportunities as $opportunity)
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e5eaf2;border-radius:12px;margin-bottom:14px"><tr><td style="padding:18px">
<h2 style="margin:0 0 6px;font-size:18px">{{ $opportunity->title_de }}</h2>
<p style="margin:0 0 14px;color:#596579">{{ $opportunity->employer_name }} · {{ $opportunity->city }}</p>
<a href="{{ $frontendUrl }}/opportunities/{{ rawurlencode($opportunity->slug) }}" style="display:inline-block;background:#175cd3;color:#fff;text-decoration:none;padding:10px 16px;border-radius:9px">مشاهده جزئیات فرصت</a>
</td></tr></table>
@empty
<p style="text-align:center;color:#596579;padding:24px 0">در این هفته فرصت جدیدی منتشر نشده است.</p>
@endforelse
<p style="margin:20px 0 0;color:#768196;font-size:12px;line-height:1.8">این گزارش خودکار از Ausbildung Match ارسال شده است.</p>
</td></tr></table>
</td></tr></table>
</body></html>
