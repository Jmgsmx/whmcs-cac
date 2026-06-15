const fs = require('node:fs');
const path = require('node:path');

const requiredResources = ['server_group', 'server', 'product_group'];
const defaultManifestPath = path.resolve(__dirname, '..', 'selectors', 'ui-selector-manifest.json');

function loadSelectorManifest(manifestPath = process.env.WHMCS_UI_SELECTOR_MANIFEST || defaultManifestPath) {
  if (!fs.existsSync(manifestPath)) {
    throw new Error(`UI selector manifest not found: ${manifestPath}`);
  }

  const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
  const validation = validateSelectorManifest(manifest);
  if (!validation.ok) {
    throw new Error(`UI selector manifest is invalid: ${validation.errors.join('; ')}`);
  }

  return manifest;
}

function validateSelectorManifest(manifest) {
  const errors = [];

  if (!manifest || typeof manifest !== 'object') {
    return { ok: false, errors: ['manifest must be an object'] };
  }

  if (manifest.version !== 1) {
    errors.push('version must be 1');
  }

  for (const resource of requiredResources) {
    const selectors = manifest.resources?.[resource];
    if (!selectors) {
      errors.push(`resources.${resource} is required`);
      continue;
    }

    if (!selectors.page || typeof selectors.page !== 'string') {
      errors.push(`resources.${resource}.page is required`);
    }
    if (!selectors.form || typeof selectors.form !== 'string') {
      errors.push(`resources.${resource}.form is required`);
    }
    if (!selectors.submit || typeof selectors.submit !== 'string') {
      errors.push(`resources.${resource}.submit is required`);
    }
    if (!selectors.fields || typeof selectors.fields !== 'object') {
      errors.push(`resources.${resource}.fields is required`);
    }
  }

  return {
    ok: errors.length === 0,
    errors,
  };
}

function selectorsFor(manifest, resourceType) {
  const selectors = manifest.resources?.[resourceType];
  if (!selectors) {
    throw new Error(`No selectors configured for ${resourceType}`);
  }
  return selectors;
}

module.exports = {
  defaultManifestPath,
  loadSelectorManifest,
  selectorsFor,
  validateSelectorManifest,
};
