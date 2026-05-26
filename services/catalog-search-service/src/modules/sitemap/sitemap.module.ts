import { Module } from '@nestjs/common';
import { CatalogModule } from '../catalog/catalog.module';
import { SitemapController } from './sitemap.controller';

@Module({
  imports: [CatalogModule],
  controllers: [SitemapController],
})
export class SitemapModule {}
