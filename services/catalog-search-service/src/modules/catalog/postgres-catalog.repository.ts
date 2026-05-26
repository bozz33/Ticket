import { Inject, Injectable } from '@nestjs/common';
import { Pool } from 'pg';
import { CATALOG_POOL } from '../../shared/database/database.constants';
import { CatalogFilters, CatalogItem, Pagination } from './catalog.types';
import { CatalogRepository } from './catalog.repository';

type CatalogRow = {
  id: number;
  tenant_slug: string;
  tenant_name: string;
  module: string;
  item_slug: string;
  title: string;
  summary: string | null;
  category: string | null;
  city: string | null;
  country_code: string | null;
  currency_code: string | null;
  price_from: string;
  is_free: boolean;
  likes_count: number;
  weekly_likes_count: number;
  popularity_score: number;
  published_at: string | null;
  starts_at: string | null;
  ends_at: string | null;
  payload: Record<string, unknown>;
};

@Injectable()
export class PostgresCatalogRepository implements CatalogRepository {
  constructor(@Inject(CATALOG_POOL) private readonly pool: Pool) {}

  async list(filters: CatalogFilters, pagination: Pagination): Promise<{ items: CatalogItem[]; total: number }> {
    const where = this.where(filters);
    const orderBy = this.orderBy(filters.sort);
    const params = where.params;
    const countResult = await this.pool.query<{ total: string }>(
      `SELECT COUNT(*)::text AS total FROM catalog_items ${where.sql}`,
      params,
    );

    const result = await this.pool.query<CatalogRow>(
      `
        SELECT *
        FROM catalog_items
        ${where.sql}
        ${orderBy}
        OFFSET $${params.length + 1}
        LIMIT $${params.length + 2}
      `,
      [...params, (pagination.page - 1) * pagination.perPage, pagination.perPage],
    );

    return { items: result.rows.map(mapRow), total: Number(countResult.rows[0]?.total ?? 0) };
  }

  async find(module: string, slug: string, tenantSlug?: string | null): Promise<CatalogItem | null> {
    const result = await this.pool.query<CatalogRow>(
      `
        SELECT *
        FROM catalog_items
        WHERE module = $1
          AND item_slug = $2
          AND ($3::text IS NULL OR tenant_slug = $3)
          AND (ends_at IS NULL OR ends_at >= NOW())
        ORDER BY published_at DESC NULLS LAST
        LIMIT 1
      `,
      [module, slug, tenantSlug ?? null],
    );

    return result.rows[0] ? mapRow(result.rows[0]) : null;
  }

  async suggestions(query: string, limit: number): Promise<CatalogItem[]> {
    const result = await this.pool.query<CatalogRow>(
      `
        SELECT *
        FROM catalog_items
        WHERE search_text ILIKE '%' || $1 || '%'
          AND (ends_at IS NULL OR ends_at >= NOW())
        ORDER BY popularity_score DESC, published_at DESC NULLS LAST
        LIMIT $2
      `,
      [query, Math.max(1, Math.min(20, limit))],
    );

    return result.rows.map(mapRow);
  }

  async upsert(item: Record<string, unknown>): Promise<void> {
    await this.pool.query(
      `
        INSERT INTO catalog_items(
          tenant_id, tenant_public_id, tenant_slug, tenant_name, module, item_public_id, item_slug,
          title, summary, category, city, country_code, currency_code, price_from, is_free,
          is_featured, likes_count, weekly_likes_count, popularity_score, published_at, starts_at,
          ends_at, search_text, payload, updated_at
        )
        VALUES (
          $1, $2, $3, $4, $5, $6, $7,
          $8, $9, $10, $11, $12, $13, $14, $15,
          $16, $17, $18, $19, $20, $21,
          $22, $23, $24::jsonb, NOW()
        )
        ON CONFLICT (tenant_id, module, item_public_id)
        DO UPDATE SET
          tenant_public_id = EXCLUDED.tenant_public_id,
          tenant_slug = EXCLUDED.tenant_slug,
          tenant_name = EXCLUDED.tenant_name,
          item_slug = EXCLUDED.item_slug,
          title = EXCLUDED.title,
          summary = EXCLUDED.summary,
          category = EXCLUDED.category,
          city = EXCLUDED.city,
          country_code = EXCLUDED.country_code,
          currency_code = EXCLUDED.currency_code,
          price_from = EXCLUDED.price_from,
          is_free = EXCLUDED.is_free,
          is_featured = EXCLUDED.is_featured,
          likes_count = EXCLUDED.likes_count,
          weekly_likes_count = EXCLUDED.weekly_likes_count,
          popularity_score = EXCLUDED.popularity_score,
          published_at = EXCLUDED.published_at,
          starts_at = EXCLUDED.starts_at,
          ends_at = EXCLUDED.ends_at,
          search_text = EXCLUDED.search_text,
          payload = EXCLUDED.payload,
          updated_at = NOW()
      `,
      [
        str(item.tenant_id),
        str(item.tenant_public_id),
        str(item.tenant_slug),
        str(item.tenant_name),
        str(item.module),
        str(item.item_public_id),
        str(item.item_slug),
        str(item.title),
        nullableStr(item.summary),
        nullableStr(item.category),
        nullableStr(item.city),
        nullableStr(item.country_code),
        nullableStr(item.currency_code),
        int(item.price_from),
        bool(item.is_free, true),
        bool(item.is_featured, false),
        int(item.likes_count),
        int(item.weekly_likes_count),
        int(item.popularity_score),
        nullableStr(item.published_at),
        nullableStr(item.starts_at),
        nullableStr(item.ends_at),
        str(item.search_text),
        JSON.stringify(isRecord(item.payload) ? item.payload : item),
      ],
    );
  }

