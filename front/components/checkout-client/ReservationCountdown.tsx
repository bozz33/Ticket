"use client";

import { useEffect, useState } from "react";

function formatRemaining(milliseconds: number) {
  const totalSeconds = Math.max(0, Math.floor(milliseconds / 1000));
  const minutes = Math.floor(totalSeconds / 60);
  const seconds = totalSeconds % 60;

  return `${minutes}:${seconds.toString().padStart(2, "0")}`;
}

export function ReservationCountdown({ expiresAt }: { expiresAt: string | null }) {
  const [remaining, setRemaining] = useState(() => expiresAt ? new Date(expiresAt).getTime() - Date.now() : 0);

  useEffect(() => {
    if (!expiresAt) {
      setRemaining(0);
      return;
    }

    const updateRemaining = () => {
      setRemaining(new Date(expiresAt).getTime() - Date.now());
    };

    updateRemaining();
    const interval = window.setInterval(updateRemaining, 1000);

    return () => {
      window.clearInterval(interval);
    };
  }, [expiresAt]);

  if (!expiresAt || remaining <= 0) {
    return null;
  }

  return (
    <p className="booking-summary__notice" role="status">
      Votre ticket est bloqué pendant encore <strong>{formatRemaining(remaining)}</strong>.
    </p>
  );
}
