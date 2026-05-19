"use client";

import { useEffect, useState } from "react";

import type { AccountUser } from "@/lib/types";

import { initials } from "./helpers";

export function AccountAvatar({ user, className }: { user: AccountUser | null; className: string }) {
  const avatarVersion = user?.avatar_url?.trim() ?? "";
  const avatarSrc = avatarVersion ? `/api/account/avatar-image?v=${encodeURIComponent(avatarVersion)}` : null;
  const [hasImageError, setHasImageError] = useState(false);

  useEffect(() => {
    setHasImageError(false);
  }, [avatarSrc]);

  if (avatarSrc && !hasImageError) {
    return (
      <img
        alt={user?.name ? `Photo de ${user.name}` : "Photo de profil"}
        className={className}
        src={avatarSrc}
        onError={() => setHasImageError(true)}
      />
    );
  }

  return <div className={className}>{user ? initials(user.name) : "?"}</div>;
}
