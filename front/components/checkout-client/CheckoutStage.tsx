"use client";

import type { CheckoutPaymentOptions, PublicContent } from "@/lib/types";
import { GuestContributorForm } from "./GuestContributorForm";
import { QuantitySelector } from "./QuantitySelector";

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
  item,
  loadingPricing,
  quantity,
  quantityBounds,
  guestContributor,
  onQuantityChange,
  onGuestContributorChange,
}: CheckoutStageProps) {
  const isCrowdfunding = item.module === "crowdfunding";

  return (
    <div className="checkout-stage checkout-stage--standard">
      <QuantitySelector
        isCrowdfunding={isCrowdfunding}
        loadingPricing={loadingPricing}
        quantity={quantity}
        quantityBounds={quantityBounds}
        onQuantityChange={onQuantityChange}
      />
      {guestContributor ? (
        <GuestContributorForm
          guestContributor={guestContributor}
          onGuestContributorChange={onGuestContributorChange}
        />
      ) : null}
    </div>
  );
}
