import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { DashboardsModule } from './modules/dashboards/dashboards.module';
import { ExportsModule } from './modules/exports/exports.module';
import { HealthModule } from './modules/health/health.module';
import { IngestionModule } from './modules/ingestion/ingestion.module';
import { KpisModule } from './modules/kpis/kpis.module';
import { DatabaseModule } from './shared/database/database.module';

@Module({
  imports: [
    ConfigModule.forRoot({ isGlobal: true }),
    DatabaseModule,
    IngestionModule,
    KpisModule,
    DashboardsModule,
    ExportsModule,
    HealthModule,
  ],
})
export class AppModule {}
