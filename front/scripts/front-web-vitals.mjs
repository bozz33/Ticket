import { chromium } from "playwright";

const baseURL = process.env.PLAYWRIGHT_BASE_URL || "http://127.0.0.1:3000";
const pages = (process.env.FRONT_PERF_PATHS || "/,/evenements,/recherche?q=formation,/compte/connexion").split(",");
const budgets = {
  domContentLoadedMs: Number(process.env.FRONT_BUDGET_DCL_MS || 4_000),
  loadMs: Number(process.env.FRONT_BUDGET_LOAD_MS || 6_000),
};
const failures = [];
const results = [];

const browser = await chromium.launch({
  channel: process.platform === "win32" && !process.env.CI ? "chrome" : undefined,
});

try {
  const context = await browser.newContext();

  for (const pathname of pages) {
    const page = await context.newPage();
    await page.goto(new URL(pathname, baseURL).toString(), { waitUntil: "load" });

    const timing = await page.evaluate(() => {
      const nav = performance.getEntriesByType("navigation")[0];

      if (!nav || !("domContentLoadedEventEnd" in nav)) {
        return null;
      }

      const entry = nav;

      return {
        domContentLoadedMs: Math.round(entry.domContentLoadedEventEnd),
        loadMs: Math.round(entry.loadEventEnd),
        transferSize: Math.round(entry.transferSize || 0),
      };
    });

    if (!timing) {
      failures.push(`${pathname}: missing navigation timing.`);
      continue;
    }

    results.push({ path: pathname, ...timing });

    if (timing.domContentLoadedMs > budgets.domContentLoadedMs) {
      failures.push(`${pathname}: DCL ${timing.domContentLoadedMs}ms > ${budgets.domContentLoadedMs}ms.`);
    }

    if (timing.loadMs > budgets.loadMs) {
      failures.push(`${pathname}: load ${timing.loadMs}ms > ${budgets.loadMs}ms.`);
    }

    await page.close();
  }

  await context.close();
} finally {
  await browser.close();
}

process.stdout.write(`${JSON.stringify({ budgets, results }, null, 2)}\n`);

if (failures.length > 0) {
  process.stderr.write(`${failures.length} web performance budget check(s) failed:\n`);
  for (const failure of failures) {
    process.stderr.write(`- ${failure}\n`);
  }
  process.exit(1);
}
