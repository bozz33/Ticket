import { Injectable, ServiceUnavailableException } from '@nestjs/common';
import { Request } from 'express';
import { GatewayServiceName, serviceConfig } from '../../shared/config/service.config';
import { buildForwardHeaders } from './gateway-headers';

@Injectable()
export class ProxyService {
  private readonly config = serviceConfig();

  async forward(service: GatewayServiceName, path: string, request: Request): Promise<unknown> {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), this.config.timeoutMs);
    const url = this.targetUrl(service, path, request.url);

    try {
      const response = await fetch(url, {
        method: request.method,
        headers: buildForwardHeaders(request.headers),
        body: ['GET', 'HEAD'].includes(request.method) ? undefined : JSON.stringify(request.body ?? {}),
        signal: controller.signal,
      });

      const text = await response.text();
      const payload = text !== '' ? JSON.parse(text) : null;

      if (!response.ok) {
        return {
          statusCode: response.status,
          error: payload,
        };
      }

      return payload;
    } catch (error) {
      const message = error instanceof Error ? error.message : String(error);
      throw new ServiceUnavailableException(`Service ${service} unavailable: ${message}`);
    } finally {
      clearTimeout(timeout);
    }
  }

  private targetUrl(service: GatewayServiceName, path: string, originalUrl: string): string {
    const query = originalUrl.includes('?') ? originalUrl.slice(originalUrl.indexOf('?')) : '';
    return `${this.config.services[service]}/${path.replace(/^\/+/, '')}${query}`;
  }
}
