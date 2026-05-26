export type ServiceConfig = {
  port: number;
  outboxPollEnabled: boolean;
  outboxPollIntervalMs: number;
  outboxBatchSize: number;
  outboxMaxAttempts: number;
  outboxRetryDelaySeconds: number;
  outboxProcessingTimeoutSeconds: number;
  outboxDatabaseUrl: string;
  notificationsDatabaseUrl: string;
};

export function serviceConfig(): ServiceConfig {
  return {
    port: intEnv('PORT', 4010),
    outboxPollEnabled: boolEnv('OUTBOX_POLL_ENABLED', true),
    outboxPollIntervalMs: intEnv('OUTBOX_POLL_INTERVAL_MS', 5000),
    outboxBatchSize: intEnv('OUTBOX_BATCH_SIZE', 50),
    outboxMaxAttempts: intEnv('OUTBOX_MAX_ATTEMPTS', 5),
    outboxRetryDelaySeconds: intEnv('OUTBOX_RETRY_DELAY_SECONDS', 60),
    outboxProcessingTimeoutSeconds: intEnv('OUTBOX_PROCESSING_TIMEOUT_SECONDS', 300),
    outboxDatabaseUrl: stringEnv('OUTBOX_DATABASE_URL'),
    notificationsDatabaseUrl: stringEnv('NOTIFICATIONS_DATABASE_URL'),
  };
}

function stringEnv(key: string, fallback = ''): string {
  return process.env[key] ?? fallback;
}

function intEnv(key: string, fallback: number): number {
  const value = Number.parseInt(process.env[key] ?? '', 10);
  return Number.isFinite(value) && value > 0 ? value : fallback;
}

function boolEnv(key: string, fallback: boolean): boolean {
  const value = (process.env[key] ?? '').trim().toLowerCase();
  if (['1', 'true', 'yes', 'on'].includes(value)) {
    return true;
  }
  if (['0', 'false', 'no', 'off'].includes(value)) {
    return false;
  }
  return fallback;
}
