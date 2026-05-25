import { cookies } from "next/headers";
import { Buffer } from "node:buffer";
import { normalizeTenantSlug, requireValidTenantSlug } from "@/lib/tenant";

const TOKEN_COOKIE = "_account_token";
const TENANT_COOKIE = "_account_tenant";
const TENANT_TOKENS_COOKIE = "_account_tenant_tokens";
const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "") ?? (process.env.NODE_ENV === "development" ? "http://127.0.0.1:8000" : "");
const MARKETPLACE_AUTH_EXCHANGE_TIMEOUT_MS = 15000;
let defaultTenantSlugPromise: Promise<string> | null = null;
const PUBLIC_TENANT_REVALIDATE = 120;

const COOKIE_OPTIONS = {
  httpOnly: true,
  secure: process.env.NODE_ENV === "production",
  sameSite: "lax" as const,
  path: "/",
  maxAge: 60 * 60 * 24 * 30,
};

export async function getAuthToken(): Promise<string | null> {
  const jar = await cookies();
  return jar.get(TOKEN_COOKIE)?.value ?? null;
}

export async function getAuthTokensByTenant(): Promise<Record<string, string>> {
  const jar = await cookies();
  const encoded = jar.get(TENANT_TOKENS_COOKIE)?.value;
  const tokens = decodeTenantTokenMap(encoded);
  const [defaultToken, defaultTenant] = await Promise.all([getAuthToken(), getCookieTenantSlug()]);

  if (defaultToken && defaultTenant && !tokens[defaultTenant]) {
    tokens[defaultTenant] = defaultToken;
  }

  return tokens;
}

export async function getAuthTokenForTenant(tenantSlug: string | null | undefined): Promise<string | null> {
  const normalizedTenantSlug = normalizeTenantSlug(tenantSlug);

  if (!normalizedTenantSlug) {
    return getAuthToken();
  }

  const tokens = await getAuthTokensByTenant();

  return tokens[normalizedTenantSlug] ?? null;
}

export async function getDefaultTenantSlug(): Promise<string> {
  if (!defaultTenantSlugPromise) {
    defaultTenantSlugPromise = (async () => {
      if (!apiBaseUrl) {
        return "";
      }

      try {
        const response = await fetch(`${apiBaseUrl}/api/v1/public/platform/configuration`, {
          cache: "force-cache",
          next: { revalidate: PUBLIC_TENANT_REVALIDATE },
          headers: {
            Accept: "application/json",
          },
        });

        if (!response.ok) {
          return "";
        }

        const payload = (await response.json()) as {
          default_tenant?: { slug?: string | null } | null;
        };

        return normalizeTenantSlug(payload.default_tenant?.slug);
      } catch {
        return "";
      }
    })();
  }

  const tenantSlug = await defaultTenantSlugPromise;

  if (!tenantSlug) {
    defaultTenantSlugPromise = null;
  }

  return tenantSlug;
}

export async function getCookieTenantSlug(): Promise<string> {
  const jar = await cookies();

  return normalizeTenantSlug(jar.get(TENANT_COOKIE)?.value);
}

export async function getTenantSlug(preferredTenantSlug?: string | null): Promise<string> {
  const requestedTenantSlug = normalizeTenantSlug(preferredTenantSlug);

  if (requestedTenantSlug) {
    return requestedTenantSlug;
  }

  const cookieTenantSlug = await getCookieTenantSlug();

  if (cookieTenantSlug) {
    return cookieTenantSlug;
  }

  const envTenantSlug = normalizeTenantSlug(process.env.NEXT_PUBLIC_TENANT_SLUG);

  if (envTenantSlug) {
    return envTenantSlug;
  }

  return getDefaultTenantSlug();
}

export async function getAccountTenantSlug(preferredTenantSlug?: string | null): Promise<string> {
  const requestedTenantSlug = normalizeTenantSlug(preferredTenantSlug);

  if (requestedTenantSlug) {
    return requestedTenantSlug;
  }

  const cookieTenantSlug = await getCookieTenantSlug();

  if (cookieTenantSlug) {
    return cookieTenantSlug;
  }

  const envTenantSlug = normalizeTenantSlug(process.env.NEXT_PUBLIC_TENANT_SLUG);

  if (envTenantSlug) {
    return envTenantSlug;
  }

  return getDefaultTenantSlug();
}

