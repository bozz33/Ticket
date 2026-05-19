import type {
  CallForProjectApplicationField,
  PublicReferenceCity,
  PublicReferenceCountry,
} from "@/lib/types";

import { normalizeDialCode } from "../helpers";
import type { FormValue } from "../types";
import { ErrorMessage, fieldHelpStyle, fieldLabel } from "./shared";

type CountryApplicationFieldProps = {
  controlId: string;
  countries: PublicReferenceCountry[];
  error: string | null;
  field: CallForProjectApplicationField;
  value: FormValue;
  onCountryChange: (fieldKey: string, countryCode: string) => void;
};

type CityApplicationFieldProps = {
  cities: PublicReferenceCity[];
  citySearchValue: string;
  controlId: string;
  countryCode: string;
  error: string | null;
  field: CallForProjectApplicationField;
  loadingCities: boolean;
  onCityInputChange: (fieldKey: string, value: string) => void;
  onCitySelect: (fieldKey: string, city: PublicReferenceCity) => void;
};

const cityResultsStyle = {
  border: "1px solid rgba(15, 23, 42, 0.12)",
  borderRadius: "14px",
  display: "grid",
  gap: "6px",
  marginTop: "8px",
  maxHeight: "220px",
  overflowY: "auto",
  padding: "8px",
} as const;

const cityButtonStyle = {
  background: "transparent",
  border: 0,
  cursor: "pointer",
  padding: "8px",
  textAlign: "left",
} as const;

export function CountryApplicationField({
  controlId,
  countries,
  error,
  field,
  value,
  onCountryChange,
}: CountryApplicationFieldProps) {
  return (
    <label className="contact-form-label" htmlFor={controlId}>
      {fieldLabel(field)}
      <select
        id={controlId}
        onChange={(event) => onCountryChange(field.key, event.target.value.toUpperCase())}
        required={field.required}
        value={String(value ?? "")}
      >
        <option value="">Sélectionnez un pays</option>
        {countries.map((country) => (
          <option key={country.iso2} value={country.iso2}>
            {country.name}
            {country.phone_code ? ` (${normalizeDialCode(country.phone_code)})` : ""}
          </option>
        ))}
      </select>
      <ErrorMessage message={error} />
    </label>
  );
}

export function CityApplicationField({
  cities,
  citySearchValue,
  controlId,
  countryCode,
  error,
  field,
  loadingCities,
  onCityInputChange,
  onCitySelect,
}: CityApplicationFieldProps) {
  const hasEnoughSearch = citySearchValue.trim().length >= 2;

  return (
    <label className="contact-form-label" htmlFor={controlId}>
      {fieldLabel(field)}
      <input
        disabled={!countryCode}
        id={controlId}
        onChange={(event) => onCityInputChange(field.key, event.target.value)}
        placeholder={countryCode ? "Rechercher une ville" : "Sélectionnez d'abord un pays"}
        required={field.required}
        type="text"
        value={citySearchValue}
      />
      {loadingCities ? <span style={fieldHelpStyle}>Recherche en cours...</span> : null}
      {!loadingCities && hasEnoughSearch && cities.length > 0 ? (
        <div style={cityResultsStyle}>
          {cities.map((city) => (
            <button
              key={city.id}
              onClick={(event) => {
                event.preventDefault();
                onCitySelect(field.key, city);
              }}
              style={cityButtonStyle}
              type="button"
            >
              <strong>{city.name}</strong>
              {city.meta?.state_name ? (
                <span style={{ color: "var(--text-soft)", display: "block", fontSize: "0.82rem" }}>
                  {city.meta.state_name}
                </span>
              ) : null}
            </button>
          ))}
        </div>
      ) : null}
      {!loadingCities && hasEnoughSearch && cities.length === 0 ? (
        <span style={fieldHelpStyle}>Aucune ville trouvée pour cette recherche.</span>
      ) : null}
      <ErrorMessage message={error} />
    </label>
  );
}
