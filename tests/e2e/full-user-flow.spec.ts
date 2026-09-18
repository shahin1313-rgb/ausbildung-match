import { expect, test, type Page } from '@playwright/test';

const PAUSE_TIME = 5_000;

async function pause(page: Page): Promise<void> {
  await page.waitForTimeout(PAUSE_TIME);
}

async function installClickMarker(page: Page): Promise<void> {
  await page.addInitScript(() => {
    document.addEventListener(
      'click',
      event => {
        const mouseEvent = event as MouseEvent;
        const marker = document.createElement('div');

        Object.assign(marker.style, {
          position: 'fixed',
          left: `${mouseEvent.clientX - 20}px`,
          top: `${mouseEvent.clientY - 20}px`,
          width: '40px',
          height: '40px',
          border: '4px solid #ef4444',
          borderRadius: '50%',
          background: 'rgba(239, 68, 68, 0.25)',
          boxShadow: '0 0 12px rgba(239, 68, 68, 0.9)',
          pointerEvents: 'none',
          zIndex: '2147483647',
        });

        document.documentElement.appendChild(marker);

        marker.animate(
          [
            {
              transform: 'scale(0.5)',
              opacity: '1',
            },
            {
              transform: 'scale(1.8)',
              opacity: '0',
            },
          ],
          {
            duration: 1_500,
            easing: 'ease-out',
          },
        );

        setTimeout(() => marker.remove(), 1_500);
      },
      true,
    );
  });
}

