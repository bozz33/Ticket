import type {
  CallForProjectApplicationField,
  PublicReferenceCity,
  PublicReferenceCountry,
} from "@/lib/types";

export type FieldErrorMap = Record<string, string>;

export type PhoneFormValue = {
  country_code?: string;
  dial_code?: string;
  number?: string;
};

export type FormValue = string | number | boolean | string[] | null | PhoneFormValue;

export type CountryPayload = {
  data?: PublicReferenceCountry[];
};

export type CityPayload = {
  data?: PublicReferenceCity[];
};

export type FormWizardSection = {
  key: string;
  title: string;
  description?: string;
  fields: CallForProjectApplicationField[];
};

export type FormWizardStep = {
  key: string;
  title: string;
  description?: string;
  fields: CallForProjectApplicationField[];
  sections: FormWizardSection[];
};
