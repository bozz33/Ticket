import { Module } from '@nestjs/common';
import { KpisModule } from '../kpis/kpis.module';
import { DashboardsController } from './dashboards.controller';

@Module({
  imports: [KpisModule],
  controllers: [DashboardsController],
})
export class DashboardsModule {}
