import { defineConfig } from '@playwright/test';
import fs from 'node:fs';

const storageState = process.env.WHMCS_STORAGE_STATE && fs.existsSync(process.env.WHMCS_STORAGE_STATE)
  ? process.env.WHMCS_STORAGE_STATE
  : undefined;

export default defineConfig({
  use: {
    headless: true,
    viewport: { width: 1280, height: 720 },
    actionTimeout: 10000,
    storageState
  },
  outputDir: 'test-results'
});
