# Ausbildung Match

پلتفرم فارسی جست‌وجو و تطبیق فرصت‌های آوسبیلدونگ آلمان. بک‌اند با Laravel و MySQL، پنل مدیریت با Filament و رابط کاربری با React ساخته شده است.

> داده‌های Seeder نمایشی و نام شرکت‌ها ساختگی هستند. لینک هر فرصت نمونه به جست‌وجوی رسمی Bundesagentur für Arbeit می‌رود. برای انتشار واقعی باید داده‌های هر منبع را با اجازه و طبق شرایط استفاده آن منبع وارد یا همگام‌سازی کنید.

## فناوری‌ها

| بخش | فناوری |
|---|---|
| API و منطق سرور | Laravel 12، PHP 8.2+ |
| احراز هویت SPA | Laravel Sanctum 4، Session Cookie و CSRF |
| پنل مدیریت | Filament 5 |
| دیتابیس | MySQL 8 / MariaDB 10.6+ |
| رابط کاربری | React 19، TypeScript، Vite 6 |
| تست | PHPUnit 11 |

Laravel 12 عمداً انتخاب شده تا با PHP 8.2.12 در XAMPP سازگار باشد؛ Laravel 13 به PHP 8.3 یا بالاتر نیاز دارد.

## امکانات آماده

- جست‌وجو، فیلتر شهر/حوزه/زبان، مرتب‌سازی و صفحه‌بندی فرصت‌ها
- تطبیق کاربر و فرصت بر پایه زبان، تحصیلات، مهارت، تجربه، جابه‌جایی و زمان شروع
- ثبت‌نام، ورود، خروج، پروفایل و علاقه‌مندی‌ها با Sanctum
- آپلود خصوصی PDF/DOC/DOCX تا ۵ مگابایت
- صفحات حریم خصوصی، شرایط استفاده، Impressum و سیاست نگهداری رزومه
- ثبت رضایت‌های حقوقی و حذف خودکار رزومه پس از دوره نگهداری
- سازنده رزومه آلمانی و خروجی PDF از پنجره چاپ مرورگر
- پنل `/admin` برای فرصت‌ها، دسته‌ها، منابع، کاربران و گزارش کلیک درخواست
- ثبت شرکت با وضعیت «در انتظار بررسی» و انتشار آگهی فقط پس از تأیید شرکت در پنل مدیریت
- ورود دسته‌ای JSON با upsert و نگهداری منبع هر رکورد
- منقضی‌کردن خودکار فرصت‌های قدیمی و ایمیل خلاصه هفتگی
- هشت فرصت نمایشی و شش دسته‌بندی برای شروع

## نصب روی Windows 10 و XAMPP

پیش‌نیازها:

- PHP 8.2 با افزونه‌های `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `intl`, `zip`
- Composer 2
- Node.js 20 یا جدیدتر
- MySQL روشن در XAMPP

در phpMyAdmin یک دیتابیس با نام `ausbildung_match` و Collation برابر `utf8mb4_unicode_ci` بسازید. سپس PowerShell را در پوشه پروژه باز کنید:

```powershell
Copy-Item .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

سایت در `http://127.0.0.1:8000` و پنل مدیریت در `http://127.0.0.1:8000/admin` باز می‌شود.

ساخت مدیر اولیه اختیاری است و فقط وقتی `SEED_ADMIN_USER=true` باشد انجام می‌شود:

```dotenv
SEED_ADMIN_USER=true
ADMIN_EMAIL=admin@your-domain.example
ADMIN_PASSWORD="use-a-unique-password-of-at-least-12-characters"
```

اگر اطلاعات MySQL شما متفاوت است، این بخش `.env` را اصلاح کنید:

داده‌های نمایشی فقط در محیط توسعه و با `SEED_DEMO_DATA=true` ساخته می‌شوند. در production حتی اجرای مستقیم Seederهای نمایشی مسدود است. برای ساخت اختیاری مدیر اولیه، `SEED_ADMIN_USER=true` را همراه ایمیل و رمز غیرپیش‌فرض حداقل ۱۲ کاراکتری تنظیم کنید. در استقرار production می‌توانید با خیال امن اجرا کنید:

