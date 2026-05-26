export type DomainOutboxRow = {
  id: number;
  event_id: string;
  type: string;
  aggregate_type: string | null;
  aggregate_id: string | null;
  payload: Record<string, unknown> | null;
  metadata: Record<string, unknown> | null;
};

export type DomainEventEnvelope = {
  eventId: string;
  type: string;
  version: number;
  aggregateType: string | null;
  aggregateId: string | null;
  tenantId: string | null;
  payload: Record<string, unknown>;
  metadata: Record<string, unknown>;
};

export function normalizeOutboxRow(row: DomainOutboxRow): DomainEventEnvelope {
  const payload = row.payload ?? {};
  const metadata = row.metadata ?? {};
  const rawEvent = isRecord(payload._event) ? payload._event : {};
  const businessPayload = { ...payload };
  delete businessPayload._event;

  return {
    eventId: stringValue(rawEvent.event_id) || row.event_id,
    type: stringValue(rawEvent.type) || row.type,
    version: positiveInt(rawEvent.version) ?? positiveInt(metadata.event_version) ?? 1,
    aggregateType: row.aggregate_type,
    aggregateId: row.aggregate_id,
    tenantId: stringValue(metadata.tenant_id) || stringValue(businessPayload.tenant_id) || null,
    payload: businessPayload,
    metadata,
  };
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function stringValue(value: unknown): string | null {
  return typeof value === 'string' && value.trim() !== '' ? value : null;
}

function positiveInt(value: unknown): number | null {
  const parsed = typeof value === 'number' ? value : Number.parseInt(String(value ?? ''), 10);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
}
