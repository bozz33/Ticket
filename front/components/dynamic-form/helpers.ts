import type { DynamicFormField } from "./types";

export function normalizeOptions(options: DynamicFormField["options"]): Array<{ value: string; label: string }> {
  if (Array.isArray(options)) {
    return options.map((option) => ({ value: String(option.value), label: String(option.label) }));
  }

  return Object.entries(options ?? {}).map(([value, label]) => ({ value, label: String(label) }));
}

export function resolveInputType(type: string) {
  return type === "email" || type === "number" || type === "date" || type === "url" ? type : "text";
}
