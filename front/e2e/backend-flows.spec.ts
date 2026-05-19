import { expect, test } from "@playwright/test";

const fullBackendEnabled = process.env.E2E_FULL_BACKEND === "1";
const tenant = process.env.E2E_TENANT || "demo-front-buyer";
const moduleName = process.env.E2E_CONTENT_MODULE || "evenements";
const slug = process.env.E2E_CONTENT_SLUG || "summit-demo-free-2026";
const orderReference = process.env.E2E_ORDER_REF || "";
const receiptReference = process.env.E2E_RECEIPT_REF || "";

test.describe("backend-backed buyer journeys", () => {
  test.skip(!fullBackendEnabled, "Set E2E_FULL_BACKEND=1 with seeded backend data to run full buyer journeys.");

  test("opens checkout for a seeded content item", async ({ page }) => {
    await page.goto(`/checkout/${moduleName}/${slug}?tenant=${tenant}`);

    await expect(page.locator("main")).toBeVisible();
    await expect(page.getByRole("button", { name: /continuer|payer|réserver/i })).toBeVisible();
  });

  test("opens a seeded receipt verification page", async ({ page }) => {
    test.skip(!receiptReference, "E2E_RECEIPT_REF is required.");

    await page.goto(`/verifier/recu/${tenant}/${receiptReference}`);

    await expect(page.locator("main")).toBeVisible();
    await expect(page.locator("body")).toContainText(receiptReference);
  });

  test("opens a seeded account order and refund workflow", async ({ page }) => {
    test.skip(!orderReference, "E2E_ORDER_REF is required.");

    await page.goto(`/compte/commandes/${orderReference}`);

    await expect(page.locator("main")).toBeVisible();
    await expect(page.locator("body")).toContainText(orderReference);
  });

  test("opens a call-for-project application form", async ({ page }) => {
    await page.goto(`/appels-a-projets/${slug}/postuler?tenant=${tenant}`);

    await expect(page.locator("main")).toBeVisible();
    await expect(page.locator("form")).toBeVisible();
  });
});
