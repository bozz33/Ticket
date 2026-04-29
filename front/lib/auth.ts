import { cookies } from "next/headers";

const TOKEN_COOKIE = "_account_token";
const TENANT_COOKIE = "_account_tenant";

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

export async function getTenantSlug(): Promise<string> {
  const jar = await cookies();
  return (
    jar.get(TENANT_COOKIE)?.value ??
    process.env.NEXT_PUBLIC_TENANT_SLUG ??
    ""
  );
}

export async function setAuthCookies(
  token: string,
  tenantSlug: string,
): Promise<void> {
  const jar = await cookies();
  jar.set(TOKEN_COOKIE, token, COOKIE_OPTIONS);
  jar.set(TENANT_COOKIE, tenantSlug, COOKIE_OPTIONS);
}

export async function clearAuthCookies(): Promise<void> {
  const jar = await cookies();
  jar.delete(TOKEN_COOKIE);
  jar.delete(TENANT_COOKIE);
}

export async function isAuthenticated(): Promise<boolean> {
  return (await getAuthToken()) !== null;
}
