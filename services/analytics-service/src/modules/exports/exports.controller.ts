import { Body, Controller, Post } from '@nestjs/common';
import { ExportJobsService } from './export-jobs.service';

@Controller('exports')
export class ExportsController {
  constructor(private readonly exports: ExportJobsService) {}

  @Post()
  async create(@Body() body: Record<string, unknown>): Promise<Record<string, unknown>> {
    return { data: await this.exports.create(body) };
  }
}
