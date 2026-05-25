import Link from "next/link";
import { notFound } from "next/navigation";

import type { CheckoutVerificationResult, PlatformConfiguration, PublicContent } from "@/lib/types";
import { formatDateLabel, formatDateRange, formatMoney } from "@/lib/utils";

import { buildPaymentQuery, buildPaymentReference, normalizePaidAt } from "./helpers";
import { LockIcon } from "./LockIcon";

export function PaymentSuccessView({
  item,
  selectedOffer,
  platform,
  paymentReference,
  paidAt,
  isConfirmed,
  verification,
}: {
  item: PublicContent | null;
  selectedOffer: PublicContent["tiers"][number] | null;
  platform: PlatformConfiguration;
  paymentReference?: string;
  paidAt?: string;
  isConfirmed?: boolean;
  verification?: CheckoutVerificationResult | null;
}) {
  if (!item) {
    notFound();
  }

  const organizerName = item.organizers[0]?.name ?? "Organisateur";
  const organizerImage = item.organizers[0]?.imageUrl ?? item.coverImageUrl;
  const subtotal = verification?.amounts.net ?? (selectedOffer?.price ?? item.priceFrom);
  const serviceFee = verification?.amounts.fees ?? 0;
  const total = verification?.amounts.gross ?? subtotal + serviceFee;
  const currency = verification?.amounts.currency ?? item.currency;
  const resolvedReference = buildPaymentReference(item, selectedOffer, paymentReference);
  const resolvedPaidAt = normalizePaidAt(paidAt);
  const isCrowdfunding = item.module === "crowdfunding";
  const paymentLabel = verification?.payment_label ?? (total === 0 ? "Confirmation immédiate" : "Paiement sécurisé");
  const receiptHref = `/checkout/${item.module}/${item.slug}/recu${buildPaymentQuery(
    selectedOffer,
    resolvedReference,
    resolvedPaidAt,
    item.organizerSlug,
  )}`;
  const resolvedConfirmed = isConfirmed ?? verification?.is_successful ?? false;

  return (
    <>
      <section className="page-hero page-hero--compact">
        <div className="shell page-hero__content">
          <p className="eyebrow">{resolvedConfirmed ? "Paiement confirme" : "Paiement en attente"}</p>
          <h1>{resolvedConfirmed ? (isCrowdfunding ? "Contribution enregistree" : "Reservation enregistree") : "Verification du paiement en cours"}</h1>
          <p>
            {resolvedConfirmed
              ? isCrowdfunding
                ? "Le paiement a ete valide et la progression de la campagne sera mise a jour."
                : "Le paiement a ete valide et un recapitulatif est deja disponible."
              : "Le paiement n'est pas encore confirme. Verifiez le statut avant de considérer la commande comme finalisée."}
          </p>
        </div>
      </section>

      <section className="section">
        <div className="shell success-layout">
          <article className="success-card">
            <span className="success-card__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24">
                {resolvedConfirmed ? <path d="M20 7 9.5 17.5 4 12" /> : <path d="M12 8v4m0 4h.01" />}
              </svg>
            </span>
            <p className="eyebrow">{resolvedConfirmed ? "Succes" : "Statut a verifier"}</p>
            <h2>{resolvedConfirmed ? (isCrowdfunding ? "Votre contribution est confirmee" : "Votre commande est confirmee") : "Votre paiement n'est pas encore confirme"}</h2>
            <p className="section-copy">
              {resolvedConfirmed
                ? isCrowdfunding
                  ? `${platform.brandName} a centralise la confirmation et la reference de contribution. Aucun pass ni reçu n'est émis pour ce soutien.`
                  : `${platform.brandName} a centralise la confirmation, la reference de paiement et le recapitulatif de commande.`
                : `${platform.brandName} attend encore une confirmation definitive du paiement ou une re-verification du serveur.`}
            </p>

            <div className="success-card__meta">
              <article>
                <span>Reference</span>
                <strong>{resolvedReference}</strong>
              </article>
              <article>
                <span>Offre</span>
                <strong>{selectedOffer?.title ?? item.title}</strong>
              </article>
              <article>
                <span>Total</span>
                <strong>{total === 0 ? "Gratuit" : formatMoney(total, currency)}</strong>
              </article>
              <article>
                <span>Règlement</span>
                <strong>{paymentLabel}</strong>
              </article>
              <article>
                <span>Paye le</span>
                <strong>{formatDateLabel(resolvedPaidAt)}</strong>
              </article>
            </div>

            <div className="success-card__actions">
              {resolvedConfirmed && !isCrowdfunding ? (
                <Link className="button" href={receiptHref}>
                  Voir le recu
                </Link>
              ) : !resolvedConfirmed ? (
                <Link className="button" href={`/${item.module}/${item.slug}?tenant=${encodeURIComponent(item.organizerSlug)}`}>
                  Revenir au checkout
                </Link>
              ) : null}
              <Link className="button button--ghost" href={`/${item.module}/${item.slug}?tenant=${encodeURIComponent(item.organizerSlug)}`}>
                Retour au contenu
              </Link>
            </div>
          </article>

          <aside className="success-side">
            <div className="success-side__panel">
              <Link className="publisher-pill publisher-pill--card" href={`/organisateurs/${item.organizerSlug}`}>
                <img alt={organizerName} src={organizerImage} />
                <span>
                  <small>Publie par</small>
                  <strong>{organizerName}</strong>
                </span>
              </Link>

              <div className="success-side__facts">
                <div>
                  <span>Date</span>
                  <strong>{formatDateRange(item)}</strong>
                </div>
                <div>
                  <span>Lieu</span>
                  <strong>
                    {item.venueName ?? item.city}, {item.country}
                  </strong>
                </div>
                <div>
                  <span>Sous-total</span>
                  <strong>{subtotal === 0 ? "Gratuit" : formatMoney(subtotal, currency)}</strong>
                </div>
                <div>
                  <span>Frais</span>
                  <strong>{serviceFee === 0 ? "—" : formatMoney(serviceFee, currency)}</strong>
                </div>
              </div>

              <div className="success-side__footer">
                <LockIcon />
                <span>Confirmation envoyee et reference disponible pour le suivi.</span>
              </div>
            </div>
          </aside>
        </div>
      </section>
    </>
  );
}
