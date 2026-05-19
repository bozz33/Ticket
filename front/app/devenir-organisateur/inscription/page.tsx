import { OrganizerRegistrationForm } from "@/components/onboarding/OrganizerRegistrationForm";
import { getPlatformConfiguration } from "@/lib/data/public";
import { createMetadata } from "@/lib/metadata";

export const revalidate = 300;

export async function generateMetadata() {
  return createMetadata({
    title: "Inscription organisateur — Ticket",
    description: "Créez votre espace organisateur Ticket et accédez à votre backoffice en quelques minutes.",
    path: "/devenir-organisateur/inscription",
  });
}

export default async function OrganizerSignupPage() {
  const platform = await getPlatformConfiguration();

  return (
    <>
      <section className="page-hero page-hero--compact">
        <div className="shell page-hero__content">
          <p className="eyebrow">Inscription organisateur</p>
          <h1>Créez votre espace organisateur</h1>
          <p>Complétez ce formulaire pour obtenir immédiatement votre espace de gestion sur Ticket.</p>
        </div>
      </section>

      <section className="section section--light">
        <div className="shell" style={{ maxWidth: "560px" }}>
          <OrganizerRegistrationForm brandName={platform.brandName} />
        </div>
      </section>
    </>
  );
}
