"use client";

import Link from "next/link";

import { AuthTicketLogo } from "./AuthTicketLogo";
import { useLoginForm } from "./useLoginForm";

export function LoginForm() {
  const {
    email,
    error,
    forgotPasswordHref,
    handleSubmit,
    isReady,
    likeReason,
    loading,
    password,
    resetSuccess,
    setEmail,
    setPassword,
    signupHref,
    verifiedSuccess,
  } = useLoginForm();

  return (
    <div className="ac-login-wrap">
      <div className="ac-login">
        <AuthTicketLogo />
        <h1 className="ac-login__title">Connexion</h1>
        <p className="ac-login__sub">Accédez à vos commandes et passes d&apos;accès.</p>

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

          <div className="ac-form__field">
            <label className="ac-form__label" htmlFor="password">
              Mot de passe
            </label>
            <input
              autoComplete="current-password"
              className="ac-form__input"
              id="password"
              onChange={(event) => setPassword(event.target.value)}
              placeholder="••••••••"
              required
              type="password"
              value={password}
            />
          </div>

          <p className="ac-form__link" style={{ marginTop: "-8px", textAlign: "right" }}>
            <Link href={forgotPasswordHref}>Mot de passe oublié ?</Link>
          </p>

          {error ? <p className="ac-form__error">{error}</p> : null}
          {isReady && likeReason ? (
            <p className="ac-form__success">Connectez-vous pour aimer un événement puis revenir exactement à votre page.</p>
          ) : null}
          {isReady && resetSuccess ? (
            <p className="ac-form__success">Votre mot de passe a été mis à jour. Vous pouvez maintenant vous connecter.</p>
          ) : null}
          {isReady && verifiedSuccess ? (
            <p className="ac-form__success">Votre adresse e-mail est vérifiée. Vous pouvez continuer.</p>
          ) : null}

          <button className="ac-form__submit" type="submit" disabled={loading}>
            {loading ? "Connexion…" : "Se connecter"}
          </button>

          <p className="ac-form__link">
            Pas encore de compte ? <Link href={signupHref}>S&apos;inscrire</Link>
          </p>
          <p className="ac-form__link">
            Un problème ? <Link href="/contact">Contacter le support</Link>
          </p>
        </form>
      </div>
    </div>
  );
}
