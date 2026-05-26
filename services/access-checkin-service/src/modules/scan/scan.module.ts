import { Module } from '@nestjs/common';
import { DatabaseModule } from '../../shared/database/database.module';
import { ScanController } from './scan.controller';
import { ScanService } from './scan.service';

@Module({
  imports: [DatabaseModule],
  controllers: [ScanController],
  providers: [ScanService],
  exports: [ScanService],
})
export class ScanModule {}
