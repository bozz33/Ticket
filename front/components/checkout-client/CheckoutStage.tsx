"use client";

import type { CheckoutPaymentOptions, PublicContent } from "@/lib/types";

type CheckoutStageProps = {
  dateLabel: string;
  item: PublicContent;
  loadingPricing: boolean;
  quantity: number;
  quantityBounds: CheckoutPaymentOptions["quantity"];
  selectedOffer: PublicContent["tiers"][number] | null;
  guestContributor: {
    name: string;
    email: string;
    phone: string;
    isAnonymous: boolean;
  } | null;
  onQuantityChange: (quantity: number) => void;
  onGuestContributorChange: (value: {
    name: string;
    email: string;
    phone: string;
    isAnonymous: boolean;
  }) => void;
};

export function CheckoutStage({
  dateLabel,
  item,
  loadingPricing,
  quantity,
  quantityBounds,
  selectedOffer,
  guestContributor,
  onQuantityChange,
  onGuestContributorChange,
}: CheckoutStageProps) {
  return (
    <div className="checkout-stage checkout-stage--standard">
      <div className="checkout-stage__intro">
        <div className="checkout-stage__badges">
          <span className="badge">{item.category}</span>
          <span className="checkout-stage-card__offer">{selectedOffer?.title ?? "Offre principale"}</span>
        </div>
        <h1>{item.title}</h1>
        <p className="section-copy">
          Choisissez la quantité, puis poursuivez sur la page de paiement sécurisée Paystack. Ticket confirme ensuite la commande, le reçu et le pass.
        </p>
        <div className="checkout-stage__meta">
          <span>{dateLabel}</span>
          <span>
            {item.venueName ?? item.city}, {item.country}
          </span>
        </div>
      </div>

      <article className="checkout-stage-card checkout-stage-card--quantity">
        <div className="checkout-stage-card__section-title">
          <div>
            <p className="eyebrow">Quantité</p>
            <h2>Sélection</h2>
          </div>
          {loadingPricing ? <span className="checkout-stage-card__hint">Mise à jour...</span> : null}
        </div>

        <div className="quantity-stepper">
          <button
            aria-label="Réduire la quantité"
            className="quantity-stepper__button"
            disabled={loadingPricing || quantity <= quantityBounds.min}
            onClick={() => onQuantityChange(Math.max(quantityBounds.min, quantity - 1))}
            type="button"
          >
            -
          </button>
          <input
            aria-label="Quantité"
            className="quantity-stepper__input"
            min={quantityBounds.min}
            readOnly
            type="number"
            value={quantity}
          />
          <button
            aria-label="Augmenter la quantité"
            className="quantity-stepper__button"
            disabled={loadingPricing || quantity >= quantityBounds.max}
            onClick={() => onQuantityChange(Math.min(quantityBounds.max, quantity + 1))}
            type="button"
          >
            +
          </button>
        </div>

        {quantityBounds.max_per_account ? (
          <p className="checkout-stage-card__hint">
            Limite par compte: {quantityBounds.max_per_account} billet{quantityBounds.max_per_account > 1 ? "s" : ""}.
          </p>
        ) : null}
      </article>
      {guestContributor ? (
        <article className="checkout-stage-card checkout-stage-card--quantity">
          <div className="checkout-stage-card__section-title">
            <div>
              <p className="eyebrow">Contributeur</p>
              <h2>Vos informations</h2>
            </div>
          </div>
          <div className="form-grid">
            <label className="field">
              <span>Nom</span>
              <input
                onChange={(event) => onGuestContributorChange({ ...guestContributor, name: event.target.value })}
                required
                type="text"
                value={guestContributor.name}
              />
            </label>
            <label className="field">
              <span>E-mail</span>
              <input
                onChange={(event) => onGuestContributorChange({ ...guestContributor, email: event.target.value })}
                required
                type="email"
                value={guestContributor.email}
              />
            </label>
            <label className="field">
              <span>Téléphone</span>
              <input
                onChange={(event) => onGuestContributorChange({ ...guestContributor, phone: event.target.value })}
                type="tel"
                value={guestContributor.phone}
              />
            </label>
            <label className="checkbox-field">
              <input
                checked={guestContributor.isAnonymous}
                onChange={(event) => onGuestContributorChange({ ...guestContributor, isAnonymous: event.target.checked })}
                type="checkbox"
              />
              <span>Contribuer anonymement si les contributeurs sont affichés plus tard</span>
            </label>
          </div>
        </article>
      ) : null}
    </div>
  );
}
