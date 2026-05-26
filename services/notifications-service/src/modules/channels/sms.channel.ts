import { Injectable, Logger } from '@nestjs/common';
import { NotificationChannel, NotificationSendInput, NotificationSendResult } from './notification-channel';

@Injectable()
export class SmsChannel implements NotificationChannel {
  readonly name = 'sms' as const;
  private readonly logger = new Logger(SmsChannel.name);

  async send(input: NotificationSendInput): Promise<NotificationSendResult> {
    this.logger.log(`sms queued for ${input.recipient}`);
    return { providerReference: `log-sms:${Date.now()}` };
  }
}