  async sitemapItems(limit: number): Promise<CatalogItem[]> {
    const result = await this.pool.query<CatalogRow>(
      `
        SELECT *
        FROM catalog_items
        WHERE published_at IS NOT NULL
          AND (ends_at IS NULL OR ends_at >= NOW())
        ORDER BY published_at DESC
        LIMIT $1
      `,
      [Math.max(1, Math.min(5000, limit))],
    );

    return result.rows.map(mapRow);
  }

  private where(filters: CatalogFilters): { sql: string; params: unknown[] } {
    const clauses = ['(ends_at IS NULL OR ends_at >= NOW())'];
    const params: unknown[] = [];

    pushFilter(clauses, params, 'module', filters.module);
    pushFilter(clauses, params, 'category', filters.category);
    pushFilter(clauses, params, 'city', filters.city);
    pushFilter(clauses, params, 'country_code', filters.countryCode);

    if (filters.price === 'free') {
      clauses.push('is_free = TRUE');
    } else if (filters.price === 'paid') {
      clauses.push('is_free = FALSE');
    }

    if (filters.q && filters.q.trim() !== '') {
      params.push(filters.q.trim());
      clauses.push(`search_text ILIKE '%' || $${params.length} || '%'`);
    }

    return { sql: `WHERE ${clauses.join(' AND ')}`, params };
  }

  private orderBy(sort?: string | null): string {
    if (sort === 'popular') {
      return 'ORDER BY popularity_score DESC, likes_count DESC, published_at DESC NULLS LAST';
    }

    if (sort === 'weekly') {
      return 'ORDER BY weekly_likes_count DESC, likes_count DESC, published_at DESC NULLS LAST';
    }

    if (sort === 'price_asc') {
      return 'ORDER BY is_free DESC, price_from ASC, published_at DESC NULLS LAST';
    }

    return 'ORDER BY published_at DESC NULLS LAST, id DESC';
  }
}

function pushFilter(clauses: string[], params: unknown[], column: string, value?: string | null): void {
  if (value && value.trim() !== '') {
    params.push(value.trim());
    clauses.push(`${column} = $${params.length}`);
  }
}

function mapRow(row: CatalogRow): CatalogItem {
  return {
    id: row.id,
    tenantSlug: row.tenant_slug,
    tenantName: row.tenant_name,
    module: row.module,
    itemSlug: row.item_slug,
    title: row.title,
    summary: row.summary,
    category: row.category,
    city: row.city,
    countryCode: row.country_code,
    currencyCode: row.currency_code,
    priceFrom: Number(row.price_from),
    isFree: row.is_free,
    likesCount: row.likes_count,
    weeklyLikesCount: row.weekly_likes_count,
    popularityScore: row.popularity_score,
    publishedAt: row.published_at,
    startsAt: row.starts_at,
    endsAt: row.ends_at,
    payload: row.payload ?? {},
  };
}

function str(value: unknown): string {
  return typeof value === 'string' ? value : String(value ?? '');
}

function nullableStr(value: unknown): string | null {
  return value === null || value === undefined || value === '' ? null : str(value);
}

function int(value: unknown): number {
  const parsed = Number.parseInt(String(value ?? '0'), 10);
  return Number.isFinite(parsed) ? parsed : 0;
}

function bool(value: unknown, fallback: boolean): boolean {
  return typeof value === 'boolean' ? value : fallback;
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}
