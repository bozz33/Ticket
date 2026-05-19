"use client";

import type { CheckoutPaymentOptions, PublicContent } from "@/lib/types";
import { formatMoney } from "@/lib/utils";

type CheckoutSummaryProps = {
  dateLabel: string;
  error: string | null;
  item: PublicContent;
  loadingPricing: boolean;
  notice: string | null;
  organizerImage: string;
  organizerName: string;
  pricing: CheckoutPaymentOptions["pricing"];
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
  selectedOffer,
  submitting,
  onSubmit,
}: CheckoutSummaryProps) {
  return (
    <aside className="booking-summary">
      <div className="booking-summary__header">
        <p className="booking-summary__eyebrow">Resume</p>
        <h2 className="booking-summary__title">{selectedOffer?.title ?? "Offre principale"}</h2>
        <p className="booking-summary__subtitle">{item.category}</p>
      </div>

      <div className="booking-summary__publisher">
        <img alt={organizerName} src={organizerImage} />
        <div className="booking-summary__publisher-copy">
          <small>Publie par</small>
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
          <span className="booking-summary__detail-value">{item.format ?? "Presentiel"}</span>
        </div>
        <div className="booking-summary__detail-row">
          <span className="booking-summary__detail-label">Règlement</span>
          <span className="booking-summary__detail-value">
            {pricing.total === 0 ? "Confirmation immédiate" : "Paystack sécurisé"}
          </span>
        </div>
      </div>

      <div className="booking-summary__price-block">
        <div className="booking-summary__price-row">
          <span className="booking-summary__price-label">Sous-total</span>
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
        <span className="booking-summary__total-label">Total a payer</span>
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
          {submitting ? "Initialisation..." : pricing.total === 0 ? "Confirmer la réservation" : "Continuer vers le paiement"}
        </button>

        {notice ? <p className="booking-summary__notice">{notice}</p> : null}
        {error ? <p className="booking-summary__error">{error}</p> : null}
      </div>

      <div className="booking-summary__trust">
        <span>Parcours sécurisé — confirmation serveur avant émission</span>
      </div>
    </aside>
  );
}
