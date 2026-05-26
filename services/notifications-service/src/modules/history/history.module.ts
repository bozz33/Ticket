import { Module } from '@nestjs/common';
import { DatabaseModule } from '../../shared/database/database.module';
import { NOTIFICATION_HISTORY } from './history.constants';
import { PostgresNotificationHistoryRepository } from './postgres-notification-history.repository';

@Module({
  imports: [DatabaseModule],
  providers: [
    PostgresNotificationHistoryRepository,
    { provide: NOTIFICATION_HISTORY, useExisting: PostgresNotificationHistoryRepository },
  ],
  exports: [NOTIFICATION_HISTORY],
})
export class HistoryModule {}
