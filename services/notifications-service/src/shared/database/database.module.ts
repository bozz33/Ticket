import { Module } from '@nestjs/common';
import { Pool } from 'pg';
import { serviceConfig } from '../config/service.config';
import { NOTIFICATIONS_POOL, OUTBOX_POOL } from './database.constants';

@Module({
  providers: [
    {
      provide: OUTBOX_POOL,
      useFactory: (): Pool => new Pool({ connectionString: serviceConfig().outboxDatabaseUrl }),
    },
    {
      provide: NOTIFICATIONS_POOL,
      useFactory: (): Pool => new Pool({ connectionString: serviceConfig().notificationsDatabaseUrl }),
    },
  ],
  exports: [OUTBOX_POOL, NOTIFICATIONS_POOL],
})
export class DatabaseModule {}
