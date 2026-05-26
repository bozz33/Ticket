import { Controller, Get, Query } from '@nestjs/common';
import { KpisService } from './kpis.service';

@Controller('kpis')
export class KpisController {
  constructor(private readonly kpis: KpisService) {}

  @Get('summary')
  async summary(@Query('tenant_id') tenantId?: string, @Query('days') days?: string): Promise<Record<string, unknown>> {
    return {
      data: await this.kpis.summary(tenantId ?? null, Number.parseInt(days ?? '', 10) || undefined),
    };
  }
}
