import { Controller, Get, Query } from '@nestjs/common';
import { KpisService } from '../kpis/kpis.service';

@Controller('dashboards')
export class DashboardsController {
  constructor(private readonly kpis: KpisService) {}

  @Get('overview')
  async overview(@Query('tenant_id') tenantId?: string): Promise<Record<string, unknown>> {
    return {
      data: {
        kpis: await this.kpis.summary(tenantId ?? null),
      },
    };
  }
}
