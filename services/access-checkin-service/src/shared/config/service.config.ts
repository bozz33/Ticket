export type ServiceConfig = {
  port: number;
  checkinDatabaseUrl: string;
  duplicateWindowSeconds: number;
};

export function serviceConfig(): ServiceConfig {
  return {
    port: intEnv('PORT', 4050),
    checkinDatabaseUrl: stringEnv('CHECKIN_DATABASE_URL'),
    duplicateWindowSeconds: intEnv('SCAN_DUPLICATE_WINDOW_SECONDS', 30),
  };
}

function stringEnv(key: string, fallback = ''): string {
  return process.env[key] ?? fallback;
}

function intEnv(key: string, fallback: number): number {
  const value = Number.parseInt(process.env[key] ?? '', 10);
  return Number.isFinite(value) && value > 0 ? value : fallback;
}
