"use client";

import type { PublicContent } from "@/lib/types";

type ApplicationActionsProps = {
  currentStepIndex: number;
  form: NonNullable<PublicContent["applicationForm"]>;
  isLastStep: boolean;
  submitting: boolean;
  onNext: () => void;
  onPrevious: () => void;
};

export function ApplicationActions({
  currentStepIndex,
  form,
  isLastStep,
  submitting,
  onNext,
  onPrevious,
}: ApplicationActionsProps) {
  return (
    <div style={{
      alignItems: "center",
      background: "linear-gradient(180deg, rgba(255, 255, 255, 0.95), rgba(247, 241, 231, 0.95))",
      border: "1px solid rgba(17, 24, 32, 0.08)",
      borderRadius: "22px",
      boxShadow: "0 16px 40px rgba(17, 24, 32, 0.05)",
      display: "flex",
      flexWrap: "wrap",
      gap: "12px",
      justifyContent: "space-between",
      padding: "16px 18px",
    }}>
      <div style={{ display: "grid", gap: "2px" }}>
        <strong style={{ fontSize: "0.92rem" }}>{isLastStep ? "Dernière étape" : "Poursuivre la candidature"}</strong>
        <span style={{ color: "var(--text-soft)", fontSize: "0.8rem" }}>
          {isLastStep ? "Vérifie les informations et soumets ton dossier." : "Valide cette étape pour passer à la suivante."}
        </span>
      </div>
      <button className="button button--ghost" disabled={currentStepIndex === 0 || submitting} onClick={onPrevious} type="button">
        Étape précédente
      </button>
      {isLastStep ? (
        <button className="button" disabled={submitting} type="submit">
          {submitting ? "Envoi en cours..." : form.submit_label ?? "Soumettre ma candidature"}
        </button>
      ) : (
        <button className="button" disabled={submitting} onClick={onNext} type="button">
          Étape suivante
        </button>
      )}
    </div>
  );
}
