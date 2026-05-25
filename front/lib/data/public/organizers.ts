import type { OrganizerCatalogPageData, OrganizerProfile, PublicContent, SearchFilters } from "@/lib/types";
import { organizerFollowKey } from "@/lib/engagement";
import { fetchContentPage } from "./catalog";
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

const ORGANIZER_FOLLOW_SUMMARY_TIMEOUT_MS = 2500;

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
  return rememberPublicData("organizer-highlights", {}, publicDataCacheSeconds, async () => {
    const contentPage = await fetchContentPage({ sort: "recent" }, 1, 48);
    const organizerSlugs = Array.from(
      new Set(contentPage.items.map((item) => item.organizerSlug).filter((slug) => slug.trim().length > 0)),
    );

    const profiles = await Promise.all(
      organizerSlugs.map(async (slug) => {
        const payload = await getOrganizerCatalogPageData(slug, { sort: "popular" }, 3);

        return payload
          ? {
              organizer: payload.organizer,
              items: payload.items,
            }
          : null;
      }),
    );

    const resolvedProfiles = profiles.filter((entry): entry is { organizer: OrganizerProfile; items: PublicContent[] } => entry !== null);

    if (resolvedProfiles.length === 0) {
      const fallbackSlug = await resolvePublicTenantSlug();
      const fallbackPayload = fallbackSlug
        ? await getOrganizerCatalogPageData(fallbackSlug, { sort: "popular" }, 3)
        : null;

      return fallbackPayload
        ? [{ organizer: fallbackPayload.organizer, items: fallbackPayload.items }]
        : [];
    }

    return resolvedProfiles
      .sort((left, right) => {
        if (right.organizer.followers !== left.organizer.followers) {
          return right.organizer.followers - left.organizer.followers;
        }

        return right.items.length - left.items.length;
      })
      .slice(0, 4);
  });
}

export async function getOrganizerFollowSummaries(
  items: Array<Pick<PublicContent, "organizerSlug">>,
  tokens: string | Record<string, string> | null,
): Promise<Record<string, { following: boolean; followers: number }>> {
  if (!tokens) {
    return {};
  }

  const organizerSlugs = Array.from(
    new Set(items.map((item) => organizerFollowKey(item.organizerSlug)).filter((slug) => slug.length > 0)),
  );

  if (organizerSlugs.length === 0) {
    return {};
  }

  const payloads = await Promise.all(
    organizerSlugs.map(async (slug) => {
      const token = typeof tokens === "string" ? tokens : tokens[slug];

      if (!token) {
        return { slug, data: null };
      }

      const payload = await fetchJson<{ data?: { following?: boolean; followers?: number | string | null } }>(
        `/api/v1/tenants/${encodeURIComponent(slug)}/organization-profile/follow`,
        {
          noStore: true,
          timeoutMs: ORGANIZER_FOLLOW_SUMMARY_TIMEOUT_MS,
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      );

      return {
        slug,
        data: payload?.data ?? null,
      };
    }),
  );

  return payloads.reduce<Record<string, { following: boolean; followers: number }>>((carry, payload) => {
    if (!payload.data) {
      return carry;
    }

    const followers = typeof payload.data.followers === "number"
      ? payload.data.followers
      : typeof payload.data.followers === "string" && payload.data.followers.trim() !== "" && !Number.isNaN(Number(payload.data.followers))
        ? Number(payload.data.followers)
        : 0;

    carry[payload.slug] = {
      following: Boolean(payload.data.following),
      followers,
    };

    return carry;
  }, {});
}
