"use client";

import { useState } from "react";

export function QuantityStepper({
  min = 1,
  max = 10,
  defaultValue = 1,
  onChange,
}: {
  min?: number;
  max?: number;
  defaultValue?: number;
  onChange?: (v: number) => void;
}) {
  const [value, setValue] = useState(defaultValue);

  const set = (v: number) => {
    setValue(v);
    onChange?.(v);
  };

  return (
    <div className="quantity-stepper">
      <button disabled={value <= min} onClick={() => set(value - 1)}>
        −
      </button>
      <span>{value}</span>
      <button disabled={value >= max} onClick={() => set(value + 1)}>
        +
      </button>
    </div>
  );
}
