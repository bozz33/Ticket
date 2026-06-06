"use client";

export default function AccountPanelError({
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {

  return (
    <div className="ac-error-boundary">
      <h2>Une erreur est survenue</h2>
      <p>Impossible de charger cette section pour le moment.</p>
      <button className="button" type="button" onClick={reset}>
        Réessayer
      </button>
    </div>
  );
}
