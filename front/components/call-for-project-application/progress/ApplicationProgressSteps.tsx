import type { FormWizardStep } from "../types";
import { stepListStyle } from "./styles";

type ApplicationProgressStepsProps = {
  currentStepIndex: number;
  wizardSteps: FormWizardStep[];
  onStepSelect: (stepIndex: number) => void;
};

export function ApplicationProgressSteps({
  currentStepIndex,
  wizardSteps,
  onStepSelect,
}: ApplicationProgressStepsProps) {
  if (wizardSteps.length <= 1) {
    return null;
  }

  return (
    <div style={{ display: "grid", gap: "14px", marginBottom: "22px" }}>
      <div style={stepListStyle}>
        {wizardSteps.map((step, index) => {
          const isActive = index === currentStepIndex;
          const isCompleted = index < currentStepIndex;
          const canOpen = index <= currentStepIndex;

          return (
            <button
              key={step.key}
              onClick={(event) => {
                event.preventDefault();
                if (canOpen) {
                  onStepSelect(index);
                }
              }}
              style={{
                alignItems: "center",
                background: isActive
                  ? "linear-gradient(135deg, rgba(213, 154, 54, 0.18), rgba(255, 255, 255, 0.92))"
                  : isCompleted
                    ? "linear-gradient(135deg, rgba(28, 124, 114, 0.16), rgba(255, 255, 255, 0.92))"
                    : "rgba(255, 255, 255, 0.75)",
                border: isActive ? "1px solid rgba(213, 154, 54, 0.45)" : "1px solid rgba(15, 23, 42, 0.08)",
                borderRadius: "20px",
                boxShadow: isActive ? "0 18px 42px rgba(213, 154, 54, 0.14)" : "0 12px 28px rgba(15, 23, 42, 0.05)",
                color: "var(--text)",
                cursor: canOpen ? "pointer" : "default",
                display: "inline-flex",
                flex: "1 1 220px",
                gap: "12px",
                minHeight: "84px",
                opacity: canOpen ? 1 : 0.72,
                padding: "14px 16px",
                textAlign: "left",
              }}
              type="button"
            >
              <span
                style={{
                  alignItems: "center",
                  background: isActive ? "var(--accent)" : isCompleted ? "var(--teal)" : "rgba(15, 23, 42, 0.1)",
                  borderRadius: "999px",
                  color: isActive || isCompleted ? "#fff" : "var(--text)",
                  display: "inline-flex",
                  flexShrink: 0,
                  fontSize: "0.8rem",
                  fontWeight: 800,
                  height: "34px",
                  justifyContent: "center",
                  width: "34px",
                }}
              >
                {isCompleted ? "✓" : index + 1}
              </span>
              <span style={{ display: "grid", gap: "4px", textAlign: "left" }}>
                <strong style={{ fontSize: "0.96rem" }}>{step.title}</strong>
                {step.description ? (
                  <span style={{ color: "var(--text-soft)", fontSize: "0.78rem", lineHeight: 1.45 }}>
                    {step.description}
                  </span>
                ) : null}
                <span
                  style={{
                    color: isCompleted ? "var(--teal)" : isActive ? "var(--accent-strong)" : "var(--text-soft)",
                    fontSize: "0.72rem",
                    fontWeight: 800,
                    letterSpacing: "0.06em",
                    textTransform: "uppercase",
                  }}
                >
                  {isCompleted ? "Complétée" : isActive ? "En cours" : "À venir"}
                </span>
              </span>
            </button>
          );
        })}
      </div>
    </div>
  );
}
