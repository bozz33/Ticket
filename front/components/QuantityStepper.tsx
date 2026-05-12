"use client";

import { useState } from "react";

export function QuantityStepper({ min = 1, max = 10, initialValue = 1 }: { min?: number; max?: number; initialValue?: number }) {
  const [value, setValue] = useState(Math.min(max, Math.max(min, initialValue)));

  return (
    <div className="quantity-stepper">
      <button
        aria-label="Réduire la quantité"
        className="quantity-stepper__button"
        onClick={() => setValue((current) => Math.max(min, current - 1))}
        type="button"
      >
        -
      </button>
      <input className="quantity-stepper__input" min={min} readOnly type="number" value={value} />
      <button
        aria-label="Augmenter la quantité"
        className="quantity-stepper__button"
        onClick={() => setValue((current) => Math.min(max, current + 1))}
        type="button"
      >
        +
      </button>
    </div>
  );
}
