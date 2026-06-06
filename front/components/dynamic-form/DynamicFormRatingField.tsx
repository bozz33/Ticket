"use client";

import type { DynamicFormField } from "./types";

export function DynamicFormRatingField({
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
  const max = field.max_rating ?? 5;
  const current = typeof value === "number" ? value : 0;

  return (
    <div
      aria-label={field.label ?? field.key}
      aria-required={field.required}
      aria-valuenow={current}
      aria-valuemin={0}
      aria-valuemax={max}
      className="dynamic-form__rating"
      id={id}
      role="slider"
    >
      {Array.from({ length: max }, (_, i) => i + 1).map((star) => (
        <button
          aria-checked={current >= star}
          aria-label={`${star} étoile${star > 1 ? "s" : ""}`}
          className={`dynamic-form__rating-star${current >= star ? " dynamic-form__rating-star--active" : ""}`}
          key={star}
          role="radio"
          type="button"
          onClick={() => onChange(current === star ? 0 : star)}
          onKeyDown={(e) => {
            if (e.key === "ArrowRight" && current < max) onChange(current + 1);
            if (e.key === "ArrowLeft" && current > 0) onChange(current - 1);
          }}
        >
          ★
        </button>
      ))}
    </div>
  );
}
