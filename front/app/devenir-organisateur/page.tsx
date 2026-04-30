import { OrganizerRegistrationForm } from "./OrganizerRegistrationForm";
import { getPlatformConfiguration } from "@/lib/data/public";

export const dynamic = "force-dynamic";

export default async function BecomeOrganizerPage() {
  const platform = await getPlatformConfiguration();

  return (
    <>
      <section className="page-hero">
        <img
          alt="Organisation d'evenements et billetterie"
          className="page-hero__image"
          src="https://images.unsplash.com/photo-1517457373958-b7bdd4587205?auto=format&fit=crop&w=1800&q=80"
        />
        <div className="shell page-hero__content">
          <p className="eyebrow">Onboarding tenant</p>
          <h1>Publier et vendre sur la plateforme</h1>
          <p>
            Un front public unifié, un backoffice tenant autonome et des parcours de conversion
            cohérents sur tous les modules.
          </p>
        </div>
      </section>

      <section className="section">
        <div className="shell tile-grid">
          <article className="explore-tile">
            <h2>Billetterie et checkout</h2>
            <p>Vendre des billets, configurer plusieurs offres et suivre vos conversions.</p>
          </article>
          <article className="explore-tile">
            <h2>Stands, appels et crowdfunding</h2>
            <p>Une même base produit pour plusieurs modules metier sans casser l&apos;experience publique.</p>
          </article>
          <article className="explore-tile">
            <h2>Pages organisateur</h2>
            <p>Chaque tenant dispose d&apos;une vitrine publique dédiée dans le portail commun.</p>
          </article>
        </div>
      </section>

      <section className="section section--light" id="inscription">
        <div className="shell" style={{ maxWidth: "520px" }}>
          <div style={{ marginBottom: "32px" }}>
            <p className="eyebrow">Commencer maintenant</p>
            <h2 style={{ fontSize: "1.8rem", fontWeight: 700, margin: "8px 0 12px" }}>
              Créer votre espace organisateur
            </h2>
            <p style={{ color: "var(--text-soft)" }}>
              Votre espace est créé instantanément. Vous pourrez publier votre premier contenu dans les minutes qui suivent.
            </p>
          </div>
          <OrganizerRegistrationForm brandName={platform.brandName} />
        </div>
      </section>
    </>
  );
}
