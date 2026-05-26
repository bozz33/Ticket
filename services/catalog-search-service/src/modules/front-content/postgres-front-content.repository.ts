import { Inject, Injectable } from '@nestjs/common';
import { Pool } from 'pg';
import { CATALOG_POOL } from '../../shared/database/database.constants';
import { FrontContentRepository } from './front-content.repository';

@Injectable()
export class PostgresFrontContentRepository implements FrontContentRepository {
  constructor(@Inject(CATALOG_POOL) private readonly pool: Pool) {}

  async menus(locale: string): Promise<Record<string, unknown>[]> {
    const result = await this.pool.query(
      'SELECT location, locale, items, updated_at FROM catalog_front_menus WHERE active = TRUE AND locale = $1 ORDER BY location',
      [locale],
    );

    return result.rows;
  }

  async pages(locale: string): Promise<Record<string, unknown>[]> {
    const result = await this.pool.query(
      "SELECT path, title, locale, seo, updated_at FROM catalog_front_pages WHERE status = 'published' AND locale = $1 ORDER BY path",
      [locale],
    );

    return result.rows;
  }

  async page(path: string, locale: string): Promise<Record<string, unknown> | null> {
    const result = await this.pool.query(
      "SELECT path, title, locale, payload, seo, updated_at FROM catalog_front_pages WHERE status = 'published' AND path = $1 AND locale = $2 LIMIT 1",
      [path, locale],
    );

    return result.rows[0] ?? null;
  }
}
