import {
  AboutModuleCard,
  CategoryOverviewEntry,
  CityOverviewEntry,
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
import { normalizeTenantSlug } from "@/lib/tenant";
import { formatDateRange, normalizeSearchParams } from "@/lib/utils";

export const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "") ?? (process.env.NODE_ENV === "development" ? "http://127.0.0.1:8000" : "");
const publicTenantSlug = normalizeTenantSlug(process.env.NEXT_PUBLIC_TENANT_SLUG);
const publicApiTimeoutMs = parsePositiveInteger(process.env.PUBLIC_API_TIMEOUT_MS, process.env.NODE_ENV === "production" ? 3000 : 5000);
const publicApiFailureCacheSeconds = parsePositiveInteger(process.env.PUBLIC_API_FAILURE_CACHE_SECONDS, process.env.NODE_ENV === "production" ? 15 : 2);
export const publicDataCacheSeconds = parsePositiveInteger(process.env.PUBLIC_DATA_CACHE_SECONDS, process.env.NODE_ENV === "production" ? 60 : 20);
export const publicCmsCacheSeconds = parsePositiveInteger(process.env.PUBLIC_CMS_CACHE_SECONDS, process.env.NODE_ENV === "production" ? 30 : 5);
export const defaultMenus: Record<FrontMenuLocation, NavigationLink[]> = {
  header_primary: [],
  header_utility: [],
  footer_explore: [],
  footer_platform: [],
  footer_bottom: [],
};

export const defaultPlatformConfiguration: PlatformConfiguration = {
  brandName: process.env.NEXT_PUBLIC_PLATFORM_NAME || "Ticket",
  logoUrl: undefined,
  faviconUrl: undefined,
  appleTouchIconUrl: undefined,
  seo: {
    defaultTitle: process.env.NEXT_PUBLIC_PLATFORM_NAME || "Ticket",
    defaultDescription: "Portail public unifie pour billetterie, reservations, paiements et contenus multi-modules.",
    keywords: ["billetterie", "evenements", "reservations", "paiement securise"],
    canonicalUrl: undefined,
    robots: {
      index: "index",
      follow: "follow",
      maxImagePreview: "large",
      allowPaths: ["/"],
      disallowPaths: ["/compte", "/checkout"],
    },
    openGraph: {
      type: "website",
    },
    twitter: {
      card: "summary_large_image",
    },
    structuredDataJson: undefined,
    sitemap: {
      enabled: true,
      includeFrontPages: true,
      includeCatalog: true,
      includeOrganizers: true,
    },
  },
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
  languages: [
    { code: "fr", locale: "fr", name: "Français", native_name: "Français", is_default: true },
    { code: "en", locale: "en", name: "Anglais", native_name: "English" },
  ],
  defaultLanguage: { code: "fr", locale: "fr", name: "Français", native_name: "Français", is_default: true },
  translations: {},
};

export type PublicPlatformPayload = {
  data?: PlatformConfiguration;
};

export type PublicFrontPagePayload = {
  data?: FrontPageData;
};

export type PublicFrontPagesIndexPayload = {
  data?: FrontPageIndexEntry[];
};

export type PublicReferenceFilters = {
  categories: string[];
  cities: string[];
};

export type PublicContentIndexPayload = {
  data?: PublicContent[];
  meta?: {
    current_page?: number;
    total?: number;
    total_pages?: number;
    per_page?: number;
  };
  filters?: Partial<PublicReferenceFilters>;
  presentation?: {
    module?: ModuleRoute;
    title?: string;
    singular?: string;
    cta?: string;
    description?: string;
    href?: string;
    heroImageUrl?: string;
  };
};

export type PublicContentQuery = SearchFilters & {
  featured?: boolean;
};

