import { Module } from '@nestjs/common';
import { DatabaseModule } from '../../shared/database/database.module';
import { AssetsController } from './assets.controller';
import { ASSETS_REPOSITORY } from './assets.repository';
import { PostgresAssetsRepository } from './postgres-assets.repository';
import { SignedUrlService } from './signed-url.service';

@Module({
  imports: [DatabaseModule],
  controllers: [AssetsController],
  providers: [
    PostgresAssetsRepository,
    SignedUrlService,
    { provide: ASSETS_REPOSITORY, useExisting: PostgresAssetsRepository },
  ],
  exports: [ASSETS_REPOSITORY, SignedUrlService],
})
export class AssetsModule {}
