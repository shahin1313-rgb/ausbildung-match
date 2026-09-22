# BA Jobsuche reader (local test)

این ابزار فقط نتایج عمومی Ausbildung را از صفحه رسمی Jobsuche می‌خواند و در یک فایل محلی ذخیره می‌کند. در این مرحله هیچ اطلاعاتی به Laravel، API یا دیتابیس ارسال نمی‌شود.

## اجرا در PowerShell

از ریشه پروژه:

```powershell
python .\collector\ba_reader.py --query "Ausbildung" --city "Berlin" --pages 1
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
