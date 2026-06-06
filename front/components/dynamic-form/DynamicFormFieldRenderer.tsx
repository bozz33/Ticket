import type { DynamicFormField } from "./types";
import { DynamicFormDateRangeField } from "./DynamicFormDateRangeField";
import { DynamicFormFileField } from "./DynamicFormFileField";
import { DynamicFormLocationField } from "./DynamicFormLocationField";
import { DynamicFormPhoneField } from "./DynamicFormPhoneField";
import { DynamicFormRatingField } from "./DynamicFormRatingField";
import { normalizeOptions, resolveInputType } from "./helpers";

export function DynamicFormFieldRenderer({
  field,
  value,
  error,
  onChange,
}: {
  field: DynamicFormField;
  value: unknown;
  error?: string;
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

  if (field.type === "hidden") {
    return (
      <input
        id={id}
        name={field.key}
        type="hidden"
        value={String(value ?? field.default_value ?? "")}
        onChange={(e) => onChange(e.target.value)}
      />
    );
  }

  return (
    <div
      aria-describedby={error ? `${id}-error` : undefined}
      className={`dynamic-form__field${error ? " dynamic-form__field--error" : ""}`}
    >
      <label htmlFor={id}>
        {field.label ?? field.key}
        {field.required ? <strong aria-hidden="true"> *</strong> : null}
      </label>
      {renderControl(field, id, value, onChange)}
      {field.help_text ? <small className="dynamic-form__help">{field.help_text}</small> : null}
      {error ? <span className="dynamic-form__field-error" id={`${id}-error`} role="alert">{error}</span> : null}
    </div>
  );
}

function renderControl(field: DynamicFormField, id: string, value: unknown, onChange: (value: unknown) => void) {
  if (field.type === "textarea") {
    return (
      <textarea
        aria-required={field.required}
        id={id}
        required={field.required}
        value={String(value ?? "")}
        onChange={(event) => onChange(event.target.value)}
      />
    );
  }

  if (field.type === "select" || field.type === "radio") {
    const options = normalizeOptions(field.options);
    return (
      <select
        aria-required={field.required}
        id={id}
        required={field.required}
        value={String(value ?? "")}
        onChange={(event) => onChange(event.target.value)}
      >
        <option value="">Sélectionner</option>
        {options.map((option) => (
          <option key={option.value} value={option.value}>{option.label}</option>
        ))}
      </select>
    );
  }

  if (["checkbox", "boolean", "consent"].includes(field.type)) {
    return (
      <input
        aria-required={field.required}
        checked={Boolean(value)}
        id={id}
        required={field.required}
        type="checkbox"
        onChange={(event) => onChange(event.target.checked)}
      />
    );
  }

  if (field.type === "file") {
    return <DynamicFormFileField field={field} id={id} onChange={onChange} />;
  }

  if (field.type === "phone") {
    return <DynamicFormPhoneField field={field} id={id} value={value} onChange={onChange} />;
  }

  if (field.type === "country" || field.type === "city") {
    return <DynamicFormLocationField field={field} id={id} value={value} onChange={onChange} />;
  }

  if (field.type === "rating") {
    return <DynamicFormRatingField field={field} id={id} value={value} onChange={onChange} />;
  }

  if (field.type === "date_range") {
    return <DynamicFormDateRangeField field={field} id={id} value={value} onChange={onChange} />;
  }

  return (
    <input
      aria-required={field.required}
      id={id}
      required={field.required}
      type={resolveInputType(field.type)}
      value={String(value ?? "")}
      onChange={(event) => onChange(event.target.value)}
    />
  );
}
