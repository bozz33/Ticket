import Link from "next/link";

import type { PlatformConfiguration, PublicContentSummary } from "@/lib/types";

import type { AboutOrganizerEntry } from "./types";

type AboutOrganizerTrustSectionProps = {
  organizers: AboutOrganizerEntry[];
  platform: PlatformConfiguration;
  summary: PublicContentSummary;
};

export function AboutOrganizerTrustSection({ organizers, platform, summary }: AboutOrganizerTrustSectionProps) {
  return (
    <section className="section">
      <div className="shell about-trust-grid">
        <article className="detail-block">
          <p className="eyebrow">Ils publient déjà</p>
          <h2>Des organisations visibles, pas juste des contenus isolés</h2>
          <p className="section-copy">
            La plateforme met aussi en valeur les structures qui publient. Chaque organisateur peut avoir sa page
            publique, sa bannière, sa description, ses liens, son compteur d&apos;abonnés et la liste de ses contenus.
          </p>
          <div className="about-logo-cloud">
            {organizers.map(({ organizer }) => (
              <Link className="about-logo-tile" href={`/organisateurs/${organizer.slug}`} key={organizer.slug}>
                <img alt={organizer.name} src={organizer.logoUrl} />
                <span>{organizer.name}</span>
              </Link>
            ))}
          </div>
        </article>

        <aside className="detail-block about-platform-stats">
          <p className="eyebrow">En bref</p>
          <h2>Le socle opérationnel de Ticket</h2>
          <div className="organizer-profile__hero-stats organizer-profile__hero-stats--compact">
            <div>
              <strong>{platform.usersCount.toLocaleString("fr-FR")}</strong>
              <span>utilisateurs suivis</span>
            </div>
            <div>
              <strong>{summary.offerCount}</strong>
              <span>offres publiées</span>
            </div>
            <div>
              <strong>{summary.freeItems}</strong>
              <span>accès gratuits</span>
            </div>
            <div>
              <strong>{summary.paidItems}</strong>
              <span>contenus monétisés</span>
            </div>
          </div>
        </aside>
      </div>
    </section>
  );
}
