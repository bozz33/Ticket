import { Inject, Injectable } from '@nestjs/common';
import { Pool } from 'pg';
import { serviceConfig } from '../../shared/config/service.config';
import { ANALYTICS_POOL } from '../../shared/database/database.constants';

@Injectable()
export class KpisService {
  constructor(@Inject(ANALYTICS_POOL) private readonly pool: Pool) {}

  async summary(tenantId: string | null, days = serviceConfig().snapshotDefaultDays): Promise<Record<string, unknown>[]> {
    const result = await this.pool.query(
      `
        SELECT day, tenant_id, module, metric_key, metric_value
        FROM analytics_daily_kpis
        WHERE day >= CURRENT_DATE - ($1::int * INTERVAL '1 day')
          AND ($2::text IS NULL OR tenant_id = $2)
        ORDER BY day DESC, module NULLS FIRST, metric_key
      `,
      [days, tenantId],
    );

    return result.rows;
  }
}
