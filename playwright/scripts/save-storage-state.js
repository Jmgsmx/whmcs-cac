const fs = require('node:fs/promises');
const path = require('node:path');
const readline = require('node:readline/promises');
const { stdin: input, stdout: output } = require('node:process');
const { chromium } = require('playwright');

async function main() {
  const adminUrl = process.env.WHMCS_ADMIN_URL;
  if (!adminUrl) {
    console.error('WHMCS_ADMIN_URL is required, for example https://billing-staging.example.com/admin');
    process.exit(1);
  }

  const storageState = process.env.WHMCS_STORAGE_STATE
    ? path.resolve(process.env.WHMCS_STORAGE_STATE)
    : path.resolve(__dirname, '..', 'storage-state', 'staging-admin.json');

  const browser = await chromium.launch({ headless: false });
  const page = await browser.newPage();
  await page.goto(adminUrl, { waitUntil: 'domcontentloaded' });

  await maybeFillCredentials(page);

  const rl = readline.createInterface({ input, output });
  await rl.question('Complete WHMCS admin login in the browser, then press Enter here to save storage state...');
  rl.close();

  await fs.mkdir(path.dirname(storageState), { recursive: true });
  await page.context().storageState({ path: storageState });
  await browser.close();

  console.log(`Storage state saved: ${storageState}`);
}

async function maybeFillCredentials(page) {
  const username = process.env.WHMCS_ADMIN_USER;
  const password = process.env.WHMCS_ADMIN_PASS;
  if (!username || !password) {
    return;
  }

  const usernameLocator = page.locator([
    'input[name="username"]',
    'input[name="user"]',
    'input[name="email"]',
    'input[type="email"]',
    'input[type="text"]',
  ].join(', ')).first();

  const passwordLocator = page.locator('input[name="password"], input[type="password"]').first();

  if (await usernameLocator.count()) {
    await usernameLocator.fill(username);
  }
  if (await passwordLocator.count()) {
    await passwordLocator.fill(password);
  }
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
