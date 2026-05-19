import type { DynamicFormField } from "./types";

export function DynamicFormFileField({
  field,
  id,
  onChange,
}: {
  field: DynamicFormField;
  id: string;
  onChange: (value: unknown) => void;
}) {
  return (
    <input
      accept={Array.isArray(field.accept) ? field.accept.join(",") : undefined}
      id={id}
      required={field.required}
      type="file"
      onChange={(event) => onChange(event.target.files?.[0] ?? null)}
    />
  );
}
