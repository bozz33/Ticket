import { Inject, Injectable } from '@nestjs/common';
import { Pool } from 'pg';
import { CHECKIN_POOL } from '../../shared/database/database.constants';
import { normalizeScanReference } from './scan-reference';

type ScanInput = {
  tenantId: string;
  reference: string;
  agentId?: string | null;
  entryPoint?: string | null;
  deviceId?: string | null;
  offlineBatchId?: string | null;
  metadata?: Record<string, unknown>;
};

@Injectable()
export class ScanService {
  constructor(@Inject(CHECKIN_POOL) private readonly pool: Pool) {}

  async scan(input: ScanInput): Promise<Record<string, unknown>> {
    const passCode = normalizeScanReference(input.reference);
    const client = await this.pool.connect();

    try {
      await client.query('BEGIN');
      const passResult = await client.query(
        `
          SELECT *
          FROM checkin_passes
          WHERE tenant_id = $1
            AND (pass_code = $2 OR receipt_reference = $2)
          LIMIT 1
          FOR UPDATE
        `,
        [input.tenantId, passCode],
      );

      const pass = passResult.rows[0] ?? null;
      if (!pass) {
        await this.recordScan(client, input, passCode, null, 'rejected', 'pass_not_found');
        await client.query('COMMIT');
        return { status: 'rejected', reason: 'pass_not_found' };
      }

      if (pass.status !== 'issued') {
        await this.recordScan(client, input, passCode, pass.event_id, 'rejected', `pass_${pass.status}`);
        await client.query('COMMIT');
        return { status: 'rejected', reason: `pass_${pass.status}`, pass };
      }

      if (pass.used_at !== null) {
        await this.recordScan(client, input, passCode, pass.event_id, 'duplicate', 'already_used');
        await client.query('COMMIT');
        return { status: 'duplicate', reason: 'already_used', pass };
      }

      await client.query('UPDATE checkin_passes SET used_at = NOW(), status = $2, updated_at = NOW() WHERE id = $1', [
        pass.id,
        'used',
      ]);
      await this.recordScan(client, input, passCode, pass.event_id, 'accepted', null);
      await client.query('COMMIT');

      return { status: 'accepted', pass: { ...pass, status: 'used' } };
    } catch (error) {
      await client.query('ROLLBACK');
      throw error;
    } finally {
      client.release();
    }
  }

  private async recordScan(
    client: { query: (sql: string, values: unknown[]) => Promise<unknown> },
    input: ScanInput,
    passCode: string,
    eventId: string | null,
    status: string,
    reason: string | null,
  ): Promise<void> {
    await client.query(
      `
        INSERT INTO checkin_scans(
          tenant_id, event_id, pass_code, agent_id, entry_point, device_id,
          status, reason, offline_batch_id, metadata
        )
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10::jsonb)
      `,
      [
        input.tenantId,
        eventId,
        passCode,
        input.agentId ?? null,
        input.entryPoint ?? null,
        input.deviceId ?? null,
        status,
        reason,
        input.offlineBatchId ?? null,
        JSON.stringify(input.metadata ?? {}),
      ],
    );
  }
}
