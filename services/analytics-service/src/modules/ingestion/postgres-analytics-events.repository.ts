import { Inject, Injectable } from '@nestjs/common';
import { Pool } from 'pg';
import { ANALYTICS_POOL } from '../../shared/database/database.constants';
import { AnalyticsEvent } from '../events/analytics-event';
import { AnalyticsEventsRepository } from './analytics-events.repository';

@Injectable()
export class PostgresAnalyticsEventsRepository implements AnalyticsEventsRepository {
  constructor(@Inject(ANALYTICS_POOL) private readonly pool: Pool) {}

  async ingest(event: AnalyticsEvent): Promise<void> {
    await this.pool.query(
      `
        INSERT INTO analytics_events(
          event_id, event_type, event_version, tenant_id, module, aggregate_type,
          aggregate_id, occurred_at, payload, metadata
        )
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9::jsonb, $10::jsonb)
        ON CONFLICT (event_id) DO NOTHING
      `,
      [
        event.eventId,
        event.eventType,
        event.eventVersion,
        event.tenantId,
        event.module,
        event.aggregateType,
        event.aggregateId,
        event.occurredAt,
        JSON.stringify(event.payload),
        JSON.stringify(event.metadata),
      ],
    );
  }
}
