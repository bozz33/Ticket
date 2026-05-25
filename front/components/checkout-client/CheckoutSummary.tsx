"use client";

import type { CheckoutPaymentOptions, PublicContent } from "@/lib/types";
import { formatMoney } from "@/lib/utils";
import { ReservationCountdown } from "./ReservationCountdown";

type CheckoutSummaryProps = {
  dateLabel: string;
  error: string | null;
  item: PublicContent;
  loadingPricing: boolean;
  notice: string | null;
  organizerImage: string;
  organizerName: string;
  pricing: CheckoutPaymentOptions["pricing"];
  reservationExpiresAt: string | null;
  selectedOffer: PublicContent["tiers"][number] | null;
  submitting: boolean;
  onSubmit: () => void;
};

export function CheckoutSummary({
  dateLabel,
  error,
  item,
  loadingPricing,
  notice,
  organizerImage,
  organizerName,
  pricing,
  reservationExpiresAt,
  selectedOffer,
  submitting,
  onSubmit,
}: CheckoutSummaryProps) {
  const isCrowdfunding = item.module === "crowdfunding";
  const contributionLabel = selectedOffer?.title ?? "Contribution";

  return (
    <aside className="booking-summary">
      <div className="booking-summary__header">
        <p className="booking-summary__eyebrow">Résumé</p>
        <h2 className="booking-summary__title">{contributionLabel}</h2>
        <p className="booking-summary__subtitle">{item.category}</p>
      </div>

      <div className="booking-summary__publisher">
        <img alt={organizerName} src={organizerImage} />
        <div className="booking-summary__publisher-copy">
          <small>Publié par</small>
          <strong>{organizerName}</strong>
        </div>
      </div>

      <div className="booking-summary__details">
        <div className="booking-summary__detail-row">
          <span className="booking-summary__detail-label">Date</span>
          <span className="booking-summary__detail-value">{dateLabel}</span>
        </div>
        <div className="booking-summary__detail-row">
          <span className="booking-summary__detail-label">Lieu</span>
          <span className="booking-summary__detail-value">
            {item.venueName ?? item.city}, {item.country}
          </span>
        </div>
        <div className="booking-summary__detail-row">
          <span className="booking-summary__detail-label">Format</span>
          <span className="booking-summary__detail-value">{isCrowdfunding ? "Contribution en ligne" : item.format ?? "Présentiel"}</span>
        </div>
        <div className="booking-summary__detail-row">
          <span className="booking-summary__detail-label">Règlement</span>
          <span className="booking-summary__detail-value">
            {pricing.total === 0 ? "Confirmation immédiate" : isCrowdfunding ? "Contribution sécurisée" : "Paystack sécurisé"}
          </span>
        </div>
      </div>

      <div className="booking-summary__price-block">
        {isCrowdfunding ? (
          <div className="booking-summary__price-row">
            <span className="booking-summary__price-label">Palier</span>
            <span className="booking-summary__price-value">{contributionLabel}</span>
          </div>
        ) : null}
        <div className="booking-summary__price-row">
          <span className="booking-summary__price-label">{isCrowdfunding ? "Contribution" : "Sous-total"}</span>
          <span className="booking-summary__price-value">
            {pricing.subtotal === 0 ? "Gratuit" : formatMoney(pricing.subtotal, pricing.currency)}
          </span>
        </div>
        <div className="booking-summary__price-row">
          <span className="booking-summary__price-label">{pricing.service_fee_label ?? "Frais de service"}</span>
          <span className="booking-summary__price-value">
            {pricing.service_fee === 0 ? "—" : formatMoney(pricing.service_fee, pricing.currency)}
          </span>
        </div>
      </div>

      {pricing.service_fee_hint ? (
        <p className="booking-summary__error" style={{ color: "var(--text-soft)" }}>
          {pricing.service_fee_hint}
        </p>
      ) : null}

      <div className="booking-summary__total-block">
        <span className="booking-summary__total-label">Total à payer</span>
        <span className="booking-summary__total-value">
          {pricing.total === 0 ? "Gratuit" : formatMoney(pricing.total, pricing.currency)}
        </span>
      </div>

      <div className="booking-summary__cta">
        <button
          className="button button--full"
          disabled={submitting || loadingPricing}
          onClick={onSubmit}
          type="button"
        >
          {submitting
            ? "Initialisation..."
            : pricing.total === 0
              ? "Confirmer la réservation"
              : isCrowdfunding
                ? "Contribuer maintenant"
                : "Continuer vers le paiement"}
        </button>

        <ReservationCountdown expiresAt={reservationExpiresAt} />
        {notice ? <p className="booking-summary__notice">{notice}</p> : null}
        {error ? <p className="booking-summary__error">{error}</p> : null}
      </div>

      <div className="booking-summary__trust">
        <span>
          {isCrowdfunding
            ? "Contribution sécurisée — aucun pass ni reçu généré après confirmation"
            : "Parcours sécurisé — confirmation serveur avant émission"}
        </span>
      </div>
    </aside>
  );
}
