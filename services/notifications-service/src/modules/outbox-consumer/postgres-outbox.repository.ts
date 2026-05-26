import { Inject, Injectable } from '@nestjs/common';
import { Pool } from 'pg';
import { serviceConfig } from '../../shared/config/service.config';
import { OUTBOX_POOL } from '../../shared/database/database.constants';
import { DomainOutboxRow } from '../events/domain-event';
import { OutboxRepository } from './outbox.repository';

@Injectable()
export class PostgresOutboxRepository implements OutboxRepository {
  private readonly config = serviceConfig();

  constructor(@Inject(OUTBOX_POOL) private readonly pool: Pool) {}

  async fetchPending(limit: number): Promise<DomainOutboxRow[]> {
    const client = await this.pool.connect();

    try {
      await client.query('BEGIN');
      const result = await client.query<DomainOutboxRow>(
        `
          WITH picked AS (
            SELECT id
            FROM domain_outbox_messages
            WHERE (
                status = 'pending'
                AND COALESCE(available_at, NOW()) <= NOW()
              )
              OR (
                status = 'processing'
                AND updated_at <= NOW() - ($2::int * INTERVAL '1 second')
              )
            ORDER BY id
            LIMIT $1
            FOR UPDATE SKIP LOCKED
          )
          UPDATE domain_outbox_messages AS message
          SET status = 'processing',
              attempts = attempts + 1,
              updated_at = NOW()
          FROM picked
          WHERE message.id = picked.id
          RETURNING message.id,
                    message.event_id,
                    message.type,
                    message.aggregate_type,
                    message.aggregate_id,
                    message.payload,
                    message.metadata
        `,
        [Math.max(1, limit), this.config.outboxProcessingTimeoutSeconds],
      );
      await client.query('COMMIT');

      return result.rows;
    } catch (error) {
      await client.query('ROLLBACK');
      throw error;
    } finally {
      client.release();
    }

  }

  async markPublished(eventId: string): Promise<void> {
    await this.pool.query(
      `
        UPDATE domain_outbox_messages
        SET status = 'published', published_at = NOW(), last_error = NULL, updated_at = NOW()
        WHERE event_id = $1
      `,
      [eventId],
    );
  }

  async markFailed(eventId: string, error: string): Promise<void> {
    await this.pool.query(
      `
        UPDATE domain_outbox_messages
        SET status = CASE WHEN attempts >= $3 THEN 'failed' ELSE 'pending' END,
            available_at = CASE WHEN attempts >= $3 THEN available_at ELSE NOW() + ($4::int * INTERVAL '1 second') END,
            last_error = $2,
            updated_at = NOW()
        WHERE event_id = $1
      `,
      [eventId, error.slice(0, 4000), this.config.outboxMaxAttempts, this.config.outboxRetryDelaySeconds],
    );
  }
}
