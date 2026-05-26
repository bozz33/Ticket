import { Controller, Get, Inject, Query } from '@nestjs/common';
import { FRONT_CONTENT_REPOSITORY, FrontContentRepository } from './front-content.repository';

@Controller('front')
export class FrontContentController {
  constructor(@Inject(FRONT_CONTENT_REPOSITORY) private readonly front: FrontContentRepository) {}

  @Get('menus')
  async menus(@Query('locale') locale = 'fr'): Promise<Record<string, unknown>> {
    return { data: await this.front.menus(locale) };
  }

  @Get('pages')
  async pages(@Query('locale') locale = 'fr'): Promise<Record<string, unknown>> {
    return { data: await this.front.pages(locale) };
  }

  @Get('pages/by-path')
  async page(@Query('path') path = '/', @Query('locale') locale = 'fr'): Promise<Record<string, unknown>> {
    return { data: await this.front.page(path, locale) };
  }
}
