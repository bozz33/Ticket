import { notFound } from "next/navigation";

import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountReceipt } from "@/lib/data/account";
import { getPlatformConfiguration } from "@/lib/data/public";
import { PrintButton } from "@/components/PrintButton";
import { ReceiptQrCode } from "./ReceiptQrCode";

export const dynamic = "force-dynamic";

function formatAmount(minor: number, currency: string) {
  return new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency: currency.toUpperCase(),
    minimumFractionDigits: 0,
  }).format(minor / 100);
}

function formatDate(iso: string | null) {
  if (!iso) return "—";
  return new Date(iso).toLocaleDateString("fr-FR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
}

const STATUS_LABELS: Record<string, string> = {
  issued: "Payé",
  cancelled: "Annulé",
  refunded: "Remboursé",
};

const OFFER_TYPE_LABELS: Record<string, string> = {
  event_ticket: "Paiement de billet",
  training_enrollment: "Paiement d'inscription formation",
  stand_reservation: "Paiement de réservation stand",
  purchase_pass: "Paiement de pass achat",
};

export default async function ReceiptPrintPage({
  params,
}: {
  params: Promise<{ ref: string }>;
}) {
  const { ref } = await params;
  const [token, tenantSlug, platform] = await Promise.all([
    getAuthToken(),
    getTenantSlug(),
    getPlatformConfiguration(),
  ]);

  if (!token) notFound();

  const receipt = await getAccountReceipt(tenantSlug, token, ref);
  if (!receipt) notFound();

  const order = receipt.order;
  const offerName = order?.offer?.name ?? "—";
  const offerTypeLabel =
    order?.offer?.offer_type
      ? (OFFER_TYPE_LABELS[order.offer.offer_type] ?? "Paiement en ligne")
      : "Paiement en ligne";
  const qty = order?.quantity ?? 1;
  const unitAmount = order?.unit_amount ?? receipt.total_amount;
  const txRef = order?.transaction_reference ?? "—";
  const paymentMethod =
    (receipt.meta?.payment_method as string | undefined) ??
    (order?.meta?.payment_method as string | undefined) ??
    "—";
  const statusLabel = STATUS_LABELS[receipt.status] ?? receipt.status;
  const qrValue = receipt.reference;

  return (
    <>
      <style>{`
        @media print {
          .site-header, .site-footer, .rp-actions { display: none !important; }
          body { background: #fff !important; }
          .rp-page { box-shadow: none !important; border: none !important; }
        }

        .rp-actions {
          display: flex;
          justify-content: flex-end;
          gap: 12px;
          padding: 20px 24px;
          max-width: 760px;
          margin: 0 auto;
        }

        .rp-page {
          background: #fff;
          max-width: 760px;
          margin: 0 auto 48px;
          padding: 48px 52px;
          border: 1px solid #e0e0e0;
          border-radius: 8px;
          box-shadow: 0 4px 24px rgba(0,0,0,0.07);
          font-family: ui-sans-serif, sans-serif;
          color: #101b26;
          font-size: 0.9rem;
          line-height: 1.5;
        }

        /* ── Header ── */
        .rp-header {
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          padding-bottom: 24px;
          border-bottom: 2px solid #f0e9df;
          margin-bottom: 28px;
        }

        .rp-brand {
          display: flex;
          flex-direction: column;
          gap: 4px;
        }

        .rp-brand__name {
          font-weight: 700;
          font-size: 1.05rem;
          color: #101b26;
        }

        .rp-brand__email {
          font-size: 0.78rem;
          color: #62707b;
        }

        .rp-title-block {
          text-align: right;
        }

        .rp-title {
          font-size: 2rem;
          font-weight: 800;
          color: #d59a36;
          margin: 0 0 10px;
          line-height: 1;
        }

        .rp-meta {
          font-size: 0.8rem;
          color: #62707b;
          display: flex;
          flex-direction: column;
          gap: 3px;
          align-items: flex-end;
        }

        .rp-meta strong {
          color: #101b26;
          font-weight: 600;
        }

        /* ── Section title ── */
        .rp-section-title {
          font-size: 1rem;
          font-weight: 700;
          color: #d59a36;
          margin: 0 0 16px;
          padding-bottom: 6px;
          border-bottom: 2px solid #f5e9d3;
        }

        /* ── Service ── */
        .rp-service {
          margin-bottom: 32px;
        }

        .rp-service-name-label {
          font-size: 0.78rem;
          color: #62707b;
          margin: 0 0 2px;
        }

        .rp-service-name-value {
          font-weight: 700;
          font-size: 0.95rem;
          color: #101b26;
          margin: 0 0 20px;
        }

        .rp-table {
          width: 100%;
          border-collapse: collapse;
        }

        .rp-table th {
          text-align: left;
          font-size: 0.75rem;
          font-weight: 600;
          color: #62707b;
          text-transform: uppercase;
          letter-spacing: 0.05em;
          padding: 8px 10px;
          border-bottom: 1px solid #e8e0d6;
        }

        .rp-table th:not(:first-child),
        .rp-table td:not(:first-child) {
          text-align: right;
        }

        .rp-table td {
          padding: 14px 10px;
          border-bottom: 1px solid #f0e9df;
          font-size: 0.875rem;
        }

        .rp-table td:first-child {
          font-weight: 600;
          color: #101b26;
        }

        .rp-table-total {
          display: flex;
          justify-content: flex-end;
          margin-top: 12px;
        }

        .rp-table-total__amount {
          font-size: 1.05rem;
          font-weight: 800;
          color: #d59a36;
          padding: 8px 10px;
        }

        /* ── Transaction ── */
        .rp-tx {
          margin-top: 36px;
        }

        .rp-tx-box {
          border: 1px solid #e8e0d6;
          border-radius: 8px;
          padding: 24px;
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          gap: 32px;
        }

        .rp-tx-fields {
          flex: 1;
          display: flex;
          flex-direction: column;
          gap: 16px;
        }

        .rp-tx-field__label {
          font-size: 0.75rem;
          color: #62707b;
          margin: 0 0 2px;
        }

        .rp-tx-field__value {
          font-weight: 700;
          font-size: 0.925rem;
          color: #101b26;
          margin: 0;
          font-family: ui-monospace, monospace;
        }

        .rp-tx-field__value--status {
          font-family: inherit;
          color: #1c7c72;
        }

        .rp-tx-field__value--amount {
          font-family: inherit;
          font-size: 1rem;
        }

        .rp-tx-qr {
          display: flex;
          flex-direction: column;
          align-items: center;
          gap: 8px;
          flex-shrink: 0;
        }

        .rp-tx-qr__label {
          font-size: 0.7rem;
          color: #62707b;
          text-align: center;
          max-width: 120px;
          line-height: 1.4;
        }

        /* ── Buyer ── */
        .rp-buyer {
          margin-top: 32px;
          padding-top: 24px;
          border-top: 1px solid #f0e9df;
          display: flex;
          gap: 40px;
          font-size: 0.8rem;
          color: #62707b;
        }

        .rp-buyer span {
          font-weight: 600;
          color: #101b26;
        }
      `}</style>

      {/* Actions bar (hidden on print) */}
      <div className="rp-actions">
        <a
          href={`/compte/recus/${receipt.reference}`}
          style={{
            padding: "9px 18px",
            borderRadius: "8px",
            border: "1.5px solid #d7d8de",
            fontSize: "0.875rem",
            fontWeight: 500,
            color: "#62707b",
            textDecoration: "none",
            display: "inline-flex",
            alignItems: "center",
            gap: "6px",
          }}
        >
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="m15 18-6-6 6-6" />
          </svg>
          Retour
        </a>
        <PrintButton
          className="button"
          style={{
            padding: "9px 20px",
            borderRadius: "8px",
            fontSize: "0.875rem",
            fontWeight: 600,
            cursor: "pointer",
            display: "inline-flex",
            alignItems: "center",
            gap: "8px",
          }}
        >
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <polyline points="6 9 6 2 18 2 18 9" />
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
            <rect width="12" height="8" x="6" y="14" />
          </svg>
          Télécharger / Imprimer
        </PrintButton>
      </div>

      {/* Receipt document */}
      <div className="rp-page">

        {/* Header */}
        <div className="rp-header">
          <div className="rp-brand">
            <span className="rp-brand__name">{platform.brandName}</span>
            {platform.supportEmail && (
              <span className="rp-brand__email">{platform.supportEmail}</span>
            )}
          </div>
          <div className="rp-title-block">
            <h1 className="rp-title">Reçu de paiement</h1>
            <div className="rp-meta">
              <div>
                N° Référence&nbsp; <strong>{receipt.reference}</strong>
              </div>
              <div>
                Généré le&nbsp; <strong>{formatDate(receipt.issued_at ?? receipt.created_at)}</strong>
              </div>
            </div>
          </div>
        </div>

        {/* Informations sur le service */}
        <div className="rp-service">
          <p className="rp-section-title">Informations sur le service</p>
          <p className="rp-service-name-label">Nom du Service</p>
          <p className="rp-service-name-value">{offerName}</p>

          <table className="rp-table">
            <thead>
              <tr>
                <th>Description</th>
                <th>Quantité</th>
                <th>Prix unitaire</th>
                <th>Montant</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>{offerTypeLabel}</td>
                <td>{qty}</td>
                <td>{formatAmount(unitAmount, receipt.currency_code)}</td>
                <td>{formatAmount(receipt.total_amount, receipt.currency_code)}</td>
              </tr>
            </tbody>
          </table>

          <div className="rp-table-total">
            <span className="rp-table-total__amount">
              {formatAmount(receipt.total_amount, receipt.currency_code)}
            </span>
          </div>
        </div>

        {/* Informations sur la transaction */}
        <div className="rp-tx">
          <p className="rp-section-title">Informations sur la transaction</p>
          <div className="rp-tx-box">
            <div className="rp-tx-fields">
              <div>
                <p className="rp-tx-field__label">ID Transaction</p>
                <p className="rp-tx-field__value">{txRef}</p>
              </div>
              <div>
                <p className="rp-tx-field__label">Montant</p>
                <p className="rp-tx-field__value rp-tx-field__value--amount">
                  {formatAmount(receipt.total_amount, receipt.currency_code)}
                </p>
              </div>
              {paymentMethod !== "—" && (
                <div>
                  <p className="rp-tx-field__label">Moyen de paiement</p>
                  <p className="rp-tx-field__value">{paymentMethod}</p>
                </div>
              )}
              <div>
                <p className="rp-tx-field__label">Statut</p>
                <p className="rp-tx-field__value rp-tx-field__value--status">
                  {statusLabel}
                </p>
              </div>
            </div>

            <div className="rp-tx-qr">
              <ReceiptQrCode value={qrValue} />
              <span className="rp-tx-qr__label">
                Scanner pour suivre l&apos;état de votre commande
              </span>
            </div>
          </div>
        </div>

        {/* Buyer */}
        {(receipt.buyer_name || receipt.buyer_email) && (
          <div className="rp-buyer">
            {receipt.buyer_name && (
              <div>Destinataire&nbsp; <span>{receipt.buyer_name}</span></div>
            )}
            {receipt.buyer_email && (
              <div>E-mail&nbsp; <span>{receipt.buyer_email}</span></div>
            )}
          </div>
        )}
      </div>
    </>
  );
}
