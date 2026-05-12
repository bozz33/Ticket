import {
  AboutModuleCard,
  CategoryOverviewEntry,
  CityOverviewEntry,
  CheckoutInitializationResult,
  CheckoutPaymentOptions,
  CheckoutVerificationResult,
  FrontMenuLocation,
  FrontPageData,
  FrontPageIndexEntry,
  ListingData,
  ModuleRoute,
  NavigationLink,
  OrganizerCatalogPageData,
  OrganizerProfile,
  PlatformConfiguration,
  PublicContent,
  PublicContentSummary,
  SearchFilters,
  SearchSuggestion,
  SpeakerHighlightEntry,
} from "@/lib/types";
import { formatDateRange, getModuleMeta, normalizeSearchParams } from "@/lib/utils";

const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "") ?? (process.env.NODE_ENV === "development" ? "http://127.0.0.1:8000" : "");
const publicTenantSlug = process.env.NEXT_PUBLIC_TENANT_SLUG?.trim() ?? "";
const defaultMenus: Record<FrontMenuLocation, NavigationLink[]> = {
  header_primary: [],
  header_utility: [],
  footer_explore: [],
  footer_platform: [],
  footer_bottom: [],
};

const defaultPlatformConfiguration: PlatformConfiguration = {
  brandName: process.env.NEXT_PUBLIC_PLATFORM_NAME || "Ticket",
  logoUrl: undefined,
  footerDescription: "Catalogue public unifie pour decouvrir, comparer et convertir sur plusieurs modules metier.",
  supportEmail: "support@ticket.africa",
  supportPhone: "+225 27 22 40 11 00",
  currencyCode: "XOF",
  accountUrl: process.env.NEXT_PUBLIC_ACCOUNT_URL || "/compte",
  organizerCtaUrl: process.env.NEXT_PUBLIC_ORGANIZER_CTA_URL || "/devenir-organisateur",
  reassuranceItems: [
    "Paiement securise et verification serveur",
    "Billets et confirmations centralises",
    "Parcours mobile optimise jusqu'au checkout",
  ],
  paymentMethods: ["Carte bancaire", "Mobile Money"],
  socialLinks: [],
  menus: defaultMenus,
  featureFlags: [],
  usersCount: 0,
};

type PublicPlatformPayload = {
  data?: PlatformConfiguration;
};

type PublicFrontPagePayload = {
  data?: FrontPageData;
};

type PublicFrontPagesIndexPayload = {
  data?: FrontPageIndexEntry[];
};

type PublicReferenceFilters = {
  categories: string[];
  cities: string[];
};

type PublicContentIndexPayload = {
  data?: PublicContent[];
  meta?: {
    current_page?: number;
    total?: number;
    total_pages?: number;
    per_page?: number;
  };
  filters?: Partial<PublicReferenceFilters>;
};

type PublicContentQuery = SearchFilters & {
  featured?: boolean;
};

type PublicOrganizerCatalogPayload = {
  data?: {
    profile?: OrganizerProfile;
    items?: PublicContent[];
    stats?: OrganizerCatalogPageData["stats"];
  };
  meta?: {
    current_page?: number;
    total?: number;
    total_pages?: number;
    per_page?: number;
  };
  filters?: Partial<PublicReferenceFilters>;
};

type PublicApiEnvelope<T> = {
  data?: T;
};

type PublicContentOverviewPayload = {
  data?: CategoryOverviewEntry[] | CityOverviewEntry[] | SpeakerHighlightEntry[];
};

type PublicContentSummaryPayload = {
  data?: PublicContentSummary;
};

let defaultPublicTenantSlugPromise: Promise<string> | null = null;
let categoryOverviewPromise: Promise<CategoryOverviewEntry[]> | null = null;
let cityOverviewPromise: Promise<CityOverviewEntry[]> | null = null;
let speakerHighlightsPromise: Promise<SpeakerHighlightEntry[]> | null = null;

