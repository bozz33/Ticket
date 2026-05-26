export type GatewayServiceName = 'catalog' | 'media' | 'analytics' | 'checkin' | 'notifications';

export type ServiceConfig = {
  port: number;
  timeoutMs: number;
  services: Record<GatewayServiceName, string>;
};

export function serviceConfig(): ServiceConfig {
  return {
    port: intEnv('PORT', 4000),
    timeoutMs: intEnv('GATEWAY_TIMEOUT_MS', 8000),
    services: {
      catalog: urlEnv('CATALOG_SERVICE_URL', 'http://127.0.0.1:4030/v1'),
      media: urlEnv('MEDIA_SERVICE_URL', 'http://127.0.0.1:4020/v1'),
      analytics: urlEnv('ANALYTICS_SERVICE_URL', 'http://127.0.0.1:4040/v1'),
      checkin: urlEnv('CHECKIN_SERVICE_URL', 'http://127.0.0.1:4050/v1'),
      notifications: urlEnv('NOTIFICATIONS_SERVICE_URL', 'http://127.0.0.1:4010/v1'),
    },
  };
}

function urlEnv(key: string, fallback: string): string {
  return (process.env[key] ?? fallback).replace(/\/$/, '');
}

function intEnv(key: string, fallback: number): number {
  const value = Number.parseInt(process.env[key] ?? '', 10);
  return Number.isFinite(value) && value > 0 ? value : fallback;
}
