import { randomUUID } from 'node:crypto';

export type IncomingGatewayHeaders = Record<string, string | string[] | undefined>;

export function buildForwardHeaders(headers: IncomingGatewayHeaders): Record<string, string> {
  const forwarded: Record<string, string> = {
    accept: 'application/json',
    'content-type': 'application/json',
    'x-correlation-id': headerValue(headers['x-correlation-id']) || randomUUID(),
  };

  const tenantId = headerValue(headers['x-tenant-id']);
  const authorization = headerValue(headers.authorization);

  if (tenantId) {
    forwarded['x-tenant-id'] = tenantId;
  }

  if (authorization) {
    forwarded.authorization = authorization;
  }

  const internalToken = process.env.INTERNAL_SERVICE_TOKEN || process.env.MICROSERVICES_INTERNAL_TOKEN || '';

  if (internalToken) {
    const internalHeader = process.env.INTERNAL_SERVICE_TOKEN_HEADER || process.env.MICROSERVICES_INTERNAL_TOKEN_HEADER || 'x-internal-service-token';
    forwarded[internalHeader.toLowerCase()] = internalToken;
  }

  return forwarded;
}

function headerValue(value: string | string[] | undefined): string | null {
  if (Array.isArray(value)) {
    return value[0] ?? null;
  }

  return typeof value === 'string' && value.trim() !== '' ? value : null;
}
