import { OrganizerFollowCard } from "@/components/OrganizerFollowCard";
import type { OrganizerCatalogStats } from "@/lib/types";

import type { OrganizerViewOrganizer } from "./types";

type OrganizerHeroProps = {
  organizer: OrganizerViewOrganizer;
  stats: OrganizerCatalogStats;
};

export function OrganizerHero({ organizer, stats }: OrganizerHeroProps) {
  const eventCount = stats.byModule.evenements ?? 0;

  return (
    <section className="organizer-hero">
      <img alt={organizer.name} className="organizer-hero__image" src={organizer.bannerUrl} />
      <div className="shell organizer-hero__content organizer-profile__hero">
        <div className="organizer-profile__hero-copy">
          <div className="organizer-hero__identity">
            <img alt={organizer.name} decoding="async" loading="lazy" src={organizer.logoUrl} />
            <div>
              <p className="eyebrow">Organisateur public</p>
              <h1>{organizer.name}</h1>
              <p>{organizer.tagline}</p>
              <div className="detail-hero__facts">
                <span>
                  {organizer.city}, {organizer.country}
                </span>
                <span>{organizer.followers} abonnés</span>
                <span>{organizer.verified ? "Profil vérifié" : "Profil public"}</span>
              </div>
            </div>
          </div>
          <p className="organizer-profile__lede">{organizer.description}</p>
          <div className="organizer-profile__hero-actions">
            {organizer.websiteUrl ? (
              <a className="button" href={organizer.websiteUrl} rel="noreferrer" target="_blank">
                Visiter le site
              </a>
            ) : null}
            <a className="button button--ghost-light" href={`mailto:${organizer.supportEmail}`}>
              Contacter l&apos;organisation
            </a>
          </div>
        </div>
        <aside className="organizer-profile__hero-card">
          <OrganizerFollowCard
            initialFollowers={organizer.followers}
            organizerName={organizer.name}
            slug={organizer.slug}
          />
          <div className="organizer-profile__hero-stats organizer-profile__hero-stats--compact">
            <div>
              <strong>{eventCount}</strong>
              <span>événements publiés</span>
            </div>
            <div>
              <strong>{stats.paid}</strong>
              <span>publications payantes</span>
            </div>
            <div>
              <strong>{stats.free}</strong>
              <span>publications gratuites</span>
            </div>
            <div>
              <strong>{organizer.socialLinks.length}</strong>
              <span>liens publics</span>
            </div>
          </div>
        </aside>
      </div>
    </section>
  );
}
