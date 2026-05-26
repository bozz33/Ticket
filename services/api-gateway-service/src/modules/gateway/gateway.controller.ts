import { All, Controller, Param, Req } from '@nestjs/common';
import { Request } from 'express';
import { GatewayServiceName } from '../../shared/config/service.config';
import { ProxyService } from './proxy.service';

@Controller()
export class GatewayController {
  constructor(private readonly proxy: ProxyService) {}

  @All('catalog/*path')
  catalog(@Param('path') path: string[], @Req() request: Request): Promise<unknown> {
    return this.proxy.forward('catalog', ['catalog', ...path].join('/'), request);
  }

  @All('search/*path')
  search(@Param('path') path: string[], @Req() request: Request): Promise<unknown> {
    return this.proxy.forward('catalog', ['search', ...path].join('/'), request);
  }

  @All('front/*path')
  front(@Param('path') path: string[], @Req() request: Request): Promise<unknown> {
    return this.proxy.forward('catalog', ['front', ...path].join('/'), request);
  }

  @All('media/*path')
  media(@Param('path') path: string[], @Req() request: Request): Promise<unknown> {
    return this.proxy.forward('media', path.join('/'), request);
  }

  @All('analytics/*path')
  analytics(@Param('path') path: string[], @Req() request: Request): Promise<unknown> {
    return this.proxy.forward('analytics', path.join('/'), request);
  }

  @All('checkin/*path')
  checkin(@Param('path') path: string[], @Req() request: Request): Promise<unknown> {
    return this.proxy.forward('checkin', path.join('/'), request);
  }

  @All('services/:service/*path')
  service(
    @Param('service') service: GatewayServiceName,
    @Param('path') path: string[],
    @Req() request: Request,
  ): Promise<unknown> {
    return this.proxy.forward(service, path.join('/'), request);
  }
}
