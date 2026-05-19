"use client";

import { OrganizerRegistrationFields } from "./OrganizerRegistrationFields";
import { OrganizerRegistrationSuccessPanel } from "./OrganizerRegistrationSuccessPanel";
import { useOrganizerRegistrationForm } from "./useOrganizerRegistrationForm";

export function OrganizerRegistrationForm({ brandName }: { brandName: string }) {
  const {
    email,
    error,
    handleSubmit,
    loading,
    orgName,
    password,
    setEmail,
    setOrgName,
    setPassword,
    success,
  } = useOrganizerRegistrationForm();

  if (success) {
    return <OrganizerRegistrationSuccessPanel brandName={brandName} success={success} />;
  }

  return (
    <form onSubmit={handleSubmit} style={{ display: "flex", flexDirection: "column", gap: "20px" }}>
      <OrganizerRegistrationFields
        email={email}
        orgName={orgName}
        password={password}
        setEmail={setEmail}
        setOrgName={setOrgName}
        setPassword={setPassword}
      />

      {error ? <p style={{ color: "#e53e3e", fontSize: "0.875rem", margin: 0 }}>{error}</p> : null}

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
