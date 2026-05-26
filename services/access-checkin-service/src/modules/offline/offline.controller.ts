import { Body, Controller, Inject, Post } from '@nestjs/common';
import { randomUUID } from 'node:crypto';
import { Pool } from 'pg';
import { CHECKIN_POOL } from '../../shared/database/database.constants';

@Controller('offline-batches')
export class OfflineController {
  constructor(@Inject(CHECKIN_POOL) private readonly pool: Pool) {}

  @Post()
  async create(@Body() body: Record<string, unknown>): Promise<Record<string, unknown>> {
    const id = randomUUID();
    await this.pool.query(
      `
        INSERT INTO checkin_offline_batches(id, tenant_id, agent_id, device_id, payload)
        VALUES ($1, $2, $3, $4, $5::jsonb)
      `,
      [
        id,
        String(body.tenant_id ?? body.tenantId ?? ''),
        typeof body.agent_id === 'string' ? body.agent_id : null,
        typeof body.device_id === 'string' ? body.device_id : null,
        JSON.stringify(Array.isArray(body.scans) ? body.scans : []),
      ],
    );

    return { status: 'queued', batch_id: id };
  }
}
