"use client";

import { useMemo, useRef, useState } from "react";

import { getCheckoutPaymentOptions, initializeCheckoutPayment } from "@/lib/client/checkout";
import { getCheckoutInitializationIds, getCheckoutSelectionParamName } from "@/lib/checkout/selection";
import { CheckoutStage } from "@/components/checkout-client/CheckoutStage";
import { CheckoutSummary } from "@/components/checkout-client/CheckoutSummary";
import { releaseReservedEventTicket, reserveSelectedEventTicket } from "@/components/checkout-client/reservations";
import { buildCheckoutCallbackUrl, buildCheckoutSuccessUrl } from "@/components/checkout-client/urls";
import type { AccountUser, CheckoutPaymentOptions, ModuleRoute, PublicContent } from "@/lib/types";

export function CheckoutClient({
  item,
  selectedOffer,
  dateLabel,
  initialPaymentOptions,
  accountUser,
  loginUrl,
}: {
  item: PublicContent;
  selectedOffer: PublicContent["tiers"][number] | null;
  dateLabel: string;
  initialPaymentOptions: CheckoutPaymentOptions | null;
  accountUser: AccountUser | null;
  loginUrl: string;
}) {
  const pricingCache = useRef(new Map<string, CheckoutPaymentOptions>());

  const [quantity, setQuantity] = useState(initialPaymentOptions?.pricing.quantity ?? 1);
  const [paymentOptions, setPaymentOptions] = useState<CheckoutPaymentOptions | null>(initialPaymentOptions);
  const [loadingPricing, setLoadingPricing] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [reservationExpiresAt, setReservationExpiresAt] = useState<string | null>(null);
  const organizerName = item.organizers[0]?.name ?? "Organisateur";
  const organizerImage = item.organizers[0]?.imageUrl ?? item.coverImageUrl;
  const quantityBounds = paymentOptions?.quantity ?? { min: 1, max: 10 };
  const pricing = paymentOptions?.pricing ?? {
    subtotal: (selectedOffer?.price ?? item.priceFrom) * quantity,
    service_fee: 0,
    service_fee_label: "Frais de service",
    service_fee_hint: null,
    total: (selectedOffer?.price ?? item.priceFrom) * quantity,
    currency: item.currency,
    quantity,
  };
  const selectedPaymentMethod = paymentOptions?.methods[0]?.code ?? (pricing.total === 0 ? "free" : "card");
  const accountReadyForActions = Boolean(accountUser?.account_ready_for_actions);
  const buyerEmail = accountUser?.email?.trim() ?? "";
  const buyerPhone = accountUser?.phone?.trim() ?? "";
  const missingCheckoutFields = useMemo(() => {
    const missing: string[] = [];
    if (!buyerEmail) {
      missing.push("adresse e-mail");
    }
    if (!buyerPhone) {
      missing.push("numéro de téléphone");
    }
    return missing;
  }, [buyerEmail, buyerPhone]);
  if (initialPaymentOptions && pricingCache.current.size === 0) {
    const initialCheckoutItemId =
      initialPaymentOptions.checkout_item?.id ??
      initialPaymentOptions.ticket?.id ??
      initialPaymentOptions.offer.id;
    pricingCache.current.set(`${initialCheckoutItemId}:${initialPaymentOptions.pricing.quantity}:${selectedPaymentMethod}`, initialPaymentOptions);
  }
  async function refreshPricing(nextQuantity: number) {
    if (!selectedOffer?.id) {
      return;
    }

    const cacheKey = `${selectedOffer.id}:${nextQuantity}:${selectedPaymentMethod}`;
    const cachedOptions = pricingCache.current.get(cacheKey);

    if (cachedOptions) {
      setPaymentOptions(cachedOptions);
      setQuantity(cachedOptions.pricing.quantity);
      setError(null);
      return;
    }
    setLoadingPricing(true);
    setError(null);

    try {
      const nextOptions = await getCheckoutPaymentOptions(
        selectedOffer.id,
        nextQuantity,
        selectedPaymentMethod,
        item.organizerSlug,
        getCheckoutSelectionParamName(selectedOffer),
      );

      if (nextOptions) {
        pricingCache.current.set(cacheKey, nextOptions);
        setPaymentOptions(nextOptions);
        setQuantity(nextOptions.pricing.quantity);
        return;
      }

      setQuantity(nextQuantity);
    } catch {
      setQuantity(nextQuantity);
    } finally {
      setLoadingPricing(false);
    }
  }
  async function handleSubmit() {
    if (!selectedOffer?.id) {
      setError("Aucune offre sélectionnée.");
      return;
    }

    if (selectedOffer.source === "event_ticket" && selectedOffer.isAvailable === false) {
      setError(selectedOffer.availabilityLabel ?? "Ce ticket est indisponible.");
      return;
    }

    if (!accountUser) {
      window.location.assign(loginUrl);
      return;
    }

    if (!accountReadyForActions || missingCheckoutFields.length > 0) {
      setError("Avant de continuer, complétez votre profil et vérifiez votre e-mail.");
      return;
    }

    setSubmitting(true);
    setError(null);
    setNotice(null);
    setReservationExpiresAt(null);

    try {
      const callbackUrl = buildCheckoutCallbackUrl(item, selectedOffer);

      const reservation = await reserveSelectedEventTicket({
        selectedOffer,
        quantity,
        tenant: item.organizerSlug,
      });

      if (reservation.error) {
        setError(reservation.error);
        return;
      }

      const ticketReservationId = reservation.reservationId;
      if (reservation.notice) {
        setNotice(reservation.notice);
      }

      setReservationExpiresAt(reservation.expiresAt ?? null);
      const result = await initializeCheckoutPayment({
        ...getCheckoutInitializationIds(selectedOffer),
        quantity,
        payment_method: selectedPaymentMethod,
        content_module: item.module as ModuleRoute,
        content_slug: item.slug,
        callback_url: callbackUrl.toString(),
        tenant: item.organizerSlug,
        ticket_reservation: ticketReservationId,
      });

      if (!result) {
        if (ticketReservationId) {
          releaseReservedEventTicket(ticketReservationId, item.organizerSlug);
        }
        setError("Impossible de contacter le serveur de paiement.");
        return;
      }

      if ("error" in result) {
        if (ticketReservationId) {
          releaseReservedEventTicket(ticketReservationId, item.organizerSlug);
        }
        setError(result.error);
        return;
      }

      if (result.authorization_url) {
        window.location.assign(result.authorization_url);
        return;
      }

      window.location.assign(buildCheckoutSuccessUrl(item, selectedOffer, result.reference));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <section className="section section--tight">
      <div className="shell checkout-layout checkout-layout--compact">
        <CheckoutStage
          dateLabel={dateLabel}
          item={item}
          loadingPricing={loadingPricing}
          quantity={quantity}
          quantityBounds={quantityBounds}
          selectedOffer={selectedOffer}
          onQuantityChange={(nextQuantity) => {
            void refreshPricing(nextQuantity);
          }}
        />

        <CheckoutSummary
          dateLabel={dateLabel}
          error={error}
          item={item}
          loadingPricing={loadingPricing}
          notice={notice}
          organizerImage={organizerImage}
          organizerName={organizerName}
          pricing={pricing}
          reservationExpiresAt={reservationExpiresAt}
          selectedOffer={selectedOffer}
          submitting={submitting}
          onSubmit={() => {
            void handleSubmit();
          }}
        />
      </div>
    </section>
  );
}
