const fs = require('node:fs');
const path = require('node:path');
const { analyzeSnapshot } = require('../lib/selector-analysis');

const artifactDir = path.resolve(__dirname, '..', 'selector-artifacts');
const targets = [
  { name: 'servers', path: 'configservers.php' },
  { name: 'products', path: 'configproducts.php' },
];

let failed = false;

for (const target of targets) {
  const file = path.join(artifactDir, `${target.name}.json`);
  if (!fs.existsSync(file)) {
    console.error(`[FAIL] ${target.name}: missing ${file}`);
    failed = true;
    continue;
  }

  const snapshot = JSON.parse(fs.readFileSync(file, 'utf8'));
  const analysis = analyzeSnapshot(snapshot, target.path);

  if (!analysis.ok) {
    failed = true;
    console.error(`[FAIL] ${target.name}`);
    for (const error of analysis.errors) {
      console.error(`  - ${error}`);
    }
  } else {
    console.log(`[OK] ${target.name}`);
  }

  for (const warning of analysis.warnings) {
    console.warn(`  [WARN] ${warning}`);
  }

  console.log(`  forms: ${analysis.candidates.forms.length}`);
  console.log(`  buttons: ${analysis.candidates.buttons.length}`);
  console.log(`  links: ${analysis.candidates.links.length}`);
}

process.exit(failed ? 1 : 0);
