"use client";

import { useRouter } from "next/navigation";
import { type ChangeEvent, type FormEvent, useEffect, useState } from "react";

import type { AccountUser } from "@/lib/types";

type ProfileFormState = {
  name: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  locale: string;
  timezone: string;
};

type PasswordFormState = {
  current_password: string;
  password: string;
  password_confirmation: string;
};

function toFormState(user: AccountUser): ProfileFormState {
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

function emptyToNull(value: string): string | null {
  const normalized = value.trim();
  return normalized.length > 0 ? normalized : null;
}

function initials(name: string): string {
  return name
    .split(" ")
    .slice(0, 2)
    .map((word) => word[0]?.toUpperCase() ?? "")
    .join("");
}

export function ProfileEditor({ initialUser }: { initialUser: AccountUser | null }) {
  const router = useRouter();
  const [user, setUser] = useState<AccountUser | null>(initialUser);
  const [form, setForm] = useState<ProfileFormState | null>(initialUser ? toFormState(initialUser) : null);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);
  const [isSendingVerification, setIsSendingVerification] = useState(false);
  const [avatarPreview, setAvatarPreview] = useState<string | null>(null);
  const [avatarError, setAvatarError] = useState<string | null>(null);
  const [avatarSuccess, setAvatarSuccess] = useState<string | null>(null);
  const [isUploadingAvatar, setIsUploadingAvatar] = useState(false);
  const [passwordForm, setPasswordForm] = useState<PasswordFormState>({
    current_password: "",
    password: "",
    password_confirmation: "",
  });
  const [passwordError, setPasswordError] = useState<string | null>(null);
  const [passwordSuccess, setPasswordSuccess] = useState<string | null>(null);
  const [isChangingPassword, setIsChangingPassword] = useState(false);

  useEffect(() => {
    setUser(initialUser);
    setForm(initialUser ? toFormState(initialUser) : null);
  }, [initialUser]);

  useEffect(() => {
    return () => {
      if (avatarPreview?.startsWith("blob:")) {
        URL.revokeObjectURL(avatarPreview);
      }
    };
  }, [avatarPreview]);

  function handleChange(event: ChangeEvent<HTMLInputElement | HTMLSelectElement>) {
    const { name, value } = event.target;
    setForm((current) => (current ? { ...current, [name]: value } : current));
    setError(null);
    setSuccess(null);
  }

  function handlePasswordChange(event: ChangeEvent<HTMLInputElement>) {
    const { name, value } = event.target;
    setPasswordForm((current) => ({ ...current, [name]: value }));
    setPasswordError(null);
    setPasswordSuccess(null);
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (!form) {
      return;
    }

    setIsSaving(true);
    setError(null);
    setSuccess(null);

    try {
      const response = await fetch("/api/account/me", {
        method: "PUT",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          name: form.name.trim(),
          first_name: emptyToNull(form.first_name),
          last_name: emptyToNull(form.last_name),
          email: form.email.trim(),
          phone: emptyToNull(form.phone),
          locale: emptyToNull(form.locale) ?? "fr",
          timezone: emptyToNull(form.timezone) ?? "Africa/Abidjan",
        }),
      });

      const payload = (await response.json().catch(() => null)) as
        | { error?: string; message?: string; user?: AccountUser }
        | null;

      if (!response.ok || !payload?.user) {
        setError(payload?.error ?? payload?.message ?? "Impossible de mettre à jour votre profil.");
        return;
      }

      setUser(payload.user);
      setForm(toFormState(payload.user));
      setSuccess(payload.message ?? "Profil mis à jour avec succès.");
      window.dispatchEvent(new CustomEvent<AccountUser>("account-profile-updated", { detail: payload.user }));
      router.refresh();
    } catch {
      setError("Impossible de contacter le serveur pour le moment.");
    } finally {
      setIsSaving(false);
    }
  }

  async function handleAvatarChange(event: ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0] ?? null;

    if (!file) {
      return;
    }

    const preview = URL.createObjectURL(file);
    setAvatarPreview((current) => {
      if (current?.startsWith("blob:")) {
        URL.revokeObjectURL(current);
      }

      return preview;
    });
    setAvatarError(null);
    setAvatarSuccess(null);
    setIsUploadingAvatar(true);

    try {
      const formData = new FormData();
      formData.append("avatar", file);

      const response = await fetch("/api/account/avatar", {
        method: "POST",
        body: formData,
      });

      const payload = (await response.json().catch(() => null)) as
        | { error?: string; message?: string; user?: AccountUser }
        | null;

      if (!response.ok || !payload?.user) {
        setAvatarError(payload?.error ?? payload?.message ?? "Impossible de mettre à jour la photo.");
        return;
      }

      setUser(payload.user);
      setForm(toFormState(payload.user));
      setAvatarSuccess(payload.message ?? "Photo de profil mise à jour.");
      window.dispatchEvent(new CustomEvent<AccountUser>("account-profile-updated", { detail: payload.user }));
      router.refresh();
    } catch {
      setAvatarError("Impossible de contacter le serveur pour le moment.");
    } finally {
      setIsUploadingAvatar(false);
      event.target.value = "";
    }
  }

  async function handleSendVerification() {
    setIsSendingVerification(true);
    setError(null);
    setSuccess(null);

    try {
      const response = await fetch("/api/account/email/verification-notification", {
        method: "POST",
        headers: { Accept: "application/json" },
      });
      const payload = (await response.json().catch(() => null)) as
        | { error?: string; message?: string; user?: AccountUser }
        | null;

      if (!response.ok) {
        setError(payload?.error ?? payload?.message ?? "Impossible d'envoyer le lien de vérification.");
        return;
      }

      if (payload?.user) {
        setUser(payload.user);
        setForm(toFormState(payload.user));
      }

      setSuccess(payload?.message ?? "Lien de vérification envoyé.");
    } catch {
      setError("Impossible de contacter le serveur pour le moment.");
    } finally {
      setIsSendingVerification(false);
    }
  }

  async function handlePasswordSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPasswordError(null);
    setPasswordSuccess(null);

    if (passwordForm.password !== passwordForm.password_confirmation) {
      setPasswordError("Les deux nouveaux mots de passe ne correspondent pas.");
      return;
    }

    setIsChangingPassword(true);

    try {
      const response = await fetch("/api/account/password", {
        method: "PUT",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify(passwordForm),
      });

      const payload = (await response.json().catch(() => null)) as
        | { error?: string; message?: string; user?: AccountUser }
        | null;

      if (!response.ok || !payload?.user) {
        setPasswordError(payload?.error ?? payload?.message ?? "Impossible de modifier le mot de passe.");
        return;
      }

      setUser(payload.user);
      setPasswordForm({
        current_password: "",
        password: "",
        password_confirmation: "",
      });
      setPasswordSuccess(payload.message ?? "Mot de passe mis à jour.");
      window.dispatchEvent(new CustomEvent<AccountUser>("account-profile-updated", { detail: payload.user }));
    } catch {
      setPasswordError("Impossible de contacter le serveur pour le moment.");
    } finally {
      setIsChangingPassword(false);
    }
  }

  const avatarSrc = avatarPreview ?? (user?.avatar_url ? `/api/account/avatar-image?v=${encodeURIComponent(user.avatar_url)}` : null);

  return (
    <>
      <div className="ac-page-header">
        <h1 className="ac-page-title">Mon profil</h1>
        <p className="ac-page-sub">Mettez à jour vos informations acheteur et gardez votre compte à jour.</p>
      </div>

      <div className="ac-profile-grid">
        <div className="ac-detail-panel">
          <div className="ac-detail-panel__head">
            <span className="ac-detail-panel__title">Informations personnelles</span>
          </div>
          <div className="ac-detail-panel__body">
            {form ? (
              <form className="ac-form ac-profile-form" onSubmit={handleSubmit}>
                {error ? <p className="ac-form__error">{error}</p> : null}
                {success ? <p className="ac-form__success">{success}</p> : null}
                {user && !user.email_verified ? (
                  <div className="ac-verification-callout">
                    <div>
                      <strong>Adresse e-mail non vérifiée</strong>
                      <p>La vérification de l&apos;e-mail et le profil complet sont requis avant les actions sensibles: achats, réservations, abonnements et candidatures.</p>
                    </div>
                    <button
                      className="ac-profile-form__button ac-profile-form__button--inline"
                      disabled={isSendingVerification}
                      onClick={() => void handleSendVerification()}
                      type="button"
                    >
                      {isSendingVerification ? "Envoi..." : "Renvoyer le lien"}
                    </button>
                  </div>
                ) : null}
                {user && !user.profile_completed ? (
                  <div className="ac-verification-callout">
                    <div>
                      <strong>Profil à compléter</strong>
                      <p>
                        Renseignez les champs manquants pour débloquer les actions sensibles :
                        {" "}
                        {user.missing_profile_fields.length > 0 ? user.missing_profile_fields.join(", ") : "informations personnelles"}.
                      </p>
                    </div>
                  </div>
                ) : null}

                <div className="ac-profile-form__grid">
                  <label className="ac-form__field">
                    <span className="ac-form__label">Nom complet</span>
                    <input className="ac-form__input" name="name" value={form.name} onChange={handleChange} required />
                  </label>

                  <label className="ac-form__field">
                    <span className="ac-form__label">Adresse e-mail</span>
                    <input className="ac-form__input" name="email" type="email" value={form.email} onChange={handleChange} required />
                  </label>

                  <label className="ac-form__field">
                    <span className="ac-form__label">Prénom</span>
                    <input className="ac-form__input" name="first_name" value={form.first_name} onChange={handleChange} />
                  </label>

                  <label className="ac-form__field">
                    <span className="ac-form__label">Nom</span>
                    <input className="ac-form__input" name="last_name" value={form.last_name} onChange={handleChange} />
                  </label>

                  <label className="ac-form__field">
                    <span className="ac-form__label">Téléphone</span>
                    <input className="ac-form__input" name="phone" type="tel" value={form.phone} onChange={handleChange} />
                  </label>
                </div>

                <div className="ac-profile-form__actions">
                  <button className="ac-profile-form__button" type="submit" disabled={isSaving}>
                    {isSaving ? "Enregistrement..." : "Enregistrer les modifications"}
                  </button>
                </div>
              </form>
            ) : (
              <p className="ac-profile-form__empty">Impossible de charger le profil pour le moment.</p>
            )}
          </div>
        </div>

        <div className="ac-profile-side">
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
                <label className="ac-profile-photo__button">
                  <input accept="image/jpeg,image/png,image/webp" type="file" onChange={handleAvatarChange} disabled={isUploadingAvatar} />
                  {isUploadingAvatar ? "Envoi en cours..." : "Modifier la photo"}
                </label>
              </div>
            </div>
          </div>

          <div className="ac-detail-panel">
            <div className="ac-detail-panel__head">
              <span className="ac-detail-panel__title">Sécurité</span>
            </div>
            <div className="ac-detail-panel__body">
              <form className="ac-form" onSubmit={handlePasswordSubmit}>
                {passwordError ? <p className="ac-form__error">{passwordError}</p> : null}
                {passwordSuccess ? <p className="ac-form__success">{passwordSuccess}</p> : null}

                <label className="ac-form__field">
                  <span className="ac-form__label">Mot de passe actuel</span>
                  <input className="ac-form__input" name="current_password" type="password" value={passwordForm.current_password} onChange={handlePasswordChange} required />
                </label>

                <label className="ac-form__field">
                  <span className="ac-form__label">Nouveau mot de passe</span>
                  <input className="ac-form__input" name="password" type="password" minLength={8} value={passwordForm.password} onChange={handlePasswordChange} required />
                </label>

                <label className="ac-form__field">
                  <span className="ac-form__label">Confirmation</span>
                  <input className="ac-form__input" name="password_confirmation" type="password" minLength={8} value={passwordForm.password_confirmation} onChange={handlePasswordChange} required />
                </label>

                <button className="ac-profile-form__button" type="submit" disabled={isChangingPassword}>
                  {isChangingPassword ? "Modification..." : "Changer le mot de passe"}
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
