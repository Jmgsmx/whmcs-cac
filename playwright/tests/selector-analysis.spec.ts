import { expect, test } from '@playwright/test';
import { analyzeSnapshot } from '../lib/selector-analysis';

test('analyzeSnapshot accepts authenticated admin page snapshots', () => {
  const analysis = analyzeSnapshot({
    name: 'servers',
    url: 'https://billing.example.com/admin/configservers.php',
    title: 'Servers - WHMCS Admin',
    capturedAt: '2026-06-15T00:00:00.000Z',
    headings: ['Servers'],
    forms: [
      {
        id: 'frmAddServer',
        name: null,
        action: 'configservers.php?action=save',
        method: 'post',
        inputs: [
          { tag: 'input', type: 'text', id: 'hostname', name: 'hostname', placeholder: null, ariaLabel: null, label: 'Hostname' },
        ],
      },
    ],
    buttons: [{ text: 'Save Changes', id: null, name: 'save', type: 'submit' }],
    links: [{ text: 'Add New Server', href: 'configservers.php?action=manage' }],
  }, 'configservers.php');

  expect(analysis.ok).toBe(true);
  expect(analysis.candidates.forms[0].selector).toBe('form#frmAddServer');
  expect(analysis.candidates.buttons[0].selector).toBe('button[name="save"]');
});

test('analyzeSnapshot rejects login page captures', () => {
  const analysis = analyzeSnapshot({
    name: 'servers',
    url: 'https://billing.example.com/admin/login.php?redirect=configservers.php',
    title: 'Login',
    capturedAt: '2026-06-15T00:00:00.000Z',
    headings: ['Admin Login'],
    forms: [
      {
        id: 'login',
        name: null,
        action: 'login.php',
        method: 'post',
        inputs: [
          { tag: 'input', type: 'text', id: 'username', name: 'username', placeholder: 'Username', ariaLabel: null, label: 'Username' },
          { tag: 'input', type: 'password', id: 'password', name: 'password', placeholder: 'Password', ariaLabel: null, label: 'Password' },
        ],
      },
    ],
    buttons: [{ text: 'Login', id: null, name: null, type: 'submit' }],
    links: [],
  }, 'configservers.php');

  expect(analysis.ok).toBe(false);
  expect(analysis.errors).toContain('Snapshot appears to be a login page, not an authenticated admin page');
});
