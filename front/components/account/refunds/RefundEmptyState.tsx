export function RefundEmptyState() {
  return (
    <div className="ac-empty">
      <svg
        className="ac-empty__icon"
        fill="none"
        stroke="currentColor"
        strokeLinecap="round"
        strokeLinejoin="round"
        strokeWidth="1.7"
        viewBox="0 0 24 24"
      >
        <path d="M3 12a9 9 0 1 0 3-6.708" />
        <path d="M3 3v6h6" />
        <path d="M12 7v5l3 3" />
      </svg>
      <p className="ac-empty__title">Aucun remboursement</p>
      <p className="ac-empty__text">Quand une demande sera initiée depuis une commande confirmée, elle apparaîtra ici.</p>
    </div>
  );
}
