import { SectionHeader } from "@/components/route/SectionHeader";

import type { OrganizerViewOrganizer } from "./types";

type OrganizerAsideProps = {
  organizer: OrganizerViewOrganizer;
};

export function OrganizerAside({ organizer }: OrganizerAsideProps) {
  return (
    <aside className="organizer-profile__aside">
      <section className="detail-block organizer-profile__media">
        <p className="eyebrow">Présentation</p>
        <img alt={organizer.name} decoding="async" loading="lazy" src={organizer.bannerUrl} />
        <div className="organizer-profile__media-copy">
          <strong>{organizer.name}</strong>
          <span>{organizer.tagline}</span>
        </div>
      </section>

      {organizer.socialLinks.length > 0 ? (
        <section className="detail-block">
          <SectionHeader eyebrow="Réseaux" title="Suivre l'organisation" />
          <div className="sticky-panel__cta-list">
            {organizer.socialLinks.map((social) => (
              <a
                className="button button--full button--ghost"
                href={social.url}
                key={social.label}
                rel="noreferrer"
                target="_blank"
              >
                {social.label}
              </a>
            ))}
          </div>
        </section>
      ) : null}
    </aside>
  );
}
