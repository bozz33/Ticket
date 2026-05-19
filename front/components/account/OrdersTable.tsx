"use client";

import Link from "next/link";
import { useMemo, useState } from "react";

import type { AccountOrder, OrderStatus } from "@/lib/types";

import { AccountTablePagination } from "./AccountTablePagination";

const PAGE_SIZE = 10;

const STATUS_LABELS: Record<OrderStatus, string> = {
  pending: "En attente",
  confirmed: "Confirmee",
  cancelled: "Annulee",
  refund_pending: "Remboursement en cours",
  refunded: "Remboursee",
};

function formatAmount(amount: number, currency: string) {
  return new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency: currency.toUpperCase(),
    minimumFractionDigits: 0,
  }).format(amount);
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString("fr-FR", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
}

export function OrdersTable({ orders }: { orders: AccountOrder[] }) {
  const [page, setPage] = useState(1);

  const rows = useMemo(() => {
    const start = (page - 1) * PAGE_SIZE;

    return orders.slice(start, start + PAGE_SIZE);
  }, [orders, page]);

  return (
    <div className="ac-table-block">
      <div className="ac-table-shell">
        <table className="ac-table">
          <thead>
            <tr>
              <th>Commande</th>
              <th>Offre</th>
              <th>Telephone</th>
              <th>Quantite</th>
              <th>Montant</th>
              <th>Statut</th>
              <th>Date</th>
              <th aria-label="Action" />
            </tr>
          </thead>
          <tbody>
            {rows.map((order) => (
              <tr key={order.id}>
                <td>
                  <div className="ac-table__primary">{order.reference}</div>
                  <div className="ac-table__secondary">{order.transaction_reference}</div>
                </td>
                <td>
                  <div className="ac-table__primary">{order.offer?.name ?? "Commande"}</div>
                  <div className="ac-table__secondary">{order.buyer_email ?? "—"}</div>
                </td>
                <td>{order.buyer_phone ?? "—"}</td>
                <td>{order.quantity}</td>
                <td>{formatAmount(order.total_amount, order.currency_code)}</td>
                <td>
                  <span className={`ac-badge ac-badge--${order.status}`}>
                    <span className="ac-badge__dot" />
                    {STATUS_LABELS[order.status] ?? order.status}
                  </span>
                </td>
                <td>{formatDate(order.created_at)}</td>
                <td className="ac-table__action-cell">
                  <Link className="ac-table__action" href={`/compte/commandes/${order.reference}`}>
                    Voir
                  </Link>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className="ac-table-mobile">
        {rows.map((order) => (
          <Link className="ac-table-card" href={`/compte/commandes/${order.reference}`} key={order.id}>
            <div className="ac-table-card__top">
              <div>
                <p className="ac-table-card__title">{order.offer?.name ?? "Commande"}</p>
                <p className="ac-table-card__sub">{order.reference}</p>
              </div>
              <span className={`ac-badge ac-badge--${order.status}`}>
                <span className="ac-badge__dot" />
                {STATUS_LABELS[order.status] ?? order.status}
              </span>
            </div>
            <dl className="ac-table-card__grid">
              <div>
                <dt>Montant</dt>
                <dd>{formatAmount(order.total_amount, order.currency_code)}</dd>
              </div>
              <div>
                <dt>Date</dt>
                <dd>{formatDate(order.created_at)}</dd>
              </div>
              <div>
                <dt>Telephone</dt>
                <dd>{order.buyer_phone ?? "—"}</dd>
              </div>
              <div>
                <dt>Quantite</dt>
                <dd>{order.quantity}</dd>
              </div>
            </dl>
          </Link>
        ))}
      </div>

      <AccountTablePagination
        onPageChange={setPage}
        page={page}
        pageSize={PAGE_SIZE}
        total={orders.length}
      />
    </div>
  );
}
