import Link from "next/link";

import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountOrders } from "@/lib/data/account";
import type { OrderStatus } from "@/lib/types";

export const dynamic = "force-dynamic";

function formatAmount(minor: number, currency: string) {
  return new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency: currency.toUpperCase(),
    minimumFractionDigits: 0,
  }).format(minor / 100);
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString("fr-FR", {
    day: "numeric",
    month: "long",
    year: "numeric",
  });
}

const STATUS_LABELS: Record<OrderStatus, string> = {
  pending: "En attente",
  confirmed: "Confirmée",
  cancelled: "Annulée",
  refunded: "Remboursée",
};

export default async function CommandesPage() {
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);
  const orders = token ? await getAccountOrders(tenantSlug, token) : [];

  return (
    <>
      <div className="ac-page-header">
        <h1 className="ac-page-title">Commandes</h1>
        <p className="ac-page-sub">Historique de toutes vos commandes.</p>
      </div>

      {orders.length === 0 ? (
        <div className="ac-empty">
          <svg className="ac-empty__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
            <line x1="3" x2="21" y1="6" y2="6" />
            <path d="M16 10a4 4 0 0 1-8 0" />
          </svg>
          <p className="ac-empty__title">Aucune commande</p>
          <p className="ac-empty__text">Vos commandes apparaîtront ici après un achat.</p>
        </div>
      ) : (
        <div className="ac-list">
          {orders.map((order) => (
            <Link key={order.id} href={`/compte/commandes/${order.reference}`} className="ac-card">
              <div className="ac-card__icon ac-card__icon--gold">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                  <line x1="3" x2="21" y1="6" y2="6" />
                  <path d="M16 10a4 4 0 0 1-8 0" />
                </svg>
              </div>
              <div className="ac-card__body">
                <p className="ac-card__title">{order.offer?.name ?? `Commande #${order.reference}`}</p>
                <p className="ac-card__meta">
                  <span>Réf. {order.reference}</span>
                  <span className="ac-card__meta-sep" />
                  <span>{formatDate(order.created_at)}</span>
                  {order.quantity > 1 && (
                    <>
                      <span className="ac-card__meta-sep" />
                      <span>{order.quantity} unités</span>
                    </>
                  )}
                </p>
              </div>
              <div className="ac-card__right">
                <p className="ac-card__amount">{formatAmount(order.total_amount, order.currency_code)}</p>
                <span className={`ac-badge ac-badge--${order.status}`}>
                  <span className="ac-badge__dot" />
                  {STATUS_LABELS[order.status]}
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
