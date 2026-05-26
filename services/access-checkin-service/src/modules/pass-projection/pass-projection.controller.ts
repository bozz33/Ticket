import { Body, Controller, Inject, Post } from '@nestjs/common';
import { Pool } from 'pg';
import { CHECKIN_POOL } from '../../shared/database/database.constants';
import { normalizeScanReference } from '../scan/scan-reference';

@Controller('projections')
export class PassProjectionController {
  constructor(@Inject(CHECKIN_POOL) private readonly pool: Pool) {}

  @Post('passes/upsert')
  async upsert(@Body() body: Record<string, unknown>): Promise<Record<string, unknown>> {
    await this.pool.query(
      `
        INSERT INTO checkin_passes(
          tenant_id, event_id, event_title, event_starts_at, event_location,
          pass_id, pass_code, receipt_reference, holder_name, holder_email,
          ticket_label, status, metadata, updated_at
        )
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13::jsonb, NOW())
        ON CONFLICT (pass_code)
        DO UPDATE SET
          tenant_id = EXCLUDED.tenant_id,
          event_id = EXCLUDED.event_id,
          event_title = EXCLUDED.event_title,
          event_starts_at = EXCLUDED.event_starts_at,
          event_location = EXCLUDED.event_location,
          receipt_reference = EXCLUDED.receipt_reference,
          holder_name = EXCLUDED.holder_name,
          holder_email = EXCLUDED.holder_email,
          ticket_label = EXCLUDED.ticket_label,
          status = EXCLUDED.status,
          metadata = EXCLUDED.metadata,
          updated_at = NOW()
      `,
      [
        str(body.tenant_id),
        str(body.event_id),
        str(body.event_title),
        nullableStr(body.event_starts_at),
        nullableStr(body.event_location),
        str(body.pass_id),
        normalizeScanReference(str(body.pass_code)),
        normalizeNullableReference(body.receipt_reference),
        nullableStr(body.holder_name),
        nullableStr(body.holder_email),
        nullableStr(body.ticket_label),
        nullableStr(body.status) ?? 'issued',
        JSON.stringify(isRecord(body.metadata) ? body.metadata : {}),
      ],
    );

    return { status: 'ok' };
  }
}

function str(value: unknown): string {
  return typeof value === 'string' ? value : String(value ?? '');
}

function nullableStr(value: unknown): string | null {
  const normalized = str(value);
  return normalized === '' ? null : normalized;
}

function normalizeNullableReference(value: unknown): string | null {
  const normalized = nullableStr(value);
  return normalized ? normalizeScanReference(normalized) : null;
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}
