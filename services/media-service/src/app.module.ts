import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { AssetsModule } from './modules/assets/assets.module';
import { DocumentsModule } from './modules/documents/documents.module';
import { HealthModule } from './modules/health/health.module';
import { QrModule } from './modules/qr/qr.module';
import { QuotasModule } from './modules/quotas/quotas.module';
import { UploadsModule } from './modules/uploads/uploads.module';
import { DatabaseModule } from './shared/database/database.module';

@Module({
  imports: [
    ConfigModule.forRoot({ isGlobal: true }),
    DatabaseModule,
    QuotasModule,
    AssetsModule,
    UploadsModule,
    QrModule,
    DocumentsModule,
    HealthModule,
  ],
})
export class AppModule {}
