import { Controller, Get, Inject, NotFoundException, Param, Query } from '@nestjs/common';
import { CATALOG_REPOSITORY, CatalogRepository } from './catalog.repository';
import { normalizePagination } from './pagination';

@Controller('catalog')
export class CatalogController {
  constructor(@Inject(CATALOG_REPOSITORY) private readonly catalog: CatalogRepository) {}

  @Get()
  async index(@Query() query: Record<string, string | undefined>): Promise<Record<string, unknown>> {
    const pagination = normalizePagination(query.page, query.per_page ?? query.perPage);
    const result = await this.catalog.list(
      {
        module: query.module,
        q: query.q,
        category: query.category,
        city: query.city,
        countryCode: query.country_code ?? query.countryCode,
        price: query.price === 'free' || query.price === 'paid' ? query.price : null,
        sort: query.sort,
      },
      pagination,
    );

    return {
      data: result.items,
      meta: {
        current_page: pagination.page,
        per_page: pagination.perPage,
        total: result.total,
        total_pages: Math.ceil(result.total / pagination.perPage),
      },
    };
  }

  @Get(':module/:slug')
  async show(
    @Param('module') module: string,
    @Param('slug') slug: string,
    @Query('tenant') tenantSlug?: string,
  ): Promise<Record<string, unknown>> {
    const item = await this.catalog.find(module, slug, tenantSlug);
    if (!item) {
      throw new NotFoundException('Catalog item not found');
    }

    return { data: item };
  }
}
