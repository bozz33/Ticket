"use client";

import { useState } from "react";

import type { DynamicFormField, DynamicFormSchema } from "./types";

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
      {schema.fields.filter((field) => field.visible !== false).map((field) => (
        <DynamicFormInput
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

function DynamicFormInput({
  field,
  value,
  onChange,
}: {
  field: DynamicFormField;
  value: unknown;
  onChange: (value: unknown) => void;
}) {
  const id = `dynamic-form-${field.key}`;

  if (field.type === "section") {
    return (
      <div className="dynamic-form__section">
        <h3>{field.label}</h3>
        {field.help_text ? <p>{field.help_text}</p> : null}
      </div>
    );
  }

  return (
    <label className="dynamic-form__field" htmlFor={id}>
      <span>
        {field.label ?? field.key}
        {field.required ? <strong> *</strong> : null}
      </span>
      {renderControl(field, id, value, onChange)}
      {field.help_text ? <small>{field.help_text}</small> : null}
    </label>
  );
}

function renderControl(field: DynamicFormField, id: string, value: unknown, onChange: (value: unknown) => void) {
  if (field.type === "textarea") {
    return <textarea id={id} required={field.required} value={String(value ?? "")} onChange={(event) => onChange(event.target.value)} />;
  }

  if (field.type === "select" || field.type === "radio") {
    const options = normalizeOptions(field.options);

    return (
      <select id={id} required={field.required} value={String(value ?? "")} onChange={(event) => onChange(event.target.value)}>
        <option value="">Sélectionner</option>
        {options.map((option) => (
          <option key={option.value} value={option.value}>{option.label}</option>
        ))}
      </select>
    );
  }

  if (["checkbox", "boolean", "consent"].includes(field.type)) {
    return <input checked={Boolean(value)} id={id} required={field.required} type="checkbox" onChange={(event) => onChange(event.target.checked)} />;
  }

  const inputType = field.type === "email" || field.type === "number" || field.type === "date" || field.type === "url" ? field.type : "text";

  return <input id={id} required={field.required} type={inputType} value={String(value ?? "")} onChange={(event) => onChange(event.target.value)} />;
}

function normalizeOptions(options: DynamicFormField["options"]): Array<{ value: string; label: string }> {
  if (Array.isArray(options)) {
    return options.map((option) => ({ value: String(option.value), label: String(option.label) }));
  }

  return Object.entries(options ?? {}).map(([value, label]) => ({ value, label: String(label) }));
}
