import type { DynamicFormField } from "./types";

export function DynamicFormLocationField({
  field,
  id,
  value,
  onChange,
}: {
  field: DynamicFormField;
  id: string;
  value: unknown;
  onChange: (value: unknown) => void;
}) {
  return (
    <input
      autoComplete={field.type === "country" ? "country-name" : "address-level2"}
      id={id}
      required={field.required}
      type="text"
      value={String(value ?? "")}
      onChange={(event) => onChange(event.target.value)}
    />
  );
}