export async function requireTenantSlug(): Promise<string> {
  const tenantSlug = await getTenantSlug();

  if (!tenantSlug) {
    throw new Error("Aucun espace acheteur actif n'a ete detecte. Verifiez la configuration du backend ou activez un tenant par defaut.");
  }

  return tenantSlug;
}

export async function setAuthCookies(
  token: string,
  tenantSlug: string,
): Promise<void> {
  const jar = await cookies();
  const normalizedTenantSlug = requireValidTenantSlug(tenantSlug);
  const tokens = decodeTenantTokenMap(jar.get(TENANT_TOKENS_COOKIE)?.value);

  tokens[normalizedTenantSlug] = token;
  jar.set(TOKEN_COOKIE, token, COOKIE_OPTIONS);
  jar.set(TENANT_COOKIE, normalizedTenantSlug, COOKIE_OPTIONS);
  jar.set(TENANT_TOKENS_COOKIE, encodeTenantTokenMap(tokens), COOKIE_OPTIONS);
}

export async function rememberAuthTokenForTenant(token: string, tenantSlug: string): Promise<void> {
  const jar = await cookies();
  const normalizedTenantSlug = requireValidTenantSlug(tenantSlug);
  const tokens = decodeTenantTokenMap(jar.get(TENANT_TOKENS_COOKIE)?.value);

  tokens[normalizedTenantSlug] = token;
  jar.set(TENANT_TOKENS_COOKIE, encodeTenantTokenMap(tokens), COOKIE_OPTIONS);
}

export async function getMarketplaceAuthTokenForTenant(
  targetTenantSlug: string,
  options: { forceExchange?: boolean; persist?: boolean; timeoutMs?: number } = {},
): Promise<string | null> {
  const normalizedTargetTenant = normalizeTenantSlug(targetTenantSlug);

  if (!normalizedTargetTenant) {
    return null;
  }

  const existingToken = options.forceExchange ? null : await getAuthTokenForTenant(normalizedTargetTenant);

  if (existingToken) {
    return existingToken;
  }

  const [sourceToken, sourceTenant] = await Promise.all([getAuthToken(), getCookieTenantSlug()]);

  if (!apiBaseUrl || !sourceToken || !sourceTenant) {
    return null;
  }

  try {
    const response = await fetch(`${apiBaseUrl}/api/v1/public/marketplace/session/exchange`, {
      method: "POST",
      cache: "no-store",
      signal: AbortSignal.timeout(options.timeoutMs ?? MARKETPLACE_AUTH_EXCHANGE_TIMEOUT_MS),
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${sourceToken}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        source_tenant: sourceTenant,
        target_tenant: normalizedTargetTenant,
        token_name: "marketplace_public",
      }),
    });
    const payload = (await response.json().catch(() => null)) as { data?: { token?: string } } | null;
    const token = typeof payload?.data?.token === "string" ? payload.data.token : "";

    if (!response.ok || !token) {
      return null;
    }

    if (options.persist) {
      await rememberAuthTokenForTenant(token, normalizedTargetTenant);
    }

    return token;
  } catch {
    return null;
  }
}

export async function clearAuthCookies(): Promise<void> {
  const jar = await cookies();
  jar.delete(TOKEN_COOKIE);
  jar.delete(TENANT_COOKIE);
  jar.delete(TENANT_TOKENS_COOKIE);
}

export async function isAuthenticated(): Promise<boolean> {
  return (await getAuthToken()) !== null;
}

function decodeTenantTokenMap(value: string | undefined): Record<string, string> {
  if (!value) {
    return {};
  }

  try {
    const decoded = Buffer.from(value, "base64url").toString("utf8");
    const parsed = JSON.parse(decoded) as unknown;

    if (!parsed || typeof parsed !== "object" || Array.isArray(parsed)) {
      return {};
    }

    return Object.fromEntries(
      Object.entries(parsed as Record<string, unknown>)
        .map(([tenant, token]) => [normalizeTenantSlug(tenant), typeof token === "string" ? token : ""])
        .filter(([tenant, token]) => tenant.length > 0 && token.length > 0),
    );
  } catch {
    return {};
  }
}

function encodeTenantTokenMap(tokens: Record<string, string>): string {
  const normalizedTokens = Object.fromEntries(
    Object.entries(tokens)
      .map(([tenant, token]) => [normalizeTenantSlug(tenant), token])
      .filter(([tenant, token]) => tenant.length > 0 && token.length > 0),
  );

  return Buffer.from(JSON.stringify(normalizedTokens), "utf8").toString("base64url");
}
