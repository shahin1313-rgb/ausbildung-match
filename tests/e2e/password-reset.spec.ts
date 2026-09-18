import { expect, test } from '@playwright/test';

test('صفحه فراموشی رمز عبور باز می‌شود', async ({ page }) => {
  const path = process.env.E2E_PASSWORD_RESET_PATH ?? '/forgot-password';
  const response = await page.goto(path, { waitUntil: 'domcontentloaded' });

  expect(response).not.toBeNull();
  expect(response!.status()).toBeLessThan(400);
  await expect(page.locator('input[type="email"], input[name="email"]')).toBeVisible();
});
