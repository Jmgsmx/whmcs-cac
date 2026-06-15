import { test, expect } from '@playwright/test';

test('admin login smoke', async ({ page }) => {
  test.skip(!process.env.WHMCS_ADMIN_URL, 'WHMCS_ADMIN_URL is required for live admin smoke tests');

  await page.goto(process.env.WHMCS_ADMIN_URL!);
  await expect(page).toHaveTitle(/WHMCS|Login|Admin/i);
});