test.describe('تست کامل مسیر کاربر Ausbildung Match', () => {
  test.setTimeout(180_000);

  test.beforeEach(async ({ page }) => {
    await installClickMarker(page);

    page.on('console', message => {
      if (message.type() === 'error') {
        console.log(`[browser:error] ${message.text()}`);
      }
    });

    page.on('pageerror', error => {
      console.log(`[pageerror] ${error.message}`);
    });
  });

  test('مسیر کامل مشاهده، جست‌وجو و احراز هویت', async ({ page }) => {
    await test.step('بازکردن صفحه اصلی', async () => {
      const response = await page.goto('/', {
        waitUntil: 'domcontentloaded',
      });

      expect(response, 'سرور پاسخی برنگرداند').not.toBeNull();
      expect(
        response!.status(),
        'صفحه اصلی خطای HTTP دارد',
      ).toBeLessThan(400);

      await expect(page).toHaveTitle(/Ausbildung Match/i);
      await expect(page.locator('body')).toBeVisible();

      await expect(
        page.getByRole('heading', {
          name: /مسیر حرفه‌ای.*آلمان|آینده شغلی/i,
        }),
      ).toBeVisible();

      await pause(page);
    });

    await test.step('جست‌وجوی فرصت Ausbildung', async () => {
      const searchInput = page.getByRole('textbox', {
        name: 'رشته، شهر یا شرکت',
      });

      await expect(
        searchInput,
        'کادر جست‌وجوی فرصت پیدا نشد',
      ).toBeVisible();

      await searchInput.click();
      await searchInput.fill('برنامه‌نویسی');

      await pause(page);

      const searchButton = page.getByRole('button', {
        name: 'جست‌وجو',
        exact: true,
      });

      await expect(searchButton).toBeVisible();
      await searchButton.click();

      await pause(page);

      await expect(page.locator('body')).toContainText(
        /برنامه‌نویسی|فناوری اطلاعات|فرصت|نتیجه/i,
      );
    });

    await test.step('بازکردن جزئیات یک فرصت', async () => {
      const opportunityCard = page
        .locator('main button')
        .filter({
          hasText: /Ausbildung|آوسبیلدونگ|کارآموز/,
        })
        .first();

      if (await opportunityCard.isVisible().catch(() => false)) {
        await opportunityCard.click();
        await pause(page);

        await expect(page.locator('body')).toContainText(
          /Ausbildung|آوسبیلدونگ|درخواست|شرکت|شهر|زبان|جزئیات/i,
        );
      } else {
        console.log(
          'کارت فرصت در نتایج جست‌وجو پیدا نشد؛ ادامه تست از صفحه اصلی.',
        );
      }
    });

    await test.step('بازگشت به صفحه اصلی', async () => {
      const brandButton = page.getByRole('button', {
        name: /Ausbildung Match/i,
      });

      if (await brandButton.isVisible().catch(() => false)) {
        await brandButton.click();
      } else {
        await page.goto('/', {
          waitUntil: 'domcontentloaded',
        });
      }

      await expect(page).toHaveTitle(/Ausbildung Match/i);
      await pause(page);
    });

    await test.step('بازکردن بخش پنل کارفرما', async () => {
      const employerButton = page.getByRole('button', {
        name: 'پنل کارفرما',
        exact: true,
      });

      await expect(
        employerButton,
        'دکمه پنل کارفرما پیدا نشد',
      ).toBeVisible();

      await employerButton.click();
      await pause(page);

      await expect(page.locator('body')).toContainText(
        /کارفرما|شرکت|ورود|ثبت‌نام/i,
      );
    });

    await test.step('بازکردن فرم ورود', async () => {
      const loginButton = page
        .getByRole('button', {
          name: 'ورود / ثبت‌نام',
          exact: true,
        })
        .first();

      await expect(
        loginButton,
        'دکمه ورود یا ثبت‌نام پیدا نشد',
      ).toBeVisible();

      await loginButton.click();
      await pause(page);

      const emailInput = page.locator([
        'input[type="email"]',
        'input[name="email"]',
        'input[autocomplete="email"]',
        'input[placeholder*="ایمیل"]',
        'input[placeholder*="email" i]',
      ].join(', ')).first();

      const passwordInput = page.locator([
        'input[type="password"]',
        'input[name="password"]',
        'input[autocomplete="current-password"]',
        'input[placeholder*="رمز"]',
        'input[placeholder*="password" i]',
      ].join(', ')).first();

      await expect(
        emailInput,
        'فیلد ایمیل در فرم ورود پیدا نشد',
      ).toBeVisible();

      await expect(
        passwordInput,
        'فیلد رمز عبور در فرم ورود پیدا نشد',
      ).toBeVisible();

      await emailInput.click();
      await emailInput.fill('user1@test.com');

      await passwordInput.click();
      await passwordInput.fill('password');

      await expect(emailInput).toHaveValue('user1@test.com');
      await expect(passwordInput).toHaveValue('password');

      await pause(page);
    });
await test.step('بازکردن فرم بازیابی رمز عبور', async () => {
  const forgotPasswordLink = page.getByRole('link', {
    name: 'رمز عبور را فراموش کرده‌اید؟',
    exact: true,
  });

  await expect(
    forgotPasswordLink,
    'لینک بازیابی رمز عبور در فرم ورود پیدا نشد',
  ).toBeVisible();

  await forgotPasswordLink.click();

  await expect(page).toHaveURL(/\/forgot-password(?:\?.*)?$/);
  await pause(page);

  const resetEmailInput = page.getByRole('textbox', {
    name: 'ایمیل',
    exact: true,
  });

  await expect(
    resetEmailInput,
    'فیلد ایمیل بازیابی رمز پیدا نشد',
  ).toBeVisible();

  await resetEmailInput.click();
  await resetEmailInput.fill('user1@test.com');

  await expect(resetEmailInput).toHaveValue('user1@test.com');

  await pause(page);

  // برای جلوگیری از ارسال ایمیل واقعی، فرم ارسال نمی‌شود.
    });

    await test.step('پایان تست و بررسی خطای عمومی', async () => {
      await expect(page.locator('body')).not.toContainText(
        /500 Server Error|Internal Server Error|Fatal error/i,
      );

      await pause(page);
    });
  });
});