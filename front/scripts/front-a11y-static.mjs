import { readFileSync, readdirSync, statSync } from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const failures = [];

function walk(directory, files = []) {
  for (const entry of readdirSync(directory)) {
    if (["node_modules", ".runtime", ".git"].includes(entry) || entry.startsWith(".next") || entry.startsWith(".tmp-")) {
      continue;
    }

    const fullPath = path.join(directory, entry);
    const stats = statSync(fullPath);

    if (stats.isDirectory()) {
      walk(fullPath, files);
    } else if (/\.(tsx|ts)$/.test(entry)) {
      files.push(fullPath);
    }
  }

  return files;
}

function fail(file, message) {
  failures.push(`${path.relative(root, file).replaceAll("\\", "/")}: ${message}`);
}

function hasAccessibleButtonName(markup) {
  return (
    /aria-label=/.test(markup) ||
    /aria-labelledby=/.test(markup) ||
    />\s*[^<{\s]/.test(markup) ||
    /{[^}]+}/.test(markup)
  );
}

for (const file of walk(root)) {
  const source = readFileSync(file, "utf8");

  for (const match of source.matchAll(/<img\b[^>]*>/g)) {
    const tag = match[0];

    if (!/\balt=/.test(tag)) {
      fail(file, "<img> must have an alt attribute.");
    }
  }

  for (const match of source.matchAll(/<button\b[^>]*>([\s\S]*?)<\/button>/g)) {
    const markup = match[0];

    if (!hasAccessibleButtonName(markup)) {
      fail(file, "<button> must have visible text or an accessible name.");
    }
  }

  for (const match of source.matchAll(/<(a|Link)\b[^>]*target=["']_blank["'][^>]*>/g)) {
    const tag = match[0];

    if (!/\brel=["'][^"']*noreferrer[^"']*["']/.test(tag)) {
      fail(file, 'External target="_blank" links must include rel="noreferrer".');
    }
  }

  for (const match of source.matchAll(/<(input|select|textarea)\b[^>]*>/g)) {
    const tag = match[0];
    const isHidden = /\btype=["']hidden["']/.test(tag);
    const hasId = /\bid=(["'][^"']+["']|{[^}]+})/.test(tag);
    const hasAria = /\baria-label=|\baria-labelledby=/.test(tag);

    if (!isHidden && !hasId && !hasAria) {
      fail(file, `${match[1]} controls should expose id+label or aria labelling.`);
    }
  }
}

if (failures.length > 0) {
  process.stderr.write(`${failures.length} accessibility static check(s) failed:\n`);
  for (const failure of failures) {
    process.stderr.write(`- ${failure}\n`);
  }
  process.exit(1);
}

process.stdout.write("Static accessibility checks passed.\n");
