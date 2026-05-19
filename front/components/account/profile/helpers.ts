import type { AccountUser } from "@/lib/types";

import type { ProfileFormState } from "./types";

export function toProfileFormState(user: AccountUser): ProfileFormState {
  return {
    name: user.name ?? "",
    first_name: user.first_name ?? "",
    last_name: user.last_name ?? "",
    email: user.email ?? "",
    phone: user.phone ?? "",
    locale: user.locale ?? "fr",
    timezone: user.timezone ?? "Africa/Abidjan",
  };
}

export function emptyToNull(value: string): string | null {
  const normalized = value.trim();
  return normalized.length > 0 ? normalized : null;
}

export function initials(name: string): string {
  return name
    .split(" ")
    .slice(0, 2)
    .map((word) => word[0]?.toUpperCase() ?? "")
    .join("");
}

export function notifyAccountProfileUpdated(user: AccountUser): void {
  window.dispatchEvent(new CustomEvent<AccountUser>("account-profile-updated", { detail: user }));
}
