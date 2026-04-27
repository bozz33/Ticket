import Link from "next/link";

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
            Un front public unifie, un backoffice tenant autonome et des parcours de conversion
            coherents sur tous les modules.
          </p>
          <Link className="button" href={platform.organizerCtaUrl}>
            Commencer l'onboarding
          </Link>
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
            <p>Une meme base produit pour plusieurs modules metier sans casser l'experience publique.</p>
          </article>
          <article className="explore-tile">
            <h2>Pages organisateur</h2>
            <p>Chaque tenant dispose d'une vitrine publique dediee dans le portail commun.</p>
          </article>
        </div>
      </section>
    </>
  );
}
