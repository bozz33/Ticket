import { expect, test } from "@playwright/test";

test.describe("checkout cart smoke", () => {
  test("exposes checkout entry points without anonymous payment mutation", async ({ page }) => {
    await page.goto("/evenements");

    const detailLink = page.locator('a[href*="/evenements/"]').first();
    await expect(detailLink).toBeVisible();
    await detailLink.click();

    await expect(page.locator("main")).toBeVisible();
    await expect(page.locator("body")).toContainText(/réserver|ticket|billet|offre/i);
  });
});
