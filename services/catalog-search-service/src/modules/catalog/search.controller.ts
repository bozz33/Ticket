import { Controller, Get, Inject, Query } from '@nestjs/common';
import { CATALOG_REPOSITORY, CatalogRepository } from './catalog.repository';

@Controller('search')
export class SearchController {
  constructor(@Inject(CATALOG_REPOSITORY) private readonly catalog: CatalogRepository) {}

  @Get('suggestions')
  async suggestions(@Query('q') query = '', @Query('limit') limit = '8'): Promise<Record<string, unknown>> {
    if (query.trim().length < 2) {
      return { data: [] };
    }

    return {
      data: await this.catalog.suggestions(query.trim(), Number.parseInt(limit, 10) || 8),
    };
  }
}
