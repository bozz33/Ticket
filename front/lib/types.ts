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

// ─── Panel acheteur ──────────────────────────────────────────────────────────

export type OrderStatus = "pending" | "confirmed" | "cancelled" | "refunded";
export type ReceiptStatus = "issued" | "cancelled" | "refunded";
export type AccessPassStatus = "active" | "used" | "revoked" | "expired";
export type AccessPassType =
  | "event_ticket"
  | "training_enrollment"
  | "stand_reservation"
  | "purchase_pass";
export type ScanResult =
  | "granted"
  | "already_used"
  | "revoked"
  | "expired"
  | "not_found"
  | "denied";

export interface AccountUser {
  id: number;
  name: string;
  email: string;
}

export interface AccountOffer {
  id: number;
  name: string;
  offer_type: string;
}

export interface AccountOrder {
  id: number;
  public_id: string;
  reference: string;
  transaction_reference: string;
  status: OrderStatus;
  quantity: number;
  unit_amount: number;
  total_amount: number;
  currency_code: string;
  buyer_name: string | null;
  buyer_email: string | null;
  buyer_phone: string | null;
  offer: AccountOffer | null;
  receipt: AccountReceipt | null;
  access_passes: AccountAccessPass[];
  access_passes_count: number;
  created_at: string;
  meta: Record<string, unknown> | null;
}

export interface AccountReceipt {
  id: number;
  public_id: string;
  reference: string;
  status: ReceiptStatus;
  total_amount: number;
  currency_code: string;
  buyer_name: string | null;
  buyer_email: string | null;
  issued_at: string | null;
  created_at: string;
  order: AccountOrder | null;
  meta: Record<string, unknown> | null;
}

export interface AccountAccessPass {
  id: number;
  public_id: string;
  access_code: string;
  type: AccessPassType;
  status: AccessPassStatus;
  holder_name: string | null;
  holder_email: string | null;
  used_at: string | null;
  expires_at: string | null;
  revoked_at: string | null;
  revocation_reason: string | null;
  order: { reference: string } | null;
  offer: { name: string } | null;
  scans_count?: number;
  created_at: string;
  meta: Record<string, unknown> | null;
}

export interface PublicPassVerification {
  public_id: string;
  type: AccessPassType;
  type_label: string;
  status: AccessPassStatus;
  holder_name: string | null;
  used_at: string | null;
  expires_at: string | null;
  qr_payload: { code: string; type: string; public_id: string };
}

