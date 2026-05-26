import { Controller, Get } from '@nestjs/common';

@Controller('health')
export class HealthController {
  @Get()
  show(): { status: 'ok'; service: string } {
    return {
      status: 'ok',
      service: 'notifications-service',
    };
  }
}
