"use client";

import { useMemo, useRef, useState } from "react";

import { getCheckoutPaymentOptions, initializeCheckoutPayment } from "@/lib/client/checkout";
import { getCheckoutInitializationIds, getCheckoutSelectionParamName } from "@/lib/checkout/selection";
import { GuestContributorForm } from "@/components/checkout-client/GuestContributorForm";
import { ReservationCountdown } from "@/components/checkout-client/ReservationCountdown";
import { releaseReservedEventTicket, reserveSelectedEventTicket } from "@/components/checkout-client/reservations";
import { buildCheckoutCallbackUrl, buildCheckoutSuccessUrl } from "@/components/checkout-client/urls";
import type { AccountUser, CheckoutPaymentOptions, ModuleRoute, PublicContent } from "@/lib/types";
import { formatMoney } from "@/lib/utils";

export type CheckoutClientProps = {
  item: PublicContent;
  selectedOffer: PublicContent["tiers"][number] | null;
  dateLabel: string;
  initialPaymentOptions: CheckoutPaymentOptions | null;
  accountUser: AccountUser | null;
  loginUrl: string;
};

function normalizeContributionAmount(value: number) {
  if (!Number.isFinite(value)) {
    return 1;
  }

  return Math.max(1, Math.trunc(value));
}

export function CheckoutProforma({
  item,
  selectedOffer,
  dateLabel,
  initialPaymentOptions,
  accountUser,
  loginUrl,
}: CheckoutClientProps) {
  const pricingCache = useRef(new Map<string, CheckoutPaymentOptions>());
  const isCrowdfunding = item.module === "crowdfunding";
  const initialContributionAmount = normalizeContributionAmount(
    initialPaymentOptions?.pricing.subtotal ??
      (selectedOffer?.price && selectedOffer.price > 0 ? selectedOffer.price : item.priceFrom || 1000),
  );

  const [quantity, setQuantity] = useState(initialPaymentOptions?.pricing.quantity ?? 1);
  const [currentOffer, setCurrentOffer] = useState(selectedOffer);
  const [customAmount, setCustomAmount] = useState(initialContributionAmount);
  const [paymentOptions, setPaymentOptions] = useState<CheckoutPaymentOptions | null>(initialPaymentOptions);
  const [paymentOptionsSelectionId, setPaymentOptionsSelectionId] = useState(selectedOffer?.id ?? null);
  const [paymentOptionsCustomAmount, setPaymentOptionsCustomAmount] = useState<number | null>(
    isCrowdfunding ? initialContributionAmount : null,
  );
  const [loadingPricing, setLoadingPricing] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [reservationExpiresAt, setReservationExpiresAt] = useState<string | null>(null);
  const [guestContributor, setGuestContributor] = useState({
    name: "",
    email: "",
    phone: "",
    isAnonymous: false,
  });
  const paymentOptionsMatchCurrentOffer = Boolean(
    paymentOptions &&
      paymentOptionsSelectionId === currentOffer?.id &&
      (!isCrowdfunding || paymentOptionsCustomAmount === customAmount),
  );
  const activePaymentOptions = paymentOptionsMatchCurrentOffer ? paymentOptions : null;
  const organizerName = item.organizers[0]?.name ?? "Organisateur";
  const organizerImage = item.organizers[0]?.imageUrl ?? item.coverImageUrl;
  const quantityBounds = activePaymentOptions?.quantity ?? {
    min: 1,
    max: Math.max(1, currentOffer?.remaining ?? 10),
    max_per_account: null,
  };
  const pricingSynced = Boolean(
    activePaymentOptions &&
      activePaymentOptions.pricing.quantity === quantity &&
      (!isCrowdfunding || paymentOptionsCustomAmount === customAmount),
  );
  const estimatedSubtotal = isCrowdfunding
    ? customAmount
    : (currentOffer?.price ?? item.priceFrom) * quantity;
  const estimatedPricing = {
    subtotal: estimatedSubtotal,
    service_fee: 0,
    service_fee_label: "Frais de service",
    service_fee_hint: null,
    total: estimatedSubtotal,
    currency: currentOffer?.currency ?? item.currency,
    quantity: isCrowdfunding ? 1 : quantity,
  };
  const pricing = pricingSynced ? activePaymentOptions?.pricing ?? estimatedPricing : estimatedPricing;
  const selectedPaymentMethod = activePaymentOptions?.methods[0]?.code ?? (isCrowdfunding || pricing.total > 0 ? "card" : "free");
  const checkoutDate = new Intl.DateTimeFormat("fr-FR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).format(new Date());
  const checkoutReference = activePaymentOptions?.proforma_reference ?? `ORD-${(currentOffer?.id ?? item.slug).replace(/[^a-zA-Z0-9]/g, "").slice(0, 10).toUpperCase()}`;
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
    pricingCache.current.set(
      `${initialCheckoutItemId}:${initialPaymentOptions.pricing.quantity}:${selectedPaymentMethod}:${isCrowdfunding ? initialContributionAmount : 0}`,
      initialPaymentOptions,
    );
  }
  async function refreshPricing(
    nextQuantity: number,
    offerOverride = currentOffer,
    paymentMethodOverride?: string,
    contributionAmountOverride = customAmount,
  ) {
    if (!offerOverride?.id) {
      return;
    }

    const contributionAmount = isCrowdfunding ? normalizeContributionAmount(contributionAmountOverride) : undefined;
    const safeQuantity = isCrowdfunding ? 1 : nextQuantity;
    const paymentMethod = paymentMethodOverride ?? (isCrowdfunding || offerOverride.price > 0 ? "card" : "free");
    const cacheKey = `${offerOverride.id}:${safeQuantity}:${paymentMethod}:${contributionAmount ?? 0}`;
    const cachedOptions = pricingCache.current.get(cacheKey);

    if (cachedOptions) {
      setPaymentOptions(cachedOptions);
      setPaymentOptionsSelectionId(offerOverride.id);
      setPaymentOptionsCustomAmount(contributionAmount ?? null);
      setQuantity(cachedOptions.pricing.quantity);
      setError(null);
      return cachedOptions;
    }
    setLoadingPricing(true);
    setError(null);

    try {
      const nextOptions = await getCheckoutPaymentOptions(
        offerOverride.id,
        safeQuantity,
        paymentMethod,
        item.organizerSlug,
        getCheckoutSelectionParamName(offerOverride),
        contributionAmount,
      );

      if (nextOptions) {
        pricingCache.current.set(cacheKey, nextOptions);
        setPaymentOptions(nextOptions);
        setPaymentOptionsSelectionId(offerOverride.id);
        setPaymentOptionsCustomAmount(contributionAmount ?? null);
        setQuantity(nextOptions.pricing.quantity);
        return nextOptions;
      }

      setQuantity(safeQuantity);
      setError(isCrowdfunding ? "Impossible de recalculer le montant de contribution." : "Impossible de recalculer le montant pour cette quantité.");
    } catch {
      setQuantity(safeQuantity);
      setError("Impossible de recalculer le montant. Vérifiez votre connexion puis réessayez.");
    } finally {
      setLoadingPricing(false);
    }
  }
  async function handleSubmit() {
    if (!currentOffer?.id) {
      setError("Aucune offre sélectionnée.");
      return;
    }

    if (!pricingSynced) {
      if (!isCrowdfunding) {
        setError("Le montant est en cours de recalcul. Patientez quelques secondes.");
        return;
      }

      const refreshedOptions = await refreshPricing(1, currentOffer, "card", customAmount);

      if (!refreshedOptions) {
        setError("Impossible de valider ce montant de contribution.");
        return;
      }
    }

    if (currentOffer.source === "event_ticket" && currentOffer.isAvailable === false) {
      setError(currentOffer.availabilityLabel ?? "Ce ticket est indisponible.");
      return;
    }

    if (!accountUser && !isCrowdfunding) {
      window.location.assign(loginUrl);
      return;
    }

    if (accountUser && !isCrowdfunding && (!accountReadyForActions || missingCheckoutFields.length > 0)) {
      setError("Avant de continuer, complétez votre profil et vérifiez votre e-mail.");
      return;
    }

    if (!accountUser && isCrowdfunding) {
      if (!guestContributor.name.trim() || !guestContributor.email.trim()) {
        setError("Indiquez votre nom et votre adresse e-mail pour enregistrer la contribution.");
        return;
      }
    }

    setSubmitting(true);
    setError(null);
    setNotice(null);
    setReservationExpiresAt(null);

    try {
      const callbackUrl = buildCheckoutCallbackUrl(item, currentOffer);

      const reservation = await reserveSelectedEventTicket({
        selectedOffer: currentOffer,
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
        ...getCheckoutInitializationIds(currentOffer),
        quantity: isCrowdfunding ? 1 : quantity,
        payment_method: selectedPaymentMethod,
        custom_amount: isCrowdfunding ? customAmount : undefined,
        buyer_name: !accountUser && isCrowdfunding ? guestContributor.name.trim() : undefined,
        buyer_email: !accountUser && isCrowdfunding ? guestContributor.email.trim() : undefined,
        buyer_phone: !accountUser && isCrowdfunding ? guestContributor.phone.trim() || undefined : undefined,
        content_module: item.module as ModuleRoute,
        content_slug: item.slug,
        contributor_display_name: !accountUser && isCrowdfunding && !guestContributor.isAnonymous ? guestContributor.name.trim() : undefined,
        contributor_is_anonymous: !accountUser && isCrowdfunding ? guestContributor.isAnonymous : undefined,
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

      window.location.assign(buildCheckoutSuccessUrl(item, currentOffer, result.reference));
    } finally {
      setSubmitting(false);
    }
  }

  async function handleTicketChange(nextSelectionId: string) {
    const nextSelection = item.tiers.find((tier) => tier.id === nextSelectionId);

    if (!nextSelection) {
      return;
    }

    setCurrentOffer(nextSelection);
    const nextContributionAmount = isCrowdfunding
      ? normalizeContributionAmount(nextSelection.price > 0 ? nextSelection.price : customAmount)
      : customAmount;
    setQuantity(1);
    setCustomAmount(nextContributionAmount);
    setPaymentOptions(null);
    setPaymentOptionsSelectionId(null);
    setPaymentOptionsCustomAmount(null);
    const url = new URL(window.location.href);
    url.searchParams.delete("offer");
    url.searchParams.delete("ticket");
    url.searchParams.set(getCheckoutSelectionParamName(nextSelection), nextSelection.id);
    window.history.replaceState(null, "", url.toString());
    const nextOptions = await refreshPricing(
      1,
      nextSelection,
      isCrowdfunding || nextSelection.price > 0 ? "card" : "free",
      nextContributionAmount,
    );
    if (!nextOptions) {
      setPaymentOptions(null);
    }
  }

  return (
    <section className="section section--tight">
      <div className="shell checkout-proforma">
        <div className="checkout-proforma__top">
          <div className="checkout-proforma__brand-card">
            <div className="checkout-proforma__logo">Ticket</div>
            <strong>{organizerName}</strong>
            <span>{isCrowdfunding ? "Service de contribution" : "Service billetterie"}</span>
            <span>{item.venueName ?? item.city}</span>
            <span>
              {item.city}, {item.country}
            </span>
          </div>

          <div className="checkout-proforma__buyer">
            <span>Abidjan le {checkoutDate}</span>
            <strong>{accountUser?.name ?? (guestContributor.name || "Client invité")}</strong>
            <span>{accountUser?.email ?? (guestContributor.email || "Informations à confirmer")}</span>
            <span>{accountUser?.phone ?? (guestContributor.phone || item.country)}</span>
          </div>
        </div>

        <div className="checkout-proforma__table">
          <div className="checkout-proforma__table-head">
            <span>Services</span>
            <span>Détails</span>
            <span>Tarif</span>
          </div>

          <div className="checkout-proforma__service-row">
            <div>
              <strong>{currentOffer?.title ?? (isCrowdfunding ? "Contribution" : "Ticket principal")}</strong>
              <span>
                {item.title} · {dateLabel}
              </span>
            </div>
            <span>{isCrowdfunding ? "Contribution en ligne" : currentOffer?.availabilityLabel ?? item.format ?? "Présentiel"}</span>
            <div className="checkout-proforma__total-box">
              <span>Sous-total : {formatMoney(pricing.subtotal, pricing.currency)}</span>
              <span>{pricing.service_fee_label ?? "Frais"} : {pricing.service_fee === 0 ? "—" : formatMoney(pricing.service_fee, pricing.currency)}</span>
              <strong>{formatMoney(pricing.total, pricing.currency)} TTC</strong>
              {!pricingSynced ? <em>{loadingPricing ? "Validation du montant..." : "Montant à valider"}</em> : null}
            </div>
          </div>

          <div className="checkout-proforma__edit-row">
            <span className="checkout-proforma__chevron">›</span>
            <label htmlFor="checkout-ticket-select">{isCrowdfunding ? "Choisir un palier :" : "Changer de ticket :"}</label>
            <select
              id="checkout-ticket-select"
              onChange={(event) => handleTicketChange(event.target.value)}
              value={currentOffer?.id ?? ""}
            >
              {item.tiers.map((tier) => (
                <option disabled={tier.isAvailable === false} key={tier.id} value={tier.id}>
                  {tier.title} · {tier.price > 0 ? formatMoney(tier.price, tier.currency) : "Montant libre"}
                </option>
              ))}
            </select>
          </div>

          {isCrowdfunding ? (
            <div className="checkout-proforma__quantity-row checkout-proforma__custom-amount-row">
              <label htmlFor="checkout-custom-amount">Montant personnalisé</label>
              <div className="checkout-proforma__custom-amount">
                <input
                  id="checkout-custom-amount"
                  inputMode="numeric"
                  min={1}
                  onBlur={() => {
                    void refreshPricing(1, currentOffer, "card", customAmount);
                  }}
                  onChange={(event) => {
                    const nextAmount = normalizeContributionAmount(Number(event.target.value));
                    setCustomAmount(nextAmount);
                    setPaymentOptions(null);
                    setPaymentOptionsSelectionId(null);
                    setPaymentOptionsCustomAmount(null);
                  }}
                  type="number"
                  value={customAmount}
                />
                <span>{currentOffer?.currency ?? item.currency}</span>
              </div>
              <small>Montant libre, contribution unique, sans pass ni reçu post-paiement.</small>
            </div>
          ) : (
            <div className="checkout-proforma__quantity-row">
              <span>Quantité</span>
              <div className="checkout-proforma__quantity">
                <button
                  disabled={loadingPricing || quantity <= quantityBounds.min}
                  onClick={() => {
                    const nextQuantity = Math.max(quantityBounds.min, quantity - 1);
                    setQuantity(nextQuantity);
                    setPaymentOptions(null);
                    setPaymentOptionsSelectionId(null);
                    setPaymentOptionsCustomAmount(null);
                    void refreshPricing(nextQuantity);
                  }}
                  type="button"
                >
                  -
                </button>
                <strong>{quantity}</strong>
                <button
                  disabled={loadingPricing || quantity >= quantityBounds.max}
                  onClick={() => {
                    const nextQuantity = Math.min(quantityBounds.max, quantity + 1);
                    setQuantity(nextQuantity);
                    setPaymentOptions(null);
                    setPaymentOptionsSelectionId(null);
                    setPaymentOptionsCustomAmount(null);
                    void refreshPricing(nextQuantity);
                  }}
                  type="button"
                >
                  +
                </button>
              </div>
              <small>
                {quantityBounds.max_per_account
                  ? `Maximum ${quantityBounds.max_per_account} par compte`
                  : `Maximum ${quantityBounds.max} billet${quantityBounds.max > 1 ? "s" : ""}`}
              </small>
            </div>
          )}
        </div>

        <div className="checkout-proforma__reference">
          <strong>{isCrowdfunding ? "RÉCAPITULATIF DE CONTRIBUTION" : "FACTURE PROFORMA"}</strong> · Référence commande <strong>{checkoutReference}</strong>
        </div>

        {!accountUser && isCrowdfunding ? (
          <div className="checkout-proforma__guest">
            <GuestContributorForm guestContributor={guestContributor} onGuestContributorChange={setGuestContributor} />
          </div>
        ) : null}

        <div className="checkout-proforma__payment">
          <h2>{isCrowdfunding ? "Pour régler cette contribution :" : "Pour régler cette commande :"}</h2>
          <div className="checkout-proforma__methods">
            <button
              className="checkout-proforma__method"
              disabled={submitting || loadingPricing || (!pricingSynced && !isCrowdfunding)}
              onClick={() => {
                void handleSubmit();
              }}
              type="button"
            >
              <strong>{pricing.total === 0 && !isCrowdfunding ? "Gratuit" : "Paystack"}</strong>
              <span>{pricing.total === 0 && !isCrowdfunding ? "Confirmer la réservation" : "Carte bancaire / Mobile money"}</span>
            </button>
          </div>
          <ReservationCountdown expiresAt={reservationExpiresAt} />
          {notice ? <p className="checkout-proforma__notice">{notice}</p> : null}
          {error ? <p className="checkout-proforma__error">{error}</p> : null}
        </div>

        <div className="checkout-proforma__help">
          <strong>Besoin d’aide ?</strong>
          <a href={`/${item.module}/${item.slug}`}>Revenir à la page détail</a>
          <a href="/contact">Contacter le support Ticket</a>
        </div>
      </div>
    </section>
  );
}
