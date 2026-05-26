import { Module } from '@nestjs/common';
import { DatabaseModule } from '../../shared/database/database.module';
import { FrontContentController } from './front-content.controller';
import { FRONT_CONTENT_REPOSITORY } from './front-content.repository';
import { PostgresFrontContentRepository } from './postgres-front-content.repository';

@Module({
  imports: [DatabaseModule],
  controllers: [FrontContentController],
  providers: [
    PostgresFrontContentRepository,
    { provide: FRONT_CONTENT_REPOSITORY, useExisting: PostgresFrontContentRepository },
  ],
})
export class FrontContentModule {}
