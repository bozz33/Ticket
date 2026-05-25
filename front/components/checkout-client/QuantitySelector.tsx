"use client";

import type { CheckoutPaymentOptions } from "@/lib/types";

type QuantitySelectorProps = {
  isCrowdfunding: boolean;
  loadingPricing: boolean;
  quantity: number;
  quantityBounds: CheckoutPaymentOptions["quantity"];
  onQuantityChange: (quantity: number) => void;
};

export function QuantitySelector({
  isCrowdfunding,
  loadingPricing,
  quantity,
  quantityBounds,
  onQuantityChange,
}: QuantitySelectorProps) {
  const quantityLabel = isCrowdfunding ? "Nombre de contributions" : "Quantité";
  const selectionTitle = isCrowdfunding ? "Contribution" : "Billets";

  return (
    <article className="checkout-stage-card checkout-stage-card--quantity">
      <div className="checkout-stage-card__section-title">
        <div>
          <p className="eyebrow">Sélection</p>
          <h2>{selectionTitle}</h2>
        </div>
        {loadingPricing ? <span className="checkout-stage-card__hint">Mise à jour...</span> : null}
      </div>

      <p className="checkout-stage-card__hint">
        {isCrowdfunding ? "Ajustez le nombre de contributions." : "Ajustez le nombre de billets."}
      </p>

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
          Maximum: {quantityBounds.max_per_account} {isCrowdfunding ? "contribution" : "billet"}
          {quantityBounds.max_per_account > 1 ? "s" : ""}.
        </p>
      ) : null}
      {isCrowdfunding ? (
        <p className="checkout-stage-card__hint">
          {quantityLabel}: {quantity}. Le montant final est affiché dans le résumé avant paiement.
        </p>
      ) : null}
    </article>
  );
}
