import type { OrganizerProfile, PublicContent } from "@/lib/types";

export type OrganizerHighlight = {
  organizer: OrganizerProfile;
  items: PublicContent[];
};
