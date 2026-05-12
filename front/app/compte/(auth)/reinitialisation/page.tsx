"use client";

import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { Suspense, useEffect, useState } from "react";

import "../../account.css";

function ForgotPasswordForm() {
  const searchParams = useSearchParams();
  const rawTenant = searchParams.get("tenant")?.trim() ?? "";
  const [tenant, setTenant] = useState("");
  const loginHref = tenant ? `/compte/connexion?tenant=${encodeURIComponent(tenant)}` : "/compte/connexion";
  const [email, setEmail] = useState("");
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    setTenant(rawTenant);
  }, [rawTenant]);

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError("");
    setSuccess("");
    setLoading(true);

    try {
      const response = await fetch("/api/account/forgot-password", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, tenant: tenant || undefined }),
      });

      const data = await response.json();

      if (!response.ok) {
        setError(data?.error ?? "Impossible d'envoyer le lien de réinitialisation.");
        return;
      }

      setSuccess(data?.message ?? "Si votre compte existe, un lien de réinitialisation vient d'être envoyé.");
    } catch {
      setError("Impossible de contacter le serveur.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="ac-login-wrap">
      <div className="ac-login">
        <div className="ac-login__logo">🔐</div>
        <h1 className="ac-login__title">Mot de passe oublie</h1>
        <p className="ac-login__sub">Recevez un lien de reinitialisation pour retrouver l'acces a votre espace acheteur.</p>

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
            Vous connaissez votre mot de passe ? <Link href={loginHref}>Revenir a la connexion</Link>
          </p>
          <p className="ac-form__link">
            Besoin d'aide ? <Link href="/contact">Contacter le support</Link>
          </p>
        </form>
      </div>
    </div>
  );
}

export default function ForgotPasswordPage() {
  return (
    <Suspense>
      <ForgotPasswordForm />
    </Suspense>
  );
}
