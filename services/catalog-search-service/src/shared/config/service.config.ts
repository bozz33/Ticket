export type ServiceConfig = {
  port: number;
  catalogDatabaseUrl: string;
  publicBaseUrl: string;
  cacheTtlSeconds: number;
};

export function serviceConfig(): ServiceConfig {
  return {
    port: intEnv('PORT', 4030),
    catalogDatabaseUrl: stringEnv('CATALOG_DATABASE_URL'),
    publicBaseUrl: stringEnv('CATALOG_PUBLIC_BASE_URL', 'http://localhost:3000'),
    cacheTtlSeconds: intEnv('CACHE_TTL_SECONDS', 60),
  };
}

function stringEnv(key: string, fallback = ''): string {
  return process.env[key] ?? fallback;
}

function intEnv(key: string, fallback: number): number {
  const value = Number.parseInt(process.env[key] ?? '', 10);
  return Number.isFinite(value) && value > 0 ? value : fallback;
}
