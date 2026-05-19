import Link from "next/link";

import type { AccountOrder } from "@/lib/types";

import { formatAccountHomeAmount, formatAccountHomeDate } from "./helpers";

type LastOrderPanelProps = {
  order: AccountOrder | null;
};

export function LastOrderPanel({ order }: LastOrderPanelProps) {
  return (
    <div className="ac-detail" style={{ marginTop: "28px" }}>
      <div>
        <div className="ac-detail-panel">
          <div className="ac-detail-panel__head">
            <span className="ac-detail-panel__title">Dernière commande</span>
          </div>
          <div className="ac-detail-panel__body">
            {order ? (
              <div className="ac-list">
                <Link className="ac-card" href={`/compte/commandes/${order.reference}`}>
                  <div className="ac-card__icon ac-card__icon--gold">
                    <svg
                      fill="none"
                      height="18"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth="2"
                      viewBox="0 0 24 24"
                      width="18"
                    >
                      <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                      <line x1="3" x2="21" y1="6" y2="6" />
                      <path d="M16 10a4 4 0 0 1-8 0" />
                    </svg>
                  </div>
                  <div className="ac-card__body">
                    <p className="ac-card__title">{order.offer?.name ?? order.reference}</p>
                    <p className="ac-card__meta">
                      <span>{order.reference}</span>
                      <span className="ac-card__meta-sep" />
                      <span>{formatAccountHomeDate(order.created_at)}</span>
                    </p>
                  </div>
                  <div className="ac-card__right">
                    <span className={`ac-badge ac-badge--${order.status}`}>
                      <span className="ac-badge__dot" />
                      {order.status === "refund_pending" ? "Remboursement" : order.status}
                    </span>
                    <span className="ac-card__amount">
                      {formatAccountHomeAmount(order.total_amount, order.currency_code)}
                    </span>
                    <svg
                      className="ac-card__arrow"
                      fill="none"
                      height="16"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth="2"
                      viewBox="0 0 24 24"
                      width="16"
                    >
                      <path d="m9 18 6-6-6-6" />
                    </svg>
                  </div>
                </Link>
              </div>
            ) : (
              <div className="ac-empty" style={{ padding: "28px 0" }}>
                <p className="ac-empty__title">Aucune commande pour le moment</p>
                <p className="ac-empty__text">Vos achats et leurs statuts apparaîtront ici.</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
