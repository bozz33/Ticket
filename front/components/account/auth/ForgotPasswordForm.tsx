"use client";

import Link from "next/link";

import { AuthTicketLogo } from "./AuthTicketLogo";
import { useForgotPasswordForm } from "./useForgotPasswordForm";

export function ForgotPasswordForm() {
  const { email, error, handleSubmit, loading, loginHref, setEmail, success } = useForgotPasswordForm();

  return (
    <div className="ac-login-wrap">
      <div className="ac-login">
        <AuthTicketLogo />
        <h1 className="ac-login__title">Mot de passe oublié</h1>
        <p className="ac-login__sub">Recevez un lien de réinitialisation pour retrouver l&apos;accès à votre espace acheteur.</p>

        <form className="ac-form" onSubmit={handleSubmit}>
          <div className="ac-form__field">
            <label className="ac-form__label" htmlFor="email">
              Adresse e-mail
            </label>
            <input
              autoComplete="email"
              className="ac-form__input"
              id="email"
              onChange={(event) => setEmail(event.target.value)}
              placeholder="votre@email.com"
              required
              type="email"
              value={email}
            />
          </div>

          {error ? <p className="ac-form__error">{error}</p> : null}
          {success ? <p className="ac-form__success">{success}</p> : null}

          <button className="ac-form__submit" disabled={loading} type="submit">
            {loading ? "Envoi…" : "Envoyer le lien"}
          </button>

          <p className="ac-form__link">
            Vous connaissez votre mot de passe ? <Link href={loginHref}>Revenir à la connexion</Link>
          </p>
          <p className="ac-form__link">
            Besoin d&apos;aide ? <Link href="/contact">Contacter le support</Link>
          </p>
        </form>
      </div>
    </div>
  );
}
