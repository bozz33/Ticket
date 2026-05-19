import type {
  CallForProjectApplicationField,
  PublicReferenceCountry,
} from "@/lib/types";

import { fieldControlId, normalizeDialCode } from "../helpers";
import type { FormValue, PhoneFormValue } from "../types";
import { ErrorMessage, fieldLabel } from "./shared";

type PhoneApplicationFieldProps = {
  countries: PublicReferenceCountry[];
  error: string | null;
  field: CallForProjectApplicationField;
  value: FormValue;
  onValueChange: (fieldKey: string, value: FormValue) => void;
};

export function PhoneApplicationField({
  countries,
  error,
  field,
  value,
  onValueChange,
}: PhoneApplicationFieldProps) {
  const currentValue = (value as PhoneFormValue | undefined) ?? {};

  return (
    <label className="contact-form-label">
      {fieldLabel(field)}
      <div style={{ display: "grid", gap: "12px", gridTemplateColumns: "minmax(140px, 180px) minmax(0, 1fr)" }}>
        <select
          aria-label={`${field.label} - indicatif`}
          id={fieldControlId(field.key, "country")}
          onChange={(event) => {
            const country = countries.find((entry) => entry.iso2 === event.target.value);
            onValueChange(field.key, {
              ...currentValue,
              country_code: event.target.value,
              dial_code: normalizeDialCode(country?.phone_code),
            });
          }}
          value={String(currentValue.country_code ?? "")}
        >
          <option value="">Indicatif</option>
          {countries
            .filter((country) => country.phone_code)
            .map((country) => (
              <option key={`${field.key}-${country.iso2}`} value={country.iso2}>
                {normalizeDialCode(country.phone_code)} · {country.name}
              </option>
            ))}
        </select>
        <input
          aria-label={`${field.label} - numéro`}
          id={fieldControlId(field.key, "number")}
          onChange={(event) =>
            onValueChange(field.key, {
              ...currentValue,
              number: event.target.value,
            })
          }
          placeholder="Numéro sans indicatif"
          required={field.required}
          type="tel"
          value={String(currentValue.number ?? "")}
        />
      </div>
      <ErrorMessage message={error} />
    </label>
  );
}
