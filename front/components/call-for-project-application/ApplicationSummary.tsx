import type { PublicContent } from "@/lib/types";
import { formatDateLabel } from "@/lib/utils";

type ApplicationSummaryProps = {
  item: PublicContent;
};

export function ApplicationSummary({ item }: ApplicationSummaryProps) {
  return (
    <aside className="booking-summary">
      <div className="booking-summary__header">
        <p className="booking-summary__eyebrow">Résumé</p>
        <h2 className="booking-summary__title">{item.title}</h2>
        <p className="booking-summary__subtitle">{item.category}</p>
      </div>

      <div className="booking-summary__details">
        {item.applicationOpensAt ? (
          <div className="booking-summary__detail-row">
            <span className="booking-summary__detail-label">Ouverture</span>
            <span className="booking-summary__detail-value">{formatDateLabel(item.applicationOpensAt)}</span>
          </div>
        ) : null}
        {item.deadlineAt ? (
          <div className="booking-summary__detail-row">
            <span className="booking-summary__detail-label">Clôture</span>
            <span className="booking-summary__detail-value">{formatDateLabel(item.deadlineAt)}</span>
          </div>
        ) : null}
        <div className="booking-summary__detail-row">
          <span className="booking-summary__detail-label">Pays</span>
          <span className="booking-summary__detail-value">Sélection robuste via référentiel local</span>
        </div>
        <div className="booking-summary__detail-row">
          <span className="booking-summary__detail-label">Ville</span>
          <span className="booking-summary__detail-value">Chargée selon le pays choisi</span>
        </div>
        <div className="booking-summary__detail-row">
          <span className="booking-summary__detail-label">Téléphone</span>
          <span className="booking-summary__detail-value">Indicatif synchronisé avec le pays</span>
        </div>
      </div>

      <div className="booking-summary__trust">
        <span>Validation serveur, contrôle des pièces jointes et anti-abus inclus</span>
      </div>
    </aside>
  );
}
