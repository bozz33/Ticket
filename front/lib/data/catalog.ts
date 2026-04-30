import {
  ModuleRoute,
  OrganizerProfile,
  PublicContent,
  SearchFilters,
  SearchSuggestion,
} from "@/lib/types";

const API_BASE = process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "") ?? "";
const TENANT_SLUG = process.env.NEXT_PUBLIC_TENANT_SLUG ?? "";

async function apiFetch<T>(path: string): Promise<T | null> {
  if (!API_BASE || !TENANT_SLUG) return null;
  try {
    const res = await fetch(`${API_BASE}${path}`, {
      cache: "no-store",
      headers: { Accept: "application/json" },
    });
    if (!res.ok) return null;
    return (await res.json()) as T;
  } catch {
    return null;
  }
}

function contentBase() {
  return `/api/v1/public/tenants/${TENANT_SLUG}/content`;
}

function buildQs(params: Record<string, string | number | undefined>): string {
  const parts: string[] = [];
  for (const [k, v] of Object.entries(params)) {
    if (v !== undefined && v !== "" && v !== "all") {
      parts.push(`${encodeURIComponent(k)}=${encodeURIComponent(String(v))}`);
    }
  }
  return parts.length ? "?" + parts.join("&") : "";
}

type ApiListResponse = {
  data: PublicContent[];
  meta: { current_page: number; total: number; total_pages: number; per_page: number };
  filters: { categories: string[]; cities: string[] };
};

export async function listContent(
  filters: SearchFilters = {},
): Promise<{ items: PublicContent[]; currentPage: number; totalItems: number; totalPages: number }> {
  const qs = buildQs({
    module: filters.module !== "all" ? filters.module : undefined,
    q: filters.q,
    category: filters.category,
    city: filters.city,
    price: filters.price,
    sort: filters.sort,
    page: filters.page,
  });
  const res = await apiFetch<ApiListResponse>(`${contentBase()}${qs}`);
  return {
    items: res?.data ?? [],
    currentPage: res?.meta?.current_page ?? 1,
    totalItems: res?.meta?.total ?? 0,
    totalPages: res?.meta?.total_pages ?? 1,
  };
}

export async function listFeaturedContent(): Promise<PublicContent[]> {
  const res = await apiFetch<ApiListResponse>(
    `${contentBase()}${buildQs({ featured: "true", sort: "recent", per_page: 6 })}`,
  );
  return res?.data ?? [];
}

export async function listPopularContent(): Promise<PublicContent[]> {
  const res = await apiFetch<ApiListResponse>(
    `${contentBase()}${buildQs({ sort: "popular", per_page: 6 })}`,
  );
  return res?.data ?? [];
}

export async function findContent(module: ModuleRoute, slug: string): Promise<PublicContent | null> {
  const res = await apiFetch<{ data: PublicContent }>(`${contentBase()}/${module}/${slug}`);
  return res?.data ?? null;
}

export async function listRelatedContent(item: PublicContent): Promise<PublicContent[]> {
  const res = await apiFetch<ApiListResponse>(
    `${contentBase()}${buildQs({ category: item.category, per_page: 6 })}`,
  );
  return (res?.data ?? []).filter((c) => c.id !== item.id).slice(0, 3);
}

export async function findOrganizerProfile(slug: string): Promise<OrganizerProfile | null> {
  const res = await apiFetch<{
    tenant: { slug: string; name: string };
    data: Record<string, unknown>;
  }>(`/api/v1/public/tenants/${slug}/organization-profile`);

  if (!res?.data) return null;
  const d = res.data;
  const socialLinks = Array.isArray(d.social_links)
    ? (d.social_links as Array<{ label: string; url: string }>)
    : Array.isArray(d.socialLinks)
      ? (d.socialLinks as Array<{ label: string; url: string }>)
      : [];
  const meta = (d.meta as Record<string, unknown>) ?? {};

  return {
    slug: res.tenant?.slug ?? slug,
    name: (d.display_name as string) ?? "",
    legalName: (d.legal_name as string) ?? "",
    tagline: (meta.tagline as string) ?? "",
    description: (d.description as string) ?? "",
    city: (d.city as string) ?? "",
    country: (d.country_code as string) ?? "",
    verified: Boolean(meta.verified ?? false),
    followers: Number(meta.followers ?? 0),
    accentColor: (d.primary_color as string) ?? "",
    logoUrl: (d.logo_url as string) ?? "",
    bannerUrl: (d.banner_url as string) ?? "",
    websiteUrl: (d.website_url as string) ?? undefined,
    supportEmail: (d.email as string) ?? "",
    supportPhone: (d.phone as string) ?? "",
    socialLinks,
  };
}

export async function findOrganizer(
  slug: string,
): Promise<{ organizer: OrganizerProfile; items: PublicContent[] } | null> {
  const [organizer, listResult] = await Promise.all([
    findOrganizerProfile(slug),
    listContent({}),
  ]);
  if (!organizer) return null;
  return { organizer, items: listResult.items.slice(0, 12) };
}

export async function listOrganizerHighlights() {
  const [organizer, listResult] = await Promise.all([
    findOrganizerProfile(TENANT_SLUG),
    listContent({}),
  ]);
  if (!organizer) return [];
  return [{ organizer, items: listResult.items.slice(0, 3) }];
}

export async function listSpeakerHighlights() {
  const listResult = await listContent({});
  return listResult.items.flatMap((item) =>
    item.speakers.map((speaker) => ({
      ...speaker,
      itemTitle: item.title,
      itemSlug: item.slug,
      itemModule: item.module,
      category: item.category,
      city: item.city,
      organizerSlug: item.organizerSlug,
      organizerName: item.organizers[0]?.name ?? "Organisateur",
      organizerLogoUrl: item.organizers[0]?.imageUrl ?? speaker.imageUrl,
    })),
  );
}

export async function listCategoryOverview() {
  const res = await apiFetch<{ data: { categories: string[] } }>(`${contentBase()}/filters`);
  const categories = res?.data?.categories ?? [];
  return categories.map((category) => ({ category, count: 0, sample: null as PublicContent | null }));
}

export async function listCityOverview() {
  const res = await apiFetch<{ data: { cities: string[] } }>(`${contentBase()}/filters`);
  const cities = res?.data?.cities ?? [];
  return cities.map((city) => ({ city, count: 0, sample: null as PublicContent | null }));
}

export async function listReferenceFilters(): Promise<{ categories: string[]; cities: string[] }> {
  const res = await apiFetch<{ data: { categories: string[]; cities: string[] } }>(
    `${contentBase()}/filters`,
  );
  return { categories: res?.data?.categories ?? [], cities: res?.data?.cities ?? [] };
}

export async function listSearchSuggestions(
  query: string,
  module?: ModuleRoute | "all",
  limit = 6,
): Promise<SearchSuggestion[]> {
  const q = query.trim();
  if (q.length < 2) return [];

  const qs = buildQs({
    q,
    module: module && module !== "all" ? module : undefined,
    per_page: limit,
  });
  const res = await apiFetch<ApiListResponse>(`${contentBase()}${qs}`);
  return (res?.data ?? []).map((item) => ({
    title: item.title,
    href: `/${item.module}/${item.slug}`,
    module: item.module,
    city: item.city,
    category: item.category,
  }));
}
