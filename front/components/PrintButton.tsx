"use client";

import type { ButtonHTMLAttributes, ReactNode } from "react";

export function PrintButton({ children, onClick, type = "button", ...props }: ButtonHTMLAttributes<HTMLButtonElement> & { children: ReactNode }) {
  return (
    <button
      {...props}
      onClick={(event) => {
        onClick?.(event);
        if (!event.defaultPrevented) {
          window.print();
        }
      }}
      type={type}
    >
      {children}
    </button>
  );
}
