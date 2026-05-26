export type ServiceConfig = {
  port: number;
  mediaDatabaseUrl: string;
  publicBaseUrl: string;
  signingSecret: string;
  uploadMaxBytes: number;
  signedUrlTtlSeconds: number;
  tenantMonthlyBytes: number;
  qrDefaultSize: number;
};

export function serviceConfig(): ServiceConfig {
  return {
    port: intEnv('PORT', 4020),
    mediaDatabaseUrl: stringEnv('MEDIA_DATABASE_URL'),
    publicBaseUrl: stringEnv('MEDIA_PUBLIC_BASE_URL', 'http://127.0.0.1:4020'),
    signingSecret: stringEnv('MEDIA_SIGNING_SECRET', 'dev-only-secret'),
    uploadMaxBytes: intEnv('UPLOAD_MAX_BYTES', 10 * 1024 * 1024),
    signedUrlTtlSeconds: intEnv('SIGNED_URL_TTL_SECONDS', 900),
    tenantMonthlyBytes: intEnv('TENANT_MONTHLY_BYTES', 1024 * 1024 * 1024),
    qrDefaultSize: intEnv('QR_DEFAULT_SIZE', 240),
  };
}

function stringEnv(key: string, fallback = ''): string {
  return process.env[key] ?? fallback;
}

function intEnv(key: string, fallback: number): number {
  const value = Number.parseInt(process.env[key] ?? '', 10);
  return Number.isFinite(value) && value > 0 ? value : fallback;
}
