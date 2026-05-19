"use client";

import { PasswordPanel } from "@/components/account/profile/PasswordPanel";
import { ProfileInfoForm } from "@/components/account/profile/ProfileInfoForm";
import { ProfilePhotoPanel } from "@/components/account/profile/ProfilePhotoPanel";
import { useProfileEditor } from "@/components/account/profile/useProfileEditor";
import type { AccountUser } from "@/lib/types";

export function ProfileEditor({ initialUser }: { initialUser: AccountUser | null }) {
  const profile = useProfileEditor(initialUser);

  return (
    <>
      <div className="ac-page-header">
        <h1 className="ac-page-title">Mon profil</h1>
        <p className="ac-page-sub">Mettez à jour vos informations acheteur et gardez votre compte à jour.</p>
      </div>

      <div className="ac-profile-grid">
        <ProfileInfoForm
          error={profile.error}
          form={profile.form}
          isSaving={profile.isSaving}
          isSendingVerification={profile.isSendingVerification}
          success={profile.success}
          user={profile.user}
          onChange={profile.handleChange}
          onSendVerification={() => void profile.handleSendVerification()}
          onSubmit={profile.handleSubmit}
        />

        <div className="ac-profile-side">
          <ProfilePhotoPanel
            avatarError={profile.avatarError}
            avatarSrc={profile.avatarSrc}
            avatarSuccess={profile.avatarSuccess}
            isUploadingAvatar={profile.isUploadingAvatar}
            user={profile.user}
            onAvatarChange={profile.handleAvatarChange}
          />

          <PasswordPanel
            error={profile.passwordError}
            form={profile.passwordForm}
            isChangingPassword={profile.isChangingPassword}
            success={profile.passwordSuccess}
            onChange={profile.handlePasswordChange}
            onSubmit={profile.handlePasswordSubmit}
          />
        </div>
      </div>
    </>
  );
}
