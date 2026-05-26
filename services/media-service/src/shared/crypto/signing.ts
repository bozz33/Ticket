import { createHmac, timingSafeEqual } from 'node:crypto';

export function signValue(value: string, secret: string): string {
  return createHmac('sha256', secret).update(value).digest('hex');
}

export function verifySignature(value: string, signature: string, secret: string): boolean {
  const expected = Buffer.from(signValue(value, secret), 'hex');
  const received = Buffer.from(signature, 'hex');

  return expected.length === received.length && timingSafeEqual(expected, received);
}
