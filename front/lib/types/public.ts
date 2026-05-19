import type { FrontMenuLocation, FrontPageData, NavigationLink } from "./cms";
import type { FaqEntry, ModuleRoute, OfferTier, PersonEntry, SearchFilters, StatEntry, TimelineEntry } from "./shared";

export interface OrganizerProfile {
  slug: string;
  name: string;
  legalName: string;
  tagline: string;
  description: string;
  city: string;
  country: string;
  verified: boolean;
  followers: number;
  accentColor: string;
  logoUrl: string;
  bannerUrl: string;
  websiteUrl?: string;
  supportEmail: string;
  supportPhone: string;
  socialLinks: Array<{
    label: string;
    url: string;
  }>;
}

export interface OrganizerCatalogStats {
  total: number;
  free: number;
  paid: number;
  byModule: Partial<Record<ModuleRoute, number>>;
}

export interface OrganizerCatalogPageData {
  organizer: OrganizerProfile;
  items: PublicContent[];
  filters: SearchFilters;
  currentPage: number;
  totalItems: number;
  totalPages: number;
  categories: string[];
  cities: string[];
  stats: OrganizerCatalogStats;
}

export interface PublicReferenceCountry {
  iso2: string;
  name: string;
  phone_code?: string | null;
}

export interface PublicReferenceCity {
  id: number;
  name: string;
  slug: string;
  meta?: {
    state_name?: string;
    state_code?: string;
    latitude?: string;
    longitude?: string;
    timezone?: string;
    is_capital?: boolean;
  };
  country?: {
    iso2?: string;
    name?: string;
    phone_code?: string | null;
  };
}

export interface CallForProjectApplicationOption {
  value: string;
  label: string;
}

export interface CallForProjectPaymentOffer {
  id: string;
  title: string;
  price: number;
  currency: string;
  cta_label: string;
}

export interface CallForProjectApplicationField {
  key: string;
  type:
    | "text"
    | "textarea"
    | "email"
    | "date"
    | "number"
    | "country"
    | "city"
    | "phone"
    | "radio"
    | "checkbox_group"
    | "boolean"
    | "file";
  label: string;
  required?: boolean;
  visible?: boolean;
  max_length?: number;
  min?: number;
  max?: number;
  max_size_mb?: number;
  autocomplete?: string;
  accept?: string[];
  options?: CallForProjectApplicationOption[];
  country_field?: string;
  must_be_true?: boolean;
  true_label?: string;
  false_label?: string;
  step?: string;
  section?: string;
  section_title?: string;
  section_description?: string;
  column_span?: 1 | 2;
  placeholder?: string;
  help_text?: string;
}

export interface CallForProjectApplicationStep {
  key: string;
  title: string;
  description?: string;
}

export interface CallForProjectApplicationForm {
  version: number;
  title: string;
  description?: string;
  submit_label?: string;
  success_message?: string;
  steps?: CallForProjectApplicationStep[];
  fields: CallForProjectApplicationField[];
  payment?: {
    has_offers: boolean;
    has_paid_offers: boolean;
    requires_receipt: boolean;
    offers: CallForProjectPaymentOffer[];
  };
}

export interface PublicContent {
  id: string;
  module: ModuleRoute;
  moduleTitle: string;
  moduleSingular: string;
  moduleCta: string;
  slug: string;
  title: string;
  eyebrow: string;
  summary: string;
  description: string;
  category: string;
  city: string;
  country: string;
  venueName?: string;
  address?: string;
  format?: "presentiel" | "hybride" | "online";
  startsAt?: string;
  endsAt?: string;
  applicationOpensAt?: string;
  deadlineAt?: string;
  publishedAt: string;
  coverImageUrl: string;
  gallery: string[];
  priceFrom: number;
  currency: string;
  isFree: boolean;
  publicStatus: string;
  featured: boolean;
  popular: boolean;
  likesCount: number;
  badges: string[];
  highlights: string[];
  organizerSlug: string;
  organizers: PersonEntry[];
  speakers: PersonEntry[];
  stats: StatEntry[];
  tiers: OfferTier[];
  timeline: TimelineEntry[];
  faq: FaqEntry[];
  program: string[];
  conditions: string[];
  requiredDocuments: string[];
  progressCurrent?: number;
  progressTarget?: number;
  backers?: number;
  capacity?: number;
  remainingSeats?: number;
  applicationForm?: CallForProjectApplicationForm | null;
}

export interface PlatformConfiguration {
  brandName: string;
  logoUrl?: string;
  footerDescription?: string;
  supportEmail: string;
  supportPhone: string;
  currencyCode: string;
  accountUrl: string;
  organizerCtaUrl: string;
  reassuranceItems: string[];
  paymentMethods: string[];
  socialLinks: Array<{
    label: string;
    url: string;
  }>;
  menus: Record<FrontMenuLocation, NavigationLink[]>;
  featureFlags: string[];
  usersCount: number;
  defaultTenant?: {
    public_id?: string;
    name?: string;
    slug?: string;
  } | null;
}

export interface ListingData {
  page?: FrontPageData | null;
  module: ModuleRoute;
  title: string;
  singular: string;
  description: string;
  heroImageUrl: string;
  items: PublicContent[];
  filters: SearchFilters;
  currentPage: number;
  totalItems: number;
  totalPages: number;
  categories?: string[];
  cities?: string[];
}

export interface SearchSuggestion {
  title: string;
  href: string;
  module: ModuleRoute;
  moduleTitle: string;
  city: string;
  category: string;
}

export interface AboutModuleCard {
  module: ModuleRoute;
  count: number;
  image: string;
  title: string;
  description: string;
  href: string;
}

export interface PublicContentSummary {
  totalItems: number;
  activeCategories: number;
  activeCities: number;
  activeCountries: number;
  offerCount: number;
  freeItems: number;
  paidItems: number;
  galleryImages: string[];
  moduleCards: AboutModuleCard[];
}

export interface CategoryOverviewEntry {
  category: string;
  count: number;
  sample: PublicContent | null;
}

export interface CityOverviewEntry {
  city: string;
  count: number;
  sample: PublicContent | null;
}

export interface SpeakerHighlightEntry extends PersonEntry {
  itemTitle: string;
  itemSlug: string;
  itemModule: ModuleRoute;
  category: string;
  city: string;
  organizerSlug: string;
  organizerName: string;
  organizerLogoUrl: string;
}
