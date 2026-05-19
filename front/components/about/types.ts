import type {
  FrontPageData,
  OrganizerProfile,
  PlatformConfiguration,
  PublicContent,
  PublicContentSummary,
} from "@/lib/types";

export type AboutOrganizerEntry = {
  organizer: OrganizerProfile;
  items: PublicContent[];
};

export type AboutHeroData = FrontPageData["sections"][number] | undefined;

export type AboutPageViewProps = {
  organizers: AboutOrganizerEntry[];
  page: FrontPageData | null;
  platform: PlatformConfiguration;
  summary: PublicContentSummary;
};
