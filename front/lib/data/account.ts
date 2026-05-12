import type {
  AccountAccessPass,
  AccountNotification,
  AccountPasswordUpdateInput,
  AccountProfileUpdateInput,
  AccountOrder,
  AccountReceipt,
  AccountUser,
  PublicPassVerification,
  PublicReceiptVerification,
} from "@/lib/types";

const apiBase = process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "") ?? (process.env.NODE_ENV === "development" ? "http://127.0.0.1:8000" : "");

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

export async function registerAccount(
  tenantSlug: string,
  name: string,
  email: string,
  password: string,
): Promise<{ token: string; user: AccountUser } | { error: string } | null> {
  if (!apiBase) return null;
  try {
    const res = await fetch(
      `${apiBase}/api/v1/tenants/${tenantSlug}/auth/register`,
      {
        method: "POST",
        cache: "no-store",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({
          name,
          email,
          password,
          password_confirmation: password,
          token_name: "panel_acheteur",
        }),
      },
    );
    const data = await res.json();
    if (!res.ok) {
      const message =
        data?.message ??
        (Object.values(data?.errors ?? {}) as string[][])[0]?.[0] ??
        "Inscription impossible.";
      return { error: message as string };
    }
    return { token: data.token as string, user: data.user as AccountUser };
  } catch {
    return { error: "Impossible de contacter le serveur." };
  }
}

