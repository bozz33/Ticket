import assert from 'node:assert/strict';
import test from 'node:test';

function normalizeAnalyticsEvent(input) {
  const payload = input.payload && typeof input.payload === 'object' ? input.payload : {};
  const metadata = input.metadata && typeof input.metadata === 'object' ? input.metadata : {};
  const embedded = payload._event && typeof payload._event === 'object' ? payload._event : {};
  const businessPayload = { ...payload };
  delete businessPayload._event;

  return {
    eventId: embedded.event_id || input.event_id || input.eventId,
    eventType: embedded.type || input.event_type || input.type,
    eventVersion: Number.parseInt(String(embedded.version ?? input.event_version ?? 1), 10),
    tenantId: metadata.tenant_id || input.tenant_id || null,
    payload: businessPayload,
  };
}

test('analytics ingestion accepts Laravel outbox envelope shape', () => {
  const event = normalizeAnalyticsEvent({
    event_id: 'evt-analytics-1',
    type: 'order.paid',
    payload: {
      amount: 45000,
      _event: { event_id: 'evt-analytics-1', type: 'order.paid', version: 2 },
    },
    metadata: { tenant_id: 'tenant-demo' },
  });

  assert.equal(event.eventType, 'order.paid');
  assert.equal(event.eventVersion, 2);
  assert.equal(event.tenantId, 'tenant-demo');
  assert.equal(event.payload.amount, 45000);
});
