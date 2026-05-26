import { Module } from '@nestjs/common';
import { Pool } from 'pg';
import { serviceConfig } from '../config/service.config';
import { CHECKIN_POOL } from './database.constants';

@Module({
  providers: [
    {
      provide: CHECKIN_POOL,
      useFactory: (): Pool => new Pool({ connectionString: serviceConfig().checkinDatabaseUrl }),
    },
  ],
  exports: [CHECKIN_POOL],
})
export class DatabaseModule {}
