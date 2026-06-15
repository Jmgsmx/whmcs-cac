import path from 'node:path';
import { expect, test } from '@playwright/test';
import { captureSelectorSnapshot, writeSelectorSnapshot } from '../lib/selector-capture';

const adminUrl = process.env.WHMCS_ADMIN_URL;

test.describe('WHMCS admin selector capture', () => {
  test.skip(!adminUrl, 'WHMCS_ADMIN_URL is required to capture live admin selectors');

  for (const target of [
    { name: 'servers', path: 'configservers.php' },
    { name: 'products', path: 'configproducts.php' },
  ]) {
    test(`captures ${target.name} admin page structure`, async ({ page }) => {
      const url = new URL(target.path, adminUrl!.endsWith('/') ? adminUrl! : `${adminUrl!}/`);
      await page.goto(url.toString());

      const snapshot = await captureSelectorSnapshot(page, target.name);
      await writeSelectorSnapshot(
        path.join(process.cwd(), 'selector-artifacts', `${target.name}.json`),
        snapshot,
      );

      expect(snapshot.url).toContain(target.path);
      expect(snapshot.forms.length + snapshot.buttons.length + snapshot.links.length).toBeGreaterThan(0);
    });
  }
});
