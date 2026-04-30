"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";

import "../../account.css";

export default function InscriptionPage() {
  const router = useRouter();

  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError("");
    setLoading(true);

    try {
      const res = await fetch("/api/account/register", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name, email, password }),
      });

      const data = await res.json();

      if (!res.ok) {
        setError(data?.error ?? "Inscription impossible.");
        return;
      }

      router.push("/compte/commandes");
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
        <h1 className="ac-login__title">Créer un compte</h1>
        <p className="ac-login__sub">Suivez vos commandes et gérez vos passes d&apos;accès.</p>

        <form className="ac-form" onSubmit={handleSubmit}>
          <div className="ac-form__field">
            <label className="ac-form__label" htmlFor="name">Nom complet</label>
            <input
              id="name"
              className="ac-form__input"
              type="text"
              autoComplete="name"
              required
              placeholder="Jean Dupont"
              value={name}
              onChange={(e) => setName(e.target.value)}
            />
          </div>

          <div className="ac-form__field">
            <label className="ac-form__label" htmlFor="email">Adresse e-mail</label>
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
            <label className="ac-form__label" htmlFor="password">Mot de passe</label>
            <input
              id="password"
              className="ac-form__input"
              type="password"
              autoComplete="new-password"
              required
              minLength={8}
              placeholder="8 caractères minimum"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
            />
          </div>

          {error && <p className="ac-form__error">{error}</p>}

          <button className="ac-form__submit" type="submit" disabled={loading}>
            {loading ? "Création…" : "Créer mon compte"}
          </button>

          <p className="ac-form__link">
            Déjà inscrit ?{" "}
            <Link href="/compte/connexion">Se connecter</Link>
          </p>
        </form>
      </div>
    </div>
  );
}
