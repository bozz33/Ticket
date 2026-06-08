"use client";

import { useEffect } from "react";

export default function GlobalError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    void fetch("/api/observability/client-event", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        type: "client-error",
        name: error.name,
        message: error.message,
        path: typeof window === "undefined" ? "" : window.location.pathname,
        stack: error.stack ?? error.digest ?? "",
      }),
      keepalive: true,
    }).catch(() => undefined);
  }, [error]);

  return (
    <html lang="fr">
      <body>
        <main style={{ display: "flex", flexDirection: "column", alignItems: "center", gap: "1rem", padding: "4rem 1.5rem", textAlign: "center", fontFamily: "system-ui, sans-serif" }}>
          <h1>Une erreur critique est survenue</h1>
          <p>L&apos;application a rencontré un problème inattendu.</p>
          <button type="button" onClick={reset} style={{ padding: "0.65rem 1.4rem", cursor: "pointer" }}>
            Recharger
          </button>
        </main>
      </body>
    </html>
  );
}
