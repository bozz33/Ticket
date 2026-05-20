"use client";

import { useState } from "react";

import { DynamicFormFieldRenderer } from "./DynamicFormFieldRenderer";
import { isFieldVisible } from "./helpers";
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
  const [status, setStatus] = useState<"idle" | "submitting" | "success" | "error">("idle");

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setStatus("submitting");

    try {
      await onSubmit?.(responses);
      setStatus("success");
    } catch {
      setStatus("error");
    }
  }

  return (
    <form className="dynamic-form" onSubmit={handleSubmit}>
      {schema.fields.filter((field) => isFieldVisible(field, responses)).map((field) => (
        <DynamicFormFieldRenderer
          field={field}
          key={field.key}
          onChange={(value) => setResponses((current) => ({ ...current, [field.key]: value }))}
          value={responses[field.key]}
        />
      ))}
      <button className="button" disabled={status === "submitting"} type="submit">
        {status === "submitting" ? "Envoi..." : submitLabel ?? schema.submit_label ?? "Envoyer"}
      </button>
      {status === "success" ? <p className="dynamic-form__success">{schema.success_message ?? "Votre réponse a été envoyée."}</p> : null}
      {status === "error" ? <p className="dynamic-form__error">Impossible d’envoyer le formulaire pour le moment.</p> : null}
    </form>
  );
}
