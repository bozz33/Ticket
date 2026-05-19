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

type ChoiceApplicationFieldProps = {
  error: string | null;
  field: CallForProjectApplicationField;
  value: FormValue;
  onValueChange: (fieldKey: string, value: FormValue) => void;
};

export function RadioApplicationField({
  error,
  field,
  value,
  onValueChange,
}: ChoiceApplicationFieldProps) {
  return (
    <fieldset style={fieldsetResetStyle}>
      <legend style={legendStyle}>{fieldLabel(field)}</legend>
      <div style={optionStackStyle}>
        {(field.options ?? []).map((option) => (
          <label key={`${field.key}-${option.value}`} style={optionLabelStyle}>
            <input
              checked={String(value ?? "") === option.value}
              id={fieldControlId(field.key, option.value)}
              name={field.key}
              onChange={() => onValueChange(field.key, option.value)}
              required={field.required}
              type="radio"
              value={option.value}
            />
            <span>{option.label}</span>
          </label>
        ))}
      </div>
      <ErrorMessage message={error} />
    </fieldset>
  );
}

export function CheckboxGroupApplicationField({
  error,
  field,
  value,
  onValueChange,
}: ChoiceApplicationFieldProps) {
  const selectedValues = (value as string[] | undefined) ?? [];

  return (
    <fieldset style={fieldsetResetStyle}>
      <legend style={legendStyle}>{fieldLabel(field)}</legend>
      <div style={optionStackStyle}>
        {(field.options ?? []).map((option) => {
          const checked = selectedValues.includes(option.value);

          return (
            <label key={`${field.key}-${option.value}`} style={optionLabelStyle}>
              <input
                checked={checked}
                id={fieldControlId(field.key, option.value)}
                onChange={(event) => {
                  const nextValues = event.target.checked
                    ? [...selectedValues, option.value]
                    : selectedValues.filter((entry) => entry !== option.value);
                  onValueChange(field.key, nextValues);
                }}
                type="checkbox"
                value={option.value}
              />
              <span>{option.label}</span>
            </label>
          );
        })}
      </div>
      <ErrorMessage message={error} />
    </fieldset>
  );
}
