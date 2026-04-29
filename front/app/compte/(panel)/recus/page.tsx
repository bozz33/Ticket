import Link from "next/link";

import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountReceipts } from "@/lib/data/account";
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
  });
}

const STATUS_LABELS: Record<ReceiptStatus, string> = {
  issued: "Émis",
  cancelled: "Annulé",
  refunded: "Remboursé",
};

export default async function RecusPage() {
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);
  const receipts = token ? await getAccountReceipts(tenantSlug, token) : [];

  return (
    <>
      <div className="ac-page-header">
        <h1 className="ac-page-title">Reçus</h1>
        <p className="ac-page-sub">Vos justificatifs de paiement.</p>
      </div>

      {receipts.length === 0 ? (
        <div className="ac-empty">
          <svg className="ac-empty__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
            <polyline points="14 2 14 8 20 8" />
            <line x1="9" y1="13" x2="15" y2="13" />
            <line x1="9" y1="17" x2="15" y2="17" />
          </svg>
          <p className="ac-empty__title">Aucun reçu</p>
          <p className="ac-empty__text">Vos reçus apparaîtront ici après confirmation du paiement.</p>
        </div>
      ) : (
        <div className="ac-list">
          {receipts.map((receipt) => (
            <Link key={receipt.id} href={`/compte/recus/${receipt.reference}`} className="ac-card">
              <div className="ac-card__icon ac-card__icon--teal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
                  <polyline points="14 2 14 8 20 8" />
                  <line x1="9" y1="13" x2="15" y2="13" />
                  <line x1="9" y1="17" x2="15" y2="17" />
                </svg>
              </div>
              <div className="ac-card__body">
                <p className="ac-card__title">Reçu {receipt.reference}</p>
                <p className="ac-card__meta">
                  <span>Émis le {formatDate(receipt.issued_at ?? receipt.created_at)}</span>
                  {receipt.buyer_name && (
                    <>
                      <span className="ac-card__meta-sep" />
                      <span>{receipt.buyer_name}</span>
                    </>
                  )}
                </p>
              </div>
              <div className="ac-card__right">
                <p className="ac-card__amount">{formatAmount(receipt.total_amount, receipt.currency_code)}</p>
                <span className={`ac-badge ac-badge--${receipt.status}`}>
                  <span className="ac-badge__dot" />
                  {STATUS_LABELS[receipt.status]}
                </span>
              </div>
              <svg className="ac-card__arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="m9 18 6-6-6-6" />
              </svg>
            </Link>
          ))}
        </div>
      )}
    </>
  );
}
