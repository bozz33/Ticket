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
  return type === "email" || type === "number" || type === "date" || type === "url" ? type : "text";
}
