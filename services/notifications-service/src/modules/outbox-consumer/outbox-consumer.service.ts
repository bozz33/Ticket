import { Inject, Injectable, Logger } from '@nestjs/common';
import { Interval } from '@nestjs/schedule';
import { serviceConfig } from '../../shared/config/service.config';
import { EmailChannel } from '../channels/email.channel';
import { InAppChannel } from '../channels/in-app.channel';
import { NotificationChannel } from '../channels/notification-channel';
import { SmsChannel } from '../channels/sms.channel';
import { normalizeOutboxRow } from '../events/domain-event';
import { NOTIFICATION_HISTORY } from '../history/history.constants';
import { NotificationHistoryRepository } from '../history/notification-history.repository';
import { PreferencesService } from '../preferences/preferences.service';
import { TemplateRendererService } from '../templates/template-renderer.service';
import { NotificationRouterService } from './notification-router.service';
import { OUTBOX_REPOSITORY } from './outbox.constants';
import { OutboxRepository } from './outbox.repository';

@Injectable()
export class OutboxConsumerService {
  private readonly logger = new Logger(OutboxConsumerService.name);
  private readonly config = serviceConfig();
  private readonly channels: Map<string, NotificationChannel>;
  private nextPollAt = 0;

  constructor(
    @Inject(OUTBOX_REPOSITORY) private readonly outbox: OutboxRepository,
    @Inject(NOTIFICATION_HISTORY) private readonly history: NotificationHistoryRepository,
    private readonly router: NotificationRouterService,
    private readonly templates: TemplateRendererService,
    private readonly preferences: PreferencesService,
    email: EmailChannel,
    sms: SmsChannel,
    inApp: InAppChannel,
  ) {
    this.channels = new Map<string, NotificationChannel>([
      [email.name, email],
      [sms.name, sms],
      [inApp.name, inApp],
    ]);
  }

  @Interval(5000)
  async poll(): Promise<void> {
    if (!this.config.outboxPollEnabled || Date.now() < this.nextPollAt) {
      return;
    }

    this.nextPollAt = Date.now() + this.config.outboxPollIntervalMs;

    const rows = await this.outbox.fetchPending(this.config.outboxBatchSize);

    for (const row of rows) {
      const event = normalizeOutboxRow(row);

      try {
        if (await this.history.hasProcessed(event.eventId)) {
          await this.outbox.markPublished(event.eventId);
          continue;
        }

        await this.history.markProcessing(event);

        const routes = this.router.route(event);
        for (const route of routes) {
          const allowed = await this.preferences.isChannelAllowed(route.recipient, route.channel, event.type);
          if (!allowed) {
            continue;
          }

          const rendered = this.templates.render(route.templateKey, route.context);
          const channel = this.channels.get(route.channel);
          if (!channel) {
            throw new Error(`Unsupported notification channel: ${route.channel}`);
          }

          const result = await channel.send({
            recipient: route.recipient,
            subject: rendered.subject,
            body: rendered.body,
            tenantId: event.tenantId,
            metadata: event.metadata,
          });

          await this.history.recordMessage({
            eventId: event.eventId,
            tenantId: event.tenantId,
            recipient: route.recipient,
            channel: route.channel,
            templateKey: route.templateKey,
            subject: rendered.subject,
            body: rendered.body,
            status: 'sent',
            providerReference: result.providerReference,
          });
        }

        await this.history.markProcessed(event.eventId);
        await this.outbox.markPublished(event.eventId);
      } catch (error) {
        const message = error instanceof Error ? error.message : String(error);
        this.logger.error(`event ${event.eventId} failed: ${message}`);
        await this.history.markFailed(event.eventId, message);
        await this.outbox.markFailed(event.eventId, message);
      }
    }
  }
}
