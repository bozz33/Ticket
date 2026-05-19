import type { CallForProjectApplicationField } from "@/lib/types";

import { fieldControlId } from "../helpers";
import type { FormValue } from "../types";
import {
  ErrorMessage,
  fieldLabel,
  fieldsetResetStyle,
  legendStyle,
  optionLabelStyle,
  optionStackStyle,
} from "./shared";

type BooleanApplicationFieldProps = {
  error: string | null;
  field: CallForProjectApplicationField;
  value: FormValue;
  onValueChange: (fieldKey: string, value: FormValue) => void;
};

export function BooleanApplicationField({
  error,
  field,
  value,
  onValueChange,
}: BooleanApplicationFieldProps) {
  const currentValue = value === true ? true : value === false ? false : null;

  return (
    <fieldset style={fieldsetResetStyle}>
      <legend style={legendStyle}>{fieldLabel(field)}</legend>
      <div style={optionStackStyle}>
        <label style={optionLabelStyle}>
          <input
            checked={currentValue === true}
            id={fieldControlId(field.key, "true")}
            name={field.key}
            onChange={() => onValueChange(field.key, true)}
            type="radio"
          />
          <span>{field.true_label ?? "Oui"}</span>
        </label>
        <label style={optionLabelStyle}>
          <input
            checked={currentValue === false}
            id={fieldControlId(field.key, "false")}
            name={field.key}
            onChange={() => onValueChange(field.key, false)}
            type="radio"
          />
          <span>{field.false_label ?? "Non"}</span>
        </label>
      </div>
      <ErrorMessage message={error} />
    </fieldset>
  );
}
