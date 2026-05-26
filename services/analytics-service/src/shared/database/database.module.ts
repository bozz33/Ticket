import { Module } from '@nestjs/common';
import { Pool } from 'pg';
import { serviceConfig } from '../config/service.config';
import { ANALYTICS_POOL } from './database.constants';

@Module({
  providers: [
    {
      provide: ANALYTICS_POOL,
      useFactory: (): Pool => new Pool({ connectionString: serviceConfig().analyticsDatabaseUrl }),
    },
  ],
  exports: [ANALYTICS_POOL],
})
export class DatabaseModule {}
