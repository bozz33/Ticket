import { Module } from '@nestjs/common';
import { DatabaseModule } from '../../shared/database/database.module';
import { CatalogController } from './catalog.controller';
import { CATALOG_REPOSITORY } from './catalog.repository';
import { PostgresCatalogRepository } from './postgres-catalog.repository';
import { SearchController } from './search.controller';

@Module({
  imports: [DatabaseModule],
  controllers: [CatalogController, SearchController],
  providers: [
    PostgresCatalogRepository,
    { provide: CATALOG_REPOSITORY, useExisting: PostgresCatalogRepository },
  ],
  exports: [CATALOG_REPOSITORY],
})
export class CatalogModule {}
