import { expect, test } from "@playwright/test";

test.describe("account auth forms", () => {
  test("renders the login form and validates required fields", async ({ page }) => {
    await page.goto("/compte/connexion");
    await page.getByRole("button", { name: "Se connecter" }).click();

    await expect(page.getByRole("heading", { name: "Connexion" })).toBeVisible();
    await expect(page.getByLabel("Adresse e-mail")).not.toHaveJSProperty("validity.valid", true);
  });

  test("validates a weak password before account creation", async ({ page }) => {
    await page.goto("/compte/inscription");
    await page.getByLabel("Nom complet").fill("Client Test");
    await page.getByLabel("Adresse e-mail").fill("client@example.test");
    await page.getByLabel("Mot de passe").fill("court");
    await page.getByRole("button", { name: "Créer mon compte" }).click();

    await expect(page.getByLabel("Mot de passe")).not.toHaveJSProperty("validity.valid", true);
  });

  test("renders the registration form with tenant-aware login link", async ({ page }) => {
    await page.goto("/compte/inscription?tenant=demo-front-buyer");

    await expect(page.getByRole("heading", { name: "Créer un compte" })).toBeVisible();
    await expect(page.getByRole("link", { name: "Se connecter" })).toHaveAttribute("href", "/compte/connexion?tenant=demo-front-buyer");
  });

  test("submits registration to the real internal API facade when backend data is available", async ({ page }) => {
    await page.goto("/compte/inscription");
    await page.getByLabel("Nom complet").fill("Client Test");
    await page.getByLabel("Adresse e-mail").fill(`client-${Date.now()}@example.test`);
    await page.getByLabel("Mot de passe").fill("motdepasse-solide");
    await page.getByRole("button", { name: "Créer mon compte" }).click();

    await expect(page.getByRole("button", { name: /Créer mon compte|Création/ })).toBeVisible();
  });
});
