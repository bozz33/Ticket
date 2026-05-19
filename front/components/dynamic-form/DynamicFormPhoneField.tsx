import type { DynamicFormField } from "./types";

export function DynamicFormPhoneField({
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
      autoComplete="tel"
      id={id}
      inputMode="tel"
      placeholder="+221 77 000 00 00"
      required={field.required}
      type="tel"
      value={String(value ?? "")}
      onChange={(event) => onChange(event.target.value)}
    />
  );
}
