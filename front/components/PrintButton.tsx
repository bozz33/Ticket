"use client";

import type { CSSProperties } from "react";

export function PrintButton({
  children,
  className,
  style,
}: {
  children: React.ReactNode;
  className?: string;
  style?: CSSProperties;
}) {
  return (
    <button className={className} style={style} onClick={() => window.print()}>
      {children}
    </button>
  );
}
