# BA Jobsuche reader (local test)

این ابزار نتایج عمومی Ausbildung و صفحه جزئیات هر آگهی را از Jobsuche می‌خواند و در یک فایل محلی ذخیره می‌کند. شرح آگهی، مدرک تحصیلی، نام مسئول، ایمیل، تلفن و نشانی تماس در صورت انتشار توسط منبع استخراج می‌شوند. اگر ایمیل یا تلفن فقط داخل متن توضیحات آمده باشد نیز استخراج می‌شود. در این مرحله هیچ اطلاعاتی به Laravel، API یا دیتابیس ارسال نمی‌شود.

## اجرا در PowerShell

از ریشه پروژه:

```powershell
python .\collector\ba_reader.py --query "Ausbildung" --city "Berlin" --pages 1
```

خواندن صفحه جزئیات به‌صورت پیش‌فرض فعال است. برای یک تست سریع که فقط فهرست را بخواند:

```powershell
python .\collector\ba_reader.py --city "Berlin" --pages 1 --skip-details
```

خروجی پیش‌فرض:

```text
storage/app/private/imports/ba-opportunities.json
```

برای انتخاب فایل خروجی:

```powershell
python .\collector\ba_reader.py --city "Hamburg" --output ".\ba-hamburg.json"
```

اجرای تست واحد:

```powershell
python -m unittest discover -s collector -p "test_*.py" -v
```

برای فشار نیاوردن به سایت، تعداد صفحات عمداً به حداکثر ۵ محدود شده و بین درخواست‌ها تأخیر وجود دارد. قبل از استفاده عملی و گسترده، شرایط استفاده و مجوز بازنشر داده‌های منبع را بررسی کنید.
