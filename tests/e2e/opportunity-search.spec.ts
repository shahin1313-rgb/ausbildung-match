import { expect, test } from '@playwright/test';

test('صفحه فرصت‌ها بدون خطای سرور باز می‌شود', async ({ page }) => {
  const path = process.env.E2E_OPPORTUNITIES_PATH ?? '/opportunities';
  const response = await page.goto(path, { waitUntil: 'domcontentloaded' });

  expect(response).not.toBeNull();
  expect(response!.status()).toBeLessThan(400);
  await expect(page.locator('body')).toBeVisible();
});
