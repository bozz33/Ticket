import type {
  AccountNotification,
  AccountPasswordUpdateInput,
  AccountProfileUpdateInput,
  AccountUser,
} from "@/lib/types";
import { apiBase, apiFetch } from "./client";

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
