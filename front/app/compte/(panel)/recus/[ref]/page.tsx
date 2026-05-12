import Link from "next/link";
import { notFound } from "next/navigation";

import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountReceipt } from "@/lib/data/account";
import type { ReceiptStatus } from "@/lib/types";

export const dynamic = "force-dynamic";

function formatAmount(amount: number, currency: string) {
  return new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency: currency.toUpperCase(),
    minimumFractionDigits: 0,
  }).format(amount);
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
        <p className="ac-page-sub">Justificatif centralisé, prêt à afficher ou à imprimer.</p>
      </div>

      <section className="receipt-panel receipt-panel--account">
        <div className="receipt-panel__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
            <polyline points="14 2 14 8 20 8" />
            <line x1="9" y1="13" x2="15" y2="13" />
            <line x1="9" y1="17" x2="15" y2="17" />
          </svg>
        </div>

        <div className="receipt-panel__status-wrap">
          <span className={`ac-badge ac-badge--${receipt.status}`}>
            <span className="ac-badge__dot" />
            {STATUS_LABELS[receipt.status]}
          </span>
        </div>

        <strong className="receipt-panel__amount">
          {formatAmount(receipt.total_amount, receipt.currency_code)}
        </strong>

        <p className="receipt-panel__caption">Reçu émis pour votre achat et prêt à être partagé ou imprimé.</p>

        <div className="receipt-panel__meta">
          <div>
            <span>N° reçu</span>
            <strong>{receipt.reference}</strong>
          </div>
          <div>
            <span>Date d&apos;émission</span>
            <strong>{formatDate(receipt.issued_at)}</strong>
          </div>
          <div>
            <span>Nom du destinataire</span>
            <strong>{receipt.buyer_name ?? "—"}</strong>
          </div>
          <div>
            <span>E-mail</span>
            <strong>{receipt.buyer_email ?? "—"}</strong>
          </div>
          <div>
            <span>Téléphone</span>
            <strong>{receipt.order?.buyer_phone ?? receipt.buyer_phone ?? "—"}</strong>
          </div>
          <div>
            <span>Référence paiement</span>
            <strong>{typeof receipt.meta?.transaction_reference === "string" ? receipt.meta.transaction_reference : receipt.order?.transaction_reference ?? "—"}</strong>
          </div>
          <div>
            <span>N° Référence</span>
            <strong>{typeof receipt.meta?.gateway_transaction_id === "string" || typeof receipt.meta?.gateway_transaction_id === "number" ? String(receipt.meta.gateway_transaction_id) : typeof receipt.meta?.gateway_reference === "string" ? receipt.meta.gateway_reference : "—"}</strong>
          </div>
          <div>
            <span>Créé le</span>
            <strong>{formatDate(receipt.created_at)}</strong>
          </div>
          <div>
            <span>Commande liée</span>
            <strong>{receipt.order?.reference ?? "—"}</strong>
          </div>
        </div>

        <div className="receipt-panel__actions">
          <Link className="button button--ghost" href={`/compte/recus/${receipt.reference}/imprimer`} target="_blank">
            Imprimer ou télécharger le reçu
          </Link>
          {receipt.order ? (
            <Link className="button" href={`/compte/commandes/${receipt.order.reference}`}>
              Voir la commande
            </Link>
          ) : null}
        </div>
      </section>
    </>
  );
}
