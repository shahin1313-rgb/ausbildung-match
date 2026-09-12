# API v1

Base path: `/api/v1`

## عمومی

| Method | Path | کاربرد |
|---|---|---|
| GET | `/meta` | دسته‌ها، شهرها و سطح‌های زبان |
| GET | `/opportunities` | فهرست صفحه‌بندی‌شده فرصت‌ها |
| GET | `/opportunities/{slug}` | جزئیات یک فرصت منتشرشده |
| POST | `/auth/register` | ساخت حساب و شروع Session |
| POST | `/auth/login` | ورود کاربر |

پارامترهای `/opportunities`: `q`, `category`, `city`, `german_level`, `international`, `sort`, `page`, `per_page`.

مقادیر `sort`: `latest`, `start`, `salary`, `match`. مرتب‌سازی `match` فقط وقتی پروفایل کاربر موجود باشد اعمال می‌شود.

## نیازمند ورود (`auth:sanctum`)

| Method | Path | کاربرد |
|---|---|---|
| GET | `/auth/me` | کاربر جاری |
| POST | `/auth/logout` | خروج و ابطال Session |
| GET / PUT | `/profile` | دریافت و ویرایش پروفایل تطبیق |
| GET / POST | `/resumes` | فهرست و آپلود رزومه خصوصی |
| DELETE | `/resumes/{id}` | حذف رزومه متعلق به کاربر |
| GET | `/favorites` | فرصت‌های ذخیره‌شده |
| PUT / DELETE | `/favorites/{slug}` | افزودن/حذف علاقه‌مندی |
| GET / PUT | `/german-cv` | دریافت و ویرایش Lebenslauf |
| POST | `/application-clicks/{slug}` | ثبت خروج به صفحه رسمی درخواست |

فرانت قبل از درخواست mutating ابتدا `/sanctum/csrf-cookie` را فراخوانی می‌کند و Cookie رمزنگاری‌شده Session را با `credentials: include` می‌فرستد.
