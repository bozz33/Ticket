"use client";

import { useEffect, useRef } from "react";
import QRCode from "qrcode";

export function QrCode({ value }: { value: string }) {
  const canvasRef = useRef<HTMLCanvasElement>(null);

  useEffect(() => {
    if (!canvasRef.current) return;
    QRCode.toCanvas(canvasRef.current, value, {
      width: 220,
      margin: 2,
      color: { dark: "#111111", light: "#ffffff" },
    }).catch(() => {});
  }, [value]);

  return <canvas ref={canvasRef} />;
}
