import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { ScheduleModule } from '@nestjs/schedule';
import { ChannelsModule } from './modules/channels/channels.module';
import { HealthModule } from './modules/health/health.module';
import { HistoryModule } from './modules/history/history.module';
import { OutboxConsumerModule } from './modules/outbox-consumer/outbox-consumer.module';
import { PreferencesModule } from './modules/preferences/preferences.module';
import { TemplatesModule } from './modules/templates/templates.module';
import { DatabaseModule } from './shared/database/database.module';

@Module({
  imports: [
    ConfigModule.forRoot({ isGlobal: true }),
    ScheduleModule.forRoot(),
    DatabaseModule,
    TemplatesModule,
    PreferencesModule,
    ChannelsModule,
    HistoryModule,
    OutboxConsumerModule,
    HealthModule,
  ],
})
export class AppModule {}
