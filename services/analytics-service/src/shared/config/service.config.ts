export type ServiceConfig = {
  port: number;
  analyticsDatabaseUrl: string;
  snapshotDefaultDays: number;
};

export function serviceConfig(): ServiceConfig {
  return {
    port: intEnv('PORT', 4040),
    analyticsDatabaseUrl: stringEnv('ANALYTICS_DATABASE_URL'),
    snapshotDefaultDays: intEnv('SNAPSHOT_DEFAULT_DAYS', 30),
  };
}

function stringEnv(key: string, fallback = ''): string {
  return process.env[key] ?? fallback;
}

function intEnv(key: string, fallback: number): number {
  const value = Number.parseInt(process.env[key] ?? '', 10);
  return Number.isFinite(value) && value > 0 ? value : fallback;
}
