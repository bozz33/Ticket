import assert from 'node:assert/strict';
import test from 'node:test';

function normalizeOutboxRow(row) {
  const payload = row.payload ?? {};
  const metadata = row.metadata ?? {};
  const rawEvent = typeof payload._event === 'object' && payload._event !== null && !Array.isArray(payload._event)
    ? payload._event
    : {};
  const businessPayload = { ...payload };
  delete businessPayload._event;

  return {
    eventId: typeof rawEvent.event_id === 'string' && rawEvent.event_id.trim() !== '' ? rawEvent.event_id : row.event_id,
    type: typeof rawEvent.type === 'string' && rawEvent.type.trim() !== '' ? rawEvent.type : row.type,
    version: Number.isFinite(Number(rawEvent.version)) && Number(rawEvent.version) > 0
      ? Number(rawEvent.version)
      : 1,
    aggregateType: row.aggregate_type,
    aggregateId: row.aggregate_id,
    tenantId: typeof metadata.tenant_id === 'string' ? metadata.tenant_id : null,
    payload: businessPayload,
    metadata,
  };
}

test('normalizes Laravel outbox payload envelope without hiding business payload', () => {
  const event = normalizeOutboxRow({
    event_id: 'evt-1',
    type: 'order.paid',
    aggregate_type: 'orders',
    aggregate_id: '42',
    payload: {
      order_reference: 'ORD-1',
      _event: {
        event_id: 'evt-1',
        type: 'order.paid',
        version: 2,
      },
    },
    metadata: {
      tenant_id: 'tenant-demo',
    },
  });

  assert.equal(event.version, 2);
  assert.equal(event.payload.order_reference, 'ORD-1');
  assert.equal(event.tenantId, 'tenant-demo');
});
