import { cookies } from "next/headers";
import { normalizeTenantSlug, requireValidTenantSlug } from "@/lib/tenant";

const TOKEN_COOKIE = "_account_token";
const TENANT_COOKIE = "_account_tenant";
const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "") ?? (process.env.NODE_ENV === "development" ? "http://127.0.0.1:8000" : "");
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

  jar.set(TOKEN_COOKIE, token, COOKIE_OPTIONS);
  jar.set(TENANT_COOKIE, normalizedTenantSlug, COOKIE_OPTIONS);
}

export async function clearAuthCookies(): Promise<void> {
  const jar = await cookies();
  jar.delete(TOKEN_COOKIE);
  jar.delete(TENANT_COOKIE);
}

export async function isAuthenticated(): Promise<boolean> {
  return (await getAuthToken()) !== null;
}
