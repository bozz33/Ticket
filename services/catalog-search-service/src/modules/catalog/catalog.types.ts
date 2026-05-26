export type CatalogFilters = {
  module?: string | null;
  q?: string | null;
  category?: string | null;
  city?: string | null;
  countryCode?: string | null;
  price?: 'free' | 'paid' | null;
  sort?: string | null;
};

export type Pagination = {
  page: number;
  perPage: number;
};

export type CatalogItem = {
  id: number;
  tenantSlug: string;
  tenantName: string;
  module: string;
  itemSlug: string;
  title: string;
  summary: string | null;
  category: string | null;
  city: string | null;
  countryCode: string | null;
  currencyCode: string | null;
  priceFrom: number;
  isFree: boolean;
  likesCount: number;
  weeklyLikesCount: number;
  popularityScore: number;
  publishedAt: string | null;
  startsAt: string | null;
  endsAt: string | null;
  payload: Record<string, unknown>;
};
