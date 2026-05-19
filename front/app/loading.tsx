export default function GlobalLoading() {
  return (
    <div className="route-loading" role="status" aria-live="polite" aria-label="Chargement en cours">
      <div className="route-loading__panel">
        <span className="route-loading__spinner" />
        <p>Chargement de la page…</p>
      </div>
    </div>
  );
}
