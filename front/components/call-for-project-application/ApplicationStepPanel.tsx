import type { ReactNode } from "react";

import type { CallForProjectApplicationField } from "@/lib/types";

import {
  getFieldGridColumn,
  getSectionGridTemplateColumns,
} from "./helpers";
import type { FormWizardStep } from "./types";

type ApplicationStepPanelProps = {
  currentStep: FormWizardStep | null;
  currentStepCompletedCount: number;
  currentStepIndex: number;
  currentStepRequiredCount: number;
  wizardStepCount: number;
  renderField: (field: CallForProjectApplicationField) => ReactNode;
};

export function ApplicationStepPanel({
  currentStep,
  currentStepCompletedCount,
  currentStepIndex,
  currentStepRequiredCount,
  wizardStepCount,
  renderField,
}: ApplicationStepPanelProps) {
  if (!currentStep) {
    return null;
  }

  const stepProgress = currentStepRequiredCount > 0
    ? Math.round((currentStepCompletedCount / currentStepRequiredCount) * 100)
    : 100;

  return (
    <div style={{ display: "grid", gap: "18px" }}>
      <div style={{
        background: "linear-gradient(135deg, rgba(13, 20, 28, 0.96), rgba(28, 124, 114, 0.9))",
        border: "1px solid rgba(17, 24, 32, 0.08)",
        borderRadius: "24px",
        boxShadow: "0 30px 70px rgba(13, 20, 28, 0.18)",
        display: "grid",
        gap: "14px",
        overflow: "hidden",
        padding: "24px",
      }}>
        <div style={{ alignItems: "center", display: "flex", justifyContent: "space-between", gap: "16px" }}>
          <div>
            <span style={{ color: "rgba(255, 255, 255, 0.72)", display: "block", fontSize: "0.76rem", fontWeight: 800, letterSpacing: "0.08em", textTransform: "uppercase" }}>
              Étape {currentStepIndex + 1} sur {wizardStepCount}
            </span>
            <h3 style={{ color: "#fff", margin: "8px 0 0", fontFamily: "var(--font-display)", fontSize: "1.7rem", lineHeight: 1.08 }}>{currentStep.title}</h3>
          </div>
          <div style={{
            background: "rgba(255, 255, 255, 0.12)",
            border: "1px solid rgba(255, 255, 255, 0.16)",
            borderRadius: "999px",
            color: "#fff",
            fontSize: "0.82rem",
            fontWeight: 800,
            padding: "10px 14px",
          }}>
            {currentStepRequiredCount > 0 ? `${currentStepCompletedCount} / ${currentStepRequiredCount} champs requis complétés` : "Étape informative"}
          </div>
        </div>
        {currentStep.description ? <p style={{ color: "rgba(255, 255, 255, 0.78)", margin: 0, maxWidth: "780px" }}>{currentStep.description}</p> : null}
        {currentStepRequiredCount > 0 ? (
          <div style={{ background: "rgba(255, 255, 255, 0.14)", borderRadius: "999px", height: "10px", overflow: "hidden" }}>
            <div style={{
              background: "linear-gradient(90deg, rgba(255,255,255,0.96), rgba(213, 154, 54, 0.95))",
              borderRadius: "999px",
              height: "100%",
              transition: "width 220ms ease",
              width: `${stepProgress}%`,
            }} />
          </div>
        ) : null}
      </div>

      {currentStep.sections.map((section, sectionIndex) => (
        <section
          key={section.key}
          style={{
            background: "#fff",
            border: "1px solid rgba(17, 24, 32, 0.08)",
            borderRadius: "22px",
            boxShadow: "0 18px 42px rgba(17, 24, 32, 0.06)",
            display: "grid",
            gap: "18px",
            overflow: "hidden",
            padding: "22px",
          }}
        >
          <div style={{
            alignItems: "start",
            borderBottom: "1px solid rgba(17, 24, 32, 0.06)",
            display: "flex",
            flexWrap: "wrap",
            gap: "14px",
            justifyContent: "space-between",
            margin: "-22px -22px 0",
            padding: "20px 22px 18px",
          }}>
            <div style={{ display: "grid", gap: "6px" }}>
              <span style={{ color: "var(--accent-strong)", fontSize: "0.72rem", fontWeight: 800, letterSpacing: "0.08em", textTransform: "uppercase" }}>
                Section {sectionIndex + 1}
              </span>
              <h3 style={{ margin: 0, fontSize: "1.08rem" }}>{section.title}</h3>
            </div>
            <span style={{
              alignItems: "center",
              background: "rgba(15, 23, 42, 0.05)",
              borderRadius: "999px",
              color: "var(--text-soft)",
              display: "inline-flex",
              fontSize: "0.74rem",
              fontWeight: 800,
              minHeight: "34px",
              padding: "0 12px",
            }}>
              {section.fields.length} champ{section.fields.length > 1 ? "s" : ""}
            </span>
          </div>
          <div style={{ display: "grid", gap: "6px" }}>
            {section.description ? <p style={{ color: "var(--text-soft)", margin: 0 }}>{section.description}</p> : null}
          </div>
          <div style={{ display: "grid", gap: "18px", gridTemplateColumns: getSectionGridTemplateColumns(section) }}>
            {section.fields.map((field) => (
              <div key={field.key} style={{ gridColumn: getFieldGridColumn(section, field), minWidth: 0 }}>
                {renderField(field)}
              </div>
            ))}
          </div>
        </section>
      ))}
    </div>
  );
}
