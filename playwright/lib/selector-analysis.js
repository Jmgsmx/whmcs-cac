function analyzeSnapshot(snapshot, expectedPath) {
  const errors = [];
  const warnings = [];

  if (!snapshot || typeof snapshot !== 'object') {
    return {
      ok: false,
      errors: ['Snapshot must be a JSON object'],
      warnings,
      candidates: emptyCandidates(),
    };
  }

  if (!String(snapshot.url || '').includes(expectedPath)) {
    errors.push(`Snapshot URL does not include ${expectedPath}`);
  }

  if (looksLikeLogin(snapshot)) {
    errors.push('Snapshot appears to be a login page, not an authenticated admin page');
  }

  const candidates = {
    forms: formCandidates(snapshot.forms || []),
    buttons: buttonCandidates(snapshot.buttons || []),
    links: linkCandidates(snapshot.links || []),
  };

  if (candidates.forms.length === 0) {
    warnings.push('No stable form selectors found');
  }
  if (candidates.buttons.length === 0) {
    warnings.push('No stable button selectors found');
  }

  return {
    ok: errors.length === 0,
    errors,
    warnings,
    candidates,
  };
}

function looksLikeLogin(snapshot) {
  const haystack = [
    snapshot.title,
    ...(snapshot.headings || []),
    ...(snapshot.forms || []).flatMap((form) => (form.inputs || []).flatMap((input) => [
      input.name,
      input.id,
      input.placeholder,
      input.label,
      input.type,
    ])),
  ]
    .filter(Boolean)
    .join(' ')
    .toLowerCase();

  return haystack.includes('password')
    && (haystack.includes('login') || haystack.includes('username') || haystack.includes('email'));
}

function formCandidates(forms) {
  return forms
    .map((form) => ({
      selector: stableSelector('form', form),
      action: form.action || null,
      method: form.method || null,
      inputNames: (form.inputs || []).map((input) => input.name).filter(Boolean),
    }))
    .filter((candidate) => candidate.selector !== null);
}

function buttonCandidates(buttons) {
  return buttons
    .map((button) => ({
      selector: stableSelector('button', button) || textSelector('button', button.text),
      text: button.text || '',
      type: button.type || null,
    }))
    .filter((candidate) => candidate.selector !== null);
}

function linkCandidates(links) {
  return links
    .map((link) => ({
      selector: link.href ? `a[href*="${escapeAttribute(link.href)}"]` : textSelector('a', link.text),
      text: link.text || '',
      href: link.href || null,
    }))
    .filter((candidate) => candidate.selector !== null);
}

function stableSelector(tag, item) {
  if (item.id) {
    return `${tag}#${cssIdentifier(item.id)}`;
  }
  if (item.name) {
    return `${tag}[name="${escapeAttribute(item.name)}"]`;
  }
  return null;
}

function textSelector(tag, text) {
  const cleanText = String(text || '').trim();
  if (!cleanText) {
    return null;
  }
  return `${tag}:has-text("${cleanText.replace(/"/g, '\\"')}")`;
}

function cssIdentifier(value) {
  return String(value).replace(/([^a-zA-Z0-9_-])/g, '\\$1');
}

function escapeAttribute(value) {
  return String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
}

function emptyCandidates() {
  return { forms: [], buttons: [], links: [] };
}

module.exports = {
  analyzeSnapshot,
};
