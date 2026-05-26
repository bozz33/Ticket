import { Module } from '@nestjs/common';
import { EmailChannel } from './email.channel';
import { InAppChannel } from './in-app.channel';
import { SmsChannel } from './sms.channel';

@Module({
  providers: [EmailChannel, SmsChannel, InAppChannel],
  exports: [EmailChannel, SmsChannel, InAppChannel],
})
export class ChannelsModule {}
