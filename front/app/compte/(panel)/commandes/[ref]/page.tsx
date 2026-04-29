import Link from "next/link";
import { notFound } from "next/navigation";

import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountOrder } from "@/lib/data/account";
import type { OrderStatus } from "@/lib/types";

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

const STATUS_LABELS: Record<OrderStatus, string> = {
  pending: "En attente",
  confirmed: "Confirmée",
  cancelled: "Annulée",
  refunded: "Remboursée",
};

const PASS_TYPE_LABELS: Record<string, string> = {
  event_ticket: "Billet",
  training_enrollment: "Inscription formation",
  stand_reservation: "Réservation stand",
  purchase_pass: "Pass achat",
};

export default async function CommandeDetailPage({
  params,
}: {
  params: Promise<{ ref: string }>;
}) {
  const { ref } = await params;
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);

  if (!token) notFound();

  const order = await getAccountOrder(tenantSlug, token, ref);
  if (!order) notFound();

  return (
    <>
      <Link href="/compte/commandes" className="ac-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="m15 18-6-6 6-6" />
        </svg>
        Retour aux commandes
      </Link>

      <div className="ac-page-header">
        <h1 className="ac-page-title">{order.offer?.name ?? `Commande #${order.reference}`}</h1>
        <p className="ac-page-sub">
          <span className={`ac-badge ac-badge--${order.status}`}>
            <span className="ac-badge__dot" />
            {STATUS_LABELS[order.status]}
          </span>
        </p>
      </div>

      <div className="ac-detail">
        <div>
          <div className="ac-detail-panel">
            <div className="ac-detail-panel__head">
              <span className="ac-detail-panel__title">Détails de la commande</span>
            </div>
            <div className="ac-detail-panel__body">
              <div className="ac-detail-row">
                <span className="ac-detail-row__label">Référence</span>
                <span className="ac-detail-row__value ac-detail-row__value--mono">{order.reference}</span>
              </div>
              <div className="ac-detail-row">
                <span className="ac-detail-row__label">Transaction</span>
                <span className="ac-detail-row__value ac-detail-row__value--mono">{order.transaction_reference}</span>
              </div>
              <div className="ac-detail-row">
                <span className="ac-detail-row__label">Date</span>
                <span className="ac-detail-row__value">{formatDate(order.created_at)}</span>
              </div>
              <div className="ac-detail-row">
                <span className="ac-detail-row__label">Quantité</span>
                <span className="ac-detail-row__value">{order.quantity}</span>
              </div>
              <div className="ac-detail-row">
                <span className="ac-detail-row__label">Prix unitaire</span>
                <span className="ac-detail-row__value">{formatAmount(order.unit_amount, order.currency_code)}</span>
              </div>
              <div className="ac-detail-row ac-detail-row--total">
                <span className="ac-detail-row__label">Total</span>
                <span className="ac-detail-row__value">{formatAmount(order.total_amount, order.currency_code)}</span>
              </div>
            </div>
          </div>

          {order.access_passes.length > 0 && (
            <div className="ac-detail-panel" style={{ marginTop: "24px" }}>
              <div className="ac-detail-panel__head">
                <span className="ac-detail-panel__title">Passes d&apos;accès ({order.access_passes.length})</span>
              </div>
              <div className="ac-detail-panel__body">
                <div className="ac-list">
                  {order.access_passes.map((pass) => (
                    <Link
                      key={pass.id}
                      href={`/compte/passes/${pass.public_id}`}
                      className="ac-card"
                      style={{ padding: "14px 16px" }}
                    >
                      <div className="ac-card__icon ac-card__icon--teal">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                          <rect width="5" height="5" x="3" y="3" rx="1" />
                          <rect width="5" height="5" x="16" y="3" rx="1" />
                          <rect width="5" height="5" x="3" y="16" rx="1" />
                          <path d="M21 16h-3a2 2 0 0 0-2 2v3" />
                          <path d="M21 21v.01" />
                        </svg>
                      </div>
                      <div className="ac-card__body">
                        <p className="ac-card__title">{pass.holder_name ?? "Porteur anonyme"}</p>
                        <p className="ac-card__meta">
                          {PASS_TYPE_LABELS[pass.type] ?? pass.type}
                          <span className="ac-card__meta-sep" />
                          <span className="ac-detail-row__value--mono" style={{ fontSize: "0.78rem" }}>
                            {pass.access_code.slice(0, 12)}…
                          </span>
                        </p>
                      </div>
                      <span className={`ac-badge ac-badge--${pass.status}`}>
                        <span className="ac-badge__dot" />
                        {pass.status}
                      </span>
                      <svg className="ac-card__arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <path d="m9 18 6-6-6-6" />
                      </svg>
                    </Link>
                  ))}
                </div>
              </div>
            </div>
          )}
        </div>

        <div>
          <div className="ac-detail-panel">
            <div className="ac-detail-panel__head">
              <span className="ac-detail-panel__title">Acheteur</span>
            </div>
            <div className="ac-detail-panel__body">
              <div className="ac-detail-row">
                <span className="ac-detail-row__label">Nom</span>
                <span className="ac-detail-row__value">{order.buyer_name ?? "—"}</span>
              </div>
              <div className="ac-detail-row">
                <span className="ac-detail-row__label">E-mail</span>
                <span className="ac-detail-row__value">{order.buyer_email ?? "—"}</span>
              </div>
              <div className="ac-detail-row">
                <span className="ac-detail-row__label">Téléphone</span>
                <span className="ac-detail-row__value">{order.buyer_phone ?? "—"}</span>
              </div>
            </div>
          </div>

          {order.receipt && (
            <div className="ac-detail-panel" style={{ marginTop: "16px" }}>
              <div className="ac-detail-panel__head">
                <span className="ac-detail-panel__title">Reçu</span>
              </div>
              <div className="ac-detail-panel__body">
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Référence</span>
                  <span className="ac-detail-row__value ac-detail-row__value--mono">{order.receipt.reference}</span>
                </div>
                <div className="ac-detail-row">
                  <span className="ac-detail-row__label">Statut</span>
                  <span className={`ac-badge ac-badge--${order.receipt.status}`}>
                    <span className="ac-badge__dot" />
                    {order.receipt.status}
                  </span>
                </div>
                <div className="ac-detail-row" style={{ border: "none", paddingTop: "12px" }}>
                  <Link
                    href={`/compte/recus/${order.receipt.reference}`}
                    className="button button--small"
                    style={{ width: "100%", textAlign: "center" }}
                  >
                    Voir le reçu
                  </Link>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </>
  );
}
