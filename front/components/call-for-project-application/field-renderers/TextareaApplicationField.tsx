import type { CallForProjectApplicationField } from "@/lib/types";

import type { FormValue } from "../types";
import { ErrorMessage, fieldLabel, FileHelp } from "./shared";

type TextareaApplicationFieldProps = {
  controlId: string;
  error: string | null;
  field: CallForProjectApplicationField;
  value: FormValue;
  onValueChange: (fieldKey: string, value: FormValue) => void;
};

export function TextareaApplicationField({
  controlId,
  error,
  field,
  value,
  onValueChange,
}: TextareaApplicationFieldProps) {
  return (
    <label className="contact-form-label" htmlFor={controlId}>
      {fieldLabel(field)}
      <textarea
        id={controlId}
        onChange={(event) => onValueChange(field.key, event.target.value)}
        required={field.required}
        rows={5}
        value={String(value ?? "")}
      />
      <FileHelp field={field} />
      <ErrorMessage message={error} />
    </label>
  );
}
