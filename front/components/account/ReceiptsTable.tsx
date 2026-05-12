"use client";

import Link from "next/link";
import { useMemo, useState } from "react";

import type { AccountReceipt, ReceiptStatus } from "@/lib/types";

import { AccountTablePagination } from "./AccountTablePagination";

const PAGE_SIZE = 10;

const STATUS_LABELS: Record<ReceiptStatus, string> = {
  issued: "Emis",
  cancelled: "Annule",
  refunded: "Rembourse",
};

function formatAmount(amount: number, currency: string) {
  return new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency: currency.toUpperCase(),
    minimumFractionDigits: 0,
  }).format(amount);
}

function formatDate(iso: string | null) {
  if (!iso) {
    return "—";
  }

  return new Date(iso).toLocaleDateString("fr-FR", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
}

export function ReceiptsTable({ receipts }: { receipts: AccountReceipt[] }) {
  const [page, setPage] = useState(1);

  const rows = useMemo(() => {
    const start = (page - 1) * PAGE_SIZE;

    return receipts.slice(start, start + PAGE_SIZE);
  }, [page, receipts]);

  return (
    <div className="ac-table-block">
      <div className="ac-table-shell">
        <table className="ac-table">
          <thead>
            <tr>
              <th>N° recu</th>
              <th>Reference paiement</th>
              <th>Commande</th>
              <th>Telephone</th>
              <th>Montant</th>
              <th>Statut</th>
              <th>Emission</th>
              <th aria-label="Action" />
            </tr>
          </thead>
          <tbody>
            {rows.map((receipt) => {
              const paymentReference = String(receipt.meta?.transaction_reference ?? receipt.order?.transaction_reference ?? "—");
              const orderReference = receipt.order?.reference ?? "—";

              return (
                <tr key={receipt.id}>
                  <td>
                    <div className="ac-table__primary">{receipt.reference}</div>
                    <div className="ac-table__secondary">{receipt.buyer_name ?? "Acheteur"}</div>
                  </td>
                  <td>{paymentReference}</td>
                  <td>{orderReference}</td>
                  <td>{receipt.buyer_phone ?? receipt.order?.buyer_phone ?? "—"}</td>
                  <td>{formatAmount(receipt.total_amount, receipt.currency_code)}</td>
                  <td>
                    <span className={`ac-badge ac-badge--${receipt.status}`}>
                      <span className="ac-badge__dot" />
                      {STATUS_LABELS[receipt.status] ?? receipt.status}
                    </span>
                  </td>
                  <td>{formatDate(receipt.issued_at ?? receipt.created_at)}</td>
                  <td className="ac-table__action-cell">
                    <Link className="ac-table__action" href={`/compte/recus/${receipt.reference}`}>
                      Ouvrir
                    </Link>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      <div className="ac-table-mobile">
        {rows.map((receipt) => (
          <Link className="ac-table-card" href={`/compte/recus/${receipt.reference}`} key={receipt.id}>
            <div className="ac-table-card__top">
              <div>
                <p className="ac-table-card__title">{receipt.reference}</p>
                <p className="ac-table-card__sub">{String(receipt.meta?.transaction_reference ?? "Paiement")}</p>
              </div>
              <span className={`ac-badge ac-badge--${receipt.status}`}>
                <span className="ac-badge__dot" />
                {STATUS_LABELS[receipt.status] ?? receipt.status}
              </span>
            </div>
            <dl className="ac-table-card__grid">
              <div>
                <dt>Montant</dt>
                <dd>{formatAmount(receipt.total_amount, receipt.currency_code)}</dd>
              </div>
              <div>
                <dt>Emission</dt>
                <dd>{formatDate(receipt.issued_at ?? receipt.created_at)}</dd>
              </div>
              <div>
                <dt>Commande</dt>
                <dd>{receipt.order?.reference ?? "—"}</dd>
              </div>
              <div>
                <dt>Telephone</dt>
                <dd>{receipt.buyer_phone ?? receipt.order?.buyer_phone ?? "—"}</dd>
              </div>
            </dl>
          </Link>
        ))}
      </div>

      <AccountTablePagination
        onPageChange={setPage}
        page={page}
        pageSize={PAGE_SIZE}
        total={receipts.length}
      />
    </div>
  );
}
