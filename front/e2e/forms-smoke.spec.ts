import { expect, test } from "@playwright/test";

test.describe("high-value public forms", () => {
  test("requests a password reset through the account facade", async ({ page }) => {
    await page.route("**/api/account/forgot-password", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({ message: "Lien de réinitialisation envoyé." }),
      });
    });

    await page.goto("/compte/reinitialisation?tenant=demo-front-buyer");
    await page.getByLabel("Adresse e-mail").fill("client@example.test");
    await page.getByRole("button", { name: "Envoyer le lien" }).click();

    await expect(page.getByText("Lien de réinitialisation envoyé.")).toBeVisible();
  });

  test("validates organizer onboarding before calling the API", async ({ page }) => {
    await page.goto("/devenir-organisateur/inscription");
    await page.getByLabel("Nom de votre organisation").fill("AC");
    await page.getByLabel("Adresse e-mail administrateur").fill("mauvais-email");
    await page.getByLabel("Mot de passe").fill("court");
    await page.getByRole("button", { name: "Créer mon espace organisateur" }).click();

    await expect(page.getByText("Adresse e-mail invalide.")).toBeVisible();
  });

  test("submits organizer onboarding through the internal API facade", async ({ page }) => {
    await page.route("**/api/onboarding/register", async (route) => {
      await route.fulfill({
        status: 201,
        contentType: "application/json",
        body: JSON.stringify({
          tenant: {
            slug: "demo-organizer",
            access_url: "https://organizer.example.test/admin",
            login_url: "https://organizer.example.test/login",
          },
        }),
      });
    });

    await page.goto("/devenir-organisateur/inscription");
    await page.getByLabel("Nom de votre organisation").fill("Association Demo");
    await page.getByLabel("Adresse e-mail administrateur").fill("admin@example.test");
    await page.getByLabel("Mot de passe").fill("motdepasse-solide");
    await page.getByRole("button", { name: "Créer mon espace organisateur" }).click();

    await expect(page.getByText("Votre espace est prêt")).toBeVisible();
  });
});
