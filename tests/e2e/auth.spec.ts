import { expect, test } from '@playwright/test';

test.beforeEach(async ({ page }) => {
  // نمایش دایره قرمز در محل هر کلیک
  await page.addInitScript(() => {
    document.addEventListener(
      'click',
      event => {
        const mouseEvent = event as MouseEvent;
        const marker = document.createElement('div');

        marker.style.position = 'fixed';
        marker.style.left = `${mouseEvent.clientX - 18}px`;
        marker.style.top = `${mouseEvent.clientY - 18}px`;
        marker.style.width = '36px';
        marker.style.height = '36px';
        marker.style.border = '4px solid red';
        marker.style.borderRadius = '50%';
        marker.style.background = 'rgba(255, 0, 0, 0.25)';
        marker.style.zIndex = '2147483647';
        marker.style.pointerEvents = 'none';

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
            duration: 1500,
            easing: 'ease-out',
          },
        );

        setTimeout(() => marker.remove(), 1500);
      },
      true,
    );
  });
});

test('فرم ورود از صفحه اصلی باز می‌شود', async ({ page }) => {
  const response = await page.goto('/', {
    waitUntil: 'networkidle',
  });

  expect(response).not.toBeNull();
  expect(response!.status()).toBeLessThan(400);

  await expect(page).toHaveTitle(/Ausbildung Match/i);

  // پنج ثانیه نمایش صفحه اصلی
  await page.waitForTimeout(5_000);

  const loginButton = page
    .getByRole('button', {
      name: 'ورود / ثبت‌نام',
      exact: true,
    })
    .first();

  await expect(loginButton).toBeVisible();

  await loginButton.click();

  // نمایش نشانگر کلیک و فرم ورود
  await page.waitForTimeout(5_000);

  const emailInput = page.locator([
    'input[type="email"]',
    'input[name="email"]',
    'input[autocomplete="email"]',
    'input[placeholder*="ایمیل"]',
  ].join(', ')).first();

  const passwordInput = page.locator([
    'input[type="password"]',
    'input[name="password"]',
    'input[autocomplete="current-password"]',
    'input[placeholder*="رمز"]',
  ].join(', ')).first();

  await expect(emailInput).toBeVisible();
  await expect(passwordInput).toBeVisible();

  await emailInput.click();
  await emailInput.fill('user1@test.com');

  await passwordInput.click();
  await passwordInput.fill('password');

  // پنج ثانیه نمایش فرم تکمیل‌شده
  await page.waitForTimeout(5_000);

  await expect(emailInput).toHaveValue('user1@test.com');
  await expect(passwordInput).toHaveValue('password');
});