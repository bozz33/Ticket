import type {
  CallForProjectApplicationField,
  PublicContent,
  PublicReferenceCity,
} from "@/lib/types";

import type {
  FieldErrorMap,
  FormValue,
  FormWizardSection,
  FormWizardStep,
  PhoneFormValue,
} from "./types";

export function normalizeDialCode(value?: string | null): string {
  const digits = (value ?? "").replace(/\D+/g, "");
  return digits ? `+${digits}` : "";
}

export function isVisible(field: CallForProjectApplicationField): boolean {
  return field.visible !== false;
}

export function createInitialValues(item: PublicContent): Record<string, FormValue> {
  const initialValues: Record<string, FormValue> = {};

  for (const field of item.applicationForm?.fields ?? []) {
    if (!isVisible(field)) {
      continue;
    }

    if (field.type === "checkbox_group") {
      initialValues[field.key] = [];
      continue;
    }

    if (field.type === "boolean") {
      initialValues[field.key] = null;
      continue;
    }

    if (field.type === "phone") {
      initialValues[field.key] = { dial_code: "", number: "", country_code: "" };
      continue;
    }

    initialValues[field.key] = "";
  }

  return initialValues;
}

export function fieldError(errors: FieldErrorMap, key: string): string | null {
  return errors[key] ?? null;
}

export function fieldControlId(key: string, suffix = "field"): string {
  const normalizedKey = key.replace(/[^a-zA-Z0-9_-]+/g, "-");
  const normalizedSuffix = suffix.replace(/[^a-zA-Z0-9_-]+/g, "-");

  return `application-${normalizedKey}-${normalizedSuffix}`;
}

export function toFieldErrors(payload: unknown): FieldErrorMap {
  if (!payload || typeof payload !== "object" || !("errors" in payload)) {
    return {};
  }

  const rawErrors = (payload as { errors?: Record<string, string[] | string> }).errors ?? {};
  const normalized: FieldErrorMap = {};

  for (const [path, value] of Object.entries(rawErrors)) {
    const message = Array.isArray(value) ? value[0] : value;

    if (!message) {
      continue;
    }

    if (path.startsWith("responses.")) {
      const segments = path.replace("responses.", "").split(".");
      normalized[segments[0] ?? path] = message;
      continue;
    }

    if (path.startsWith("files.")) {
      normalized[path.replace("files.", "")] = message;
      continue;
    }

    normalized[path] = message;
  }

  return normalized;
}

export function buildWizardSteps(
  form: PublicContent["applicationForm"] | null | undefined,
  fields: CallForProjectApplicationField[],
): FormWizardStep[] {
  const configuredSteps = form?.steps?.length
    ? form.steps.map((step) => ({ key: step.key, title: step.title, description: step.description }))
    : [{ key: "application", title: "Votre candidature", description: form?.description }];

  const fallbackStepKey = configuredSteps[0]?.key ?? "application";
  const unknownStepKeys = fields
    .map((field) => field.step)
    .filter((step): step is string => Boolean(step && !configuredSteps.some((entry) => entry.key === step)));

  const allSteps = [
    ...configuredSteps,
    ...unknownStepKeys
      .filter((step, index, array) => array.indexOf(step) === index)
      .map((step) => ({ key: step, title: step, description: undefined })),
  ];

  return allSteps
    .map((step) => {
      const stepFields = fields.filter((field) => (field.step ?? fallbackStepKey) === step.key);
      const sections = new Map<string, FormWizardSection>();

      for (const field of stepFields) {
        const sectionKey = field.section ?? `${step.key}-main`;

        if (!sections.has(sectionKey)) {
          sections.set(sectionKey, {
            key: sectionKey,
            title: field.section_title ?? step.title,
            description: field.section_description,
            fields: [],
          });
        }

        sections.get(sectionKey)?.fields.push(field);
      }

      return {
        key: step.key,
        title: step.title,
        description: step.description,
        fields: stepFields,
        sections: Array.from(sections.values()),
      };
    })
    .filter((step) => step.fields.length > 0);
}

export function getSectionGridTemplateColumns(section: FormWizardSection): string {
  const fieldKeys = section.fields.map((field) => field.key);

  if (section.key === "personal_identity" || fieldKeys.every((fieldKey) => ["full_name", "date_of_birth", "age"].includes(fieldKey))) {
    return "repeat(3, minmax(0, 1fr))";
  }

  if (section.key === "location") {
    return "repeat(3, minmax(0, 1fr))";
  }

  return "repeat(auto-fit, minmax(240px, 1fr))";
}

export function getFieldGridColumn(section: FormWizardSection, field: CallForProjectApplicationField): string {
  if (section.key === "personal_identity") {
    return "span 1";
  }

  if (section.key === "location") {
    if (field.key === "nationality") {
      return "span 1";
    }

    if (field.key === "country_of_residence") {
      return "span 2";
    }

    if (["city_of_residence", "whatsapp_number", "email"].includes(field.key)) {
      return "span 1";
    }
  }

  return field.column_span === 1 ? "span 1" : "1 / -1";
}

export function isFieldCompleted(field: CallForProjectApplicationField, value: FormValue, file: File | null | undefined): boolean {
  if (field.type === "file") {
    return !field.required || Boolean(file);
  }

  if (field.type === "checkbox_group") {
    return !field.required || (((value as string[] | undefined) ?? []).length > 0);
  }

  if (field.type === "phone") {
    const phoneValue = (value as PhoneFormValue | undefined) ?? {};
    return !field.required || Boolean(phoneValue.country_code && phoneValue.number?.trim());
  }

  if (field.type === "boolean") {
    return !field.required || value === true || value === false;
  }

  return !field.required || String(value ?? "").trim() !== "";
}

export function formatCityLabel(city: PublicReferenceCity): string {
  return city.meta?.state_name ? `${city.name} · ${city.meta.state_name}` : city.name;
}

export function buildApplicationFormData(
  fields: CallForProjectApplicationField[],
  values: Record<string, FormValue>,
  files: Record<string, File | null>,
): FormData {
  const formData = new FormData();

  for (const field of fields) {
    const value = values[field.key];

    if (field.type === "file") {
      const file = files[field.key];
      if (file) {
        formData.append(`files[${field.key}]`, file);
      }
      continue;
    }

    if (field.type === "checkbox_group") {
      for (const entry of (value as string[] | undefined) ?? []) {
        formData.append(`responses[${field.key}][]`, entry);
      }
      continue;
    }

    if (field.type === "phone") {
      const phoneValue = (value as PhoneFormValue | undefined) ?? {};
      formData.append(`responses[${field.key}][dial_code]`, phoneValue.dial_code ?? "");
      formData.append(`responses[${field.key}][number]`, phoneValue.number ?? "");
      formData.append(`responses[${field.key}][country_code]`, phoneValue.country_code ?? "");
      continue;
    }

    if (field.type === "boolean") {
      formData.append(`responses[${field.key}]`, value == null ? "" : value ? "1" : "0");
      continue;
    }

    formData.append(`responses[${field.key}]`, value == null ? "" : String(value));
  }

  return formData;
}
