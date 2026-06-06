"use client";

import { useState } from "react";

import { DynamicFormFieldRenderer } from "./DynamicFormFieldRenderer";
import { isFieldVisible, validateField } from "./helpers";
import type { DynamicFormSchema } from "./types";

export function DynamicFormRenderer({
  schema,
  submitLabel,
  onSubmit,
}: {
  schema: DynamicFormSchema;
  submitLabel?: string;
  onSubmit?: (responses: Record<string, unknown>) => Promise<void> | void;
}) {
  const [responses, setResponses] = useState<Record<string, unknown>>({});
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [status, setStatus] = useState<"idle" | "submitting" | "success" | "error">("idle");

  function validateAll(): boolean {
    const errors: Record<string, string> = {};

    for (const field of schema.fields) {
      if (field.type === "section" || field.type === "hidden") continue;
      if (!isFieldVisible(field, responses)) continue;

      const error = validateField(field, responses[field.key]);
      if (error) errors[field.key] = error;
    }

    setFieldErrors(errors);
    return Object.keys(errors).length === 0;
  }

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (!validateAll()) return;

    setStatus("submitting");

    try {
      await onSubmit?.(responses);
      setStatus("success");
    } catch (error: unknown) {
      setStatus("error");

      // Parse field-level errors from API response (422 validation errors)
      if (error && typeof error === "object" && "errors" in error) {
        const apiErrors = (error as { errors: Record<string, string[]> }).errors;
        const mapped: Record<string, string> = {};

        for (const [key, messages] of Object.entries(apiErrors)) {
          // Strip "responses." prefix returned by Laravel validator
          const fieldKey = key.startsWith("responses.") ? key.slice("responses.".length) : key;
          mapped[fieldKey] = Array.isArray(messages) ? messages[0] : String(messages);
        }

        if (Object.keys(mapped).length > 0) {
          setFieldErrors(mapped);
        }
      }
    }
  }

  return (
    <form className="dynamic-form" noValidate onSubmit={handleSubmit}>
      {schema.fields.filter((field) => isFieldVisible(field, responses)).map((field) => (
        <DynamicFormFieldRenderer
          error={fieldErrors[field.key]}
          field={field}
          key={field.key}
          value={responses[field.key]}
          onChange={(value) => {
            setResponses((current) => ({ ...current, [field.key]: value }));
            // Clear error on change
            if (fieldErrors[field.key]) {
              setFieldErrors((current) => {
                const next = { ...current };
                delete next[field.key];
                return next;
              });
            }
          }}
        />
      ))}
      <button className="button" disabled={status === "submitting"} type="submit">
        {status === "submitting" ? "Envoi en cours…" : submitLabel ?? schema.submit_label ?? "Envoyer"}
      </button>
      {status === "success" ? (
        <p className="dynamic-form__success" role="status">
          {schema.success_message ?? "Votre réponse a été envoyée."}
        </p>
      ) : null}
      {status === "error" && Object.keys(fieldErrors).length === 0 ? (
        <p className="dynamic-form__error" role="alert">
          Impossible d'envoyer le formulaire pour le moment. Veuillez vérifier vos réponses et réessayer.
        </p>
      ) : null}
    </form>
  );
}
