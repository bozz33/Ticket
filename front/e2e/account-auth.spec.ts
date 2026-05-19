import { expect, test } from "@playwright/test";

test.describe("account auth forms", () => {
  test("shows backend validation errors on login", async ({ page }) => {
    await page.route("**/api/account/login", async (route) => {
      await route.fulfill({
        status: 401,
        contentType: "application/json",
        body: JSON.stringify({ error: "Identifiants incorrects." }),
      });
    });

    await page.goto("/compte/connexion");
    await page.getByLabel("Adresse e-mail").fill("client@example.test");
    await page.getByLabel("Mot de passe").fill("wrong-password");
    await page.getByRole("button", { name: "Se connecter" }).click();

    await expect(page.getByText("Identifiants incorrects.")).toBeVisible();
  });

  test("validates a weak password before account creation", async ({ page }) => {
    await page.goto("/compte/inscription");
    await page.getByLabel("Nom complet").fill("Client Test");
    await page.getByLabel("Adresse e-mail").fill("client@example.test");
    await page.getByLabel("Mot de passe").fill("court");
    await page.getByRole("button", { name: "Créer mon compte" }).click();

    await expect(page.getByText("Le mot de passe doit contenir au moins 8 caractères.")).toBeVisible();
  });

  test("submits a valid registration payload through the internal API facade", async ({ page }) => {
    let submittedBody: unknown = null;

    await page.route("**/api/account/register", async (route) => {
      submittedBody = route.request().postDataJSON();
      await route.fulfill({
        status: 201,
        contentType: "application/json",
        body: JSON.stringify({ user: { id: "buyer-1", name: "Client Test", email: "client@example.test" } }),
      });
    });

    await page.goto("/compte/inscription?tenant=demo-front-buyer");
    await page.getByLabel("Nom complet").fill("Client Test");
    await page.getByLabel("Adresse e-mail").fill("client@example.test");
    await page.getByLabel("Mot de passe").fill("motdepasse-solide");
    await page.getByRole("button", { name: "Créer mon compte" }).click();

    await expect.poll(() => submittedBody).toMatchObject({
      name: "Client Test",
      email: "client@example.test",
      tenant: "demo-front-buyer",
    });
  });
});
