"use client";

import type { DynamicFormField } from "./types";

interface DateRange {
  start?: string;
  end?: string;
}

export function DynamicFormDateRangeField({
  field,
  id,
  value,
  onChange,
}: {
  field: DynamicFormField;
  id: string;
  value: unknown;
  onChange: (value: unknown) => void;
}) {
  const range: DateRange = (value && typeof value === "object") ? (value as DateRange) : {};

  function update(key: "start" | "end", date: string) {
    onChange({ ...range, [key]: date });
  }

  return (
    <div className="dynamic-form__date-range">
      <div>
        <label htmlFor={`${id}-start`}>Du</label>
        <input
          id={`${id}-start`}
          max={range.end ?? undefined}
          required={field.required}
          type="date"
          value={range.start ?? ""}
          onChange={(e) => update("start", e.target.value)}
        />
      </div>
      <div>
        <label htmlFor={`${id}-end`}>Au</label>
        <input
          id={`${id}-end`}
          min={range.start ?? undefined}
          required={field.required}
          type="date"
          value={range.end ?? ""}
          onChange={(e) => update("end", e.target.value)}
        />
      </div>
    </div>
  );
}
