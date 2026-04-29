import Link from "next/link";
import { notFound } from "next/navigation";

import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountReceipt } from "@/lib/data/account";
import type { ReceiptStatus } from "@/lib/types";

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
    day: "numeric",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

const STATUS_LABELS: Record<ReceiptStatus, string> = {
  issued: "Émis",
  cancelled: "Annulé",
  refunded: "Remboursé",
};

export default async function RecuDetailPage({
  params,
}: {
  params: Promise<{ ref: string }>;
}) {
  const { ref } = await params;
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);

  if (!token) notFound();

  const receipt = await getAccountReceipt(tenantSlug, token, ref);
  if (!receipt) notFound();

  return (
    <>
      <Link href="/compte/recus" className="ac-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="m15 18-6-6 6-6" />
        </svg>
        Retour aux reçus
      </Link>

      <div className="ac-page-header">
        <h1 className="ac-page-title">Reçu {receipt.reference}</h1>
        <p className="ac-page-sub">
          <span className={`ac-badge ac-badge--${receipt.status}`}>
            <span className="ac-badge__dot" />
            {STATUS_LABELS[receipt.status]}
          </span>
        </p>
      </div>

      <div className="ac-detail">
        <div>
          <div className="ac-detail-panel">
            <div className="ac-detail-panel__head">
              <span className="ac-detail-panel__title">Détails du reçu</span>
            </div>
            <div className="ac-detail-panel__body">
              <div className="ac-detail-row">
                <span className="ac-detail-row__label">Référence</span>
                <span className="ac-detail-row__value ac-detail-row__value--mono">{receipt.reference}</span>
              </div>
              <div className="ac-detail-row">
                <span className="ac-detail-row__label">Date d&apos;émission</span>
                <span className="ac-detail-row__value">{formatDate(receipt.issued_at)}</span>
              </div>
              <div className="ac-detail-row">
                <span className="ac-detail-row__label">Créé le</span>
                <span className="ac-detail-row__value">{formatDate(receipt.created_at)}</span>
              </div>
              <div className="ac-detail-row ac-detail-row--total">
                <span className="ac-detail-row__label">Montant total</span>
                <span className="ac-detail-row__value">{formatAmount(receipt.total_amount, receipt.currency_code)}</span>
              </div>
            </div>
          </div>

          {receipt.order && (
            <div className="ac-detail-panel" style={{ marginTop: "24px" }}>
              <div className="ac-detail-panel__head">
                <span className="ac-detail-panel__title">Commande associée</span>
              </div>
              <div className="ac-detail-panel__body">
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Référence</span>
                  <span className="ac-detail-row__value ac-detail-row__value--mono">{receipt.order.reference}</span>
                </div>
                <div className="ac-detail-row" style={{ border: "none", paddingTop: "12px" }}>
                  <Link
                    href={`/compte/commandes/${receipt.order.reference}`}
                    className="button button--small"
                    style={{ width: "100%", textAlign: "center" }}
                  >
                    Voir la commande
                  </Link>
                </div>
              </div>
            </div>
          )}
        </div>

        <div>
          <div className="ac-detail-panel">
            <div className="ac-detail-panel__head">
              <span className="ac-detail-panel__title">Destinataire</span>
            </div>
            <div className="ac-detail-panel__body">
              <div className="ac-detail-row">
                <span className="ac-detail-row__label">Nom</span>
                <span className="ac-detail-row__value">{receipt.buyer_name ?? "—"}</span>
              </div>
              <div className="ac-detail-row">
                <span className="ac-detail-row__label">E-mail</span>
                <span className="ac-detail-row__value">{receipt.buyer_email ?? "—"}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
