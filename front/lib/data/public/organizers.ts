import type { OrganizerCatalogPageData, OrganizerProfile, PublicContent, SearchFilters } from "@/lib/types";
import {
  buildContentQuery,
  fetchJson,
  getTenantPublicPath,
  normalizeStringArray,
  publicDataCacheSeconds,
  rememberPublicData,
  resolvePublicTenantSlug,
  type PublicOrganizerCatalogPayload,
} from "./shared";

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
  return rememberPublicData("organizer-catalog-page", { slug, filters, perPage }, publicDataCacheSeconds, async () => {
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
  });
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
