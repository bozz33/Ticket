import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { DatabaseModule } from './shared/database/database.module';
import { HealthModule } from './modules/health/health.module';
import { OfflineModule } from './modules/offline/offline.module';
import { PassProjectionModule } from './modules/pass-projection/pass-projection.module';
import { ScanModule } from './modules/scan/scan.module';
import { SupervisionModule } from './modules/supervision/supervision.module';

@Module({
  imports: [
    ConfigModule.forRoot({ isGlobal: true }),
    DatabaseModule,
    PassProjectionModule,
    ScanModule,
    OfflineModule,
    SupervisionModule,
    HealthModule,
  ],
})
export class AppModule {}
