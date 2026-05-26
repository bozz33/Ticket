type InternalTokenRequest = {
  originalUrl?: string;
  url?: string;
  header: (name: string) => string | undefined;
};

type InternalTokenResponse = {
  status: (code: number) => { json: (body: Record<string, string>) => void };
};

type NextFunction = () => void;

export function internalTokenMiddleware(request: InternalTokenRequest, response: InternalTokenResponse, next: NextFunction): void {
  const token = process.env.INTERNAL_SERVICE_TOKEN || process.env.MICROSERVICES_INTERNAL_TOKEN || '';

  if (!token) {
    next();
    return;
  }

  const headerName = process.env.INTERNAL_SERVICE_TOKEN_HEADER || process.env.MICROSERVICES_INTERNAL_TOKEN_HEADER || 'x-internal-service-token';
  const requestPath = request.originalUrl || request.url || '';

  if (requestPath === '/v1/health' || requestPath === '/health') {
    next();
    return;
  }

  const provided = request.header(headerName) || request.header(headerName.toLowerCase());

  if (provided === token) {
    next();
    return;
  }

  response.status(401).json({ message: 'Unauthorized internal service request' });
}
