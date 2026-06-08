import type { PublicContent, SearchFilters, SortOption } from "@/lib/types";

export const staticPageHeroImages = {
  accueil: "https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1800&q=80",
  "a-propos": "https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=1800&q=80",
  categories: "https://images.unsplash.com/photo-1505236858219-8359eb29e329?auto=format&fit=crop&w=1800&q=80",
  contact: "https://images.unsplash.com/photo-1516321497487-e288fb19713f?auto=format&fit=crop&w=1800&q=80",
  remboursement: "https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=1800&q=80",
  "mentions-legales": "https://images.unsplash.com/photo-1450101499163-c8848c66ca85?auto=format&fit=crop&w=1800&q=80",
  faq: "https://images.unsplash.com/photo-1517048676732-d65bc937f952?auto=format&fit=crop&w=1800&q=80",
  "devenir-organisateur": "https://images.unsplash.com/photo-1505373877841-8d25f7d46678?auto=format&fit=crop&w=1800&q=80",
} as const;

export type StaticPageHeroKey = keyof typeof staticPageHeroImages;

export function getStaticPageHeroImage(page: StaticPageHeroKey): string {
  return staticPageHeroImages[page];
}

/**
 * Return the first non-empty (trimmed) image candidate, or undefined.
 * Passing undefined to an <img> src omits the attribute, whereas an empty string
 * triggers a React warning and a full-page re-download. Use this for any dynamic
 * image source that may be missing.
 */
export function resolveImageSrc(...candidates: Array<string | null | undefined>): string | undefined {
  for (const candidate of candidates) {
    const trimmed = candidate?.trim();

    if (trimmed) {
      return trimmed;
    }
  }

  return undefined;
}

export function formatMoney(amount: number, currency: string): string {
  return new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency,
    maximumFractionDigits: 0,
  }).format(amount);
}

export function formatDateLabel(value?: string): string {
  if (!value) {
    return "Date a venir";
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return "Date a venir";
  }

  return new Intl.DateTimeFormat("fr-FR", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(date);
}

export function formatDateRange(item: PublicContent): string {
  if (item.startsAt && item.endsAt) {
    return `${formatDateLabel(item.startsAt)} - ${formatDateLabel(item.endsAt)}`;
  }

  return formatDateLabel(item.startsAt ?? item.deadlineAt);
}

export function buildPublicUrl(path: string): string {
  const baseUrl = (process.env.NEXT_PUBLIC_SITE_URL || "http://127.0.0.1:3000").replace(/\/$/, "");

  return `${baseUrl}${path.startsWith("/") ? path : `/${path}`}`;
}

export function buildSearchQuery(filters: SearchFilters): string {
  const params = new URLSearchParams();

  if (filters.module && filters.module !== "all") {
    params.set("module", filters.module);
  }

  if (filters.q?.trim()) {
    params.set("q", filters.q.trim());
  }

  if (filters.category?.trim()) {
    params.set("category", filters.category.trim());
  }

  if (filters.city?.trim()) {
    params.set("city", filters.city.trim());
  }

  if (filters.dateFrom?.trim()) {
    params.set("date_from", filters.dateFrom.trim());
  }

  if (filters.dateTo?.trim()) {
    params.set("date_to", filters.dateTo.trim());
  }

  if (filters.price && filters.price !== "all") {
    params.set("price", filters.price);
  }

  if (filters.sort) {
    params.set("sort", filters.sort);
  }

  if (typeof filters.page === "number" && Number.isFinite(filters.page)) {
    params.set("page", String(filters.page));
  }

  const query = params.toString();

  return query.length > 0 ? `?${query}` : "";
}

export function normalizeSearchParams(
  searchParams: Record<string, string | string[] | undefined>,
): SearchFilters {
  const getValue = (key: string) => {
    const value = searchParams[key];

    return Array.isArray(value) ? value[0] : value;
  };

  const sort = getValue("sort");
  const pageValue = getValue("page");
  const parsedPage = pageValue ? Number(pageValue) : NaN;

  return {
    module: getValue("module") as SearchFilters["module"],
    q: getValue("q"),
    category: getValue("category"),
    city: getValue("city"),
    dateFrom: getValue("date_from"),
    dateTo: getValue("date_to"),
    price: (getValue("price") as SearchFilters["price"]) ?? "all",
    sort: isSortOption(sort) ? sort : "popular",
    page: Number.isInteger(parsedPage) && parsedPage > 0 ? parsedPage : undefined,
  };
}

function isSortOption(value?: string): value is SortOption {
  return value === "popular" || value === "recent" || value === "price";
}

export function matchesFilters(item: PublicContent, filters: SearchFilters): boolean {
  const q = filters.q?.trim().toLowerCase();
  const category = filters.category?.trim().toLowerCase();
  const city = filters.city?.trim().toLowerCase();
  const dateFrom = filters.dateFrom ? new Date(`${filters.dateFrom}T00:00:00`) : null;
  const dateTo = filters.dateTo ? new Date(`${filters.dateTo}T23:59:59`) : null;
  const itemStart = item.startsAt ? new Date(item.startsAt) : item.deadlineAt ? new Date(item.deadlineAt) : null;
  const itemEnd = item.endsAt ? new Date(item.endsAt) : itemStart;

  if (filters.module && filters.module !== "all" && item.module !== filters.module) {
    return false;
  }

  if (
    q &&
    ![
      item.title,
      item.summary,
      item.description,
      item.category,
      item.city,
      item.country,
    ]
      .join(" ")
      .toLowerCase()
      .includes(q)
  ) {
    return false;
  }

  if (category && item.category.toLowerCase() !== category) {
    return false;
  }

  if (city && item.city.toLowerCase() !== city) {
    return false;
  }

  if (dateFrom && itemEnd && itemEnd < dateFrom) {
    return false;
  }

  if (dateFrom && !itemEnd) {
    return false;
  }

  if (dateTo && itemStart && itemStart > dateTo) {
    return false;
  }

  if (dateTo && !itemStart) {
    return false;
  }

  if (filters.price === "free" && !item.isFree) {
    return false;
  }

  if (filters.price === "paid" && item.isFree) {
    return false;
  }

  return true;
}

export function sortItems(items: PublicContent[], sort: SortOption = "popular"): PublicContent[] {
  const copy = [...items];

  if (sort === "price") {
    return copy.sort((left, right) => left.priceFrom - right.priceFrom);
  }

  if (sort === "recent") {
    return copy.sort(
      (left, right) =>
        new Date(right.publishedAt).getTime() - new Date(left.publishedAt).getTime(),
    );
  }

  return copy.sort((left, right) => Number(right.popular) - Number(left.popular));
}

export function uniqueValues(items: PublicContent[], field: keyof Pick<PublicContent, "category" | "city">) {
  return [...new Set(items.map((item) => item[field]))].sort((left, right) =>
    left.localeCompare(right, "fr"),
  );
}
