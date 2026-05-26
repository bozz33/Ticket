import { Module } from '@nestjs/common';
import { CatalogModule } from '../catalog/catalog.module';
import { ProjectionsController } from './projections.controller';

@Module({
  imports: [CatalogModule],
  controllers: [ProjectionsController],
})
export class ProjectionsModule {}
