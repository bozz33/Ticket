"use client";

import type { ChangeEvent, FormEvent } from "react";

import type { PasswordFormState } from "./types";

type PasswordPanelProps = {
  error: string | null;
  form: PasswordFormState;
  isChangingPassword: boolean;
  success: string | null;
  onChange: (event: ChangeEvent<HTMLInputElement>) => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export function PasswordPanel({
  error,
  form,
  isChangingPassword,
  success,
  onChange,
  onSubmit,
}: PasswordPanelProps) {
  return (
    <div className="ac-detail-panel">
      <div className="ac-detail-panel__head">
        <span className="ac-detail-panel__title">Sécurité</span>
      </div>
      <div className="ac-detail-panel__body">
        <form className="ac-form" onSubmit={onSubmit}>
          {error ? <p className="ac-form__error">{error}</p> : null}
          {success ? <p className="ac-form__success">{success}</p> : null}

          <label className="ac-form__field" htmlFor="profile-current-password">
            <span className="ac-form__label">Mot de passe actuel</span>
            <input className="ac-form__input" id="profile-current-password" name="current_password" type="password" value={form.current_password} onChange={onChange} required />
          </label>

          <label className="ac-form__field" htmlFor="profile-new-password">
            <span className="ac-form__label">Nouveau mot de passe</span>
            <input className="ac-form__input" id="profile-new-password" name="password" type="password" minLength={8} value={form.password} onChange={onChange} required />
          </label>

          <label className="ac-form__field" htmlFor="profile-password-confirmation">
            <span className="ac-form__label">Confirmation</span>
            <input className="ac-form__input" id="profile-password-confirmation" name="password_confirmation" type="password" minLength={8} value={form.password_confirmation} onChange={onChange} required />
          </label>

          <button className="ac-profile-form__button" type="submit" disabled={isChangingPassword}>
            {isChangingPassword ? "Modification..." : "Changer le mot de passe"}
          </button>
        </form>
      </div>
    </div>
  );
}
