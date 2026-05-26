export type AnalyticsEvent = {
  eventId: string;
  eventType: string;
  eventVersion: number;
  tenantId: string | null;
  module: string | null;
  aggregateType: string | null;
  aggregateId: string | null;
  occurredAt: string;
  payload: Record<string, unknown>;
  metadata: Record<string, unknown>;
};

export function normalizeAnalyticsEvent(input: Record<string, unknown>): AnalyticsEvent {
  const payload = isRecord(input.payload) ? input.payload : {};
  const metadata = isRecord(input.metadata) ? input.metadata : {};
  const embedded = isRecord(payload._event) ? payload._event : {};

  return {
    eventId: str(embedded.event_id) || str(input.event_id) || str(input.eventId),
    eventType: str(embedded.type) || str(input.event_type) || str(input.type),
    eventVersion: positiveInt(embedded.version) ?? positiveInt(input.event_version) ?? 1,
    tenantId: str(metadata.tenant_id) || str(input.tenant_id) || null,
    module: str(input.module) || str(payload.module) || null,
    aggregateType: str(input.aggregate_type) || str(input.aggregateType) || null,
    aggregateId: str(input.aggregate_id) || str(input.aggregateId) || null,
    occurredAt: str(input.occurred_at) || new Date().toISOString(),
    payload: withoutEventEnvelope(payload),
    metadata,
  };
}

function withoutEventEnvelope(payload: Record<string, unknown>): Record<string, unknown> {
  const copy = { ...payload };
  delete copy._event;
  return copy;
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function str(value: unknown): string {
  return typeof value === 'string' && value.trim() !== '' ? value : '';
}

function positiveInt(value: unknown): number | null {
  const parsed = Number.parseInt(String(value ?? ''), 10);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
}
