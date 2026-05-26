import { Injectable, Logger } from '@nestjs/common';
import { NotificationChannel, NotificationSendInput, NotificationSendResult } from './notification-channel';

@Injectable()
export class EmailChannel implements NotificationChannel {
  readonly name = 'email' as const;
  private readonly logger = new Logger(EmailChannel.name);

  async send(input: NotificationSendInput): Promise<NotificationSendResult> {
    this.logger.log(`email queued for ${input.recipient}`);
    return { providerReference: `log-email:${Date.now()}` };
  }
}
