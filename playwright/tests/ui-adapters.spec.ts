import { expect, test } from '@playwright/test';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import {
  buildUiPlans,
  ProductGroupUiAdapter,
  ServerGroupUiAdapter,
  ServerUiAdapter,
} from '../lib/ui-adapters';

const adminUrl = 'https://billing.example.com/admin/';

test('server group adapter builds a dry-run plan', async () => {
  const adapter = new ServerGroupUiAdapter({ adminUrl });

  const plan = await adapter.apply(
    {
      management_mode: 'ui',
      name: 'cPanel MX',
      fill_strategy: 'least_full',
    },
    'cpanel-mx',
  );

  expect(plan).toEqual({
    resourceType: 'server_group',
    key: 'cpanel-mx',
    action: 'ensure',
    url: 'https://billing.example.com/admin/configservers.php',
    fields: {
      name: 'cPanel MX',
      fill_strategy: 'least_full',
    },
  });
});

test('server adapter keeps credential references out of logs except the reference key', () => {
  const adapter = new ServerUiAdapter({ adminUrl });
  const plan = adapter.plan(
    {
      management_mode: 'ui',
      group_key: 'cpanel-mx',
      module: 'cpanel',
      hostname: 'whm01.example.com',
      ipaddress: '203.0.113.10',
      username: 'root',
      secure: true,
      port: 2087,
      credentials_ref: 'vault.cpanel.whm01',
    },
    'cpanel-01',
  );

  expect(plan.url).toBe('https://billing.example.com/admin/configservers.php');
  expect(plan.fields).toMatchObject({
    group_key: 'cpanel-mx',
    credentials_ref: 'vault.cpanel.whm01',
  });
  expect(JSON.stringify(plan)).not.toContain('password');
});

test('product group adapter builds a dry-run plan', () => {
  const adapter = new ProductGroupUiAdapter({ adminUrl });
  const plan = adapter.plan(
    {
      management_mode: 'ui',
      name: 'Shared Hosting',
      slug: 'shared-hosting',
      headline: 'Hosting administrado',
      orderform_template: 'standard_cart',
      allowed_gateways: ['banktransfer', 'stripe'],
    },
    'shared-hosting',
  );

  expect(plan).toMatchObject({
    resourceType: 'product_group',
    key: 'shared-hosting',
    url: 'https://billing.example.com/admin/configproducts.php',
  });
  expect(plan.fields.allowed_gateways).toEqual(['banktransfer', 'stripe']);
});

test('buildUiPlans includes only ui-managed resources', () => {
  const plans = buildUiPlans(
    {
      server_groups: {
        'cpanel-mx': { management_mode: 'ui', name: 'cPanel MX' },
      },
      servers: {
        'cpanel-01': { management_mode: 'ui', hostname: 'whm01.example.com' },
      },
      product_groups: {
        'shared-hosting': { management_mode: 'ui', name: 'Shared Hosting' },
        ignored: { management_mode: 'api', name: 'Ignored' },
      },
    },
    adminUrl,
  );

  expect(plans.map((plan) => plan.key)).toEqual(['cpanel-mx', 'cpanel-01', 'shared-hosting']);
});

test('live apply is guarded until stable selectors are recorded', async () => {
  const adapter = new ProductGroupUiAdapter({ adminUrl, liveApply: true });

  await expect(adapter.apply({ name: 'Shared Hosting' }, 'shared-hosting')).rejects.toThrow(
    /UI selector manifest not found/,
  );
});

test('live apply is blocked in explicit read-only mode', async () => {
  const previousReadOnly = process.env.WHMCS_UI_READ_ONLY;
  process.env.WHMCS_UI_READ_ONLY = '1';

  try {
    const adapter = new ProductGroupUiAdapter({ adminUrl, liveApply: true });
    await expect(adapter.apply({ name: 'Shared Hosting' }, 'shared-hosting')).rejects.toThrow(
      /WHMCS_UI_READ_ONLY is enabled/,
    );
  } finally {
    if (previousReadOnly === undefined) {
      delete process.env.WHMCS_UI_READ_ONLY;
    } else {
      process.env.WHMCS_UI_READ_ONLY = previousReadOnly;
    }
  }
});

test('live apply attaches selectors when a manifest is configured', async () => {
  const tmpDir = fs.mkdtempSync(path.join(os.tmpdir(), 'whmcs-selectors-'));
  const manifestPath = path.join(tmpDir, 'manifest.json');
  fs.writeFileSync(manifestPath, JSON.stringify({
    version: 1,
    resources: {
      server_group: {
        page: 'configservers.php',
        form: 'form#serverGroup',
        submit: 'button[name="save"]',
        fields: { name: 'input[name="name"]' },
      },
      server: {
        page: 'configservers.php',
        form: 'form#server',
        submit: 'button[name="save"]',
        fields: { hostname: 'input[name="hostname"]' },
      },
      product_group: {
        page: 'configproducts.php',
        form: 'form#productGroup',
        submit: 'button[name="save"]',
        fields: { name: 'input[name="name"]' },
      },
    },
  }));

  const previousManifest = process.env.WHMCS_UI_SELECTOR_MANIFEST;
  process.env.WHMCS_UI_SELECTOR_MANIFEST = manifestPath;
  try {
    const adapter = new ProductGroupUiAdapter({ adminUrl, liveApply: true });
    const plan = await adapter.apply({ name: 'Shared Hosting' }, 'shared-hosting');

    expect(plan.selectors?.form).toBe('form#productGroup');
  } finally {
    if (previousManifest === undefined) {
      delete process.env.WHMCS_UI_SELECTOR_MANIFEST;
    } else {
      process.env.WHMCS_UI_SELECTOR_MANIFEST = previousManifest;
    }
  }
});
