import Link from "next/link";

import { getOrganizerHighlights, getPlatformConfiguration } from "@/lib/data/public";

export const dynamic = "force-dynamic";

export default async function AboutPage() {
  const [platform, organizers] = await Promise.all([
    getPlatformConfiguration(),
    getOrganizerHighlights(),
  ]);

  return (
    <>
      <section className="page-hero">
        <img
          alt="Public et scene pendant un evenement premium"
          className="page-hero__image"
          src="https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1800&q=80"
        />
        <div className="shell page-hero__content">
          <p className="eyebrow">A propos</p>
          <h1>Une plateforme publique pour vendre, reserver et soutenir.</h1>
          <p>
            {platform.brandName} rassemble des experiences live, des parcours de candidature, des
            offres de formation et des campagnes de financement dans un meme front public.
          </p>
        </div>
      </section>

      <section className="section">
        <div className="shell about-grid">
          <article className="detail-block">
            <p className="eyebrow">Notre approche</p>
            <h2>Des pages transactionnelles qui restent desirables.</h2>
            <p className="section-copy">
              Le projet melange le sens du spectacle de Boleto avec une logique marketplace plus
              directe, afin de garder de la chaleur visuelle sans alourdir le parcours d'achat.
            </p>
            <ul className="bullet-list">
              <li>Front public unifie pour tous les tenants</li>
              <li>Pages detail denses avec CTA visibles sans friction</li>
              <li>Checkout rassurant, mobile-first et lisible</li>
              <li>Valorisation publique des organisateurs et intervenants</li>
            </ul>
          </article>

          <aside className="sticky-panel">
            <div className="sticky-panel__price">
              <span>En bref</span>
              <strong>{platform.brandName}</strong>
            </div>
            <ul className="facts-list">
              <li>
                <strong>Support</strong>
                <span>{platform.supportEmail}</span>
              </li>
              <li>
                <strong>Paiement</strong>
                <span>{platform.paymentMethods.join(", ")}</span>
              </li>
              <li>
                <strong>Experience</strong>
                <span>Catalogue, detail, checkout et espace public organisateur</span>
              </li>
            </ul>
            <div className="sticky-panel__cta-list">
              <Link className="button button--full" href="/evenements">
                Explorer les evenements
              </Link>
              <Link className="button button--full button--ghost" href="/devenir-organisateur">
                Devenir organisateur
              </Link>
            </div>
          </aside>
        </div>
      </section>

      <section className="section section--light">
        <div className="shell">
          <div className="section-header">
            <div>
              <p className="eyebrow">Organisateurs</p>
              <h2>Des profils publics qui comptent autant que les contenus.</h2>
              <p className="section-copy">
                Chaque structure publie dans le portail commun tout en gardant sa propre vitrine.
              </p>
            </div>
          </div>

          <div className="organizer-grid">
            {organizers.map(({ organizer, items }) => (
              <article className="organizer-card" key={organizer.slug}>
                <div className="organizer-card__banner">
                  <img alt={organizer.name} src={organizer.bannerUrl} />
                </div>
                <div className="organizer-card__body">
                  <div className="organizer-card__identity">
                    <img alt={organizer.name} src={organizer.logoUrl} />
                    <div>
                      <Link href={`/organisateurs/${organizer.slug}`}>{organizer.name}</Link>
                      <p>
                        {organizer.city}, {organizer.country}
                      </p>
                    </div>
                  </div>
                  <p className="organizer-card__copy">{organizer.tagline}</p>
                  <div className="organizer-card__mini-list">
                    {items.map((item) => (
                      <Link href={`/${item.module}/${item.slug}`} key={item.id}>
                        {item.title}
                      </Link>
                    ))}
                  </div>
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>
    </>
  );
}
