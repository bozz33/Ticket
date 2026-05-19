import { progressHeaderStyle, progressScoreStyle, progressShellStyle } from "./styles";

type ApplicationProgressSummaryProps = {
  overallCompletedCount: number;
  overallCompletionPercentage: number;
  overallRequiredCount: number;
  visibleFieldCount: number;
  wizardStepCount: number;
};

export function ApplicationProgressSummary({
  overallCompletedCount,
  overallCompletionPercentage,
  overallRequiredCount,
  visibleFieldCount,
  wizardStepCount,
}: ApplicationProgressSummaryProps) {
  return (
    <div style={progressShellStyle}>
      <div style={progressHeaderStyle}>
        <div style={{ display: "grid", gap: "8px", maxWidth: "680px" }}>
          <span
            style={{
              color: "var(--accent-strong)",
              fontSize: "0.74rem",
              fontWeight: 800,
              letterSpacing: "0.12em",
              textTransform: "uppercase",
            }}
          >
            Parcours guidé
          </span>
          <h3 style={{ fontFamily: "var(--font-display)", fontSize: "1.65rem", lineHeight: 1.05, margin: 0 }}>
            Une candidature structurée, étape par étape
          </h3>
          <p style={{ color: "var(--text-soft)", margin: 0 }}>
            Complète chaque section dans l’ordre pour soumettre un dossier propre, cohérent et validé avant envoi.
          </p>
        </div>
        <div style={progressScoreStyle}>
          <strong style={{ fontFamily: "var(--font-display)", fontSize: "2rem", lineHeight: 1 }}>
            {overallCompletionPercentage}%
          </strong>
          <span
            style={{
              color: "rgba(255, 255, 255, 0.7)",
              fontSize: "0.78rem",
              fontWeight: 700,
              letterSpacing: "0.06em",
              textTransform: "uppercase",
            }}
          >
            progression
          </span>
        </div>
      </div>

      <div style={{ background: "rgba(15, 23, 42, 0.08)", borderRadius: "999px", height: "12px", overflow: "hidden" }}>
        <div
          style={{
            background: "linear-gradient(90deg, var(--accent), var(--teal))",
            borderRadius: "999px",
            height: "100%",
            transition: "width 220ms ease",
            width: `${overallCompletionPercentage}%`,
          }}
        />
      </div>

      <div
        style={{
          color: "var(--text-soft)",
          display: "flex",
          flexWrap: "wrap",
          fontSize: "0.84rem",
          fontWeight: 700,
          gap: "10px 18px",
        }}
      >
        <span>
          {wizardStepCount} étape{wizardStepCount > 1 ? "s" : ""}
        </span>
        <span>
          {overallCompletedCount} / {overallRequiredCount} champs requis complétés
        </span>
        <span>
          {visibleFieldCount} champ{visibleFieldCount > 1 ? "s" : ""} visibles
        </span>
      </div>
    </div>
  );
}
