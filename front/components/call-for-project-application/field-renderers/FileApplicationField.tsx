import type { CallForProjectApplicationField } from "@/lib/types";

import { ErrorMessage, fieldHelpStyle, fieldLabel, FileHelp } from "./shared";

type FileApplicationFieldProps = {
  controlId: string;
  error: string | null;
  field: CallForProjectApplicationField;
  file: File | null | undefined;
  onFileChange: (fieldKey: string, file: File | null) => void;
};

export function FileApplicationField({
  controlId,
  error,
  field,
  file,
  onFileChange,
}: FileApplicationFieldProps) {
  return (
    <label className="contact-form-label" htmlFor={controlId}>
      {fieldLabel(field)}
      <input
        accept={field.accept?.join(",")}
        id={controlId}
        onChange={(event) => onFileChange(field.key, event.target.files?.[0] ?? null)}
        required={field.required}
        type="file"
      />
      {file ? <span style={fieldHelpStyle}>{file.name}</span> : null}
      <FileHelp field={field} />
      <ErrorMessage message={error} />
    </label>
  );
}
