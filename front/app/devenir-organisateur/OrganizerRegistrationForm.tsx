"use client";

import { useState } from "react";

export function OrganizerRegistrationForm({ brandName }: { brandName: string }) {
  const [orgName, setOrgName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState<{ slug: string; loginUrl: string } | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError("");
    setLoading(true);

    try {
      const res = await fetch("/api/onboarding/register", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ org_name: orgName, email, password }),
      });

      const data = await res.json();

      if (!res.ok) {
        setError(data?.error ?? "Inscription impossible.");
        return;
      }

      setSuccess({ slug: data.tenant.slug, loginUrl: data.tenant.login_url });
    } catch {
      setError("Impossible de contacter le serveur.");
    } finally {
      setLoading(false);
    }
  }

  if (success) {
    return (
      <div style={{ background: "var(--surface)", border: "1px solid var(--border)", borderRadius: "12px", padding: "32px", textAlign: "center" }}>
        <div style={{ fontSize: "2.5rem", marginBottom: "16px" }}>✓</div>
        <h3 style={{ fontWeight: 700, fontSize: "1.2rem", marginBottom: "8px" }}>
          Votre espace est prêt !
        </h3>
        <p style={{ color: "var(--text-soft)", marginBottom: "24px" }}>
          L&apos;espace <strong>{success.slug}</strong> a été créé sur {brandName}. Connectez-vous à votre backoffice pour publier votre premier contenu.
        </p>
        <a
          href={success.loginUrl}
          className="button"
          style={{ display: "inline-block" }}
        >
          Accéder au backoffice
        </a>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} style={{ display: "flex", flexDirection: "column", gap: "20px" }}>
      <div>
        <label style={{ display: "block", fontWeight: 600, marginBottom: "6px", fontSize: "0.9rem" }}>
          Nom de votre organisation
        </label>
        <input
          type="text"
          required
          placeholder="Association des arts de Dakar"
          value={orgName}
          onChange={(e) => setOrgName(e.target.value)}
          style={{ width: "100%", padding: "10px 14px", border: "1px solid var(--border)", borderRadius: "8px", fontSize: "0.95rem", background: "var(--surface)", color: "var(--text)", boxSizing: "border-box" }}
        />
      </div>

      <div>
        <label style={{ display: "block", fontWeight: 600, marginBottom: "6px", fontSize: "0.9rem" }}>
          Adresse e-mail administrateur
        </label>
        <input
          type="email"
          required
          placeholder="contact@organisation.com"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          style={{ width: "100%", padding: "10px 14px", border: "1px solid var(--border)", borderRadius: "8px", fontSize: "0.95rem", background: "var(--surface)", color: "var(--text)", boxSizing: "border-box" }}
        />
      </div>

      <div>
        <label style={{ display: "block", fontWeight: 600, marginBottom: "6px", fontSize: "0.9rem" }}>
          Mot de passe
        </label>
        <input
          type="password"
          required
          minLength={8}
          placeholder="8 caractères minimum"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          style={{ width: "100%", padding: "10px 14px", border: "1px solid var(--border)", borderRadius: "8px", fontSize: "0.95rem", background: "var(--surface)", color: "var(--text)", boxSizing: "border-box" }}
        />
      </div>

      {error && (
        <p style={{ color: "#e53e3e", fontSize: "0.875rem", margin: 0 }}>{error}</p>
      )}

      <button
        type="submit"
        disabled={loading}
        className="button"
        style={{ width: "100%", justifyContent: "center" }}
      >
        {loading ? "Création en cours…" : "Créer mon espace organisateur"}
      </button>

      <p style={{ fontSize: "0.8rem", color: "var(--text-soft)", textAlign: "center", margin: 0 }}>
        En créant un compte, vous acceptez les conditions d&apos;utilisation de la plateforme.
      </p>
    </form>
  );
}
