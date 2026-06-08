"use client";

import { useEffect } from "react";

export default function AppError({
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
    <section className="section">
      <div className="shell page-error-boundary">
        <h1>Une erreur est survenue</h1>
        <p>Un problème inattendu s&apos;est produit. Vous pouvez réessayer.</p>
        <button className="button button--primary" type="button" onClick={reset}>
          Réessayer
        </button>
      </div>
    </section>
  );
}
