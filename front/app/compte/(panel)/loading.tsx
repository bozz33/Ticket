export default function AccountPanelLoading() {
  return (
    <div className="ac-skeleton" aria-busy="true" aria-label="Chargement en cours">
      <div className="ac-skeleton__title" />
      <div className="ac-skeleton__line" />
      <div className="ac-skeleton__line ac-skeleton__line--short" />
      <div className="ac-skeleton__block" />
    </div>
  );
}
