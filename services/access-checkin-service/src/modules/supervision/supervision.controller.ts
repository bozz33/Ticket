import { Controller, Get, Inject, Param, Query } from '@nestjs/common';
import { Pool } from 'pg';
import { CHECKIN_POOL } from '../../shared/database/database.constants';

@Controller('events')
export class SupervisionController {
  constructor(@Inject(CHECKIN_POOL) private readonly pool: Pool) {}

  @Get(':eventId/summary')
  async summary(@Param('eventId') eventId: string, @Query('tenant_id') tenantId = ''): Promise<Record<string, unknown>> {
    const result = await this.pool.query(
      `
        SELECT status, COUNT(*)::int AS total
        FROM checkin_scans
        WHERE event_id = $1
          AND ($2::text = '' OR tenant_id = $2)
        GROUP BY status
        ORDER BY status
      `,
      [eventId, tenantId],
    );

    return { data: result.rows };
  }
}