export type PublicOrganizerCatalogPayload = {
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

export type PublicApiEnvelope<T> = {
  data?: T;
};

export type PublicContentOverviewPayload = {
  data?: CategoryOverviewEntry[] | CityOverviewEntry[] | SpeakerHighlightEntry[];
};

export type PublicContentSummaryPayload = {
  data?: PublicContentSummary;
};

let defaultPublicTenantSlugPromise: Promise<string> | null = null;
const MAX_PUBLIC_REQUEST_CACHE_ENTRIES = 500;
const publicRequestCache = new Map<string, { expiresAt: number; value: Promise<unknown | null> }>();
const MAX_PUBLIC_DATA_CACHE_ENTRIES = 250;
const publicDataCache = new Map<string, { expiresAt: number; value: Promise<unknown> }>();

function parsePositiveInteger(value: string | undefined, fallback: number): number {
  const parsed = Number(value);

  return Number.isFinite(parsed) && parsed > 0 ? Math.round(parsed) : fallback;
}

function canUsePublicRequestCache(options: {
  noStore?: boolean;
  revalidate?: number;
  headers?: HeadersInit;
}): boolean {
  if (options.noStore) {
    return false;
  }

  const headers = new Headers(options.headers);

  return !headers.has("Authorization");
}

function getPublicRequestCacheKey(
  path: string,
  options: {
    noStore?: boolean;
    revalidate?: number;
    headers?: HeadersInit;
  },
): string {
  const headers = new Headers(options.headers);

  return JSON.stringify({
    path,
    revalidate: options.revalidate ?? 120,
    headers: Array.from(headers.entries()).sort(([left], [right]) => left.localeCompare(right)),
  });
}

function prunePublicRequestCache(now: number): void {
  for (const [key, entry] of publicRequestCache.entries()) {
    if (entry.expiresAt <= now) {
      publicRequestCache.delete(key);
    }
  }

  if (publicRequestCache.size <= MAX_PUBLIC_REQUEST_CACHE_ENTRIES) {
    return;
  }

  const overflow = publicRequestCache.size - MAX_PUBLIC_REQUEST_CACHE_ENTRIES;
  const oldestKeys = Array.from(publicRequestCache.entries())
    .sort(([, left], [, right]) => left.expiresAt - right.expiresAt)
    .slice(0, overflow)
    .map(([key]) => key);

  for (const key of oldestKeys) {
    publicRequestCache.delete(key);
  }
}

function normalizeCacheInput(value: unknown): unknown {
  if (Array.isArray(value)) {
    return value.map(normalizeCacheInput);
  }

  if (value && typeof value === "object") {
    return Object.fromEntries(
      Object.entries(value as Record<string, unknown>)
        .filter(([, entryValue]) => entryValue !== undefined)
        .sort(([left], [right]) => left.localeCompare(right))
        .map(([key, entryValue]) => [key, normalizeCacheInput(entryValue)]),
    );
  }

  return value ?? null;
}

function getPublicDataCacheKey(scope: string, input: unknown): string {
  return `${scope}:${JSON.stringify(normalizeCacheInput(input))}`;
}

function prunePublicDataCache(now: number): void {
  for (const [key, entry] of publicDataCache.entries()) {
    if (entry.expiresAt <= now) {
      publicDataCache.delete(key);
    }
  }

  if (publicDataCache.size <= MAX_PUBLIC_DATA_CACHE_ENTRIES) {
    return;
  }

  const overflow = publicDataCache.size - MAX_PUBLIC_DATA_CACHE_ENTRIES;
  const oldestKeys = Array.from(publicDataCache.entries())
    .sort(([, left], [, right]) => left.expiresAt - right.expiresAt)
    .slice(0, overflow)
    .map(([key]) => key);

  for (const key of oldestKeys) {
    publicDataCache.delete(key);
  }
}

export async function rememberPublicData<T>(
  scope: string,
  input: unknown,
  ttlSeconds: number,
  loader: () => Promise<T>,
): Promise<T> {
  if (ttlSeconds <= 0) {
    return loader();
  }

  const now = Date.now();
  const key = getPublicDataCacheKey(scope, input);

  prunePublicDataCache(now);

  const cachedEntry = publicDataCache.get(key);

  if (cachedEntry && cachedEntry.expiresAt > now) {
    return await cachedEntry.value as T;
  }

  const value = loader();

  publicDataCache.set(key, {
    expiresAt: now + (ttlSeconds * 1000),
    value,
  });

  try {
    return await value;
  } catch (error) {
    const entryAfterError = publicDataCache.get(key);

    if (entryAfterError?.value === value) {
      publicDataCache.delete(key);
    }

    throw error;
  }
}

export async function resolvePublicTenantSlug(): Promise<string> {
  if (publicTenantSlug) {
    return publicTenantSlug;
  }

  if (!defaultPublicTenantSlugPromise) {
    defaultPublicTenantSlugPromise = (async () => {
      const payload = await fetchJson<PublicPlatformPayload>("/api/v1/public/platform/configuration");
      const slug = normalizeTenantSlug(getStringValue(payload?.data?.defaultTenant?.slug));

      return slug;
    })();
  }

  const slug = await defaultPublicTenantSlugPromise;

  if (!slug) {
    defaultPublicTenantSlugPromise = null;
  }

  return slug;
}

export async function getTenantPublicPath(path: string, tenantSlug?: string): Promise<string | null> {
  const resolvedTenantSlug = normalizeTenantSlug(tenantSlug) || (await resolvePublicTenantSlug());

  if (!resolvedTenantSlug) {
    return null;
  }

  const normalizedPath = path.startsWith("/") ? path : `/${path}`;

  return `/api/v1/public/tenants/${resolvedTenantSlug}${normalizedPath}`;
}

export function getGlobalPublicPath(path: string): string {
  const normalizedPath = path.startsWith("/") ? path : `/${path}`;

  return `/api/v1/public${normalizedPath}`;
}

export async function fetchJson<T>(
  path: string,
  options: {
    noStore?: boolean;
    revalidate?: number;
    headers?: HeadersInit;
    timeoutMs?: number;
  } = {},
): Promise<T | null> {
  if (!apiBaseUrl) {
    return null;
  }

  const revalidate = options.revalidate ?? 120;
  const timeoutMs = options.timeoutMs ?? publicApiTimeoutMs;
  const shouldUseRequestCache = canUsePublicRequestCache(options);

  if (shouldUseRequestCache) {
    const cacheKey = getPublicRequestCacheKey(path, options);
    const now = Date.now();

    prunePublicRequestCache(now);

    const cachedEntry = publicRequestCache.get(cacheKey);

    if (cachedEntry && cachedEntry.expiresAt > now) {
      return await cachedEntry.value as T | null;
    }

    const requestPromise = (async () => {
      try {
        const response = await fetch(`${apiBaseUrl}${path}`, {
          cache: "force-cache",
          next: { revalidate },
          signal: AbortSignal.timeout(timeoutMs),
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
    })();

    publicRequestCache.set(cacheKey, {
      expiresAt: now + (revalidate * 1000),
      value: requestPromise,
    });

    const result = await requestPromise;

    if (result === null) {
      const cachedEntryAfterFailure = publicRequestCache.get(cacheKey);

      if (cachedEntryAfterFailure?.value === requestPromise) {
        cachedEntryAfterFailure.expiresAt = Date.now() + (publicApiFailureCacheSeconds * 1000);
      }
    }

    return result as T | null;
  }

  try {
    const response = await fetch(`${apiBaseUrl}${path}`, {
      cache: options.noStore ? "no-store" : "force-cache",
      next: options.noStore ? undefined : { revalidate },
      signal: AbortSignal.timeout(timeoutMs),
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

export function normalizeStringArray(values: unknown): string[] {
  return Array.isArray(values)
    ? values.map((value) => String(value).trim()).filter((value) => value.length > 0)
    : [];
}

function getStringValue(value: unknown): string {
  return typeof value === "string" ? value : "";
}

export function buildContentQuery(filters: PublicContentQuery, page: number, perPage: number): string {
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
