import { Body, Controller, Inject, Post } from '@nestjs/common';
import { CATALOG_REPOSITORY, CatalogRepository } from '../catalog/catalog.repository';

@Controller('projections')
export class ProjectionsController {
  constructor(@Inject(CATALOG_REPOSITORY) private readonly catalog: CatalogRepository) {}

  @Post('catalog-items/upsert')
  async upsertCatalogItem(@Body() body: Record<string, unknown>): Promise<Record<string, unknown>> {
    await this.catalog.upsert(body);
    return { status: 'ok' };
  }
}
