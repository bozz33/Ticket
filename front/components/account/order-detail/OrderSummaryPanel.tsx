import type { AccountOrder } from "@/lib/types";

import { formatAccountAmount, formatAccountDate } from "./helpers";

type OrderSummaryPanelProps = {
  order: AccountOrder;
};

export function OrderSummaryPanel({ order }: OrderSummaryPanelProps) {
  return (
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
          <span className="ac-detail-row__value">{formatAccountDate(order.created_at)}</span>
        </div>
        <div className="ac-detail-row">
          <span className="ac-detail-row__label">Quantité</span>
          <span className="ac-detail-row__value">{order.quantity}</span>
        </div>
        <div className="ac-detail-row">
          <span className="ac-detail-row__label">Prix unitaire</span>
          <span className="ac-detail-row__value">{formatAccountAmount(order.unit_amount, order.currency_code)}</span>
        </div>
        <div className="ac-detail-row ac-detail-row--total">
          <span className="ac-detail-row__label">Total</span>
          <span className="ac-detail-row__value">{formatAccountAmount(order.total_amount, order.currency_code)}</span>
        </div>
      </div>
    </div>
  );
}
