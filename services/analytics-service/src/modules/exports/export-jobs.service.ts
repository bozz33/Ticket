import { randomUUID } from 'node:crypto';
import { Inject, Injectable } from '@nestjs/common';
import { Pool } from 'pg';
import { ANALYTICS_POOL } from '../../shared/database/database.constants';

@Injectable()
export class ExportJobsService {
  constructor(@Inject(ANALYTICS_POOL) private readonly pool: Pool) {}

  async create(input: Record<string, unknown>): Promise<Record<string, unknown>> {
    const id = randomUUID();
    const result = await this.pool.query(
      `
        INSERT INTO analytics_export_jobs(id, tenant_id, export_type, filters)
        VALUES ($1, $2, $3, $4::jsonb)
        RETURNING id, tenant_id, export_type, status, created_at
      `,
      [
        id,
        typeof input.tenant_id === 'string' ? input.tenant_id : null,
        typeof input.export_type === 'string' ? input.export_type : 'dashboard',
        JSON.stringify(isRecord(input.filters) ? input.filters : {}),
      ],
    );

    return result.rows[0];
  }
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}
