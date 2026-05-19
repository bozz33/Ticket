import type { DynamicFormField } from "./types";
import { DynamicFormFileField } from "./DynamicFormFileField";
import { DynamicFormLocationField } from "./DynamicFormLocationField";
import { DynamicFormPhoneField } from "./DynamicFormPhoneField";
import { normalizeOptions, resolveInputType } from "./helpers";

export function DynamicFormFieldRenderer({
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

  if (field.type === "file") {
    return <DynamicFormFileField field={field} id={id} onChange={onChange} />;
  }

  if (field.type === "phone") {
    return <DynamicFormPhoneField field={field} id={id} value={value} onChange={onChange} />;
  }

  if (field.type === "country" || field.type === "city") {
    return <DynamicFormLocationField field={field} id={id} value={value} onChange={onChange} />;
  }

  return <input id={id} required={field.required} type={resolveInputType(field.type)} value={String(value ?? "")} onChange={(event) => onChange(event.target.value)} />;
}
