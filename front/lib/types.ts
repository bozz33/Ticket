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
  dateFrom?: string;
  dateTo?: string;
  price?: "all" | "free" | "paid";
  sort?: SortOption;
  page?: number;
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

export type FrontMenuLocation =
  | "header_primary"
  | "header_utility"
  | "footer_explore"
  | "footer_platform"
  | "footer_bottom";

export interface NavigationLink {
  label: string;
  href: string;
  target?: string;
  meta?: Record<string, unknown>;
}

export type FrontPageSectionType =
  | "hero"
  | "split_overview"
  | "feature_grid"
  | "metrics"
  | "faq"
  | "contact_channels"
  | "contact_form"
  | "organizer_highlights"
  | "legal_article"
  | "onboarding_form";

export interface FrontPageSectionItem {
  title?: string;
  label?: string;
  value?: string;
  body?: string;
  href?: string;
  icon?: string;
  image_url?: string;
  meta?: Record<string, unknown>;
}

export interface FrontPageSection {
  key: string;
  type: FrontPageSectionType;
  title?: string | null;
  eyebrow?: string | null;
  body?: string | null;
  image_url?: string | null;
  primary_cta?: {
    label?: string | null;
    url?: string | null;
  };
  secondary_cta?: {
    label?: string | null;
    url?: string | null;
  };
  items: FrontPageSectionItem[];
  settings: Record<string, unknown>;
}

export interface FrontPageData {
  key: string;
  title: string;
  path: string;
  slug: string;
  template: string;
  seo: {
    title: string;
    description: string;
    image?: string | null;
  };
  meta: Record<string, unknown>;
  sections: FrontPageSection[];
}

export interface FrontPageIndexEntry {
  key: string;
  title: string;
  path: string;
  updated_at?: string | null;
  published_at?: string | null;
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

export interface CheckoutPaymentMethod {
  code: string;
  label: string;
  kind: "free" | "card" | "mobile_money";
}

export interface CheckoutPricing {
  subtotal: number;
  service_fee: number;
  total: number;
  currency: string;
  quantity: number;
  policy?: {
    module: string;
    mode: string | null;
    commission_rate: number;
    flat_fee_amount: number;
  } | null;
}

export interface CheckoutPaymentOptions {
  gateway: {
    code: string;
    name: string;
    provider: string;
  } | null;
  methods: CheckoutPaymentMethod[];
  pricing: CheckoutPricing;
  quantity: {
    min: number;
    max: number;
    max_per_account?: number | null;
  };
  offer: {
    id: string;
    title: string;
    currency: string;
    unit_amount: number;
  };
  tenant: {
    public_id: string;
    name: string;
    slug: string;
  };
}

export interface CheckoutInitializationResult {
  mode: "free" | "redirect";
  reference: string;
  status: string;
  authorization_url: string | null;
  gateway?: {
    code: string;
    name: string;
    provider: string;
  } | null;
  order?: {
    public_id: string;
    reference: string;
  } | null;
}

export interface CheckoutVerificationResult {
  reference: string;
  gateway_reference?: string | null;
  gateway_transaction_id?: string | number | null;
  status: string;
  is_successful: boolean;
  paid_at?: string | null;
  quantity: number;
  amounts: {
    gross: number;
    fees: number;
    net: number;
    currency: string;
  };
  order?: {
    public_id: string;
    reference: string;
    status: string;
  } | null;
  receipt?: {
    public_id: string;
    reference: string;
    status: string;
    gateway_reference?: string | null;
    gateway_transaction_id?: string | number | null;
  } | null;
  access_passes_count: number;
  gateway?: {
    code: string;
    name: string;
    provider?: string;
    mode?: string;
  } | null;
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
  first_name: string | null;
  last_name: string | null;
  username: string | null;
  email: string;
  email_verified: boolean;
  email_verified_at: string | null;
  phone: string | null;
  locale: string | null;
  timezone: string | null;
  avatar_url: string | null;
  last_login_at: string | null;
  unread_notifications_count: number;
  profile_completed: boolean;
  missing_profile_fields: string[];
  account_ready_for_actions: boolean;
}

export interface AccountProfileUpdateInput {
  name: string;
  first_name?: string | null;
  last_name?: string | null;
  email: string;
  phone?: string | null;
  locale?: string | null;
  timezone?: string | null;
}

export interface AccountPasswordUpdateInput {
  current_password: string;
  password: string;
  password_confirmation: string;
}

export interface AccountNotification {
  id: string;
  title: string;
  body: string;
  icon: string;
  action_url: string | null;
  read_at: string | null;
  created_at: string | null;
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
  subtotal_amount?: number;
  customer_fee_amount?: number;
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
  buyer_phone?: string | null;
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
  order: { reference: string; buyer_phone?: string | null } | null;
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

export interface PublicReceiptVerification {
  public_id: string;
  reference: string;
  status: string;
  total_amount: number;
  currency_code: string;
  issued_at: string | null;
  buyer_name: string | null;
  buyer_email_masked: string | null;
  buyer_phone_masked: string | null;
  order_reference: string | null;
  transaction_reference: string | null;
  gateway_reference: string | null;
  gateway_transaction_id?: string | number | null;
  offer_name: string | null;
  access_passes_count: number;
}

