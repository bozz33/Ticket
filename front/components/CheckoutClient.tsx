"use client";

import Link from "next/link";
import { useMemo, useState } from "react";

import { initializeCheckoutPayment, getCheckoutPaymentOptions } from "@/lib/data/public";
import type {
  AccountUser,
  CheckoutPaymentOptions,
  ModuleRoute,
  PlatformConfiguration,
  PublicContent,
} from "@/lib/types";
import { formatMoney } from "@/lib/utils";

export function CheckoutClient({
  item,
  selectedOffer,
  platform,
  dateLabel,
  initialPaymentOptions,
  accountUser,
  loginUrl,
}: {
  item: PublicContent;
  selectedOffer: PublicContent["tiers"][number] | null;
  platform: PlatformConfiguration;
  dateLabel: string;
  initialPaymentOptions: CheckoutPaymentOptions | null;
  accountUser: AccountUser | null;
  loginUrl: string;
}) {
  const [quantity, setQuantity] = useState(initialPaymentOptions?.pricing.quantity ?? 1);
  const [paymentOptions, setPaymentOptions] = useState<CheckoutPaymentOptions | null>(initialPaymentOptions);
  const [loadingPricing, setLoadingPricing] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [resendingVerification, setResendingVerification] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const organizerName = item.organizers[0]?.name ?? "Organisateur";
  const organizerImage = item.organizers[0]?.imageUrl ?? item.coverImageUrl;
  const quantityBounds = paymentOptions?.quantity ?? { min: 1, max: 10 };
  const missingProfileLabels = useMemo(() => {
    const fieldMap: Record<string, string> = {
      first_name: "prénom",
      last_name: "nom",
      phone: "numéro de téléphone",
    };

    return (accountUser?.missing_profile_fields ?? []).map((field) => fieldMap[field] ?? field);
  }, [accountUser]);
  const accountReadyForActions = Boolean(accountUser?.account_ready_for_actions);
  const pricing = paymentOptions?.pricing ?? {
    subtotal: (selectedOffer?.price ?? item.priceFrom) * quantity,
    service_fee: 0,
    total: (selectedOffer?.price ?? item.priceFrom) * quantity,
    currency: item.currency,
    quantity,
  };
  const methodLabels = useMemo(
    () => paymentOptions?.methods.map((method) => method.label) ?? platform.paymentMethods,
    [paymentOptions, platform.paymentMethods],
  );

  async function refreshPricing(nextQuantity: number) {
    if (!selectedOffer?.id) {
      return;
    }

    setLoadingPricing(true);
    setError(null);

    try {
      const nextOptions = await getCheckoutPaymentOptions(selectedOffer.id, nextQuantity);
      if (nextOptions) {
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

    if (!accountUser) {
      setError("Connectez-vous à votre compte acheteur pour continuer.");
      return;
    }

    if (!accountReadyForActions) {
      setError("Avant de continuer, complétez votre profil et vérifiez votre e-mail.");
      return;
    }

    setSubmitting(true);
    setError(null);

    const callbackUrl = new URL(`/checkout/${item.module}/${item.slug}/succes`, window.location.origin);
    callbackUrl.searchParams.set("offer", selectedOffer.id);

    const result = await initializeCheckoutPayment({
      offer: selectedOffer.id,
      quantity,
      content_module: item.module as ModuleRoute,
      content_slug: item.slug,
      callback_url: callbackUrl.toString(),
    });

    if (!result) {
      setError("Impossible de contacter le serveur de paiement.");
      setSubmitting(false);
      return;
    }

    if ("error" in result) {
      setError(result.error);
      setSubmitting(false);
      return;
    }

    if (result.mode === "redirect" && result.authorization_url) {
      window.location.assign(result.authorization_url);
      return;
    }

    const successUrl = new URL(`/checkout/${item.module}/${item.slug}/succes`, window.location.origin);
    successUrl.searchParams.set("offer", selectedOffer.id);
    successUrl.searchParams.set("tx", result.reference);
    window.location.assign(successUrl.toString());
  }

  async function handleResendVerification() {
    setResendingVerification(true);
    setError(null);
    setNotice(null);

    try {
      const response = await fetch("/api/account/email/verification-notification", {
        method: "POST",
        headers: { Accept: "application/json" },
      });
      const payload = await response.json();

      if (!response.ok) {
        setError(payload?.error ?? "Impossible d'envoyer le lien de vérification.");
        return;
      }

      setNotice(payload?.message ?? "Lien de vérification envoyé.");
    } catch {
      setError("Impossible de contacter le serveur.");
    } finally {
      setResendingVerification(false);
    }
  }

  return (
    <>
      <section className="page-hero page-hero--compact">
        <div className="shell page-hero__content">
          <p className="eyebrow">Checkout</p>
          <h1>{item.title}</h1>
          <p>Parcours court, rassurant et lisible jusqu'au paiement.</p>
          <div className="page-hero__pills">
            <span>{selectedOffer?.title ?? "Offre principale"}</span>
            <span>{dateLabel}</span>
          </div>
        </div>
      </section>

      <section className="section">
        <div className="shell checkout-layout">
          <div className="checkout-form">
            <div className="checkout-head">
              <div>
                <span className="badge">{item.category}</span>
                <h2>{selectedOffer?.title ?? "Offre principale"}</h2>
                <p className="section-copy">
                  Acheteur, quantite, paiement et confirmation reunis dans une seule surface.
                </p>
              </div>
              <Link className="publisher-pill" href={`/organisateurs/${item.organizerSlug}`}>
                <img alt={organizerName} src={organizerImage} />
                <span>
                  <small>Organisateur</small>
                  <strong>{organizerName}</strong>
                </span>
              </Link>
            </div>

            <div className="checkout-steps">
              <span className="is-active">1. Offre</span>
              <span className="is-active">2. Acheteur</span>
              <span className="is-active">3. Paiement</span>
              <span>4. Confirmation</span>
            </div>

            <div className="checkout-section">
              <h2>Compte acheteur</h2>
              {!accountUser ? (
                <div className="checkout-account-card checkout-account-card--warning">
                  <div>
                    <strong>Connexion requise</strong>
                    <p>Les réservations gratuites et les paiements sont rattachés à un compte acheteur unique.</p>
                  </div>
                  <Link className="button button--small" href={loginUrl}>
                    Se connecter
                  </Link>
                </div>
              ) : (
                <div className="checkout-account-card">
                  <div className="checkout-account-card__avatar" aria-hidden="true">
                    {accountUser.avatar_url ? <img alt="" src={accountUser.avatar_url} /> : accountUser.name.slice(0, 2).toUpperCase()}
                  </div>
                  <div>
                    <strong>{accountUser.name}</strong>
                    <p>{accountUser.email}</p>
                    <span className={accountUser.email_verified ? "checkout-account-card__status" : "checkout-account-card__status checkout-account-card__status--warning"}>
                      {accountUser.email_verified ? "E-mail vérifié" : "E-mail à vérifier"}
                    </span>
                    {!accountUser.account_ready_for_actions ? (
                      <p className="checkout-account-card__hint">
                        Finalisez d&apos;abord votre compte acheteur. {accountUser.email_verified ? "" : "Vérifiez votre adresse e-mail. "}
                        {missingProfileLabels.length > 0
                          ? `Renseignez aussi ${missingProfileLabels.join(", ")}.`
                          : "Complétez les informations manquantes dans votre profil."}
                      </p>
                    ) : null}
                  </div>
                  <div className="checkout-account-card__actions">
                    {!accountUser.email_verified ? (
                      <button
                        className="button button--small button--ghost"
                        disabled={resendingVerification}
                        onClick={() => void handleResendVerification()}
                        type="button"
                      >
                        {resendingVerification ? "Envoi..." : "Renvoyer le lien"}
                      </button>
                    ) : null}
                    {!accountUser.account_ready_for_actions ? (
                      <Link className="button button--small button--ghost" href="/compte/profil">
                        Compléter mon profil
                      </Link>
                    ) : null}
                  </div>
                </div>
              )}
              <div className="form-grid">
                <label>
                  Quantite
                  <div className="quantity-stepper">
                    <button
                      aria-label="Réduire la quantité"
                      className="quantity-stepper__button"
                      disabled={loadingPricing || quantity <= quantityBounds.min}
                      onClick={() => {
                        void refreshPricing(Math.max(quantityBounds.min, quantity - 1));
                      }}
                      type="button"
                    >
                      -
                    </button>
                    <input className="quantity-stepper__input" min={quantityBounds.min} readOnly type="number" value={quantity} />
                    <button
                      aria-label="Augmenter la quantité"
                      className="quantity-stepper__button"
                      disabled={loadingPricing || quantity >= quantityBounds.max}
                      onClick={() => {
                        void refreshPricing(Math.min(quantityBounds.max, quantity + 1));
                      }}
                      type="button"
                    >
                      +
                    </button>
                  </div>
                </label>
              </div>
            </div>

            <div className="checkout-section">
              <h2>Paiement</h2>
              <div className="payment-methods">
                {methodLabels.map((method) => (
                  <span className="payment-chip" key={method}>
                    {method}
                  </span>
                ))}
              </div>
              <p className="section-copy">
                La confirmation finale est validee par verification serveur et webhook de paiement.
              </p>
              {notice ? <p className="section-copy" style={{ color: "#047857" }}>{notice}</p> : null}
              {error ? <p className="section-copy" style={{ color: "#b91c1c" }}>{error}</p> : null}
            </div>
          </div>

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
            </div>

            <div className="booking-summary__price-block">
              <div className="booking-summary__price-row">
                <span className="booking-summary__price-label">Sous-total</span>
                <span className="booking-summary__price-value">
                  {pricing.subtotal === 0 ? "Gratuit" : formatMoney(pricing.subtotal, pricing.currency)}
                </span>
              </div>
              <div className="booking-summary__price-row">
                <span className="booking-summary__price-label">Frais de service</span>
                <span className="booking-summary__price-value">
                  {pricing.service_fee === 0 ? "—" : formatMoney(pricing.service_fee, pricing.currency)}
                </span>
              </div>
            </div>

            <div className="booking-summary__total-block">
              <span className="booking-summary__total-label">Total a payer</span>
              <span className="booking-summary__total-value">
                {pricing.total === 0 ? "Gratuit" : formatMoney(pricing.total, pricing.currency)}
              </span>
            </div>

            <div className="booking-summary__cta">
              <button className="button button--full" disabled={submitting || loadingPricing || (Boolean(accountUser) && !accountReadyForActions)} onClick={() => void handleSubmit()} type="button">
                {submitting ? "Initialisation..." : pricing.total === 0 ? "Confirmer la reservation" : "Continuer vers le paiement"}
              </button>
              {accountUser && !accountReadyForActions ? (
                <p className="booking-summary__error">
                  Profil acheteur incomplet ou e-mail non vérifié. Ouvrez votre profil avant de lancer une réservation ou un paiement.
                </p>
              ) : null}
              {error ? <p className="booking-summary__error">{error}</p> : null}
            </div>

            <div className="booking-summary__trust">
              <span>Paiement securise — verification serveur</span>
            </div>
          </aside>
        </div>
      </section>
    </>
  );
}
