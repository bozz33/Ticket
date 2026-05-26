import { Injectable } from '@nestjs/common';
import { DomainEventEnvelope } from '../events/domain-event';
import { NotificationChannelName } from '../channels/notification-channel';

export type NotificationRoute = {
  recipient: string;
  channel: NotificationChannelName;
  templateKey: string;
  context: Record<string, unknown>;
};

@Injectable()
export class NotificationRouterService {
  route(event: DomainEventEnvelope): NotificationRoute[] {
    const recipients = this.recipients(event);

    return recipients.flatMap((recipient) => this.channels(event).map((channel) => ({
      recipient,
      channel,
      templateKey: this.templateKey(event),
      context: {
        ...event.payload,
        event_type: event.type,
        tenant_id: event.tenantId,
      },
    })));
  }

  private recipients(event: DomainEventEnvelope): string[] {
    const direct = event.payload.recipients ?? event.metadata.recipients;
    if (Array.isArray(direct)) {
      return direct.filter((value): value is string => typeof value === 'string' && value.trim() !== '');
    }

    const candidates = [
      event.payload.email,
      event.payload.buyer_email,
      event.payload.holder_email,
      event.payload.recipient,
      event.metadata.recipient,
    ];

    return candidates.filter((value): value is string => typeof value === 'string' && value.trim() !== '');
  }

  private channels(event: DomainEventEnvelope): NotificationChannelName[] {
    const configured = event.payload.channels ?? event.metadata.channels;
    if (Array.isArray(configured)) {
      const valid = configured.filter((value): value is NotificationChannelName =>
        value === 'email' || value === 'sms' || value === 'in-app',
      );

      if (valid.length > 0) {
        return valid;
      }
    }

    return ['email'];
  }

  private templateKey(event: DomainEventEnvelope): string {
    const value = event.payload.template_key ?? event.metadata.template_key;
    return typeof value === 'string' && value.trim() !== '' ? value : event.type;
  }
}
