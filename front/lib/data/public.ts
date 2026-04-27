import {
  ListingData,
  ModuleRoute,
  OrganizerProfile,
  PlatformConfiguration,
  PublicContent,
  SearchFilters,
} from "@/lib/types";
import { mockContent, mockOrganizers, mockPlatformConfiguration } from "@/lib/data/mock";
import {
  findContent,
  findOrganizer,
  listCategoryOverview,
  listCityOverview,
  listContent,
  listFeaturedContent,
  listOrganizerHighlights,
  listSpeakerHighlights,
  listPopularContent,
  listReferenceFilters,
  listRelatedContent,
} from "@/lib/data/catalog";
import {
  formatDateRange,
  getModuleMeta,
  normalizeSearchParams,
} from "@/lib/utils";

const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "");

async function fetchJson<T>(path: string): Promise<T | null> {
  if (!apiBaseUrl) {
    return null;
  }

  try {
    const response = await fetch(`${apiBaseUrl}${path}`, {
      cache: "no-store",
      headers: {
        Accept: "application/json",
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

export async function getPlatformConfiguration(): Promise<PlatformConfiguration> {
  const payload = await fetchJson<{
    settings?: Record<string, Record<string, unknown>>;
    feature_flags?: string[];
  }>("/api/v1/public/platform/configuration");

  if (!payload) {
    return mockPlatformConfiguration;
  }

  const branding = payload.settings?.branding ?? {};
  const support = payload.settings?.support ?? {};
  const payments = payload.settings?.payments ?? {};

  return {
    brandName:
      typeof branding.platform_name === "string"
        ? branding.platform_name
        : mockPlatformConfiguration.brandName,
    supportEmail:
      typeof support.email === "string" ? support.email : mockPlatformConfiguration.supportEmail,
    supportPhone:
      typeof support.phone === "string" ? support.phone : mockPlatformConfiguration.supportPhone,
    currencyCode:
      typeof payments.currency === "string"
        ? payments.currency
        : mockPlatformConfiguration.currencyCode,
    accountUrl: mockPlatformConfiguration.accountUrl,
    organizerCtaUrl: mockPlatformConfiguration.organizerCtaUrl,
    reassuranceItems: mockPlatformConfiguration.reassuranceItems,
    paymentMethods: Array.isArray(payments.methods)
      ? payments.methods.map(String)
      : mockPlatformConfiguration.paymentMethods,
    featureFlags: Array.isArray(payload.feature_flags)
      ? payload.feature_flags.map(String)
      : mockPlatformConfiguration.featureFlags,
  };
}

export async function getAllContent(filters: SearchFilters = {}): Promise<PublicContent[]> {
  return listContent(filters);
}

export async function getFeaturedContent(): Promise<PublicContent[]> {
  return listFeaturedContent();
}

export async function getPopularContent(): Promise<PublicContent[]> {
  return listPopularContent();
}

export async function getContentByModule(
  module: ModuleRoute,
  filters: SearchFilters = {},
): Promise<ListingData> {
  const items = await getAllContent({
    ...filters,
    module,
  });

  const meta = getModuleMeta(module);

  return {
    module,
    title: meta.title,
    description: meta.description,
    heroImageUrl: meta.heroImageUrl,
    items,
    filters: {
      ...filters,
      module,
    },
  };
}

export async function getContentDetail(
  module: ModuleRoute,
  slug: string,
): Promise<PublicContent | null> {
  return findContent(module, slug);
}

export async function getRelatedContent(item: PublicContent): Promise<PublicContent[]> {
  return listRelatedContent(item);
}

export async function getOrganizerBySlug(
  slug: string,
): Promise<{ organizer: OrganizerProfile; items: PublicContent[] } | null> {
  return findOrganizer(slug);
}

export async function getOrganizerHighlights() {
  return listOrganizerHighlights();
}

export async function getSpeakerHighlights() {
  return listSpeakerHighlights();
}

export async function getCategoryOverview() {
  return listCategoryOverview();
}

export async function getCityOverview() {
  return listCityOverview();
}

export async function getReferenceFilters() {
  return listReferenceFilters();
}

export async function getHomePageData() {
  const [platform, featured, popular, organizers] = await Promise.all([
    getPlatformConfiguration(),
    getFeaturedContent(),
    getPopularContent(),
    getOrganizerHighlights(),
  ]);

  return {
    platform,
    featured,
    popular,
    organizers,
    stats: [
      { label: "Contenus publies", value: String(mockContent.length) },
      { label: "Organisateurs", value: String(mockOrganizers.length) },
      { label: "Modules actifs", value: "5" },
    ],
  };
}

export async function getSearchPageData(searchParams: Record<string, string | string[] | undefined>) {
  const filters = normalizeSearchParams(searchParams);
  const [platform, items, references] = await Promise.all([
    getPlatformConfiguration(),
    getAllContent(filters),
    getReferenceFilters(),
  ]);

  return {
    platform,
    filters,
    items,
    references,
  };
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

  return {
    platform,
    item,
    selectedOffer,
    dateLabel: formatDateRange(item),
  };
}