async function resolvePublicTenantSlug(): Promise<string> {
  if (publicTenantSlug) {
    return publicTenantSlug;
  }

  if (!defaultPublicTenantSlugPromise) {
    defaultPublicTenantSlugPromise = (async () => {
      const payload = await fetchJson<PublicPlatformPayload>("/api/v1/public/platform/configuration");
      const slug = getStringValue(payload?.data?.defaultTenant?.slug).trim();

      return slug;
    })();
  }

  const slug = await defaultPublicTenantSlugPromise;

  if (!slug) {
    defaultPublicTenantSlugPromise = null;
  }

  return slug;
}

async function getTenantPublicPath(path: string, tenantSlug?: string): Promise<string | null> {
  const resolvedTenantSlug = tenantSlug?.trim() || (await resolvePublicTenantSlug());

  if (!resolvedTenantSlug) {
    return null;
  }

  const normalizedPath = path.startsWith("/") ? path : `/${path}`;

  return `/api/v1/public/tenants/${resolvedTenantSlug}${normalizedPath}`;
}

function getGlobalPublicPath(path: string): string {
  const normalizedPath = path.startsWith("/") ? path : `/${path}`;

  return `/api/v1/public${normalizedPath}`;
}

async function fetchJson<T>(
  path: string,
  options: {
    noStore?: boolean;
    revalidate?: number;
    headers?: HeadersInit;
  } = {},
): Promise<T | null> {
  if (!apiBaseUrl) {
    return null;
  }

  try {
    const response = await fetch(`${apiBaseUrl}${path}`, {
      cache: options.noStore ? "no-store" : "force-cache",
      next: options.noStore ? undefined : { revalidate: options.revalidate ?? 120 },
      headers: {
        Accept: "application/json",
        ...options.headers,
      },
    });

    if (!response.ok) {
      return null;
    }

    return (await response.json()) as T;
  } catch {
    return null;
  }
}

function normalizeStringArray(values: unknown): string[] {
  return Array.isArray(values)
    ? values.map((value) => String(value).trim()).filter((value) => value.length > 0)
    : [];
}

function getStringValue(value: unknown): string {
  return typeof value === "string" ? value : "";
}

function getNumberValue(value: unknown): number {
  return typeof value === "number"
    ? value
    : typeof value === "string" && value.trim() !== "" && !Number.isNaN(Number(value))
      ? Number(value)
      : 0;
}

function buildContentQuery(filters: PublicContentQuery, page: number, perPage: number): string {
  const params = new URLSearchParams();

  if (filters.module && filters.module !== "all") {
    params.set("module", filters.module);
  }

  if (filters.q) {
    params.set("q", filters.q);
  }

  if (filters.category) {
    params.set("category", filters.category);
  }

  if (filters.city) {
    params.set("city", filters.city);
  }

  if (filters.dateFrom) {
    params.set("date_from", filters.dateFrom);
  }

  if (filters.dateTo) {
    params.set("date_to", filters.dateTo);
  }

  if (filters.price && filters.price !== "all") {
    params.set("price", filters.price);
  }

  if (filters.sort) {
    params.set("sort", filters.sort);
  }

  if (filters.featured) {
    params.set("featured", "true");
  }

  params.set("page", String(page));
  params.set("per_page", String(perPage));

  return params.toString();
}

async function fetchContentPage(
  filters: PublicContentQuery = {},
  page = 1,
  perPage = 12,
): Promise<{
  items: PublicContent[];
  currentPage: number;
  totalItems: number;
  totalPages: number;
  references: PublicReferenceFilters;
}> {
  const path = getGlobalPublicPath("/content");

  const query = buildContentQuery(filters, page, perPage);
  const payload = await fetchJson<PublicContentIndexPayload>(`${path}?${query}`);

  return {
    items: payload?.data ?? [],
    currentPage: payload?.meta?.current_page ?? page,
    totalItems: payload?.meta?.total ?? 0,
    totalPages: payload?.meta?.total_pages ?? 0,
    references: {
      categories: normalizeStringArray(payload?.filters?.categories),
      cities: normalizeStringArray(payload?.filters?.cities),
    },
  };
}

