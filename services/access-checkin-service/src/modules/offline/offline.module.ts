import { Module } from '@nestjs/common';
import { DatabaseModule } from '../../shared/database/database.module';
import { OfflineController } from './offline.controller';

@Module({
  imports: [DatabaseModule],
  controllers: [OfflineController],
})
export class OfflineModule {}
