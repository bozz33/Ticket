"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { Suspense, useEffect, useState } from "react";

import "../../account.css";

function LoginForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const rawRedirect = searchParams.get("redirect") ?? "/compte/commandes";
  const rawTenant = searchParams.get("tenant")?.trim() ?? "";
  const rawResetSuccess = searchParams.get("reset") === "success";
  const rawVerifiedSuccess = searchParams.get("verified") === "success";
  const rawLikeReason = searchParams.get("reason") === "like";
  const [redirect, setRedirect] = useState("/compte/commandes");
  const [tenant, setTenant] = useState("");
  const [resetSuccess, setResetSuccess] = useState(false);
  const [verifiedSuccess, setVerifiedSuccess] = useState(false);
  const [likeReason, setLikeReason] = useState(false);
  const signupHref = tenant ? `/compte/inscription?tenant=${encodeURIComponent(tenant)}` : "/compte/inscription";
  const forgotPasswordHref = tenant ? `/compte/reinitialisation?tenant=${encodeURIComponent(tenant)}` : "/compte/reinitialisation";

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    setRedirect(rawRedirect);
    setTenant(rawTenant);
    setResetSuccess(rawResetSuccess);
    setVerifiedSuccess(rawVerifiedSuccess);
    setLikeReason(rawLikeReason);
  }, [rawLikeReason, rawRedirect, rawResetSuccess, rawTenant, rawVerifiedSuccess]);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError("");
    setLoading(true);

    try {
      const res = await fetch("/api/account/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password, tenant: tenant || undefined }),
      });

      const data = await res.json();

      if (!res.ok) {
        setError(data?.error ?? "Identifiants incorrects.");
        return;
      }

      router.push(redirect);
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
        <div className="ac-login__logo">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z" />
            <path d="M13 5v2" />
            <path d="M13 17v2" />
            <path d="M13 11v2" />
          </svg>
        </div>
        <h1 className="ac-login__title">Connexion</h1>
        <p className="ac-login__sub">Accédez à vos commandes et passes d&apos;accès.</p>

        <form className="ac-form" onSubmit={handleSubmit}>
          <div className="ac-form__field">
            <label className="ac-form__label" htmlFor="email">
              Adresse e-mail
            </label>
            <input
              id="email"
              className="ac-form__input"
              type="email"
              autoComplete="email"
              required
              placeholder="votre@email.com"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
            />
          </div>

          <div className="ac-form__field">
            <label className="ac-form__label" htmlFor="password">
              Mot de passe
            </label>
            <input
              id="password"
              className="ac-form__input"
              type="password"
              autoComplete="current-password"
              required
              placeholder="••••••••"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
            />
          </div>

          <p className="ac-form__link" style={{ marginTop: "-8px", textAlign: "right" }}>
            <Link href={forgotPasswordHref}>Mot de passe oublie ?</Link>
          </p>

          {error && <p className="ac-form__error">{error}</p>}
          {likeReason ? <p className="ac-form__success">Connectez-vous pour aimer un événement puis revenir exactement à votre page.</p> : null}
          {resetSuccess ? <p className="ac-form__success">Votre mot de passe a ete mis a jour. Vous pouvez maintenant vous connecter.</p> : null}
          {verifiedSuccess ? <p className="ac-form__success">Votre adresse e-mail est vérifiée. Vous pouvez continuer.</p> : null}

          <button className="ac-form__submit" type="submit" disabled={loading}>
            {loading ? "Connexion…" : "Se connecter"}
          </button>

          <p className="ac-form__link">
            Pas encore de compte ?{" "}
            <Link href={signupHref}>S&apos;inscrire</Link>
          </p>
          <p className="ac-form__link">
            Un problème ?{" "}
            <Link href="/contact">Contacter le support</Link>
          </p>
        </form>
      </div>
    </div>
  );
}

export default function ConnexionPage() {
  return (
    <Suspense>
      <LoginForm />
    </Suspense>
  );
}
