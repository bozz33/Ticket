import { Module } from '@nestjs/common';
import { Pool } from 'pg';
import { serviceConfig } from '../config/service.config';
import { CATALOG_POOL } from './database.constants';

@Module({
  providers: [
    {
      provide: CATALOG_POOL,
      useFactory: (): Pool => new Pool({ connectionString: serviceConfig().catalogDatabaseUrl }),
    },
  ],
  exports: [CATALOG_POOL],
})
export class DatabaseModule {}
