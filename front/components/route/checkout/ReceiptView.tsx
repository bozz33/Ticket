import Link from "next/link";
import { notFound } from "next/navigation";

import { PrintButton } from "@/components/PrintButton";
import { QrCode } from "@/components/QrCode";
import type { CheckoutVerificationResult, PlatformConfiguration, PublicContent } from "@/lib/types";
import { buildPublicUrl, formatDateLabel, formatMoney, resolveImageSrc } from "@/lib/utils";

import { buildPaymentQuery, buildPaymentReference, normalizePaidAt } from "./helpers";

export function ReceiptView({
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
  const organizerImage = resolveImageSrc(item.organizers[0]?.imageUrl, item.coverImageUrl);
  const subtotal = verification?.amounts.net ?? (selectedOffer?.price ?? item.priceFrom);
  const serviceFee = verification?.amounts.fees ?? 0;
  const total = verification?.amounts.gross ?? subtotal + serviceFee;
  const currency = verification?.amounts.currency ?? item.currency;
  const resolvedReference = buildPaymentReference(item, selectedOffer, paymentReference);
  const resolvedPaidAt = normalizePaidAt(paidAt);
  const receiptReference = verification?.receipt?.reference ?? resolvedReference;
  const orderReference = verification?.order?.reference ?? "—";
  const paymentMethod = verification?.payment_label ?? (total === 0 ? "Confirmation immédiate" : "Paiement sécurisé");
  const receiptTrackingUrl = buildPublicUrl(`/checkout/${item.module}/${item.slug}/recu${buildPaymentQuery(
    selectedOffer,
    resolvedReference,
    resolvedPaidAt,
    item.organizerSlug,
  )}`);
  const successHref = `/checkout/${item.module}/${item.slug}/succes${buildPaymentQuery(
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
          <p className="eyebrow">Recu</p>
          <h1>{resolvedConfirmed ? "Recu de paiement" : "Recu indisponible"}</h1>
          <p>
            {resolvedConfirmed
              ? "Un recapitulatif clair, imprimable et partageable pour la transaction."
              : "Le paiement n'est pas encore confirmé, le reçu officiel n'est donc pas disponible pour le moment."}
          </p>
        </div>
      </section>

      <section className="section">
        <div className="shell receipt-layout">
          <article className="receipt-card">
            <header className="receipt-card__header">
              <div>
                <p className="eyebrow">Reçu de paiement</p>
                <h2>{platform.brandName}</h2>
                <p className="section-copy">N° référence {receiptReference}</p>
              </div>
              <span className="receipt-card__status">{resolvedConfirmed ? "Paye" : "En attente"}</span>
            </header>

            <div className="receipt-transaction">
              <div>
                <span>Montant</span>
                <strong>{total === 0 ? "Gratuit" : formatMoney(total, currency)}</strong>
              </div>
              <div>
                <span>Référence de suivi</span>
                <strong>{resolvedReference}</strong>
              </div>
              <div>
                <span>Moyen de paiement</span>
                <strong>{paymentMethod}</strong>
              </div>
              <div>
                <span>Statut</span>
                <strong>{resolvedConfirmed ? "Payé" : "En attente"}</strong>
              </div>
              <div>
                <span>Commande</span>
                <strong>{orderReference}</strong>
              </div>
              <div>
                <span>Généré le</span>
                <strong>{formatDateLabel(resolvedPaidAt)}</strong>
              </div>
            </div>

            <div className="receipt-card__meta">
              <div>
                <span>Contenu</span>
                <strong>{item.title}</strong>
              </div>
              <div>
                <span>Offre</span>
                <strong>{selectedOffer?.title ?? "Offre principale"}</strong>
              </div>
              <div>
                <span>Date de paiement</span>
                <strong>{formatDateLabel(resolvedPaidAt)}</strong>
              </div>
              <div>
                <span>Lieu</span>
                <strong>
                  {item.venueName ?? item.city}, {item.country}
                </strong>
              </div>
            </div>

            <div className="receipt-card__publisher">
              {organizerImage ? <img alt={organizerName} src={organizerImage} /> : null}
              <div>
                <small>Organisateur</small>
                <strong>{organizerName}</strong>
              </div>
            </div>

            <div className="receipt-lines">
              <div className="receipt-line">
                <span>{selectedOffer?.title ?? item.title}</span>
                <strong>{subtotal === 0 ? "Gratuit" : formatMoney(subtotal, currency)}</strong>
              </div>
              <div className="receipt-line receipt-line--muted">
                <span>Quantite</span>
                <strong>{verification?.quantity ?? 1}</strong>
              </div>
              <div className="receipt-line receipt-line--muted">
                <span>Frais de service</span>
                <strong>{serviceFee === 0 ? "—" : formatMoney(serviceFee, currency)}</strong>
              </div>
            </div>

            <div className="receipt-card__total">
              <span>{resolvedConfirmed ? "Total regle" : "Total a verifier"}</span>
              <strong>{total === 0 ? "Gratuit" : formatMoney(total, currency)}</strong>
            </div>

            <footer className="receipt-card__footer">
              <div>
                <span>Support</span>
                <strong>{platform.supportEmail}</strong>
              </div>
              <div>
                <span>Telephone</span>
                <strong>{platform.supportPhone}</strong>
              </div>
            </footer>
          </article>

          <aside className="receipt-side">
            <div className="receipt-side__panel">
              <div className="receipt-qr">
                <QrCode value={receiptTrackingUrl} />
                <span>Scanner pour suivre l'état de votre réservation</span>
              </div>
              {resolvedConfirmed ? <PrintButton className="button button--full">Imprimer le recu</PrintButton> : null}
              <Link className="button button--ghost button--full" href={successHref}>
                Retour au succes
              </Link>
              <Link className="button button--ghost button--full" href={`/${item.module}/${item.slug}?tenant=${encodeURIComponent(item.organizerSlug)}`}>
                Retour au contenu
              </Link>
              <p className="receipt-side__note">
                Ce reçu reprend la référence de suivi, le montant et l'organisateur visibles publiquement.
              </p>
            </div>
          </aside>
        </div>
      </section>
    </>
  );
}
