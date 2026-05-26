import { DomainEventEnvelope } from '../events/domain-event';

export interface NotificationHistoryRepository {
  hasProcessed(eventId: string): Promise<boolean>;
  markProcessing(event: DomainEventEnvelope): Promise<void>;
  markProcessed(eventId: string): Promise<void>;
  markFailed(eventId: string, error: string): Promise<void>;
  recordMessage(message: NotificationHistoryMessage): Promise<void>;
}

export type NotificationHistoryMessage = {
  eventId: string;
  tenantId: string | null;
  recipient: string;
  channel: string;
  templateKey: string;
  subject: string | null;
  body: string;
  status: string;
  providerReference?: string | null;
  error?: string | null;
};
