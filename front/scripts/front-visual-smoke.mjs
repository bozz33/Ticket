import { chromium } from "playwright";
import { mkdirSync } from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const baseURL = process.env.PLAYWRIGHT_BASE_URL || "http://127.0.0.1:3000";
const outputDir = path.join(root, ".screenshots", "qa");
const pages = ["/", "/evenements", "/recherche?q=formation", "/compte/connexion", "/devenir-organisateur/inscription"];
const viewports = [
  { name: "desktop", width: 1440, height: 1000 },
  { name: "mobile", width: 390, height: 844 },
];

mkdirSync(outputDir, { recursive: true });

const browser = await chromium.launch({
  channel: process.platform === "win32" && !process.env.CI ? "chrome" : undefined,
});
const failures = [];

try {
  for (const viewport of viewports) {
    const context = await browser.newContext({ viewport });

    for (const pathname of pages) {
      const page = await context.newPage();
      const response = await page.goto(new URL(pathname, baseURL).toString(), { waitUntil: "domcontentloaded" });

      if (!response || response.status() >= 500) {
        failures.push(`${viewport.name} ${pathname} returned ${response?.status() ?? "no response"}.`);
      }

      await page.screenshot({
        path: path.join(outputDir, `${viewport.name}-${pathname.replace(/[^a-z0-9]+/gi, "-").replace(/^-|-$/g, "") || "home"}.png`),
        fullPage: true,
      });

      await page.close();
    }

    await context.close();
  }
} finally {
  await browser.close();
}

if (failures.length > 0) {
  process.stderr.write(`${failures.length} visual smoke check(s) failed:\n`);
  for (const failure of failures) {
    process.stderr.write(`- ${failure}\n`);
  }
  process.exit(1);
}

process.stdout.write(`Visual smoke screenshots written to ${path.relative(root, outputDir)}.\n`);
