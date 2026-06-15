import fs from 'node:fs/promises';
import path from 'node:path';
import type { Page } from '@playwright/test';

export type SelectorSnapshot = {
  name: string;
  url: string;
  title: string;
  capturedAt: string;
  headings: string[];
  forms: Array<{
    id: string | null;
    name: string | null;
    action: string | null;
    method: string | null;
    inputs: Array<{
      tag: string;
      type: string | null;
      id: string | null;
      name: string | null;
      placeholder: string | null;
      ariaLabel: string | null;
      label: string | null;
    }>;
  }>;
  buttons: Array<{
    text: string;
    id: string | null;
    name: string | null;
    type: string | null;
  }>;
  links: Array<{
    text: string;
    href: string | null;
  }>;
};

export async function captureSelectorSnapshot(page: Page, name: string): Promise<SelectorSnapshot> {
  return page.evaluate((snapshotName) => {
    const clean = (value: string | null | undefined): string | null => {
      if (!value) return null;
      const normalized = value.replace(/\s+/g, ' ').trim();
      return normalized === '' ? null : normalized.slice(0, 160);
    };

    const inputLabel = (element: Element): string | null => {
      const id = element.getAttribute('id');
      if (id) {
        const label = document.querySelector(`label[for="${CSS.escape(id)}"]`);
        if (label?.textContent) return clean(label.textContent);
      }

      const parentLabel = element.closest('label');
      return clean(parentLabel?.textContent);
    };

    const forms = Array.from(document.querySelectorAll('form')).slice(0, 20).map((form) => ({
      id: clean(form.getAttribute('id')),
      name: clean(form.getAttribute('name')),
      action: clean(form.getAttribute('action')),
      method: clean(form.getAttribute('method')),
      inputs: Array.from(form.querySelectorAll('input, select, textarea')).slice(0, 80).map((input) => ({
        tag: input.tagName.toLowerCase(),
        type: clean(input.getAttribute('type')),
        id: clean(input.getAttribute('id')),
        name: clean(input.getAttribute('name')),
        placeholder: clean(input.getAttribute('placeholder')),
        ariaLabel: clean(input.getAttribute('aria-label')),
        label: inputLabel(input),
      })),
    }));

    const buttons = Array.from(document.querySelectorAll('button, input[type="submit"], input[type="button"]'))
      .slice(0, 80)
      .map((button) => ({
        text: clean(button.textContent || button.getAttribute('value')) ?? '',
        id: clean(button.getAttribute('id')),
        name: clean(button.getAttribute('name')),
        type: clean(button.getAttribute('type')),
      }));

    const links = Array.from(document.querySelectorAll('a'))
      .slice(0, 120)
      .map((link) => ({
        text: clean(link.textContent) ?? '',
        href: clean(link.getAttribute('href')),
      }))
      .filter((link) => link.text !== '' || link.href !== null);

    const headings = Array.from(document.querySelectorAll('h1, h2, h3'))
      .slice(0, 40)
      .map((heading) => clean(heading.textContent))
      .filter((heading): heading is string => heading !== null);

    return {
      name: snapshotName,
      url: window.location.href,
      title: document.title,
      capturedAt: new Date().toISOString(),
      headings,
      forms,
      buttons,
      links,
    };
  }, name);
}

export async function writeSelectorSnapshot(filePath: string, snapshot: SelectorSnapshot): Promise<void> {
  await fs.mkdir(path.dirname(filePath), { recursive: true });
  await fs.writeFile(filePath, `${JSON.stringify(snapshot, null, 2)}\n`, 'utf8');
}
