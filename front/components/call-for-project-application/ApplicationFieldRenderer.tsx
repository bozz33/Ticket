"use client";

import type {
  CallForProjectApplicationField,
  PublicReferenceCity,
  PublicReferenceCountry,
} from "@/lib/types";

import { fieldControlId, fieldError, formatCityLabel } from "./helpers";
import { BooleanApplicationField } from "./field-renderers/BooleanApplicationField";
import {
  CheckboxGroupApplicationField,
  RadioApplicationField,
} from "./field-renderers/ChoiceApplicationFields";
import { FileApplicationField } from "./field-renderers/FileApplicationField";
import {
  CityApplicationField,
  CountryApplicationField,
} from "./field-renderers/LocationApplicationFields";
import { PhoneApplicationField } from "./field-renderers/PhoneApplicationField";
import { TextApplicationField } from "./field-renderers/TextApplicationField";
import { TextareaApplicationField } from "./field-renderers/TextareaApplicationField";
import type { FieldErrorMap, FormValue } from "./types";

type ApplicationFieldRendererProps = {
  cities: PublicReferenceCity[];
  citySearchValue: string;
  countries: PublicReferenceCountry[];
  countryCode: string;
  errorMap: FieldErrorMap;
  field: CallForProjectApplicationField;
  file: File | null | undefined;
  loadingCities: boolean;
  value: FormValue;
  onCityInputChange: (fieldKey: string, value: string) => void;
  onCitySelect: (fieldKey: string, city: PublicReferenceCity) => void;
  onCountryChange: (fieldKey: string, countryCode: string) => void;
  onFileChange: (fieldKey: string, file: File | null) => void;
  onValueChange: (fieldKey: string, value: FormValue) => void;
};

export function ApplicationFieldRenderer({
  cities,
  citySearchValue,
  countries,
  countryCode,
  errorMap,
  field,
  file,
  loadingCities,
  value,
  onCityInputChange,
  onCitySelect,
  onCountryChange,
  onFileChange,
  onValueChange,
}: ApplicationFieldRendererProps) {
  const error = fieldError(errorMap, field.key);
  const controlId = fieldControlId(field.key);

  if (field.type === "textarea") {
    return (
      <TextareaApplicationField
        controlId={controlId}
        error={error}
        field={field}
        onValueChange={onValueChange}
        value={value}
      />
    );
  }

  if (field.type === "country") {
    return (
      <CountryApplicationField
        controlId={controlId}
        countries={countries}
        error={error}
        field={field}
        onCountryChange={onCountryChange}
        value={value}
      />
    );
  }

  if (field.type === "city") {
    return (
      <CityApplicationField
        cities={cities}
        citySearchValue={citySearchValue}
        controlId={controlId}
        countryCode={countryCode}
        error={error}
        field={field}
        loadingCities={loadingCities}
        onCityInputChange={onCityInputChange}
        onCitySelect={onCitySelect}
      />
    );
  }

  if (field.type === "phone") {
    return (
      <PhoneApplicationField
        countries={countries}
        error={error}
        field={field}
        onValueChange={onValueChange}
        value={value}
      />
    );
  }

  if (field.type === "radio") {
    return (
      <RadioApplicationField
        error={error}
        field={field}
        onValueChange={onValueChange}
        value={value}
      />
    );
  }

  if (field.type === "checkbox_group") {
    return (
      <CheckboxGroupApplicationField
        error={error}
        field={field}
        onValueChange={onValueChange}
        value={value}
      />
    );
  }

  if (field.type === "boolean") {
    return (
      <BooleanApplicationField
        error={error}
        field={field}
        onValueChange={onValueChange}
        value={value}
      />
    );
  }

  if (field.type === "file") {
    return (
      <FileApplicationField
        controlId={controlId}
        error={error}
        field={field}
        file={file}
        onFileChange={onFileChange}
      />
    );
  }

  return (
    <TextApplicationField
      controlId={controlId}
      error={error}
      field={field}
      onValueChange={onValueChange}
      value={value}
    />
  );
}

export { formatCityLabel };
