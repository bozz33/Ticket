import { Module } from '@nestjs/common';
import { ChannelsModule } from '../channels/channels.module';
import { HistoryModule } from '../history/history.module';
import { PreferencesModule } from '../preferences/preferences.module';
import { TemplatesModule } from '../templates/templates.module';
import { DatabaseModule } from '../../shared/database/database.module';
import { NotificationRouterService } from './notification-router.service';
import { OUTBOX_REPOSITORY } from './outbox.constants';
import { OutboxConsumerService } from './outbox-consumer.service';
import { PostgresOutboxRepository } from './postgres-outbox.repository';

@Module({
  imports: [DatabaseModule, ChannelsModule, HistoryModule, PreferencesModule, TemplatesModule],
  providers: [
    PostgresOutboxRepository,
    NotificationRouterService,
    OutboxConsumerService,
    { provide: OUTBOX_REPOSITORY, useExisting: PostgresOutboxRepository },
  ],
})
export class OutboxConsumerModule {}
