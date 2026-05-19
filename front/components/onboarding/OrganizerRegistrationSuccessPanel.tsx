import type { OrganizerRegistrationSuccess } from "./useOrganizerRegistrationForm";

type OrganizerRegistrationSuccessPanelProps = {
  brandName: string;
  success: OrganizerRegistrationSuccess;
};

export function OrganizerRegistrationSuccessPanel({ brandName, success }: OrganizerRegistrationSuccessPanelProps) {
  return (
    <div
      style={{
        background: "var(--surface)",
        border: "1px solid var(--border)",
        borderRadius: "12px",
        padding: "32px",
        textAlign: "center",
      }}
    >
      <div style={{ fontSize: "2.5rem", marginBottom: "16px" }}>✓</div>
      <h3 style={{ fontWeight: 700, fontSize: "1.2rem", marginBottom: "8px" }}>Votre espace est prêt !</h3>
      <p style={{ color: "var(--text-soft)", marginBottom: "24px" }}>
        L&apos;espace <strong>{success.slug}</strong> a été créé sur {brandName}. Vous pouvez accéder directement à
        votre backoffice.
      </p>
      <a href={success.accessUrl} className="button" style={{ display: "inline-block" }}>
        Accéder au backoffice
      </a>
    </div>
  );
}
