import assert from 'node:assert/strict';
import test from 'node:test';
import { createHmac, timingSafeEqual } from 'node:crypto';

function signValue(value, secret) {
  return createHmac('sha256', secret).update(value).digest('hex');
}

function verifySignature(value, signature, secret) {
  const expected = Buffer.from(signValue(value, secret), 'hex');
  const received = Buffer.from(signature, 'hex');
  return expected.length === received.length && timingSafeEqual(expected, received);
}

test('signed media URLs are tamper resistant', () => {
  const payload = 'asset-1|tenant/event/asset.png|1770000000|inline';
  const signature = signValue(payload, 'secret');

  assert.equal(verifySignature(payload, signature, 'secret'), true);
  assert.equal(verifySignature(`${payload}-changed`, signature, 'secret'), false);
});
