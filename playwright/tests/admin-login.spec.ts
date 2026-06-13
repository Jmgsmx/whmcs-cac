import { test, expect } from '@playwright/test';

test('admin login smoke', async ({ page }) => {
  await page.goto(process.env.WHMCS_ADMIN_URL || 'about:blank');
  // Placeholder test: replace selectors when known
  await expect(page).toHaveTitle(/WHMCS|Login|Admin/i);
});
