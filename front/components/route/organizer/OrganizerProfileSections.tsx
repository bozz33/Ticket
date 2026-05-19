import { SectionHeader } from "@/components/route/SectionHeader";

import type { OrganizerViewOrganizer } from "./types";

type OrganizerProfileSectionsProps = {
  organizer: OrganizerViewOrganizer;
};

export function OrganizerProfileSections({ organizer }: OrganizerProfileSectionsProps) {
  return (
    <div className="organizer-profile__main">
      <section className="detail-block">
        <SectionHeader
          eyebrow="À propos"
          title={organizer.legalName}
          description="Une page publique claire pour présenter l'entreprise, rassurer les acheteurs et rendre les informations immédiatement lisibles."
        />
        <div className="prose">
          <p>{organizer.description}</p>
        </div>
      </section>

      <section className="detail-block">
        <SectionHeader eyebrow="Informations" title="Coordonnées et présence publique" />
        <div className="organizer-profile__facts">
          <div>
            <span>Ville</span>
            <strong>
              {organizer.city}, {organizer.country}
            </strong>
          </div>
          <div>
            <span>E-mail</span>
            <strong>{organizer.supportEmail}</strong>
          </div>
          <div>
            <span>Téléphone</span>
            <strong>{organizer.supportPhone}</strong>
          </div>
          <div>
            <span>Site web</span>
            <strong>{organizer.websiteUrl ?? "À définir"}</strong>
          </div>
        </div>
      </section>
    </div>
  );
}
