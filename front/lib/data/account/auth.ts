import type { AccountUser } from "@/lib/types";
import { apiBase } from "./client";

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