```powershell
php artisan migrate --force
php artisan db:seed --force
```

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ausbildung_match
DB_USERNAME=root
DB_PASSWORD=
```

### حالت توسعه React

در `.env` مقدار زیر را تنظیم کنید:

```dotenv
FRONTEND_DEV_SERVER_URL=http://127.0.0.1:5173
```

و در دو پنجره جدا اجرا کنید:

```powershell
php artisan serve
npm run dev
```

برای استفاده از فایل buildشده، `FRONTEND_DEV_SERVER_URL` را خالی بگذارید.

## ورود فرصت‌ها از JSON

ابتدا منبع را در Filament بسازید یا از Seeder استفاده کنید؛ سپس فایل را با قالب [docs/import-template.json](docs/import-template.json) آماده کنید:

```powershell
php artisan opportunities:import-json docs/import-template.json --source="Bundesagentur für Arbeit"
```

ترکیب `source_id + external_id` یکتا است؛ بنابراین اجرای دوباره فایل، فرصت موجود را به‌روزرسانی می‌کند و رکورد تکراری نمی‌سازد.

## زمان‌بندی کارها

Laravel روزانه فرصت‌های گذشته و رزومه‌های منقضی را پاک‌سازی و دوشنبه ساعت ۸ صبح به وقت برلین گزارش هفتگی را ارسال می‌کند. ایمیل مقصد:

```dotenv
WEEKLY_DIGEST_EMAIL=you@example.com
```

در توسعه می‌توانید زمان‌بند را زنده اجرا کنید:

```powershell
php artisan schedule:work
```

روی Linux/VPS این Cron را یک بار ثبت کنید:

```cron
* * * * * cd /var/www/ausbildung-match && php artisan schedule:run >> /dev/null 2>&1
```

برای ارسال واقعی ایمیل، `MAIL_MAILER=smtp` و مشخصات SMTP را در `.env` وارد کنید. مقدار پیش‌فرض `log` است و ایمیل را در `storage/logs/laravel.log` می‌نویسد.

ایمیل تأیید حساب، بازیابی رمز عبور و گزارش هفتگی به‌صورت Queue ارسال می‌شوند. بنابراین در توسعه یک پنجره جدا برای Worker باز نگه دارید:

```powershell
php artisan queue:work --tries=3 --backoff=60
```

در production همین فرمان باید با Supervisor یا systemd همیشه فعال نگه داشته شود. برای مشاهده و اجرای دوباره کارهای ناموفق می‌توانید از دستورهای زیر استفاده کنید:

```powershell
php artisan queue:failed
php artisan queue:retry all
```

## تنظیم اطلاعات قانونی

پیش از انتشار عمومی، متغیرهای `LEGAL_PROVIDER_*` در `.env` را با اطلاعات واقعی مالک/شرکت تکمیل کنید. این اطلاعات در Impressum و سیاست حریم خصوصی نمایش داده می‌شوند. حداقل نام ارائه‌دهنده، نشانی کامل و ایمیل را وارد کنید و بسته به شکل حقوقی، نماینده، ثبت تجاری، شماره مالیات و مرجع ناظر را نیز تکمیل کنید:

```dotenv
LEGAL_PROVIDER_NAME="Example GmbH"
LEGAL_PROVIDER_LEGAL_FORM="GmbH"
LEGAL_PROVIDER_REPRESENTATIVE="Max Mustermann"
LEGAL_PROVIDER_STREET_ADDRESS="Musterstraße 1"
LEGAL_PROVIDER_POSTAL_CODE=10115
LEGAL_PROVIDER_CITY=Berlin
LEGAL_PROVIDER_COUNTRY=Deutschland
LEGAL_PROVIDER_EMAIL=legal@example.com
LEGAL_DATA_PROTECTION_EMAIL=privacy@example.com
LEGAL_RESUME_RETENTION_DAYS=180
```

پس از تغییر مدت نگهداری یا متن‌های قانونی، نسخه‌های `LEGAL_TERMS_VERSION` و `LEGAL_PRIVACY_VERSION` را افزایش دهید. متن‌های آماده‌شده قالب عملیاتی هستند و باید پیش از انتشار با وضعیت واقعی کسب‌وکار و نظر مشاور حقوقی تطبیق داده شوند.

## تست و بررسی

```powershell
composer test
npm run build
```

تست PHP از SQLite حافظه‌ای استفاده می‌کند و به دیتابیس MySQL توسعه دست نمی‌زند.

## API

مسیرهای عمومی و خصوصی در [docs/API.md](docs/API.md) آمده‌اند. همه مسیرهای برنامه زیر `/api/v1` هستند. درخواست‌های خصوصی از Session Cookie، CSRF و middleware `auth:sanctum` استفاده می‌کنند.

## نکات استقرار

- Document Root وب‌سرور باید پوشه `public/` باشد.
- `APP_DEBUG=false`، رمز مدیر قوی و HTTPS را فعال کنید.
- پوشه‌های `storage/` و `bootstrap/cache/` باید برای کاربر وب‌سرور قابل نوشتن باشند.
- پس از استقرار اجرا کنید: `php artisan optimize` و `php artisan filament:optimize`.
- Worker صف را با Supervisor یا systemd اجرا کنید؛ بدون Worker، ایمیل‌ها در جدول `jobs` منتظر می‌مانند.
- فایل‌های رزومه روی disk خصوصی ذخیره می‌شوند و URL عمومی ندارند.
- شرکت‌های تازه از مسیر «کارفرمایان ← تأیید شرکت‌ها» در پنل مدیریت بررسی می‌شوند؛ تا پیش از تأیید، دسترسی انتشار فرصت فعال نیست.
- Scraping کورکورانه منابع توصیه نمی‌شود؛ API/Feed رسمی یا واردسازی مجاز را برای هر منبع پیاده‌سازی کنید.
