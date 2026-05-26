import { Inject, Injectable } from '@nestjs/common';
import { Pool } from 'pg';
import { NOTIFICATIONS_POOL } from '../../shared/database/database.constants';
import { DomainEventEnvelope } from '../events/domain-event';
import { NotificationHistoryMessage, NotificationHistoryRepository } from './notification-history.repository';

@Injectable()
export class PostgresNotificationHistoryRepository implements NotificationHistoryRepository {
  constructor(@Inject(NOTIFICATIONS_POOL) private readonly pool: Pool) {}

  async hasProcessed(eventId: string): Promise<boolean> {
    const result = await this.pool.query<{ exists: boolean }>(
      `SELECT EXISTS(SELECT 1 FROM notification_event_deliveries WHERE event_id = $1 AND status = 'processed') AS "exists"`,
      [eventId],
    );

    return result.rows[0]?.exists === true;
  }

  async markProcessing(event: DomainEventEnvelope): Promise<void> {
    await this.pool.query(
      `
        INSERT INTO notification_event_deliveries(event_id, event_type, tenant_id, status, attempts, payload, metadata)
        VALUES ($1, $2, $3, 'processing', 1, $4::jsonb, $5::jsonb)
        ON CONFLICT (event_id)
        DO UPDATE SET status = 'processing', attempts = notification_event_deliveries.attempts + 1, updated_at = NOW()
      `,
      [event.eventId, event.type, event.tenantId, JSON.stringify(event.payload), JSON.stringify(event.metadata)],
    );
  }

  async markProcessed(eventId: string): Promise<void> {
    await this.pool.query(
      `UPDATE notification_event_deliveries SET status = 'processed', processed_at = NOW(), updated_at = NOW(), last_error = NULL WHERE event_id = $1`,
      [eventId],
    );
  }

  async markFailed(eventId: string, error: string): Promise<void> {
    await this.pool.query(
      `UPDATE notification_event_deliveries SET status = 'failed', last_error = $2, updated_at = NOW() WHERE event_id = $1`,
      [eventId, error.slice(0, 4000)],
    );
  }

  async recordMessage(message: NotificationHistoryMessage): Promise<void> {
    await this.pool.query(
      `
        INSERT INTO notification_messages(
          event_id, tenant_id, recipient, channel, template_key, subject, body, status, provider_reference, last_error, sent_at
        )
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, CASE WHEN $8 = 'sent' THEN NOW() ELSE NULL END)
      `,
      [
        message.eventId,
        message.tenantId,
        message.recipient,
        message.channel,
        message.templateKey,
        message.subject,
        message.body,
        message.status,
        message.providerReference ?? null,
        message.error ?? null,
      ],
    );
  }
}
