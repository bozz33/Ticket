import type { CallForProjectApplicationField } from "@/lib/types";

import type { FormValue } from "../types";
import { ErrorMessage, fieldLabel } from "./shared";

type TextApplicationFieldProps = {
  controlId: string;
  error: string | null;
  field: CallForProjectApplicationField;
  value: FormValue;
  onValueChange: (fieldKey: string, value: FormValue) => void;
};

export function TextApplicationField({
  controlId,
  error,
  field,
  value,
  onValueChange,
}: TextApplicationFieldProps) {
  const inputType =
    field.type === "email" ? "email" : field.type === "date" ? "date" : field.type === "number" ? "number" : "text";

  return (
    <label className="contact-form-label" htmlFor={controlId}>
      {fieldLabel(field)}
      <input
        autoComplete={field.autocomplete}
        id={controlId}
        max={field.max}
        min={field.min}
        onChange={(event) => onValueChange(field.key, event.target.value)}
        required={field.required}
        type={inputType}
        value={String(value ?? "")}
      />
      <ErrorMessage message={error} />
    </label>
  );
}
