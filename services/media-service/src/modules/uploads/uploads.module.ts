import { Module } from '@nestjs/common';
import { AssetsModule } from '../assets/assets.module';
import { QuotasModule } from '../quotas/quotas.module';
import { UploadsController } from './uploads.controller';

@Module({
  imports: [AssetsModule, QuotasModule],
  controllers: [UploadsController],
})
export class UploadsModule {}
