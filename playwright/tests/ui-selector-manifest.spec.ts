import { expect, test } from '@playwright/test';
import {
  selectorsFor,
  validateSelectorManifest,
} from '../lib/ui-selector-manifest';

const validManifest = {
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
};

test('validateSelectorManifest accepts complete manifests', () => {
  expect(validateSelectorManifest(validManifest)).toEqual({ ok: true, errors: [] });
});

test('validateSelectorManifest rejects missing resource selectors', () => {
  const validation = validateSelectorManifest({
    version: 1,
    resources: {
      server_group: validManifest.resources.server_group,
    },
  });

  expect(validation.ok).toBe(false);
  expect(validation.errors).toContain('resources.server is required');
  expect(validation.errors).toContain('resources.product_group is required');
});

test('selectorsFor returns selectors for a resource type', () => {
  expect(selectorsFor(validManifest, 'server').form).toBe('form#server');
});
