import type {
  AccountAccessPass,
  AccountOrder,
  AccountReceipt,
  AccountUser,
  PublicPassVerification,
} from "@/lib/types";

const apiBase = process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "") ?? "";

async function apiFetch<T>(
  path: string,
  token: string,
  init: RequestInit = {},
): Promise<T | null> {
  if (!apiBase) return null;

  try {
    const res = await fetch(`${apiBase}${path}`, {
      cache: "no-store",
      ...init,
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
        ...(init.headers ?? {}),
      },
    });

    if (!res.ok) return null;
    return (await res.json()) as T;
  } catch {
    return null;
  }
}

export async function loginAccount(
  tenantSlug: string,
  email: string,
  password: string,
): Promise<{ token: string; user: AccountUser } | { error: string } | null> {
  if (!apiBase) return null;

  try {
    const res = await fetch(
      `${apiBase}/api/v1/tenants/${tenantSlug}/auth/login`,
      {
        method: "POST",
        cache: "no-store",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          email,
          password,
          token_name: "panel_acheteur",
        }),
      },
    );

    const data = await res.json();

    if (!res.ok) {
      const message =
        data?.message ??
        (Object.values(data?.errors ?? {}) as string[][])[0]?.[0] ??
        "Identifiants incorrects.";
      return { error: message as string };
    }

    return { token: data.token as string, user: data.user as AccountUser };
  } catch {
    return { error: "Impossible de contacter le serveur." };
  }
}

export async function logoutAccount(
  tenantSlug: string,
  token: string,
): Promise<void> {
  if (!apiBase) return;
  await fetch(`${apiBase}/api/v1/tenants/${tenantSlug}/auth/logout`, {
    method: "POST",
    cache: "no-store",
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: "application/json",
    },
  }).catch(() => {});
}

export async function getAccountMe(
  tenantSlug: string,
  token: string,
): Promise<AccountUser | null> {
  const p = await apiFetch<{ data: AccountUser }>(
    `/api/v1/tenants/${tenantSlug}/auth/me`,
    token,
  );
  return p?.data ?? null;
}

export async function getAccountOrders(
  tenantSlug: string,
  token: string,
): Promise<AccountOrder[]> {
  const p = await apiFetch<{ data: AccountOrder[] }>(
    `/api/v1/tenants/${tenantSlug}/orders`,
    token,
  );
  return p?.data ?? [];
}

export async function getAccountOrder(
  tenantSlug: string,
  token: string,
  ref: string,
): Promise<AccountOrder | null> {
  const p = await apiFetch<{ data: AccountOrder }>(
    `/api/v1/tenants/${tenantSlug}/orders/${ref}`,
    token,
  );
  return p?.data ?? null;
}

export async function getAccountReceipts(
  tenantSlug: string,
  token: string,
): Promise<AccountReceipt[]> {
  const p = await apiFetch<{ data: AccountReceipt[] }>(
    `/api/v1/tenants/${tenantSlug}/receipts`,
    token,
  );
  return p?.data ?? [];
}

export async function getAccountReceipt(
  tenantSlug: string,
  token: string,
  ref: string,
): Promise<AccountReceipt | null> {
  const p = await apiFetch<{ data: AccountReceipt }>(
    `/api/v1/tenants/${tenantSlug}/receipts/${ref}`,
    token,
  );
  return p?.data ?? null;
}

export async function getAccountPasses(
  tenantSlug: string,
  token: string,
): Promise<AccountAccessPass[]> {
  const p = await apiFetch<{ data: AccountAccessPass[] }>(
    `/api/v1/tenants/${tenantSlug}/access-passes`,
    token,
  );
  return p?.data ?? [];
}

export async function getAccountPass(
  tenantSlug: string,
  token: string,
  id: string,
): Promise<AccountAccessPass | null> {
  const p = await apiFetch<{ data: AccountAccessPass }>(
    `/api/v1/tenants/${tenantSlug}/access-passes/${id}`,
    token,
  );
  return p?.data ?? null;
}

export async function getPublicPass(
  tenantSlug: string,
  code: string,
): Promise<PublicPassVerification | null> {
  if (!apiBase) return null;
  try {
    const res = await fetch(
      `${apiBase}/api/v1/public/tenants/${tenantSlug}/access-passes/${code}`,
      { cache: "no-store", headers: { Accept: "application/json" } },
    );
    if (!res.ok) return null;
    const body = await res.json();
    return (body?.data as PublicPassVerification) ?? null;
  } catch {
    return null;
  }
}
