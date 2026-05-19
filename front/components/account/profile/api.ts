import type { AccountUser } from "@/lib/types";

import type { PasswordFormState, ProfileFormState } from "./types";
import { emptyToNull } from "./helpers";

type AccountUserPayload = {
  error?: string;
  message?: string;
  user?: AccountUser;
};

async function readAccountPayload(response: Response): Promise<AccountUserPayload | null> {
  return (await response.json().catch(() => null)) as AccountUserPayload | null;
}

export async function updateProfile(form: ProfileFormState) {
  const response = await fetch("/api/account/me", {
    body: JSON.stringify({
      email: form.email.trim(),
      first_name: emptyToNull(form.first_name),
      last_name: emptyToNull(form.last_name),
      locale: emptyToNull(form.locale) ?? "fr",
      name: form.name.trim(),
      phone: emptyToNull(form.phone),
      timezone: emptyToNull(form.timezone) ?? "Africa/Abidjan",
    }),
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
    },
    method: "PUT",
  });
  const payload = await readAccountPayload(response);

  return { payload, response };
}

export async function uploadAvatar(file: File) {
  const formData = new FormData();
  formData.append("avatar", file);

  const response = await fetch("/api/account/avatar", {
    body: formData,
    method: "POST",
  });
  const payload = await readAccountPayload(response);

  return { payload, response };
}

export async function sendVerificationNotification() {
  const response = await fetch("/api/account/email/verification-notification", {
    headers: { Accept: "application/json" },
    method: "POST",
  });
  const payload = await readAccountPayload(response);

  return { payload, response };
}

export async function updatePassword(passwordForm: PasswordFormState) {
  const response = await fetch("/api/account/password", {
    body: JSON.stringify(passwordForm),
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
    },
    method: "PUT",
  });
  const payload = await readAccountPayload(response);

  return { payload, response };
}
