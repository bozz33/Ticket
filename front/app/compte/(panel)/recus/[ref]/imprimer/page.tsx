import Link from "next/link";
import { notFound } from "next/navigation";

import { PrintButton } from "@/components/PrintButton";
import { QrCode } from "@/components/QrCode";
import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountReceipt } from "@/lib/data/account";
import { buildPublicUrl, formatMoney } from "@/lib/utils";

export const dynamic = "force-dynamic";

function formatLongDate(iso: string | null) {
  if (!iso) return "—";

  return new Intl.DateTimeFormat("fr-FR", {
    day: "numeric",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(new Date(iso));
}

function formatReceiptDate(iso: string | null) {
  if (!iso) return "—";

  return new Intl.DateTimeFormat("fr-FR", {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
  }).format(new Date(iso));
}

export default async function ReceiptPrintPage({
  params,
}: {
  params: Promise<{ ref: string }>;
}) {
  const { ref } = await params;
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);

  if (!token) notFound();

  const receipt = await getAccountReceipt(tenantSlug, token, ref);

  if (!receipt) notFound();

  const orderReference = receipt.order?.reference ?? "—";
  const paymentReference =
    (typeof receipt.meta?.transaction_reference === "string" && receipt.meta.transaction_reference) ||
    receipt.order?.transaction_reference ||
    "—";
  const gatewayTransactionId =
    (typeof receipt.meta?.gateway_transaction_id === "string" || typeof receipt.meta?.gateway_transaction_id === "number"
      ? String(receipt.meta.gateway_transaction_id)
      : null) ?? "—";
  const providerReference =
    (typeof receipt.meta?.gateway_reference === "string" && receipt.meta.gateway_reference) ||
    paymentReference ||
    "—";
  const paymentMethod =
    (typeof receipt.meta?.payment_method_label === "string" && receipt.meta.payment_method_label) ||
    (typeof receipt.meta?.payment_method === "string" && receipt.meta.payment_method) ||
    "Paiement électronique";
  const verificationUrl = buildPublicUrl(`/verifier/recu/${tenantSlug}/${encodeURIComponent(receipt.reference)}`);
  const offerName =
    receipt.order?.offer?.name ??
    (typeof receipt.meta?.offer_name === "string" && receipt.meta.offer_name) ??
    "Achat Ticket";
  const quantity = receipt.order?.quantity ?? 1;
  const unitAmount =
    typeof receipt.order?.unit_amount === "number" && quantity > 0
      ? receipt.order.unit_amount
      : Math.round(receipt.total_amount / Math.max(quantity, 1));
  const buyerPhone =
    receipt.order?.buyer_phone ??
    (typeof receipt.buyer_phone === "string" ? receipt.buyer_phone : null) ??
    "—";
  const serviceDescription =
    (typeof receipt.meta?.service_description === "string" && receipt.meta.service_description) ||
    `${offerName} - confirmation officielle de votre achat sur Ticket Public Marketplace.`;

  return (
    <div className="receipt-print-shell">
      <div className="receipt-print-toolbar">
        <Link href={`/compte/recus/${receipt.reference}`} className="ac-back">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="m15 18-6-6 6-6" />
          </svg>
          Fermer les détails du reçu
        </Link>

        <div className="receipt-print-toolbar__actions">
          <PrintButton className="button button--ghost">Imprimer</PrintButton>
          <a className="button" href={verificationUrl} target="_blank" rel="noreferrer">
            Vérifier le reçu
          </a>
        </div>
      </div>

      <article className="receipt-document">
        <div className="receipt-document__topline">
          <div>
            <p className="receipt-document__eyebrow">Reçu de paiement</p>
            <p className="receipt-document__origin">Ticket Public Marketplace</p>
          </div>
          <span className="receipt-document__status-pill">Payé</span>
        </div>

        <div className="receipt-document__hero">
          <div>
            <h1>Payée le {formatReceiptDate(receipt.issued_at ?? receipt.created_at)}</h1>
            <p className="receipt-document__lede">
              Reçu émis pour votre achat. Ce document peut être imprimé, partagé ou vérifié à partir du QR code.
            </p>
          </div>

          <div className="receipt-document__hero-amount">
            <span>Montant payé</span>
            <strong>{formatMoney(receipt.total_amount, receipt.currency_code)}</strong>
          </div>
        </div>

        <section className="receipt-document__section">
          <div className="receipt-document__section-title">
            <span>Récapitulatif</span>
          </div>

          <div className="receipt-document__summary receipt-document__summary--compact">
            <div>
              <span>À</span>
              <strong>{receipt.buyer_name ?? "Acheteur Ticket"}</strong>
            </div>
            <div>
              <span>De</span>
              <strong>Ticket Public Marketplace</strong>
            </div>
            <div>
              <span>Facture</span>
              <strong>{receipt.reference}</strong>
            </div>
          </div>
        </section>

        <section className="receipt-document__section">
          <div className="receipt-document__section-title">
            <span>Informations sur la transaction</span>
          </div>

          <div className="receipt-document__grid">
            <div>
              <span>Montant</span>
              <strong>{formatMoney(receipt.total_amount, receipt.currency_code)}</strong>
            </div>
            <div>
              <span>ID Transaction</span>
              <strong>{gatewayTransactionId}</strong>
            </div>
            <div>
              <span>N° Référence</span>
              <strong>{providerReference}</strong>
            </div>
            <div>
              <span>Référence paiement</span>
              <strong>{paymentReference}</strong>
            </div>
            <div>
              <span>Moyen de paiement</span>
              <strong>{paymentMethod}</strong>
            </div>
            <div>
              <span>Commande liée</span>
              <strong>{orderReference}</strong>
            </div>
            <div>
              <span>Statut</span>
              <strong>Payé</strong>
            </div>
            <div>
              <span>Généré le</span>
              <strong>{formatLongDate(receipt.issued_at ?? receipt.created_at)}</strong>
            </div>
          </div>
        </section>

        <section className="receipt-document__section">
          <div className="receipt-document__section-title">
            <span>Informations sur le service</span>
          </div>

          <div className="receipt-document__grid">
            <div>
              <span>Nom du service</span>
              <strong>{offerName}</strong>
            </div>
            <div>
              <span>Description</span>
              <strong>{serviceDescription}</strong>
            </div>
            <div>
              <span>Acheteur</span>
              <strong>{receipt.buyer_name ?? "Acheteur Ticket"}</strong>
            </div>
            <div>
              <span>E-mail</span>
              <strong>{receipt.buyer_email ?? "—"}</strong>
            </div>
            <div>
              <span>Téléphone</span>
              <strong>{buyerPhone}</strong>
            </div>
            <div>
              <span>Quantité</span>
              <strong>{quantity}</strong>
            </div>
            <div>
              <span>Prix unitaire</span>
              <strong>{formatMoney(unitAmount, receipt.currency_code)}</strong>
            </div>
            <div>
              <span>Devise</span>
              <strong>{receipt.currency_code}</strong>
            </div>
          </div>
        </section>

        <section className="receipt-document__section">
          <div className="receipt-document__section-title">
            <span>Articles</span>
          </div>

          <div className="receipt-document__table">
            <div className="receipt-document__table-head">
              <span>Article</span>
              <span>Qté</span>
              <span>Prix unit.</span>
              <span>Montant</span>
            </div>

            <div className="receipt-document__table-row">
              <strong>{offerName}</strong>
              <span>{quantity}</span>
              <span>{formatMoney(unitAmount, receipt.currency_code)}</span>
              <span>{formatMoney(receipt.total_amount, receipt.currency_code)}</span>
            </div>
          </div>
        </section>

        <div className="receipt-document__bottom">
          <section className="receipt-document__section">
            <div className="receipt-document__section-title">
              <span>Totaux</span>
            </div>

            <div className="receipt-document__totals">
              <div>
                <span>Montant dû</span>
                <strong>{formatMoney(receipt.total_amount, receipt.currency_code)}</strong>
              </div>
              <div>
                <span>Montant payé</span>
                <strong>{formatMoney(receipt.total_amount, receipt.currency_code)}</strong>
              </div>
              <div>
                <span>Montant restant</span>
                <strong>{formatMoney(0, receipt.currency_code)}</strong>
              </div>
            </div>
          </section>

          <aside className="receipt-document__qr-panel">
            <QrCode value={verificationUrl} size={148} />
            <strong>QR de vérification</strong>
            <p>Scannez pour suivre l’état de votre achat et vérifier publiquement l’authenticité du reçu.</p>
            <span className="receipt-document__qr-url">{verificationUrl}</span>
          </aside>
        </div>

        <div className="receipt-document__footer">
          <div>
            <span>Support</span>
            <strong>support@ticket.africa</strong>
          </div>
          <div>
            <span>Contact</span>
            <strong>+225 27 22 40 11 00</strong>
          </div>
          <div>
            <span>Émis le</span>
            <strong>{formatLongDate(receipt.issued_at ?? receipt.created_at)}</strong>
          </div>
        </div>
      </article>
    </div>
  );
}
