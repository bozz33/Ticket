"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { Suspense, useEffect, useMemo, useState } from "react";

import "../../../account.css";

function ResetPasswordForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const rawEmail = searchParams.get("email") ?? "";
  const rawToken = searchParams.get("token") ?? "";
  const [email, setEmail] = useState("");
  const [token, setToken] = useState("");

  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const hasValidLink = useMemo(() => email.length > 0 && token.length > 0, [email, token]);

  useEffect(() => {
    setEmail(rawEmail);
    setToken(rawToken);
  }, [rawEmail, rawToken]);

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError("");

    if (!hasValidLink) {
      setError("Le lien de reinitialisation est incomplet ou invalide.");
      return;
    }

    if (password !== passwordConfirmation) {
      setError("Les mots de passe ne correspondent pas.");
      return;
    }

    setLoading(true);

    try {
      const response = await fetch("/api/account/reset-password", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          email,
          token,
          password,
          passwordConfirmation,
        }),
      });

      const data = await response.json();

      if (!response.ok) {
        setError(data?.error ?? "Impossible de reinitialiser le mot de passe.");
        return;
      }

      router.push("/compte/connexion?reset=success");
      router.refresh();
    } catch {
      setError("Impossible de contacter le serveur.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="ac-login-wrap">
      <div className="ac-login">
        <div className="ac-login__logo">🗝️</div>
        <h1 className="ac-login__title">Nouveau mot de passe</h1>
        <p className="ac-login__sub">Definissez un nouveau mot de passe pour le compte {email || "selectionne"}.</p>

        {!hasValidLink ? <p className="ac-form__error">Le lien de reinitialisation est invalide ou a expire.</p> : null}

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
              placeholder="Au moins 8 caracteres"
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

          <button className="ac-form__submit" disabled={loading || !hasValidLink} type="submit">
            {loading ? "Validation…" : "Mettre a jour le mot de passe"}
          </button>

          <p className="ac-form__link">
            <Link href="/compte/reinitialisation">Demander un nouveau lien</Link>
          </p>
          <p className="ac-form__link">
            <Link href="/compte/connexion">Retour a la connexion</Link>
          </p>
        </form>
      </div>
    </div>
  );
}

export default function NewPasswordPage() {
  return (
    <Suspense>
      <ResetPasswordForm />
    </Suspense>
  );
}
