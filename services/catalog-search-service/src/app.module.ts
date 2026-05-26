import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { CatalogModule } from './modules/catalog/catalog.module';
import { FrontContentModule } from './modules/front-content/front-content.module';
import { HealthModule } from './modules/health/health.module';
import { ProjectionsModule } from './modules/projections/projections.module';
import { SitemapModule } from './modules/sitemap/sitemap.module';
import { DatabaseModule } from './shared/database/database.module';

@Module({
  imports: [
    ConfigModule.forRoot({ isGlobal: true }),
    DatabaseModule,
    CatalogModule,
    ProjectionsModule,
    FrontContentModule,
    SitemapModule,
    HealthModule,
  ],
})
export class AppModule {}
