import { Module } from '@nestjs/common';
import { DatabaseModule } from '../../shared/database/database.module';
import { ExportJobsService } from './export-jobs.service';
import { ExportsController } from './exports.controller';

@Module({
  imports: [DatabaseModule],
  controllers: [ExportsController],
  providers: [ExportJobsService],
})
export class ExportsModule {}
