import { Module } from '@nestjs/common';
import { Pool } from 'pg';
import { serviceConfig } from '../config/service.config';
import { MEDIA_POOL } from './database.constants';

@Module({
  providers: [
    {
      provide: MEDIA_POOL,
      useFactory: (): Pool => new Pool({ connectionString: serviceConfig().mediaDatabaseUrl }),
    },
  ],
  exports: [MEDIA_POOL],
})
export class DatabaseModule {}
