"use client";

import Link from "next/link";

import { AuthTicketLogo } from "./AuthTicketLogo";
import { useRegistrationForm } from "./useRegistrationForm";

export function RegistrationForm() {
  const {
    email,
    error,
    handleSubmit,
    loading,
    loginHref,
    name,
    password,
    setEmail,
    setName,
    setPassword,
  } = useRegistrationForm();

  return (
    <div className="ac-login-wrap">
      <div className="ac-login">
        <AuthTicketLogo />
        <h1 className="ac-login__title">Créer un compte</h1>
        <p className="ac-login__sub">Suivez vos commandes et gérez vos passes d&apos;accès.</p>

        <form className="ac-form" onSubmit={handleSubmit}>
          <div className="ac-form__field">
            <label className="ac-form__label" htmlFor="name">
              Nom complet
            </label>
            <input
              autoComplete="name"
              className="ac-form__input"
              id="name"
              onChange={(event) => setName(event.target.value)}
              placeholder="Jean Dupont"
              required
              type="text"
              value={name}
            />
          </div>

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
              autoComplete="new-password"
              className="ac-form__input"
              id="password"
              minLength={8}
              onChange={(event) => setPassword(event.target.value)}
              placeholder="8 caractères minimum"
              required
              type="password"
              value={password}
            />
          </div>

          {error ? <p className="ac-form__error">{error}</p> : null}

          <button className="ac-form__submit" type="submit" disabled={loading}>
            {loading ? "Création…" : "Créer mon compte"}
          </button>

          <p className="ac-form__link">
            Déjà inscrit ? <Link href={loginHref}>Se connecter</Link>
          </p>
        </form>
      </div>
    </div>
  );
}