export async function registerOrganizer(payload: {
  org_name: string;
  email: string;
  password: string;
  country_code?: string;
  currency_code?: string;
}): Promise<{ tenant: { slug: string; name: string; access_url: string; login_url: string } } | { error: string } | null> {
  if (!apiBase) return null;
  try {
    const res = await fetch(`${apiBase}/api/v1/public/onboarding/register`, {
      method: "POST",
      cache: "no-store",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify({ ...payload, password_confirmation: payload.password }),
    });
    const data = await res.json();
    if (!res.ok) {
      const message =
        data?.message ??
        (Object.values(data?.errors ?? {}) as string[][])[0]?.[0] ??
        "Inscription impossible.";
      return { error: message as string };
    }
    return { tenant: data.tenant };
  } catch {
    return { error: "Impossible de contacter le serveur." };
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

export async function requestAccountPasswordReset(
  tenantSlug: string,
  email: string,
): Promise<{ message: string } | { error: string } | null> {
  if (!apiBase) return null;

  try {
    const res = await fetch(`${apiBase}/api/v1/tenants/${tenantSlug}/auth/forgot-password`, {
      method: "POST",
      cache: "no-store",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({ email }),
    });

    const data = await res.json();

    if (!res.ok) {
      const message =
        data?.message ??
        data?.error ??
        (Object.values(data?.errors ?? {}) as string[][])[0]?.[0] ??
        "Impossible d'envoyer le lien de reinitialisation.";

      return { error: message as string };
    }

    return { message: (data?.message as string) ?? "Lien de reinitialisation envoye." };
  } catch {
    return { error: "Impossible de contacter le serveur." };
  }
}

export async function resetAccountPassword(
  tenantSlug: string,
  payload: {
    email: string;
    token: string;
    password: string;
    passwordConfirmation: string;
  },
): Promise<{ message: string } | { error: string } | null> {
  if (!apiBase) return null;

  try {
    const res = await fetch(`${apiBase}/api/v1/tenants/${tenantSlug}/auth/reset-password`, {
      method: "POST",
      cache: "no-store",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({
        email: payload.email,
        token: payload.token,
        password: payload.password,
        password_confirmation: payload.passwordConfirmation,
      }),
    });

    const data = await res.json();

    if (!res.ok) {
      const message =
        data?.message ??
        data?.error ??
        (Object.values(data?.errors ?? {}) as string[][])[0]?.[0] ??
        "Impossible de reinitialiser le mot de passe.";

      return { error: message as string };
    }

    return { message: (data?.message as string) ?? "Mot de passe reinitialise avec succes." };
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

export async function updateAccountMe(
  tenantSlug: string,
  token: string,
  payload: AccountProfileUpdateInput,
): Promise<{ user: AccountUser; message: string } | { error: string } | null> {
  if (!apiBase) return null;

  try {
    const res = await fetch(`${apiBase}/api/v1/tenants/${tenantSlug}/auth/me`, {
      method: "PUT",
      cache: "no-store",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(payload),
    });

    const data = await res.json();

    if (!res.ok) {
      const message =
        data?.message ??
        data?.error ??
        (Object.values(data?.errors ?? {}) as string[][])[0]?.[0] ??
        "Impossible de mettre à jour le profil.";

      return { error: message as string };
    }

    return {
      user: data.data as AccountUser,
      message: (data.message as string) ?? "Profil mis à jour avec succès.",
    };
  } catch {
    return { error: "Impossible de contacter le serveur." };
  }
}

export async function updateAccountPassword(
  tenantSlug: string,
  token: string,
  payload: AccountPasswordUpdateInput,
): Promise<{ user: AccountUser; message: string } | { error: string } | null> {
  if (!apiBase) return null;

  try {
    const res = await fetch(`${apiBase}/api/v1/tenants/${tenantSlug}/auth/password`, {
      method: "PUT",
      cache: "no-store",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(payload),
    });

    const data = await res.json();

    if (!res.ok) {
      const message =
        data?.message ??
        data?.error ??
        (Object.values(data?.errors ?? {}) as string[][])[0]?.[0] ??
        "Impossible de modifier le mot de passe.";

      return { error: message as string };
    }

    return {
      user: data.data as AccountUser,
      message: (data.message as string) ?? "Mot de passe mis à jour avec succès.",
    };
  } catch {
    return { error: "Impossible de contacter le serveur." };
  }
}

export async function sendAccountEmailVerificationNotification(
  tenantSlug: string,
  token: string,
): Promise<{ user: AccountUser; message: string } | { error: string } | null> {
  if (!apiBase) return null;

  try {
    const res = await fetch(`${apiBase}/api/v1/tenants/${tenantSlug}/auth/email/verification-notification`, {
      method: "POST",
      cache: "no-store",
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
    });

    const data = await res.json();

    if (!res.ok) {
      const message =
        data?.message ??
        data?.error ??
        (Object.values(data?.errors ?? {}) as string[][])[0]?.[0] ??
        "Impossible d'envoyer le lien de vérification.";

      return { error: message as string };
    }

    return {
      user: data.data as AccountUser,
      message: (data.message as string) ?? "Lien de vérification envoyé.",
    };
  } catch {
    return { error: "Impossible de contacter le serveur." };
  }
}

export async function updateAccountAvatar(
  tenantSlug: string,
  token: string,
  formData: FormData,
): Promise<{ user: AccountUser; message: string } | { error: string } | null> {
  if (!apiBase) return null;

  try {
    const res = await fetch(`${apiBase}/api/v1/tenants/${tenantSlug}/auth/avatar`, {
      method: "POST",
      cache: "no-store",
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: formData,
    });

    const data = await res.json();

    if (!res.ok) {
      const message =
        data?.message ??
        data?.error ??
        (Object.values(data?.errors ?? {}) as string[][])[0]?.[0] ??
        "Impossible de mettre à jour la photo.";

      return { error: message as string };
    }

    return {
      user: data.data as AccountUser,
      message: (data.message as string) ?? "Photo de profil mise à jour avec succès.",
    };
  } catch {
    return { error: "Impossible de contacter le serveur." };
  }
}

export async function getAccountAvatarResponse(
  tenantSlug: string,
  token: string,
): Promise<Response | null> {
  if (!apiBase) return null;

  try {
    const res = await fetch(`${apiBase}/api/v1/tenants/${tenantSlug}/auth/avatar`, {
      cache: "no-store",
      headers: {
        Accept: "image/*",
        Authorization: `Bearer ${token}`,
      },
    });

    return res.ok ? res : null;
  } catch {
    return null;
  }
}

export async function getAccountNotifications(
  tenantSlug: string,
  token: string,
): Promise<{ notifications: AccountNotification[]; unreadCount: number } | null> {
  const p = await apiFetch<{ data: AccountNotification[]; meta?: { unread_count?: number } }>(
    `/api/v1/tenants/${tenantSlug}/auth/notifications`,
    token,
  );

  if (!p) return null;

  return {
    notifications: p.data ?? [],
    unreadCount: Number(p.meta?.unread_count ?? 0),
  };
}

export async function markAccountNotificationAsRead(
  tenantSlug: string,
  token: string,
  id: string,
): Promise<{ notification: AccountNotification; unreadCount: number } | null> {
  const p = await apiFetch<{ data: AccountNotification; meta?: { unread_count?: number } }>(
    `/api/v1/tenants/${tenantSlug}/auth/notifications/${id}/read`,
    token,
    { method: "PATCH" },
  );

  if (!p) return null;

  return {
    notification: p.data,
    unreadCount: Number(p.meta?.unread_count ?? 0),
  };
}

export async function markAllAccountNotificationsAsRead(
  tenantSlug: string,
  token: string,
): Promise<{ unreadCount: number } | null> {
  const p = await apiFetch<{ meta?: { unread_count?: number } }>(
    `/api/v1/tenants/${tenantSlug}/auth/notifications/read-all`,
    token,
    { method: "PATCH" },
  );

  if (!p) return null;

  return {
    unreadCount: Number(p.meta?.unread_count ?? 0),
  };
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

export async function getPublicReceiptVerification(
  tenantSlug: string,
  reference: string,
): Promise<PublicReceiptVerification | null> {
  if (!apiBase) return null;

  try {
    const res = await fetch(
      `${apiBase}/api/v1/public/tenants/${tenantSlug}/receipts/${encodeURIComponent(reference)}/verify`,
      { cache: "no-store", headers: { Accept: "application/json" } },
    );
    if (!res.ok) return null;
    const body = await res.json();
    return (body?.data as PublicReceiptVerification) ?? null;
  } catch {
    return null;
  }
}
