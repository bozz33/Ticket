import { Injectable, Logger } from '@nestjs/common';
import { NotificationChannel, NotificationSendInput, NotificationSendResult } from './notification-channel';

@Injectable()
export class InAppChannel implements NotificationChannel {
  readonly name = 'in-app' as const;
  private readonly logger = new Logger(InAppChannel.name);

  async send(input: NotificationSendInput): Promise<NotificationSendResult> {
    this.logger.log(`in-app notification queued for ${input.recipient}`);
    return { providerReference: `log-in-app:${Date.now()}` };
  }
}
