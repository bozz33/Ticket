"use client";

import Link from "next/link";
import { type FormEvent, useEffect, useMemo, useState } from "react";

import type {
  CallForProjectApplicationField,
  PublicContent,
  PublicReferenceCity,
  PublicReferenceCountry,
} from "@/lib/types";
import { formatDateLabel, formatMoney } from "@/lib/utils";

type FieldErrorMap = Record<string, string>;
type FormValue = string | number | boolean | string[] | null | { dial_code?: string; number?: string; country_code?: string };

type CountryPayload = {
  data?: PublicReferenceCountry[];
};

type CityPayload = {
  data?: PublicReferenceCity[];
};

type FormWizardSection = {
  key: string;
  title: string;
  description?: string;
  fields: CallForProjectApplicationField[];
};

type FormWizardStep = {
  key: string;
  title: string;
  description?: string;
  fields: CallForProjectApplicationField[];
  sections: FormWizardSection[];
};

function normalizeDialCode(value?: string | null): string {
  const digits = (value ?? "").replace(/\D+/g, "");
  return digits ? `+${digits}` : "";
}

function isVisible(field: CallForProjectApplicationField): boolean {
  return field.visible !== false;
}

function createInitialValues(item: PublicContent): Record<string, FormValue> {
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

function fieldError(errors: FieldErrorMap, key: string): string | null {
  return errors[key] ?? null;
}

function toFieldErrors(payload: unknown): FieldErrorMap {
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

function buildWizardSteps(
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

function getSectionGridTemplateColumns(section: FormWizardSection): string {
  const fieldKeys = section.fields.map((field) => field.key);

  if (section.key === "personal_identity" || fieldKeys.every((fieldKey) => ["full_name", "date_of_birth", "age"].includes(fieldKey))) {
    return "repeat(3, minmax(0, 1fr))";
  }

  if (section.key === "location") {
    return "repeat(3, minmax(0, 1fr))";
  }

  return "repeat(auto-fit, minmax(240px, 1fr))";
}

function getFieldGridColumn(section: FormWizardSection, field: CallForProjectApplicationField): string {
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

function isFieldCompleted(field: CallForProjectApplicationField, value: FormValue, file: File | null | undefined): boolean {
  if (field.type === "file") {
    return !field.required || Boolean(file);
  }

  if (field.type === "checkbox_group") {
    return !field.required || (((value as string[] | undefined) ?? []).length > 0);
  }

  if (field.type === "phone") {
    const phoneValue = (value as { dial_code?: string; number?: string; country_code?: string } | undefined) ?? {};
    return !field.required || Boolean(phoneValue.country_code && phoneValue.number?.trim());
  }

  if (field.type === "boolean") {
    return !field.required || value === true || value === false;
  }

  return !field.required || String(value ?? "").trim() !== "";
}

export function CallForProjectApplicationForm({ item }: { item: PublicContent }) {
  const form = item.applicationForm;
  const [values, setValues] = useState<Record<string, FormValue>>(() => createInitialValues(item));
  const [files, setFiles] = useState<Record<string, File | null>>({});
  const [countries, setCountries] = useState<PublicReferenceCountry[]>([]);
  const [citySearchInput, setCitySearchInput] = useState<Record<string, string>>({});
  const [citySearchResults, setCitySearchResults] = useState<Record<string, PublicReferenceCity[]>>({});
  const [loadingCitySearch, setLoadingCitySearch] = useState<Record<string, boolean>>({});
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [submitSuccess, setSubmitSuccess] = useState<string | null>(null);
  const [errors, setErrors] = useState<FieldErrorMap>({});
  const [currentStepIndex, setCurrentStepIndex] = useState(0);

  const visibleFields = useMemo(
    () => (form?.fields ?? []).filter(isVisible),
    [form],
  );
  const wizardSteps = useMemo(() => buildWizardSteps(form, visibleFields), [form, visibleFields]);
  const currentStep = wizardSteps[currentStepIndex] ?? wizardSteps[0] ?? null;
  const isLastStep = wizardSteps.length === 0 || currentStepIndex === wizardSteps.length - 1;
  const currentStepRequiredCount = currentStep?.fields.filter((field) => field.required).length ?? 0;
  const currentStepCompletedCount = currentStep?.fields.filter((field) => field.required && isFieldCompleted(field, values[field.key], files[field.key])).length ?? 0;
  const overallRequiredCount = visibleFields.filter((field) => field.required).length;
  const overallCompletedCount = visibleFields.filter((field) => field.required && isFieldCompleted(field, values[field.key], files[field.key])).length;
  const overallCompletionPercentage = overallRequiredCount > 0 ? Math.round((overallCompletedCount / overallRequiredCount) * 100) : 100;

  useEffect(() => {
    setValues(createInitialValues(item));
    setFiles({});
    setCitySearchInput({});
    setCitySearchResults({});
    setErrors({});
    setSubmitError(null);
    setSubmitSuccess(null);
    setCurrentStepIndex(0);
  }, [item]);

  useEffect(() => {
    let isMounted = true;

    async function loadCountries() {
      try {
        const response = await fetch("/api/public/references/countries", {
          headers: { Accept: "application/json" },
        });
        const payload = (await response.json().catch(() => null)) as CountryPayload | null;

        if (!response.ok || !payload?.data || !isMounted) {
          return;
        }

        setCountries(payload.data);
      } catch {}
    }

    void loadCountries();

    return () => {
      isMounted = false;
    };
  }, []);

  useEffect(() => {
    const controllers: AbortController[] = [];
    const timeouts: number[] = [];
    const cityFields = visibleFields.filter((field) => field.type === "city" && field.country_field);

    for (const field of cityFields) {
      const countryCode = String(values[field.country_field ?? ""] ?? "").toUpperCase();
      const query = (citySearchInput[field.key] ?? "").trim();

      if (!countryCode || query.length < 2) {
        setLoadingCitySearch((current) => ({ ...current, [field.key]: false }));
        setCitySearchResults((current) => ({ ...current, [field.key]: [] }));
        continue;
      }

      const controller = new AbortController();
      controllers.push(controller);
      setLoadingCitySearch((current) => ({ ...current, [field.key]: true }));

      const timeout = window.setTimeout(() => {
        void fetch(`/api/public/references/cities?country=${encodeURIComponent(countryCode)}&q=${encodeURIComponent(query)}&limit=25`, {
          headers: { Accept: "application/json" },
          signal: controller.signal,
        })
          .then(async (response) => {
            const payload = (await response.json().catch(() => null)) as CityPayload | null;

            if (!response.ok || !payload?.data) {
              return;
            }

            setCitySearchResults((current) => ({ ...current, [field.key]: payload.data ?? [] }));
          })
          .catch(() => {})
          .finally(() => {
            setLoadingCitySearch((current) => ({ ...current, [field.key]: false }));
          });
      }, 250);

      timeouts.push(timeout);
    }

    return () => {
      controllers.forEach((controller) => controller.abort());
      timeouts.forEach((timeout) => window.clearTimeout(timeout));
    };
  }, [citySearchInput, values, visibleFields]);

  function updateValue(key: string, value: FormValue) {
    setValues((current) => ({ ...current, [key]: value }));
    setErrors((current) => {
      if (!(key in current)) {
        return current;
      }

      const next = { ...current };
      delete next[key];
      return next;
    });
    setSubmitError(null);
  }

  function formatCityLabel(city: PublicReferenceCity): string {
    return city.meta?.state_name ? `${city.name} · ${city.meta.state_name}` : city.name;
  }

  function clearDependentCities(countryFieldKey: string) {
    const dependentFields = visibleFields.filter((field) => field.type === "city" && field.country_field === countryFieldKey);

    if (dependentFields.length === 0) {
      return;
    }

    setValues((current) => {
      const next = { ...current };
      for (const field of dependentFields) {
        next[field.key] = "";
      }
      return next;
    });
    setCitySearchInput((current) => {
      const next = { ...current };
      for (const field of dependentFields) {
        next[field.key] = "";
      }
      return next;
    });
    setCitySearchResults((current) => {
      const next = { ...current };
      for (const field of dependentFields) {
        next[field.key] = [];
      }
      return next;
    });
  }

  function goToPreviousStep() {
    setCurrentStepIndex((current) => Math.max(0, current - 1));
  }

  function goToNextStep() {
    if (!currentStep) {
      return;
    }

    const invalidFields = currentStep.fields.filter((field) => !isFieldCompleted(field, values[field.key], files[field.key]));

    if (invalidFields.length > 0) {
      setErrors((current) => {
        const next = { ...current };

        for (const field of invalidFields) {
          if (!next[field.key]) {
            next[field.key] = `Le champ \"${field.label}\" est requis.`;
          }
        }

        return next;
      });
      setSubmitError("Veuillez compléter les champs requis de cette étape avant de continuer.");
      return;
    }

    setSubmitError(null);
    setCurrentStepIndex((current) => Math.min(wizardSteps.length - 1, current + 1));
  }

  if (!form) {
    return null;
  }

  function renderHelp(field: CallForProjectApplicationField) {
    if (field.type === "file") {
      const parts = [] as string[];
      if (field.max_size_mb) {
        parts.push(`Taille max ${field.max_size_mb} Mo`);
      }
      if (field.accept && field.accept.length > 0) {
        parts.push(field.accept.join(", "));
      }

      if (parts.length > 0) {
        return <span style={{ color: "var(--text-soft)", fontSize: "0.78rem" }}>{parts.join(" · ")}</span>;
      }
    }

    return null;
  }

  function renderField(field: CallForProjectApplicationField) {
    const error = fieldError(errors, field.key);

    if (field.type === "textarea") {
      return (
        <label className="contact-form-label" key={field.key}>
          {field.label}{field.required ? " *" : ""}
          <textarea
            onChange={(event) => updateValue(field.key, event.target.value)}
            required={field.required}
            rows={5}
            value={String(values[field.key] ?? "")}
          />
          {renderHelp(field)}
          {error ? <span style={{ color: "#b91c1c", fontSize: "0.78rem" }}>{error}</span> : null}
        </label>
      );
    }

    if (field.type === "country") {
      return (
        <label className="contact-form-label" key={field.key}>
          {field.label}{field.required ? " *" : ""}
          <select
            onChange={(event) => {
              const nextCountryCode = event.target.value.toUpperCase();
              clearDependentCities(field.key);
              updateValue(field.key, nextCountryCode);
            }}
            required={field.required}
            value={String(values[field.key] ?? "")}
          >
            <option value="">Sélectionnez un pays</option>
            {countries.map((country) => (
              <option key={country.iso2} value={country.iso2}>
                {country.name}{country.phone_code ? ` (${normalizeDialCode(country.phone_code)})` : ""}
              </option>
            ))}
          </select>
          {error ? <span style={{ color: "#b91c1c", fontSize: "0.78rem" }}>{error}</span> : null}
        </label>
      );
    }

    if (field.type === "city") {
      const countryCode = String(values[field.country_field ?? ""] ?? "").toUpperCase();
      const searchValue = citySearchInput[field.key] ?? "";
      const cities = citySearchResults[field.key] ?? [];

      return (
        <label className="contact-form-label" key={field.key}>
          {field.label}{field.required ? " *" : ""}
          <input
            disabled={!countryCode}
            onChange={(event) => {
              const nextValue = event.target.value;
              setCitySearchInput((current) => ({ ...current, [field.key]: nextValue }));
              updateValue(field.key, "");
            }}
            placeholder={countryCode ? "Rechercher une ville" : "Sélectionnez d'abord un pays"}
            required={field.required}
            type="text"
            value={searchValue}
          />
          {loadingCitySearch[field.key] ? <span style={{ color: "var(--text-soft)", fontSize: "0.78rem" }}>Recherche en cours...</span> : null}
          {!loadingCitySearch[field.key] && searchValue.trim().length >= 2 && cities.length > 0 ? (
            <div style={{ border: "1px solid rgba(15, 23, 42, 0.12)", borderRadius: "14px", display: "grid", gap: "6px", marginTop: "8px", maxHeight: "220px", overflowY: "auto", padding: "8px" }}>
              {cities.map((city) => (
                <button
                  key={city.id}
                  onClick={(event) => {
                    event.preventDefault();
                    setCitySearchInput((current) => ({ ...current, [field.key]: formatCityLabel(city) }));
                    setCitySearchResults((current) => ({ ...current, [field.key]: [] }));
                    updateValue(field.key, String(city.id));
                  }}
                  style={{ background: "transparent", border: 0, cursor: "pointer", padding: "8px", textAlign: "left" }}
                  type="button"
                >
                  <strong>{city.name}</strong>
                  {city.meta?.state_name ? <span style={{ color: "var(--text-soft)", display: "block", fontSize: "0.82rem" }}>{city.meta.state_name}</span> : null}
                </button>
              ))}
            </div>
          ) : null}
          {!loadingCitySearch[field.key] && searchValue.trim().length >= 2 && cities.length === 0 ? (
            <span style={{ color: "var(--text-soft)", fontSize: "0.78rem" }}>Aucune ville trouvée pour cette recherche.</span>
          ) : null}
          {error ? <span style={{ color: "#b91c1c", fontSize: "0.78rem" }}>{error}</span> : null}
        </label>
      );
    }

    if (field.type === "phone") {
      const currentValue = (values[field.key] as { dial_code?: string; number?: string; country_code?: string } | undefined) ?? {};

      return (
        <label className="contact-form-label" key={field.key}>
          {field.label}{field.required ? " *" : ""}
          <div style={{ display: "grid", gap: "12px", gridTemplateColumns: "minmax(140px, 180px) minmax(0, 1fr)" }}>
            <select
              onChange={(event) => {
                const country = countries.find((entry) => entry.iso2 === event.target.value);
                updateValue(field.key, {
                  ...currentValue,
                  country_code: event.target.value,
                  dial_code: normalizeDialCode(country?.phone_code),
                });
              }}
              value={String(currentValue.country_code ?? "")}
            >
              <option value="">Indicatif</option>
              {countries.filter((country) => country.phone_code).map((country) => (
                <option key={`${field.key}-${country.iso2}`} value={country.iso2}>
                  {normalizeDialCode(country.phone_code)} · {country.name}
                </option>
              ))}
            </select>
            <input
              onChange={(event) => updateValue(field.key, {
                ...currentValue,
                number: event.target.value,
              })}
              placeholder="Numéro sans indicatif"
              required={field.required}
              type="tel"
              value={String(currentValue.number ?? "")}
            />
          </div>
          {error ? <span style={{ color: "#b91c1c", fontSize: "0.78rem" }}>{error}</span> : null}
        </label>
      );
    }

    if (field.type === "radio") {
      return (
        <fieldset key={field.key} style={{ border: 0, margin: 0, padding: 0 }}>
          <legend style={{ fontWeight: 700, marginBottom: "10px" }}>
            {field.label}{field.required ? " *" : ""}
          </legend>
          <div style={{ display: "grid", gap: "10px" }}>
            {(field.options ?? []).map((option) => (
              <label key={`${field.key}-${option.value}`} style={{ alignItems: "center", display: "flex", gap: "10px" }}>
                <input
                  checked={String(values[field.key] ?? "") === option.value}
                  name={field.key}
                  onChange={() => updateValue(field.key, option.value)}
                  required={field.required}
                  type="radio"
                  value={option.value}
                />
                <span>{option.label}</span>
              </label>
            ))}
          </div>
          {error ? <span style={{ color: "#b91c1c", fontSize: "0.78rem" }}>{error}</span> : null}
        </fieldset>
      );
    }

    if (field.type === "checkbox_group") {
      const selectedValues = (values[field.key] as string[] | undefined) ?? [];

      return (
        <fieldset key={field.key} style={{ border: 0, margin: 0, padding: 0 }}>
          <legend style={{ fontWeight: 700, marginBottom: "10px" }}>
            {field.label}{field.required ? " *" : ""}
          </legend>
          <div style={{ display: "grid", gap: "10px" }}>
            {(field.options ?? []).map((option) => {
              const checked = selectedValues.includes(option.value);

              return (
                <label key={`${field.key}-${option.value}`} style={{ alignItems: "center", display: "flex", gap: "10px" }}>
                  <input
                    checked={checked}
                    onChange={(event) => {
                      const nextValues = event.target.checked
                        ? [...selectedValues, option.value]
                        : selectedValues.filter((entry) => entry !== option.value);
                      updateValue(field.key, nextValues);
                    }}
                    type="checkbox"
                    value={option.value}
                  />
                  <span>{option.label}</span>
                </label>
              );
            })}
          </div>
          {error ? <span style={{ color: "#b91c1c", fontSize: "0.78rem" }}>{error}</span> : null}
        </fieldset>
      );
    }

    if (field.type === "boolean") {
      const currentValue = values[field.key] === true ? true : values[field.key] === false ? false : null;

      return (
        <fieldset key={field.key} style={{ border: 0, margin: 0, padding: 0 }}>
          <legend style={{ fontWeight: 700, marginBottom: "10px" }}>
            {field.label}{field.required ? " *" : ""}
          </legend>
          <div style={{ display: "grid", gap: "10px" }}>
            <label style={{ alignItems: "center", display: "flex", gap: "10px" }}>
              <input
                checked={currentValue === true}
                name={field.key}
                onChange={() => updateValue(field.key, true)}
                type="radio"
              />
              <span>{field.true_label ?? "Oui"}</span>
            </label>
            <label style={{ alignItems: "center", display: "flex", gap: "10px" }}>
              <input
                checked={currentValue === false}
                name={field.key}
                onChange={() => updateValue(field.key, false)}
                type="radio"
              />
              <span>{field.false_label ?? "Non"}</span>
            </label>
          </div>
          {error ? <span style={{ color: "#b91c1c", fontSize: "0.78rem" }}>{error}</span> : null}
        </fieldset>
      );
    }

    if (field.type === "file") {
      return (
        <label className="contact-form-label" key={field.key}>
          {field.label}{field.required ? " *" : ""}
          <input
            accept={field.accept?.join(",")}
            onChange={(event) => {
              const file = event.target.files?.[0] ?? null;
              setFiles((current) => ({ ...current, [field.key]: file }));
              setErrors((current) => {
                if (!(field.key in current)) {
                  return current;
                }

                const next = { ...current };
                delete next[field.key];
                return next;
              });
            }}
            required={field.required}
            type="file"
          />
          {files[field.key] ? <span style={{ color: "var(--text-soft)", fontSize: "0.78rem" }}>{files[field.key]?.name}</span> : null}
          {renderHelp(field)}
          {error ? <span style={{ color: "#b91c1c", fontSize: "0.78rem" }}>{error}</span> : null}
        </label>
      );
    }

    const type = field.type === "email" ? "email" : field.type === "date" ? "date" : field.type === "number" ? "number" : "text";

    return (
      <label className="contact-form-label" key={field.key}>
        {field.label}{field.required ? " *" : ""}
        <input
          autoComplete={field.autocomplete}
          max={field.max}
          min={field.min}
          onChange={(event) => updateValue(field.key, event.target.value)}
          required={field.required}
          type={type}
          value={String(values[field.key] ?? "")}
        />
        {error ? <span style={{ color: "#b91c1c", fontSize: "0.78rem" }}>{error}</span> : null}
      </label>
    );
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSubmitting(true);
    setSubmitError(null);
    setSubmitSuccess(null);
    setErrors({});

    const formData = new FormData();

    for (const field of visibleFields) {
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
        const phoneValue = (value as { dial_code?: string; number?: string; country_code?: string } | undefined) ?? {};
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

    try {
      const response = await fetch(`/api/public/call-for-projects/${encodeURIComponent(item.slug)}/apply?tenant=${encodeURIComponent(item.organizerSlug)}`, {
        method: "POST",
        body: formData,
      });
      const payload = await response.json().catch(() => null) as
        | {
            message?: string;
            error?: string;
            data?: { id?: string };
            errors?: Record<string, string[] | string>;
          }
        | null;

      if (!response.ok) {
        setErrors(toFieldErrors(payload));
        setSubmitError(payload?.error ?? payload?.message ?? "Impossible d'envoyer votre candidature.");
        setSubmitting(false);
        return;
      }

      setSubmitSuccess(payload?.message ?? form?.success_message ?? "Votre candidature a bien été envoyée.");
      setValues(createInitialValues(item));
      setFiles({});
      setCitySearchInput({});
      setCitySearchResults({});
      setErrors({});
      setCurrentStepIndex(0);
    } catch {
      setSubmitError("Impossible de contacter le serveur pour le moment.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="checkout-layout">
      <div className="checkout-form">
        <div className="checkout-head">
          <div>
            <span className="badge">Candidature publique</span>
            <h2>{form.title}</h2>
            <p className="section-copy">{form.description ?? "Renseignez soigneusement les informations requises avant l'envoi."}</p>
          </div>
        </div>

        {form.payment?.has_paid_offers ? (
          <div className="checkout-section">
            <h2>Paiement et frais de dossier</h2>
            <p className="section-copy">
              Cet appel à projets comporte une offre gratuite ou payante. Vous pouvez finaliser le paiement avant de soumettre votre candidature.
            </p>
            <div style={{ display: "grid", gap: "12px" }}>
              {form.payment.offers.map((offer) => (
                <Link className="button button--ghost" href={`/checkout/${item.module}/${item.slug}?offer=${offer.id}`} key={offer.id}>
                  {offer.cta_label} · {offer.title} · {offer.price === 0 ? "Gratuit" : formatMoney(offer.price, offer.currency)}
                </Link>
              ))}
            </div>
          </div>
        ) : null}

        <form className="contact-form-group" onSubmit={handleSubmit}>
          <div style={{
            background: "linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(250, 243, 231, 0.96))",
            border: "1px solid rgba(213, 154, 54, 0.2)",
            borderRadius: "24px",
            boxShadow: "0 24px 64px rgba(17, 24, 32, 0.08)",
            display: "grid",
            gap: "18px",
            marginBottom: "6px",
            overflow: "hidden",
            padding: "24px",
          }}>
            <div style={{ alignItems: "start", display: "flex", flexWrap: "wrap", gap: "18px", justifyContent: "space-between" }}>
              <div style={{ display: "grid", gap: "8px", maxWidth: "680px" }}>
                <span style={{ color: "var(--accent-strong)", fontSize: "0.74rem", fontWeight: 800, letterSpacing: "0.12em", textTransform: "uppercase" }}>
                  Parcours guidé
                </span>
                <h3 style={{ fontFamily: "var(--font-display)", fontSize: "1.65rem", lineHeight: 1.05, margin: 0 }}>
                  Une candidature structurée, étape par étape
                </h3>
                <p style={{ color: "var(--text-soft)", margin: 0 }}>
                  Complète chaque section dans l’ordre pour soumettre un dossier propre, cohérent et validé avant envoi.
                </p>
              </div>
              <div style={{
                alignItems: "center",
                alignSelf: "stretch",
                background: "rgba(13, 20, 28, 0.92)",
                borderRadius: "20px",
                color: "#fff",
                display: "grid",
                minWidth: "152px",
                padding: "16px 18px",
                placeItems: "center",
              }}>
                <strong style={{ fontFamily: "var(--font-display)", fontSize: "2rem", lineHeight: 1 }}>{overallCompletionPercentage}%</strong>
                <span style={{ color: "rgba(255, 255, 255, 0.7)", fontSize: "0.78rem", fontWeight: 700, letterSpacing: "0.06em", textTransform: "uppercase" }}>
                  progression
                </span>
              </div>
            </div>

            <div style={{ background: "rgba(15, 23, 42, 0.08)", borderRadius: "999px", height: "12px", overflow: "hidden" }}>
              <div style={{
                background: "linear-gradient(90deg, var(--accent), var(--teal))",
                borderRadius: "999px",
                height: "100%",
                transition: "width 220ms ease",
                width: `${overallCompletionPercentage}%`,
              }} />
            </div>

            <div style={{ color: "var(--text-soft)", display: "flex", flexWrap: "wrap", gap: "10px 18px", fontSize: "0.84rem", fontWeight: 700 }}>
              <span>{wizardSteps.length} étape{wizardSteps.length > 1 ? "s" : ""}</span>
              <span>{overallCompletedCount} / {overallRequiredCount} champs requis complétés</span>
              <span>{visibleFields.length} champ{visibleFields.length > 1 ? "s" : ""} visibles</span>
            </div>
          </div>

          {wizardSteps.length > 1 ? (
            <div style={{ display: "grid", gap: "14px", marginBottom: "22px" }}>
              <div style={{ alignItems: "stretch", display: "flex", flexWrap: "wrap", gap: "12px" }}>
                {wizardSteps.map((step, index) => {
                  const isActive = index === currentStepIndex;
                  const isCompleted = index < currentStepIndex;
                  const canOpen = index <= currentStepIndex;

                  return (
                    <button
                      key={step.key}
                      onClick={(event) => {
                        event.preventDefault();
                        if (canOpen) {
                          setCurrentStepIndex(index);
                        }
                      }}
                      style={{
                        alignItems: "center",
                        background: isActive
                          ? "linear-gradient(135deg, rgba(213, 154, 54, 0.18), rgba(255, 255, 255, 0.92))"
                          : isCompleted
                            ? "linear-gradient(135deg, rgba(28, 124, 114, 0.16), rgba(255, 255, 255, 0.92))"
                            : "rgba(255, 255, 255, 0.75)",
                        border: isActive ? "1px solid rgba(213, 154, 54, 0.45)" : "1px solid rgba(15, 23, 42, 0.08)",
                        borderRadius: "20px",
                        boxShadow: isActive ? "0 18px 42px rgba(213, 154, 54, 0.14)" : "0 12px 28px rgba(15, 23, 42, 0.05)",
                        color: "var(--text)",
                        cursor: canOpen ? "pointer" : "default",
                        display: "inline-flex",
                        flex: "1 1 220px",
                        gap: "12px",
                        minHeight: "84px",
                        opacity: canOpen ? 1 : 0.72,
                        padding: "14px 16px",
                        textAlign: "left",
                      }}
                      type="button"
                    >
                      <span style={{
                        alignItems: "center",
                        background: isActive ? "var(--accent)" : isCompleted ? "var(--teal)" : "rgba(15, 23, 42, 0.1)",
                        borderRadius: "999px",
                        color: isActive || isCompleted ? "#fff" : "var(--text)",
                        display: "inline-flex",
                        fontSize: "0.8rem",
                        fontWeight: 800,
                        flexShrink: 0,
                        height: "34px",
                        justifyContent: "center",
                        width: "34px",
                      }}>{isCompleted ? "✓" : index + 1}</span>
                      <span style={{ display: "grid", gap: "4px", textAlign: "left" }}>
                        <strong style={{ fontSize: "0.96rem" }}>{step.title}</strong>
                        {step.description ? <span style={{ color: "var(--text-soft)", fontSize: "0.78rem", lineHeight: 1.45 }}>{step.description}</span> : null}
                        <span style={{ color: isCompleted ? "var(--teal)" : isActive ? "var(--accent-strong)" : "var(--text-soft)", fontSize: "0.72rem", fontWeight: 800, letterSpacing: "0.06em", textTransform: "uppercase" }}>
                          {isCompleted ? "Complétée" : isActive ? "En cours" : "À venir"}
                        </span>
                      </span>
                    </button>
                  );
                })}
              </div>
            </div>
          ) : null}

          {currentStep ? (
            <div style={{ display: "grid", gap: "18px" }}>
              <div style={{
                background: "linear-gradient(135deg, rgba(13, 20, 28, 0.96), rgba(28, 124, 114, 0.9))",
                border: "1px solid rgba(17, 24, 32, 0.08)",
                borderRadius: "24px",
                boxShadow: "0 30px 70px rgba(13, 20, 28, 0.18)",
                display: "grid",
                gap: "14px",
                overflow: "hidden",
                padding: "24px",
              }}>
                <div style={{ alignItems: "center", display: "flex", justifyContent: "space-between", gap: "16px" }}>
                  <div>
                    <span style={{ color: "rgba(255, 255, 255, 0.72)", display: "block", fontSize: "0.76rem", fontWeight: 800, letterSpacing: "0.08em", textTransform: "uppercase" }}>
                      Étape {currentStepIndex + 1} sur {wizardSteps.length}
                    </span>
                    <h3 style={{ color: "#fff", margin: "8px 0 0", fontFamily: "var(--font-display)", fontSize: "1.7rem", lineHeight: 1.08 }}>{currentStep.title}</h3>
                  </div>
                  <div style={{
                    background: "rgba(255, 255, 255, 0.12)",
                    border: "1px solid rgba(255, 255, 255, 0.16)",
                    borderRadius: "999px",
                    color: "#fff",
                    fontSize: "0.82rem",
                    fontWeight: 800,
                    padding: "10px 14px",
                  }}>
                    {currentStepRequiredCount > 0 ? `${currentStepCompletedCount} / ${currentStepRequiredCount} champs requis complétés` : "Étape informative"}
                  </div>
                </div>
                {currentStep.description ? <p style={{ color: "rgba(255, 255, 255, 0.78)", margin: 0, maxWidth: "780px" }}>{currentStep.description}</p> : null}
                {currentStepRequiredCount > 0 ? (
                  <div style={{ background: "rgba(255, 255, 255, 0.14)", borderRadius: "999px", height: "10px", overflow: "hidden" }}>
                    <div style={{
                      background: "linear-gradient(90deg, rgba(255,255,255,0.96), rgba(213, 154, 54, 0.95))",
                      borderRadius: "999px",
                      height: "100%",
                      transition: "width 220ms ease",
                      width: `${Math.round((currentStepCompletedCount / currentStepRequiredCount) * 100)}%`,
                    }} />
                  </div>
                ) : null}
              </div>

              {currentStep.sections.map((section, sectionIndex) => (
                <section
                  key={section.key}
                  style={{
                    background: "#fff",
                    border: "1px solid rgba(17, 24, 32, 0.08)",
                    borderRadius: "22px",
                    boxShadow: "0 18px 42px rgba(17, 24, 32, 0.06)",
                    display: "grid",
                    gap: "18px",
                    overflow: "hidden",
                    padding: "22px",
                  }}
                >
                  <div style={{
                    alignItems: "start",
                    borderBottom: "1px solid rgba(17, 24, 32, 0.06)",
                    display: "flex",
                    flexWrap: "wrap",
                    gap: "14px",
                    justifyContent: "space-between",
                    margin: "-22px -22px 0",
                    padding: "20px 22px 18px",
                  }}>
                    <div style={{ display: "grid", gap: "6px" }}>
                      <span style={{ color: "var(--accent-strong)", fontSize: "0.72rem", fontWeight: 800, letterSpacing: "0.08em", textTransform: "uppercase" }}>
                        Section {sectionIndex + 1}
                      </span>
                      <h3 style={{ margin: 0, fontSize: "1.08rem" }}>{section.title}</h3>
                    </div>
                    <span style={{
                      alignItems: "center",
                      background: "rgba(15, 23, 42, 0.05)",
                      borderRadius: "999px",
                      color: "var(--text-soft)",
                      display: "inline-flex",
                      fontSize: "0.74rem",
                      fontWeight: 800,
                      minHeight: "34px",
                      padding: "0 12px",
                    }}>
                      {section.fields.length} champ{section.fields.length > 1 ? "s" : ""}
                    </span>
                  </div>
                  <div style={{ display: "grid", gap: "6px" }}>
                    {section.description ? <p style={{ color: "var(--text-soft)", margin: 0 }}>{section.description}</p> : null}
                  </div>
                  <div style={{ display: "grid", gap: "18px", gridTemplateColumns: getSectionGridTemplateColumns(section) }}>
                    {section.fields.map((field) => (
                      <div key={field.key} style={{ gridColumn: getFieldGridColumn(section, field), minWidth: 0 }}>
                        {renderField(field)}
                      </div>
                    ))}
                  </div>
                </section>
              ))}
            </div>
          ) : null}

          {submitError ? <p style={{ color: "#b91c1c", margin: 0 }}>{submitError}</p> : null}
          {submitSuccess ? <p style={{ color: "#15803d", margin: 0 }}>{submitSuccess}</p> : null}

          <div style={{
            alignItems: "center",
            background: "linear-gradient(180deg, rgba(255, 255, 255, 0.95), rgba(247, 241, 231, 0.95))",
            border: "1px solid rgba(17, 24, 32, 0.08)",
            borderRadius: "22px",
            boxShadow: "0 16px 40px rgba(17, 24, 32, 0.05)",
            display: "flex",
            flexWrap: "wrap",
            gap: "12px",
            justifyContent: "space-between",
            padding: "16px 18px",
          }}>
            <div style={{ display: "grid", gap: "2px" }}>
              <strong style={{ fontSize: "0.92rem" }}>{isLastStep ? "Dernière étape" : "Poursuivre la candidature"}</strong>
              <span style={{ color: "var(--text-soft)", fontSize: "0.8rem" }}>
                {isLastStep ? "Vérifie les informations et soumets ton dossier." : "Valide cette étape pour passer à la suivante."}
              </span>
            </div>
            <button className="button button--ghost" disabled={currentStepIndex === 0 || submitting} onClick={goToPreviousStep} type="button">
              Étape précédente
            </button>
            {isLastStep ? (
              <button className="button" disabled={submitting} type="submit">
                {submitting ? "Envoi en cours..." : form.submit_label ?? "Soumettre ma candidature"}
              </button>
            ) : (
              <button className="button" disabled={submitting} onClick={goToNextStep} type="button">
                Étape suivante
              </button>
            )}
          </div>
        </form>
      </div>

      <aside className="booking-summary">
        <div className="booking-summary__header">
          <p className="booking-summary__eyebrow">Résumé</p>
          <h2 className="booking-summary__title">{item.title}</h2>
          <p className="booking-summary__subtitle">{item.category}</p>
        </div>

        <div className="booking-summary__details">
          {item.applicationOpensAt ? (
            <div className="booking-summary__detail-row">
              <span className="booking-summary__detail-label">Ouverture</span>
              <span className="booking-summary__detail-value">{formatDateLabel(item.applicationOpensAt)}</span>
            </div>
          ) : null}
          {item.deadlineAt ? (
            <div className="booking-summary__detail-row">
              <span className="booking-summary__detail-label">Clôture</span>
              <span className="booking-summary__detail-value">{formatDateLabel(item.deadlineAt)}</span>
            </div>
          ) : null}
          <div className="booking-summary__detail-row">
            <span className="booking-summary__detail-label">Pays</span>
            <span className="booking-summary__detail-value">Sélection robuste via référentiel local</span>
          </div>
          <div className="booking-summary__detail-row">
            <span className="booking-summary__detail-label">Ville</span>
            <span className="booking-summary__detail-value">Chargée selon le pays choisi</span>
          </div>
          <div className="booking-summary__detail-row">
            <span className="booking-summary__detail-label">Téléphone</span>
            <span className="booking-summary__detail-value">Indicatif synchronisé avec le pays</span>
          </div>
        </div>

        <div className="booking-summary__trust">
          <span>Validation serveur, contrôle des pièces jointes et anti-abus inclus</span>
        </div>
      </aside>
    </div>
  );
}
