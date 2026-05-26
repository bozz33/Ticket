import assert from 'node:assert/strict';
import test from 'node:test';
import { randomUUID } from 'node:crypto';

function buildForwardHeaders(headers) {
  const forwarded = {
    accept: 'application/json',
    'content-type': 'application/json',
    'x-correlation-id': headerValue(headers['x-correlation-id']) || randomUUID(),
  };
  const tenantId = headerValue(headers['x-tenant-id']);
  const authorization = headerValue(headers.authorization);
  if (tenantId) forwarded['x-tenant-id'] = tenantId;
  if (authorization) forwarded.authorization = authorization;
  return forwarded;
}

function headerValue(value) {
  if (Array.isArray(value)) return value[0] ?? null;
  return typeof value === 'string' && value.trim() !== '' ? value : null;
}

test('gateway propagates tenant, auth and correlation headers', () => {
  const headers = buildForwardHeaders({
    'x-tenant-id': 'tenant-demo',
    'x-correlation-id': 'corr-1',
    authorization: 'Bearer token',
  });

  assert.equal(headers['x-tenant-id'], 'tenant-demo');
  assert.equal(headers['x-correlation-id'], 'corr-1');
  assert.equal(headers.authorization, 'Bearer token');
});
