import type { CSSProperties } from "react";

import type { CallForProjectApplicationField } from "@/lib/types";

export const fieldHelpStyle: CSSProperties = {
  color: "var(--text-soft)",
  fontSize: "0.78rem",
};

export const fieldsetResetStyle: CSSProperties = {
  border: 0,
  margin: 0,
  padding: 0,
};

export const legendStyle: CSSProperties = {
  fontWeight: 700,
  marginBottom: "10px",
};

export const optionStackStyle: CSSProperties = {
  display: "grid",
  gap: "10px",
};

export const optionLabelStyle: CSSProperties = {
  alignItems: "center",
  display: "flex",
  gap: "10px",
};

export function fieldLabel(field: CallForProjectApplicationField) {
  return `${field.label}${field.required ? " *" : ""}`;
}

export function FileHelp({ field }: { field: CallForProjectApplicationField }) {
  if (field.type !== "file") {
    return null;
  }

  const parts = [] as string[];
  if (field.max_size_mb) {
    parts.push(`Taille max ${field.max_size_mb} Mo`);
  }
  if (field.accept && field.accept.length > 0) {
    parts.push(field.accept.join(", "));
  }

  return parts.length > 0 ? <span style={fieldHelpStyle}>{parts.join(" · ")}</span> : null;
}

export function ErrorMessage({ message }: { message: string | null }) {
  return message ? <span style={{ color: "#b91c1c", fontSize: "0.78rem" }}>{message}</span> : null;
}
