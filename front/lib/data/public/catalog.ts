import type {
  AboutModuleCard,
  CategoryOverviewEntry,
  CityOverviewEntry,
  ListingData,
  ModuleRoute,
  PublicContent,
  PublicContentSummary,
  SearchFilters,
  SearchSuggestion,
  SpeakerHighlightEntry,
} from "@/lib/types";
import { normalizeTenantSlug } from "@/lib/tenant";
import {
  apiBaseUrl,
  buildContentQuery,
  fetchJson,
  getGlobalPublicPath,
  normalizeStringArray,
  publicDataCacheSeconds,
  rememberPublicData,
  type PublicContentIndexPayload,
  type PublicContentOverviewPayload,
  type PublicContentQuery,
  type PublicContentSummaryPayload,
  type PublicReferenceFilters,
} from "./shared";
import { getFrontPageData } from "./cms";

let categoryOverviewPromise: Promise<CategoryOverviewEntry[]> | null = null;
let cityOverviewPromise: Promise<CityOverviewEntry[]> | null = null;
let speakerHighlightsPromise: Promise<SpeakerHighlightEntry[]> | null = null;

export async function fetchContentPage(
  filters: PublicContentQuery = {},
  page = 1,
  perPage = 12,
): Promise<{
  items: PublicContent[];
  currentPage: number;
  totalItems: number;
  totalPages: number;
  references: PublicReferenceFilters;
  presentation?: NonNullable<PublicContentIndexPayload["presentation"]>;
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
    presentation: payload?.presentation,
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

export async function getCatalogPageData(
  filters: SearchFilters = {},
  page = filters.page ?? 1,
  perPage = 12,
) {
  return rememberPublicData("catalog-page", { filters, page, perPage }, publicDataCacheSeconds, () =>
    fetchContentPage(filters, page, perPage),
  );
}

export async function getFeaturedContent(): Promise<PublicContent[]> {
  return rememberPublicData("featured-content", {}, publicDataCacheSeconds, async () => {
    const latest = await fetchContentPage({ sort: "recent" }, 1, 8);

    if (latest.items.length > 0) {
      return latest.items.slice(0, 8);
    }

    const fallback = await fetchContentPage({ sort: "popular" }, 1, 8);

    return fallback.items.slice(0, 8);
  });
}

export async function getPopularContent(): Promise<PublicContent[]> {
  return rememberPublicData("popular-content", {}, publicDataCacheSeconds, async () => {
    const popular = await fetchContentPage({ sort: "popular" }, 1, 6);

    return popular.items.slice(0, 6);
  });
}

export async function getContentByModule(
  module: ModuleRoute,
  filters: SearchFilters = {},
): Promise<ListingData & { categories: string[]; cities: string[] }> {
  return rememberPublicData("content-by-module", { module, filters }, publicDataCacheSeconds, async () => {
    const currentPage = filters.page ?? 1;
    const [result, page] = await Promise.all([
      fetchContentPage({ ...filters, module }, currentPage, 12),
      getFrontPageData(`/${module}`),
    ]);
    const presentation = result.presentation;

    return {
      page,
      module,
      title: presentation?.title ?? module,
      singular: presentation?.singular ?? module,
      description: presentation?.description ?? "",
      heroImageUrl: presentation?.heroImageUrl ?? "",
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
  });
}

export async function getEventCatalogPageData(filters: SearchFilters = {}): Promise<ListingData & { categories: string[]; cities: string[] }> {
  return rememberPublicData("event-catalog-page", { filters }, publicDataCacheSeconds, async () => {
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
    const presentation = result.presentation;

    return {
      page,
      module: "evenements",
      title: presentation?.title ?? "Evenements",
      singular: presentation?.singular ?? "evenement",
      description: presentation?.description ?? "",
      heroImageUrl: presentation?.heroImageUrl ?? "",
      items: result.items,
      filters: normalizedFilters,
      currentPage: result.currentPage,
      totalItems: result.totalItems,
      totalPages: result.totalPages,
      categories: result.references.categories,
      cities: result.references.cities,
    };
  });
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

    const tenant = normalizeTenantSlug(item.organizerSlug);
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
  return rememberPublicData("content-detail", { module, slug, tenantSlug }, publicDataCacheSeconds, async () => {
    const path = getGlobalPublicPath(`/content/${module}/${slug}`);
    const normalizedTenantSlug = normalizeTenantSlug(tenantSlug);
    const query = normalizedTenantSlug ? `?tenant=${encodeURIComponent(normalizedTenantSlug)}` : "";

    const payload = await fetchJson<{ data?: PublicContent }>(`${path}${query}`);

    return payload?.data ?? null;
  });
}

export async function getRelatedContent(item: PublicContent): Promise<PublicContent[]> {
  return rememberPublicData("related-content", { module: item.module, slug: item.slug, organizerSlug: item.organizerSlug }, publicDataCacheSeconds, async () => {
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
  });
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