async function fetchAllContent(filters: PublicContentQuery = {}): Promise<PublicContent[]> {
  const firstPage = await fetchContentPage(filters, 1, 48);

  if (firstPage.totalPages <= 1) {
    return firstPage.items;
  }

  const remainingPages = await Promise.all(
    Array.from({ length: firstPage.totalPages - 1 }, (_, index) =>
      fetchContentPage(filters, index + 2, 48),
    ),
  );

  return [
    ...firstPage.items,
    ...remainingPages.flatMap((pagePayload) => pagePayload.items),
  ];
}

export async function getPlatformConfiguration(): Promise<PlatformConfiguration> {
  const payload = await fetchJson<PublicPlatformPayload>("/api/v1/public/platform/configuration");
  return payload?.data
    ? {
      ...defaultPlatformConfiguration,
      ...payload.data,
      menus: {
        ...defaultMenus,
        ...(payload.data.menus ?? {}),
      },
      socialLinks: Array.isArray(payload.data.socialLinks) ? payload.data.socialLinks : defaultPlatformConfiguration.socialLinks,
      reassuranceItems: Array.isArray(payload.data.reassuranceItems) ? payload.data.reassuranceItems : defaultPlatformConfiguration.reassuranceItems,
      paymentMethods: Array.isArray(payload.data.paymentMethods) ? payload.data.paymentMethods : defaultPlatformConfiguration.paymentMethods,
      featureFlags: Array.isArray(payload.data.featureFlags) ? payload.data.featureFlags : defaultPlatformConfiguration.featureFlags,
      usersCount: typeof payload.data.usersCount === "number" ? payload.data.usersCount : defaultPlatformConfiguration.usersCount,
    }
    : defaultPlatformConfiguration;
}

export async function getFrontPageData(path: string): Promise<FrontPageData | null> {
  const normalizedPath = path.startsWith("/") ? path : `/${path}`;
  const payload = await fetchJson<PublicFrontPagePayload>(`/api/v1/public/front/pages?path=${encodeURIComponent(normalizedPath)}`);

  return payload?.data ?? null;
}

export async function getFrontPagesIndex(): Promise<FrontPageIndexEntry[]> {
  const payload = await fetchJson<PublicFrontPagesIndexPayload>("/api/v1/public/front/pages");

  return payload?.data ?? [];
}

export async function getPublicContentSummary(): Promise<PublicContentSummary> {
  const payload = await fetchJson<PublicContentSummaryPayload>("/api/v1/public/content/summary", { revalidate: 120 });

  return payload?.data ?? {
    totalItems: 0,
    activeCategories: 0,
    activeCities: 0,
    activeCountries: 0,
    offerCount: 0,
    freeItems: 0,
    paidItems: 0,
    galleryImages: [],
    moduleCards: [] as AboutModuleCard[],
  };
}

export async function getAllContent(filters: SearchFilters = {}): Promise<PublicContent[]> {
  return fetchAllContent(filters);
}

export async function getFeaturedContent(): Promise<PublicContent[]> {
  const latest = await fetchContentPage({ sort: "recent" }, 1, 8);

  if (latest.items.length > 0) {
    return latest.items.slice(0, 8);
  }

  const fallback = await fetchContentPage({ sort: "popular" }, 1, 8);

  return fallback.items.slice(0, 8);
}

export async function getPopularContent(): Promise<PublicContent[]> {
  const popular = await fetchContentPage({ sort: "popular" }, 1, 6);

  return popular.items.slice(0, 6);
}

export async function getContentByModule(
  module: ModuleRoute,
  filters: SearchFilters = {},
): Promise<ListingData & { categories: string[]; cities: string[] }> {
  const currentPage = filters.page ?? 1;
  const [result, page] = await Promise.all([
    fetchContentPage({ ...filters, module }, currentPage, 12),
    getFrontPageData(`/${module}`),
  ]);
  const meta = getModuleMeta(module);

  return {
    page,
    module,
    title: meta.title,
    description: meta.description,
    heroImageUrl: meta.heroImageUrl,
    items: result.items,
    filters: {
      ...filters,
      module,
    },
    currentPage: result.currentPage,
    totalItems: result.totalItems,
    totalPages: result.totalPages,
    categories: result.references.categories,
    cities: result.references.cities,
  };
}

