import { expect, test } from "@playwright/test";

test.describe("public front smoke", () => {
  test("loads the home page and exposes primary navigation", async ({ page }) => {
    await page.goto("/");

    await expect(page.locator("main")).toBeVisible();
    await expect(page.locator("body")).toContainText("Ticket");
    await expect(page.getByRole("navigation", { name: "Navigation principale" })).toBeVisible();
  });

  test("submits the public search form", async ({ page }) => {
    await page.goto("/");

    const search = page.locator('form[action="/recherche"] input[name="q"]').first();
    await expect(search).toBeVisible();
    await search.fill("formation");
    await page.locator('form[action="/recherche"]').first().getByRole("button").click();

    await expect(page).toHaveURL(/\/recherche\?q=formation/);
  });

  test("redirects protected account pages to login", async ({ page }) => {
    await page.goto("/compte/accueil");

    await expect(page).toHaveURL(/\/compte\/connexion/);
    await expect(page.getByRole("heading", { name: "Connexion" })).toBeVisible();
  });
});
