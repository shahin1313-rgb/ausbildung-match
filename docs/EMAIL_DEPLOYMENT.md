# استقرار ایمیل و احراز هویت

برای production، `APP_URL` را به نشانی HTTPS بک‌اند و `FRONTEND_URL` را به نشانی HTTPS فرانت‌اند تنظیم کنید. اگر React از همان دامنه Laravel سرو می‌شود، این دو برابرند. لینک تأیید به صفحه `/verify-email` و لینک بازیابی به `/reset-password` در `FRONTEND_URL` می‌رود؛ این صفحات باید با fallback وب‌سرور به برنامه React قابل دسترس باشند. در حالت دامنهٔ جدا، درخواست‌های `/api/v1` و `/sanctum/csrf-cookie` باید از همان origin فرانت‌اند به Laravel proxy شوند؛ `SANCTUM_STATEFUL_DOMAINS`، `SESSION_DOMAIN`، HTTPS cookie و CORS باید با topology واقعی تنظیم شوند. نشانی ورودی و Host معتبر را در reverse proxy محدود کنید. `SESSION_DRIVER=database` و اجرای migration جدول `sessions` برای ابطال همهٔ نشست‌ها الزامی است.

در `.env` سرور، مقادیر نمونه را با مشخصات **واقعی ارائه‌دهندهٔ SMTP خودتان** جایگزین کنید:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=sample-smtp-user
MAIL_PASSWORD=replace-with-smtp-secret
MAIL_ENCRYPTION=tls
MAIL_SCHEME=smtp
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Ausbildung Match"
FRONTEND_URL=https://www.example.com
APP_URL=https://www.example.com
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
```

مثال‌ها ساختگی هستند. طبق سرویس خودتان برای STARTTLS معمولاً پورت 587 با `MAIL_SCHEME=smtp` و برای TLS ضمنی پورت 465 با `MAIL_SCHEME=smtps` تعیین می‌شود. اعتبارنامه را فقط در `.env` غیرقابل انتشار/secret manager نگه دارید. `MAIL_URL` اگر تنظیم شود می‌تواند پارامترهای مجزا را بازنویسی کند؛ آن را در حالت تنظیم جداگانه خالی بگذارید. Laravel 12 `config/mail.php` مقدار `MAIL_ENCRYPTION` قدیمی را به scheme تبدیل می‌کند؛ scheme واقعی انتخاب سرویس باید تنظیم شود. پس از تغییر تنظیمات `php artisan config:clear` و سپس `php artisan config:cache` اجرا کنید.

دامنهٔ `MAIL_FROM_ADDRESS` باید در سرویس SMTP تأیید شده باشد. SPF را با رکورد TXT مطابق include/IP اعلام‌شدهٔ سرویس تنظیم کنید؛ کلید و selector رکورد DKIM را از همان سرویس دریافت کنید و در DNS منتشر کنید؛ برای DMARC ابتدا رکورد TXT `_dmarc` با سیاست و نشانی گزارش مناسب خودتان تنظیم کنید و پس از بررسی alignment با From و گزارش‌ها سیاست را سخت‌تر کنید. مقدار DNS سرویس را حدس نزنید. رکوردهای SPF/DKIM/DMARC را با ابزار DNS خودتان بررسی و سپس یک ایمیل آزمایشی به صندوق واقعی ارسال کنید و headers، امضای DKIM، نتیجهٔ SPF/DMARC، رسیدن به Inbox و لینک HTTPS را کنترل کنید.

برای آزمایش امن روی سرور: ابتدا migrationها را اجرا کنید، `MAIL_MAILER=log` را برای بررسی ساخت URL بدون ارسال واقعی به‌کار ببرید، سپس SMTP را تنظیم کنید و با یک حساب آزمایشی دارای صندوق قابل دسترس در سایت ثبت‌نام کنید؛ ایمیل تأیید را باز کنید، بازفرستادن لینک و بازیابی رمز را آزمون کنید. اگر ارسال خطا داشت، host، port، TLS scheme، اعتبارنامه، مجاز بودن From، دسترسی شبکه و `storage/logs` را بررسی کنید. رمز SMTP یا لینک بازیابی را در log عمومی/گزارش پشتیبانی قرار ندهید. بررسی PHP unit با `MAIL_MAILER=array`/notification fake اتصال واقعی SMTP، DNS و تحویل صندوق را آزمایش نمی‌کند.

APIهای جدید: `GET /api/v1/auth/email/status`، `POST /api/v1/auth/email/resend`، `GET /api/v1/auth/email/verify/{id}/{hash}` با امضای نسبی زمان‌دار، `PUT /api/v1/auth/email` با رمز فعلی، `POST /api/v1/auth/forgot-password`، `POST /api/v1/auth/reset-password` و `PUT /api/v1/auth/password`. نشست‌ها و توکن‌های قبلی پس از تغییر رمز/ایمیل باطل می‌شوند. لینک بازیابی Laravel تا ۶۰ دقیقه معتبر و یک‌بارمصرف است؛ درخواست‌ها به‌علاوهٔ throttle خود broker محدود شده‌اند.
