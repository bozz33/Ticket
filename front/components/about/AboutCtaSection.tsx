import Link from "next/link";

export function AboutCtaSection() {
  return (
    <section className="section section--light">
      <div className="shell about-cta-band">
        <div>
          <p className="eyebrow">Aller plus loin</p>
          <h2>Publier, organiser, convertir et suivre sur une seule plateforme</h2>
          <p className="section-copy">
            Ticket est pensé pour les organisations qui veulent une présence publique plus sérieuse, et pour les
            acheteurs qui attendent un parcours plus clair, du catalogue jusqu&apos;au reçu ou au pass d&apos;accès.
          </p>
        </div>

        <div className="about-cta-band__actions">
          <Link className="button" href="/recherche">
            Explorer le catalogue
          </Link>
          <Link className="button button--ghost" href="/devenir-organisateur">
            Devenir organisateur
          </Link>
          <Link className="button button--ghost" href="/contact">
            Parler au support
          </Link>
        </div>
      </div>
    </section>
  );
}
