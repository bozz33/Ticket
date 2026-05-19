"use client";

import type { ChangeEvent, FormEvent } from "react";

import type { AccountUser } from "@/lib/types";

import type { ProfileFormState } from "./types";

type ProfileInfoFormProps = {
  error: string | null;
  form: ProfileFormState | null;
  isSaving: boolean;
  isSendingVerification: boolean;
  success: string | null;
  user: AccountUser | null;
  onChange: (event: ChangeEvent<HTMLInputElement | HTMLSelectElement>) => void;
  onSendVerification: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export function ProfileInfoForm({
  error,
  form,
  isSaving,
  isSendingVerification,
  success,
  user,
  onChange,
  onSendVerification,
  onSubmit,
}: ProfileInfoFormProps) {
  return (
    <div className="ac-detail-panel">
      <div className="ac-detail-panel__head">
        <span className="ac-detail-panel__title">Informations personnelles</span>
      </div>
      <div className="ac-detail-panel__body">
        {form ? (
          <form className="ac-form ac-profile-form" onSubmit={onSubmit}>
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
                  onClick={onSendVerification}
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
              <label className="ac-form__field" htmlFor="profile-name">
                <span className="ac-form__label">Nom complet</span>
                <input className="ac-form__input" id="profile-name" name="name" value={form.name} onChange={onChange} required />
              </label>

              <label className="ac-form__field" htmlFor="profile-email">
                <span className="ac-form__label">Adresse e-mail</span>
                <input className="ac-form__input" id="profile-email" name="email" type="email" value={form.email} onChange={onChange} required />
              </label>

              <label className="ac-form__field" htmlFor="profile-first-name">
                <span className="ac-form__label">Prénom</span>
                <input className="ac-form__input" id="profile-first-name" name="first_name" value={form.first_name} onChange={onChange} />
              </label>

              <label className="ac-form__field" htmlFor="profile-last-name">
                <span className="ac-form__label">Nom</span>
                <input className="ac-form__input" id="profile-last-name" name="last_name" value={form.last_name} onChange={onChange} />
              </label>

              <label className="ac-form__field" htmlFor="profile-phone">
                <span className="ac-form__label">Téléphone</span>
                <input className="ac-form__input" id="profile-phone" name="phone" type="tel" value={form.phone} onChange={onChange} />
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
  );
}
