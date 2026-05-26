import { Controller, Get, Header, Inject } from '@nestjs/common';
import { serviceConfig } from '../../shared/config/service.config';
import { CATALOG_REPOSITORY, CatalogRepository } from '../catalog/catalog.repository';

@Controller('sitemap.xml')
export class SitemapController {
  constructor(@Inject(CATALOG_REPOSITORY) private readonly catalog: CatalogRepository) {}

  @Get()
  @Header('Content-Type', 'application/xml')
  async index(): Promise<string> {
    const baseUrl = serviceConfig().publicBaseUrl.replace(/\/$/, '');
    const items = await this.catalog.sitemapItems(5000);
    const urls = items.map((item) => {
      const loc = `${baseUrl}/${encodeURIComponent(item.module)}/${encodeURIComponent(item.itemSlug)}`;
      return `<url><loc>${loc}</loc></url>`;
    });

    return `<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">${urls.join('')}</urlset>`;
  }
}
