import { expect, test } from "@playwright/test";

test.describe("high-value public forms", () => {
  test.describe.configure({ mode: "serial" });

  test("requests a password reset through the account facade", async ({ page }) => {
    await page.route("**/api/account/forgot-password", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({ message: "Lien de réinitialisation envoyé." }),
      });
    });

    await page.goto("/compte/reinitialisation?tenant=demo-front-buyer");
    const email = page.getByLabel("Adresse e-mail");
    await expect(email).toBeVisible();
    await email.fill("client@example.test");
    await expect(email).toHaveValue("client@example.test");
    const submit = page.getByRole("button", { name: "Envoyer le lien" });
    await expect(submit).toBeEnabled();
    await submit.click();

    await expect(page.getByText("Lien de réinitialisation envoyé.")).toBeVisible();
  });

  test("validates organizer onboarding before calling the API", async ({ page }) => {
    await page.goto("/devenir-organisateur/inscription");
    await fillOrganizerRegistration(page, {
      orgName: "AC",
      email: "mauvais-email",
      password: "court",
    });
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
    await fillOrganizerRegistration(page, {
      orgName: "Association Demo",
      email: "admin@example.test",
      password: "motdepasse-solide",
    });
    await page.getByRole("button", { name: "Créer mon espace organisateur" }).click();

    await expect(page.getByText("Votre espace est prêt")).toBeVisible();
  });
});

async function fillOrganizerRegistration(
  page: import("@playwright/test").Page,
  values: { orgName: string; email: string; password: string },
) {
  const orgName = page.getByLabel("Nom de votre organisation");
  const email = page.getByLabel("Adresse e-mail administrateur");
  const password = page.getByLabel("Mot de passe");

  await expect(orgName).toBeVisible();
  await expect(orgName).toBeEnabled();
  await expect(email).toBeEnabled();
  await expect(password).toBeEnabled();
  await orgName.fill(values.orgName);
  await email.fill(values.email);
  await password.fill(values.password);
  await expect(orgName).toHaveValue(values.orgName);
  await expect(email).toHaveValue(values.email);
  await expect(password).toHaveValue(values.password);
}
