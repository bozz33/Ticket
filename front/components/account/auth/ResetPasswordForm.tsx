"use client";

import Link from "next/link";

import { AuthTicketLogo } from "./AuthTicketLogo";
import { useResetPasswordForm } from "./useResetPasswordForm";

export function ResetPasswordForm() {
  const {
    email,
    error,
    handleSubmit,
    hasValidLink,
    isReady,
    loading,
    password,
    passwordConfirmation,
    setPassword,
    setPasswordConfirmation,
  } = useResetPasswordForm();

  return (
    <div className="ac-login-wrap">
      <div className="ac-login">
        <AuthTicketLogo />
        <h1 className="ac-login__title">Nouveau mot de passe</h1>
        <p className="ac-login__sub">
          Définissez un nouveau mot de passe pour le compte {isReady && email ? email : "sélectionné"}.
        </p>

        {isReady && !hasValidLink ? (
          <p className="ac-form__error">Le lien de réinitialisation est invalide ou a expiré.</p>
        ) : null}

        <form className="ac-form" onSubmit={handleSubmit}>
          <div className="ac-form__field">
            <label className="ac-form__label" htmlFor="password">
              Nouveau mot de passe
            </label>
            <input
              autoComplete="new-password"
              className="ac-form__input"
              id="password"
              minLength={8}
              onChange={(event) => setPassword(event.target.value)}
              placeholder="Au moins 8 caractères"
              required
              type="password"
              value={password}
            />
          </div>

          <div className="ac-form__field">
            <label className="ac-form__label" htmlFor="passwordConfirmation">
              Confirmation du mot de passe
            </label>
            <input
              autoComplete="new-password"
              className="ac-form__input"
              id="passwordConfirmation"
              minLength={8}
              onChange={(event) => setPasswordConfirmation(event.target.value)}
              placeholder="Retapez votre mot de passe"
              required
              type="password"
              value={passwordConfirmation}
            />
          </div>

          {error ? <p className="ac-form__error">{error}</p> : null}

          <button className="ac-form__submit" disabled={loading || (isReady && !hasValidLink)} type="submit">
            {loading ? "Validation…" : "Mettre à jour le mot de passe"}
          </button>

          <p className="ac-form__link">
            <Link href="/compte/reinitialisation">Demander un nouveau lien</Link>
          </p>
          <p className="ac-form__link">
            <Link href="/compte/connexion">Retour à la connexion</Link>
          </p>
        </form>
      </div>
    </div>
  );
}
