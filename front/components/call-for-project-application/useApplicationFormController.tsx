"use client";

import { type FormEvent, useCallback, useEffect, useMemo, useState } from "react";

import type { CallForProjectApplicationField, PublicContent } from "@/lib/types";

import { ApplicationFieldRenderer, formatCityLabel } from "./ApplicationFieldRenderer";
import {
  buildWizardSteps,
  createInitialValues,
  isFieldCompleted,
  isVisible,
} from "./helpers";
import { useCitySearch, useReferenceCountries } from "./hooks";
import { submitApplication } from "./submission";
import type { FieldErrorMap, FormValue } from "./types";

export function useApplicationFormController(item: PublicContent) {
  const form = item.applicationForm;
  const [values, setValues] = useState<Record<string, FormValue>>(() => createInitialValues(item));
  const [files, setFiles] = useState<Record<string, File | null>>({});
  const [citySearchInput, setCitySearchInput] = useState<Record<string, string>>({});
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [submitSuccess, setSubmitSuccess] = useState<string | null>(null);
  const [errors, setErrors] = useState<FieldErrorMap>({});
  const [currentStepIndex, setCurrentStepIndex] = useState(0);

  const visibleFields = useMemo(() => (form?.fields ?? []).filter(isVisible), [form]);
  const countries = useReferenceCountries();
  const { citySearchResults, loadingCitySearch, setCitySearchResults } = useCitySearch(
    visibleFields,
    values,
    citySearchInput,
  );
  const wizardSteps = useMemo(() => buildWizardSteps(form, visibleFields), [form, visibleFields]);
  const currentStep = wizardSteps[currentStepIndex] ?? wizardSteps[0] ?? null;
  const isLastStep = wizardSteps.length === 0 || currentStepIndex === wizardSteps.length - 1;
  const currentStepRequiredCount = currentStep?.fields.filter((field) => field.required).length ?? 0;
  const currentStepCompletedCount =
    currentStep?.fields.filter((field) => field.required && isFieldCompleted(field, values[field.key], files[field.key]))
      .length ?? 0;
  const overallRequiredCount = visibleFields.filter((field) => field.required).length;
  const overallCompletedCount = visibleFields.filter(
    (field) => field.required && isFieldCompleted(field, values[field.key], files[field.key]),
  ).length;
  const overallCompletionPercentage =
    overallRequiredCount > 0 ? Math.round((overallCompletedCount / overallRequiredCount) * 100) : 100;

  const resetFormState = useCallback(() => {
    setValues(createInitialValues(item));
    setFiles({});
    setCitySearchInput({});
    setCitySearchResults({});
    setErrors({});
    setSubmitError(null);
    setSubmitSuccess(null);
    setCurrentStepIndex(0);
  }, [item, setCitySearchResults]);

  useEffect(() => {
    resetFormState();
  }, [resetFormState]);

  const updateValue = useCallback((key: string, value: FormValue) => {
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
  }, []);

  const clearDependentCities = useCallback(
    (countryFieldKey: string) => {
      const dependentFields = visibleFields.filter(
        (field) => field.type === "city" && field.country_field === countryFieldKey,
      );

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
    },
    [setCitySearchResults, visibleFields],
  );

  const goToPreviousStep = useCallback(() => {
    setCurrentStepIndex((current) => Math.max(0, current - 1));
  }, []);

  const goToNextStep = useCallback(() => {
    if (!currentStep) {
      return;
    }

    const invalidFields = currentStep.fields.filter((field) => !isFieldCompleted(field, values[field.key], files[field.key]));

    if (invalidFields.length > 0) {
      setErrors((current) => {
        const next = { ...current };

        for (const field of invalidFields) {
          if (!next[field.key]) {
            next[field.key] = `Le champ "${field.label}" est requis.`;
          }
        }

        return next;
      });
      setSubmitError("Veuillez compléter les champs requis de cette étape avant de continuer.");
      return;
    }

    setSubmitError(null);
    setCurrentStepIndex((current) => Math.min(wizardSteps.length - 1, current + 1));
  }, [currentStep, files, values, wizardSteps.length]);

  const handleCountryChange = useCallback(
    (fieldKey: string, countryCode: string) => {
      clearDependentCities(fieldKey);
      updateValue(fieldKey, countryCode);
      const country = countries.find((entry) => entry.iso2 === countryCode);
      const dependentPhoneFields = visibleFields.filter(
        (field) => field.type === "phone" && field.country_field === fieldKey,
      );

      if (dependentPhoneFields.length > 0) {
        setValues((current) => {
          const next = { ...current };

          for (const field of dependentPhoneFields) {
            const currentPhoneValue = typeof next[field.key] === "object" && next[field.key] !== null
              ? (next[field.key] as Record<string, string>)
              : {};

            next[field.key] = {
              ...currentPhoneValue,
              country_code: countryCode,
              dial_code: country?.phone_code ? `+${country.phone_code.replace(/\D+/g, "")}` : "",
            };
          }

          return next;
        });
      }
    },
    [clearDependentCities, countries, updateValue, visibleFields],
  );

  const handleCityInputChange = useCallback(
    (fieldKey: string, value: string) => {
      setCitySearchInput((current) => ({ ...current, [fieldKey]: value }));
      updateValue(fieldKey, "");
    },
    [updateValue],
  );

  const handleCitySelect = useCallback(
    (fieldKey: string, city: Parameters<typeof formatCityLabel>[0]) => {
      setCitySearchInput((current) => ({ ...current, [fieldKey]: formatCityLabel(city) }));
      setCitySearchResults((current) => ({ ...current, [fieldKey]: [] }));
      updateValue(fieldKey, String(city.id));
    },
    [setCitySearchResults, updateValue],
  );

  const handleFileChange = useCallback((fieldKey: string, file: File | null) => {
    setFiles((current) => ({ ...current, [fieldKey]: file }));
    setErrors((current) => {
      if (!(fieldKey in current)) {
        return current;
      }

      const next = { ...current };
      delete next[fieldKey];
      return next;
    });
    setSubmitError(null);
  }, []);

  const renderField = useCallback(
    (field: CallForProjectApplicationField) => (
      <ApplicationFieldRenderer
        cities={citySearchResults[field.key] ?? []}
        citySearchValue={citySearchInput[field.key] ?? ""}
        countries={countries}
        countryCode={String(values[field.country_field ?? ""] ?? "").toUpperCase()}
        errorMap={errors}
        field={field}
        file={files[field.key]}
        key={field.key}
        loadingCities={loadingCitySearch[field.key] ?? false}
        onCityInputChange={handleCityInputChange}
        onCitySelect={handleCitySelect}
        onCountryChange={handleCountryChange}
        onFileChange={handleFileChange}
        onValueChange={updateValue}
        value={values[field.key]}
      />
    ),
    [
      citySearchInput,
      citySearchResults,
      countries,
      errors,
      files,
      handleCityInputChange,
      handleCitySelect,
      handleCountryChange,
      handleFileChange,
      loadingCitySearch,
      updateValue,
      values,
    ],
  );

  const handleSubmit = useCallback(
    async (event: FormEvent<HTMLFormElement>) => {
      event.preventDefault();

      if (!form) {
        return;
      }

      setSubmitting(true);
      setSubmitError(null);
      setSubmitSuccess(null);
      setErrors({});

      try {
        const result = await submitApplication({ files, form, item, values, visibleFields });

        if (!result.ok) {
          setErrors(result.errors);
          setSubmitError(result.message);
          return;
        }

        resetFormState();
        setSubmitSuccess(result.message);
      } catch {
        setSubmitError("Impossible de contacter le serveur pour le moment.");
      } finally {
        setSubmitting(false);
      }
    },
    [files, form, item, resetFormState, values, visibleFields],
  );

  return {
    currentStep,
    currentStepCompletedCount,
    currentStepIndex,
    currentStepRequiredCount,
    form,
    handleSubmit,
    isLastStep,
    overallCompletedCount,
    overallCompletionPercentage,
    overallRequiredCount,
    renderField,
    setCurrentStepIndex,
    submitError,
    submitSuccess,
    submitting,
    visibleFields,
    wizardSteps,
    goToNextStep,
    goToPreviousStep,
  };
}
