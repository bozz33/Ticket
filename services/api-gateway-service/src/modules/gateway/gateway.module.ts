import { Module } from '@nestjs/common';
import { GatewayController } from './gateway.controller';
import { ProxyService } from './proxy.service';

@Module({
  controllers: [GatewayController],
  providers: [ProxyService],
})
export class GatewayModule {}
