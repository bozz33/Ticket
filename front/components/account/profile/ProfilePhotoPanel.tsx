"use client";

import type { ChangeEvent } from "react";

import type { AccountUser } from "@/lib/types";

import { initials } from "./helpers";

type ProfilePhotoPanelProps = {
  avatarError: string | null;
  avatarSrc: string | null;
  avatarSuccess: string | null;
  isUploadingAvatar: boolean;
  user: AccountUser | null;
  onAvatarChange: (event: ChangeEvent<HTMLInputElement>) => void;
};

export function ProfilePhotoPanel({
  avatarError,
  avatarSrc,
  avatarSuccess,
  isUploadingAvatar,
  user,
  onAvatarChange,
}: ProfilePhotoPanelProps) {
  return (
    <div className="ac-detail-panel">
      <div className="ac-detail-panel__head">
        <span className="ac-detail-panel__title">Photo</span>
      </div>
      <div className="ac-detail-panel__body">
        <div className="ac-profile-photo">
          <div className="ac-profile-photo__avatar">
            {avatarSrc ? (
              <img alt={user?.name ? `Photo de ${user.name}` : "Photo de profil"} src={avatarSrc} />
            ) : (
              <span>{user ? initials(user.name) : "?"}</span>
            )}
          </div>
          {avatarError ? <p className="ac-form__error">{avatarError}</p> : null}
          {avatarSuccess ? <p className="ac-form__success">{avatarSuccess}</p> : null}
          <label className="ac-profile-photo__button" htmlFor="profile-avatar">
            <input accept="image/jpeg,image/png,image/webp" id="profile-avatar" type="file" onChange={onAvatarChange} disabled={isUploadingAvatar} />
            {isUploadingAvatar ? "Envoi en cours..." : "Modifier la photo"}
          </label>
        </div>
      </div>
    </div>
  );
}
