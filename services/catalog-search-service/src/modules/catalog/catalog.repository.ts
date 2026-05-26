import { CatalogFilters, CatalogItem, Pagination } from './catalog.types';

export interface CatalogRepository {
  list(filters: CatalogFilters, pagination: Pagination): Promise<{ items: CatalogItem[]; total: number }>;
  find(module: string, slug: string, tenantSlug?: string | null): Promise<CatalogItem | null>;
  suggestions(query: string, limit: number): Promise<CatalogItem[]>;
  upsert(item: Record<string, unknown>): Promise<void>;
  sitemapItems(limit: number): Promise<CatalogItem[]>;
}

export const CATALOG_REPOSITORY = Symbol('CATALOG_REPOSITORY');
