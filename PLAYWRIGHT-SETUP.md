# اجرای تست مرورگری

محتویات این پوشه را در ریشه پروژه `G:\ausbildung-match-laravel-react` کپی کنید.

```powershell
cd "G:\ausbildung-match-laravel-react"
npm install --save-dev @playwright/test
npx playwright install chromium
php artisan serve --host=127.0.0.1 --port=8000
```

در یک پنجره PowerShell دیگر:

```powershell
cd "G:\ausbildung-match-laravel-react"
npx playwright test tests/e2e/auth.spec.ts --headed
npx playwright show-report
```

اگر مسیر ورود پروژه متفاوت است:

```powershell
$env:E2E_LOGIN_PATH="/auth/login"
npx playwright test tests/e2e/auth.spec.ts --headed
```

اگر frontend روی پورت دیگری اجرا می‌شود:

```powershell
$env:E2E_BASE_URL="http://127.0.0.1:5173"
npx playwright test tests/e2e/auth.spec.ts --headed
```
