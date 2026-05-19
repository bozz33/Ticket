import { OrganizerAside } from "./OrganizerAside";
import { OrganizerProfileSections } from "./OrganizerProfileSections";
import type { OrganizerViewOrganizer } from "./types";

type OrganizerBodySectionProps = {
  organizer: OrganizerViewOrganizer;
};

export function OrganizerBodySection({ organizer }: OrganizerBodySectionProps) {
  return (
    <section className="section">
      <div className="shell organizer-profile__body">
        <OrganizerProfileSections organizer={organizer} />
        <OrganizerAside organizer={organizer} />
      </div>
    </section>
  );
}
