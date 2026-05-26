import { Module } from '@nestjs/common';
import { DatabaseModule } from '../../shared/database/database.module';
import { DocumentsController } from './documents.controller';
import { DocumentJobsService } from './document-jobs.service';

@Module({
  imports: [DatabaseModule],
  controllers: [DocumentsController],
  providers: [DocumentJobsService],
})
export class DocumentsModule {}
