import type { OrganizerCatalogStats, PublicContent, SearchFilters } from "@/lib/types";

export type OrganizerViewOrganizer = {
  slug: string;
  name: string;
  legalName: string;
  tagline: string;
  description: string;
  city: string;
  country: string;
  verified: boolean;
  followers: number;
  logoUrl: string;
  bannerUrl: string;
  websiteUrl?: string;
  supportEmail: string;
  supportPhone: string;
  socialLinks: Array<{ label: string; url: string }>;
};

export type OrganizerViewProps = {
  organizer: OrganizerViewOrganizer | null;
  items: PublicContent[];
  filters: SearchFilters;
  currentPage: number;
  totalItems: number;
  totalPages: number;
  categories: string[];
  cities: string[];
  stats: OrganizerCatalogStats;
};
