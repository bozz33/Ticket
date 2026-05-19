import Link from "next/link";

import type { AccountOrder } from "@/lib/types";

type OrderReceiptPanelProps = {
  receipt: AccountOrder["receipt"];
};

export function OrderReceiptPanel({ receipt }: OrderReceiptPanelProps) {
  if (!receipt) {
    return null;
  }

  return (
    <div className="ac-detail-panel" style={{ marginTop: "16px" }}>
      <div className="ac-detail-panel__head">
        <span className="ac-detail-panel__title">Reçu</span>
      </div>
      <div className="ac-detail-panel__body">
        <div className="ac-detail-row">
          <span className="ac-detail-row__label">Référence</span>
          <span className="ac-detail-row__value ac-detail-row__value--mono">{receipt.reference}</span>
        </div>
        <div className="ac-detail-row">
          <span className="ac-detail-row__label">Statut</span>
          <span className={`ac-badge ac-badge--${receipt.status}`}>
            <span className="ac-badge__dot" />
            {receipt.status}
          </span>
        </div>
        <div className="ac-detail-row" style={{ border: "none", paddingTop: "12px" }}>
          <Link
            className="button button--small"
            href={`/compte/recus/${receipt.reference}`}
            style={{ width: "100%", textAlign: "center" }}
          >
            Voir le reçu
          </Link>
        </div>
      </div>
    </div>
  );
}