export async function getEventCatalogPageData(filters: SearchFilters = {}): Promise<ListingData & { categories: string[]; cities: string[] }> {
  const currentPage = filters.page ?? 1;
  const activeModule = filters.module && filters.module !== "evenements" ? filters.module : "all";
  const normalizedFilters: SearchFilters = {
    ...filters,
    module: activeModule,
  };
  const [result, page] = await Promise.all([
    fetchContentPage(normalizedFilters, currentPage, 12),
    getFrontPageData("/evenements"),
  ]);
  const meta = getModuleMeta("evenements");

  return {
    page,
    module: "evenements",
    title: meta.title,
    description: "Explorez tout le catalogue public depuis la vitrine événements, puis filtrez par module selon vos besoins.",
    heroImageUrl: meta.heroImageUrl,
    items: result.items,
    filters: normalizedFilters,
    currentPage: result.currentPage,
    totalItems: result.totalItems,
    totalPages: result.totalPages,
    categories: result.references.categories,
    cities: result.references.cities,
  };
}

export async function getEventLikeSummaries(
  items: Array<Pick<PublicContent, "module" | "slug" | "organizerSlug">>,
  token: string | null,
): Promise<Record<string, { liked: boolean; likes: number }>> {
  if (!apiBaseUrl || !token) {
    return {};
  }

  const groupedByTenant = items.reduce<Record<string, string[]>>((groups, item) => {
    if (item.module !== "evenements" || !item.organizerSlug || !item.slug) {
      return groups;
    }

    const tenant = item.organizerSlug.trim();
    const slug = item.slug.trim();

    if (!tenant || !slug) {
      return groups;
    }

    if (!groups[tenant]) {
      groups[tenant] = [];
    }

    if (!groups[tenant].includes(slug)) {
      groups[tenant].push(slug);
    }

    return groups;
  }, {});

  const tenantEntries = Object.entries(groupedByTenant);

  if (tenantEntries.length === 0) {
    return {};
  }

  const payloads = await Promise.all(
    tenantEntries.map(async ([tenantSlug, eventSlugs]) => {
      const query = new URLSearchParams();

      for (const eventSlug of eventSlugs) {
        query.append("events[]", eventSlug);
      }

      const payload = await fetchJson<{ data?: Record<string, { liked?: boolean; likes?: number | string | null }> }>(
        `/api/v1/tenants/${encodeURIComponent(tenantSlug)}/events/likes?${query.toString()}`,
        {
          noStore: true,
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      );

      return payload?.data ?? {};
    }),
  );

  return payloads.reduce<Record<string, { liked: boolean; likes: number }>>((carry, payload) => {
    for (const [key, value] of Object.entries(payload)) {
      carry[key] = {
        liked: Boolean(value?.liked),
        likes: typeof value?.likes === "number"
          ? value.likes
          : typeof value?.likes === "string" && value.likes.trim() !== "" && !Number.isNaN(Number(value.likes))
            ? Number(value.likes)
            : 0,
      };
    }

    return carry;
  }, {});
}

export async function getContentDetail(
  module: ModuleRoute,
  slug: string,
  tenantSlug?: string,
): Promise<PublicContent | null> {
  const path = getGlobalPublicPath(`/content/${module}/${slug}`);
  const query = tenantSlug?.trim() ? `?tenant=${encodeURIComponent(tenantSlug.trim())}` : "";

  const payload = await fetchJson<{ data?: PublicContent }>(`${path}${query}`);

  return payload?.data ?? null;
}

export async function getRelatedContent(item: PublicContent): Promise<PublicContent[]> {
  const params = new URLSearchParams();

  if (item.organizerSlug) {
    params.set("tenant", item.organizerSlug);
  }

  params.set("limit", "3");

  const payload = await fetchJson<{ data?: PublicContent[] }>(
    `${getGlobalPublicPath(`/content/${item.module}/${item.slug}/related`)}?${params.toString()}`,
    { revalidate: 120 },
  );

  return payload?.data ?? [];
}

export async function getOrganizerBySlug(
  slug: string,
): Promise<{ organizer: OrganizerProfile; items: PublicContent[] } | null> {
  const payload = await getOrganizerCatalogPageData(slug, { sort: "popular", module: "evenements" }, 12);

  if (!payload?.organizer) {
    return null;
  }

  return {
    organizer: payload.organizer,
    items: payload.items,
  };
}

export async function getOrganizerCatalogPageData(
  slug: string,
  filters: SearchFilters = {},
  perPage = 12,
): Promise<OrganizerCatalogPageData | null> {
  const path = await getTenantPublicPath("/organization-profile/catalog", slug);

  if (!path) {
    return null;
  }

  const requestedPage = filters.page ?? 1;
  const query = buildContentQuery(filters, requestedPage, perPage);
  const payload = await fetchJson<PublicOrganizerCatalogPayload>(`${path}?${query}`);

  if (!payload?.data?.profile) {
    return null;
  }

  const items = payload.data.items ?? [];

  return {
    organizer: payload.data.profile,
    items,
    filters: {
      ...filters,
      page: payload.meta?.current_page ?? requestedPage,
    },
    currentPage: payload.meta?.current_page ?? requestedPage,
    totalItems: payload.meta?.total ?? 0,
    totalPages: payload.meta?.total_pages ?? 0,
    categories: normalizeStringArray(payload.filters?.categories),
    cities: normalizeStringArray(payload.filters?.cities),
    stats: payload.data.stats ?? { total: 0, free: 0, paid: 0, byModule: {} },
  };
}

export async function getOrganizerHighlights() {
  const tenantSlug = await resolvePublicTenantSlug();

  if (!tenantSlug) {
    return [];
  }

  const payload = await getOrganizerCatalogPageData(tenantSlug, { sort: "popular" }, 3);

  if (!payload) {
    return [];
  }

  return [
    {
      organizer: payload.organizer,
      items: payload.items,
    },
  ];
}

export async function getSpeakerHighlights() {
  if (!speakerHighlightsPromise) {
    speakerHighlightsPromise = (async () => {
      const payload = await fetchJson<PublicContentOverviewPayload>("/api/v1/public/content/speakers");

      return Array.isArray(payload?.data)
        ? payload.data as SpeakerHighlightEntry[]
        : [];
    })();
  }

  const speakers = await speakerHighlightsPromise;

  if (speakers.length === 0) {
    speakerHighlightsPromise = null;
  }

  return speakers;
}

export async function getCategoryOverview() {
  if (!categoryOverviewPromise) {
    categoryOverviewPromise = (async () => {
      const payload = await fetchJson<PublicContentOverviewPayload>("/api/v1/public/content/categories");

      return Array.isArray(payload?.data)
        ? payload.data as CategoryOverviewEntry[]
        : [];
    })();
  }

  const categories = await categoryOverviewPromise;

  if (categories.length === 0) {
    categoryOverviewPromise = null;
  }

  return categories;
}

export async function getCityOverview() {
  if (!cityOverviewPromise) {
    cityOverviewPromise = (async () => {
      const payload = await fetchJson<PublicContentOverviewPayload>("/api/v1/public/content/cities");

      return Array.isArray(payload?.data)
        ? payload.data as CityOverviewEntry[]
        : [];
    })();
  }

  const cities = await cityOverviewPromise;

  if (cities.length === 0) {
    cityOverviewPromise = null;
  }

  return cities;
}

export async function getSearchSuggestions(
  query: string,
  module?: ModuleRoute | "all",
  limit = 6,
): Promise<SearchSuggestion[]> {
  const trimmed = query.trim();

  if (trimmed.length < 2) {
    return [];
  }

  const params = new URLSearchParams();

  params.set("q", trimmed);
  params.set("limit", String(limit));

  if (module && module !== "all") {
    params.set("module", module);
  }

  const payload = await fetchJson<{ data?: SearchSuggestion[] }>(
    `${getGlobalPublicPath("/content/search/suggestions")}?${params.toString()}`,
    { revalidate: 60 },
  );

  return payload?.data ?? [];
}

export async function getHomePageData() {
  const [platform, page, featured, popular, organizers, contentCount] = await Promise.all([
    getPlatformConfiguration(),
    getFrontPageData("/"),
    getFeaturedContent(),
    getPopularContent(),
    getOrganizerHighlights(),
    fetchContentPage({}, 1, 1),
  ]);

  return {
    page,
    platform,
    featured,
    popular,
    organizers,
    categories: contentCount.references.categories,
    stats: [
      { label: "Contenus publies", value: String(contentCount.totalItems) },
      { label: "Organisateurs", value: String(organizers.length) },
      { label: "Utilisateurs", value: String(platform.usersCount) },
    ],
  };
}

export async function getSearchPageData(searchParams: Record<string, string | string[] | undefined>) {
  const filters = normalizeSearchParams(searchParams);
  const currentPage = filters.page ?? 1;
  const [platform, page, result] = await Promise.all([
    getPlatformConfiguration(),
    getFrontPageData("/recherche"),
    fetchContentPage(filters, currentPage, 12),
  ]);

  return {
    platform,
    page,
    filters,
    items: result.items,
    currentPage: result.currentPage,
    totalItems: result.totalItems,
    totalPages: result.totalPages,
    references: result.references,
  };
}

export async function getCheckoutPaymentOptions(
  offerId: string,
  quantity = 1,
): Promise<CheckoutPaymentOptions | null> {
  const path = await getTenantPublicPath(`/payment-options?offer=${encodeURIComponent(offerId)}&quantity=${quantity}`);

  if (!path) {
    return null;
  }

  const payload = await fetchJson<PublicApiEnvelope<CheckoutPaymentOptions>>(path, { noStore: true });

  return payload?.data ?? null;
}

export async function initializeCheckoutPayment(input: {
  offer: string;
  quantity: number;
  content_module: ModuleRoute;
  content_slug: string;
  callback_url: string;
}): Promise<CheckoutInitializationResult | { error: string } | null> {
  try {
    const response = await fetch("/api/checkout/initialize", {
      method: "POST",
      cache: "no-store",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
      body: JSON.stringify(input),
    });
    const payload = (await response.json()) as PublicApiEnvelope<CheckoutInitializationResult> & {
      message?: string;
      error?: string;
      code?: string;
    };

    if (!response.ok || !payload.data) {
      return { error: payload.message ?? payload.error ?? "Impossible d'initialiser le paiement." };
    }

    return payload.data;
  } catch {
    return null;
  }
}

export async function verifyCheckoutPayment(reference: string): Promise<CheckoutVerificationResult | null> {
  const path = await getTenantPublicPath(`/payments/verify/${encodeURIComponent(reference)}`);

  if (!path) {
    return null;
  }

  const payload = await fetchJson<PublicApiEnvelope<CheckoutVerificationResult>>(path, { noStore: true });

  return payload?.data ?? null;
}

export async function getCheckoutData(module: ModuleRoute, slug: string, offerId?: string) {
  const [platform, item] = await Promise.all([
    getPlatformConfiguration(),
    getContentDetail(module, slug),
  ]);

  if (!item) {
    return null;
  }

  const selectedOffer = item.tiers.find((tier) => tier.id === offerId) ?? item.tiers[0] ?? null;
  const paymentOptions = selectedOffer
    ? await getCheckoutPaymentOptions(selectedOffer.id, 1)
    : null;

  return {
    platform,
    item,
    selectedOffer,
    dateLabel: formatDateRange(item),
    paymentOptions,
  };
}
