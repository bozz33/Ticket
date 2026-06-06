import type { DynamicFormField, DynamicFormOption, DynamicFormVisibilityCondition } from "./types";

export function normalizeOptions(options: DynamicFormField["options"]): Array<{ value: string; label: string }> {
  if (Array.isArray(options)) {
    return options.map((option) => ({ value: String(option.value), label: String(option.label) }));
  };

  return Object.entries(options ?? {}).map(([value, label]) => ({ value, label: String(label) }));
}

export function isFieldVisible(field: DynamicFormField, responses: Record<string, unknown>) {
  if (field.visible === false) {
    return false;
  }

  const conditions = Array.isArray(field.visible_if)
    ? field.visible_if
    : field.visible_if
      ? [field.visible_if]
      : [];

  return conditions.every((condition) => evaluateVisibilityCondition(condition, responses));
}

function evaluateVisibilityCondition(condition: DynamicFormVisibilityCondition, responses: Record<string, unknown>) {
  const actual = responses[condition.field];
  const operator = condition.operator ?? "equals";

  if (operator === "filled") {
    return actual !== undefined && actual !== null && String(actual).trim() !== "";
  }

  if (operator === "empty") {
    return actual === undefined || actual === null || String(actual).trim() === "";
  }

  const expectedValues = Array.isArray(condition.value) ? condition.value : [condition.value];
  const actualValue = String(actual ?? "");
  const matches = expectedValues.some((value) => actualValue === String(value ?? ""));

  if (operator === "not_equals" || operator === "not_in") {
    return !matches;
  }

  return matches;
}

export function resolveInputType(type: string) {
  switch (type) {
    case "email": return "email";
    case "number": return "number";
    case "date": return "date";
    case "time": return "time";
    case "datetime": return "datetime-local";
    case "url": return "url";
    default: return "text";
  }
}

export function validateField(field: { type: string; required?: boolean; max_rating?: number }, value: unknown): string | null {
  if (field.required && (value === undefined || value === null || value === "" || (Array.isArray(value) && value.length === 0))) {
    return "Ce champ est requis.";
  }

  if (value === undefined || value === null || value === "") {
    return null;
  }

  if (field.type === "email" && typeof value === "string" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
    return "Veuillez saisir une adresse e-mail valide.";
  }

  if (field.type === "url" && typeof value === "string" && !/^https?:\/\/.+/.test(value)) {
    return "Veuillez saisir une URL valide (ex: https://...).";
  }

  if (field.type === "rating") {
    const max = field.max_rating ?? 5;
    const num = Number(value);
    if (!Number.isInteger(num) || num < 0 || num > max) {
      return `La note doit être comprise entre 0 et ${max}.`;
    }
  }

  if (field.type === "date_range" && typeof value === "object" && value !== null) {
    const range = value as Record<string, unknown>;
    if (range.start && range.end && String(range.start) > String(range.end)) {
      return "La date de début doit être antérieure à la date de fin.";
    }
  }

  return null;
}
