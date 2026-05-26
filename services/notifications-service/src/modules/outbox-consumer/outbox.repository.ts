import { DomainOutboxRow } from '../events/domain-event';

export interface OutboxRepository {
  fetchPending(limit: number): Promise<DomainOutboxRow[]>;
  markPublished(eventId: string): Promise<void>;
  markFailed(eventId: string, error: string): Promise<void>;
}
