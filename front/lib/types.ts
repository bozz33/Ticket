export type ModuleRoute =
  | "evenements"
  | "formations"
  | "stands"
  | "appels-a-projets"
  | "crowdfunding";

export type SortOption = "popular" | "recent" | "price";

export interface SearchFilters {
  module?: ModuleRoute | "all";
  q?: string;
  category?: string;
  city?: string;
  price?: "all" | "free" | "paid";
  sort?: SortOption;
}

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

export interface OfferTier {
  id: string;
  title: string;
  subtitle?: string;
  price: number;
  currency: string;
  remaining?: number;
  quantityLabel?: string;
  ctaLabel: string;
  perks: string[];
}

export interface TimelineEntry {
  label: string;
  dateLabel: string;
  description: string;
}

export interface PersonEntry {
  name: string;
  role: string;
  imageUrl: string;
}

export interface FaqEntry {
  question: string;
  answer: string;
}

export interface StatEntry {
  label: string;
  value: string;
}

export interface PublicContent {
  id: string;
  module: ModuleRoute;
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
}

export interface PlatformConfiguration {
  brandName: string;
  supportEmail: string;
  supportPhone: string;
  currencyCode: string;
  accountUrl: string;
  organizerCtaUrl: string;
  reassuranceItems: string[];
  paymentMethods: string[];
  featureFlags: string[];
}

export interface ListingData {
  module: ModuleRoute;
  title: string;
  description: string;
  heroImageUrl: string;
  items: PublicContent[];
  filters: SearchFilters;
}

export interface SearchSuggestion {
  title: string;
  href: string;
  module: ModuleRoute;
  city: string;
  category: string;
}
