import { Module } from '@nestjs/common';
import { DatabaseModule } from '../../shared/database/database.module';
import { ANALYTICS_EVENTS_REPOSITORY } from './analytics-events.repository';
import { IngestionController } from './ingestion.controller';
import { PostgresAnalyticsEventsRepository } from './postgres-analytics-events.repository';

@Module({
  imports: [DatabaseModule],
  controllers: [IngestionController],
  providers: [
    PostgresAnalyticsEventsRepository,
    { provide: ANALYTICS_EVENTS_REPOSITORY, useExisting: PostgresAnalyticsEventsRepository },
  ],
  exports: [ANALYTICS_EVENTS_REPOSITORY],
})
export class IngestionModule {}
