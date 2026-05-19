import { RefundRequestForm } from "@/components/account/RefundRequestForm";
import type { AccountOrder } from "@/lib/types";

import { ORDER_STATUS_LABELS, type RefundRequestSummary, formatAccountDate } from "./helpers";

type OrderRefundPanelProps = {
  canRequestRefund: boolean;
  order: AccountOrder;
  refundRequest: RefundRequestSummary | null;
};

export function OrderRefundPanel({ canRequestRefund, order, refundRequest }: OrderRefundPanelProps) {
  return (
    <div className="ac-detail-panel" style={{ marginTop: "24px" }}>
      <div className="ac-detail-panel__head">
        <span className="ac-detail-panel__title">Remboursement</span>
      </div>
      <div className="ac-detail-panel__body">
        {refundRequest ? (
          <>
            <div className="ac-detail-row">
              <span className="ac-detail-row__label">Statut</span>
              <span className={`ac-badge ac-badge--${order.status}`}>
                <span className="ac-badge__dot" />
                {ORDER_STATUS_LABELS[order.status]}
              </span>
            </div>
            <div className="ac-detail-row">
              <span className="ac-detail-row__label">Demandé le</span>
              <span className="ac-detail-row__value">{formatAccountDate(refundRequest.requested_at ?? null)}</span>
            </div>
            <div className="ac-detail-row" style={{ borderBottom: "none" }}>
              <span className="ac-detail-row__label">Motif</span>
              <span className="ac-detail-row__value">{refundRequest.reason ?? refundRequest.reason_code ?? "—"}</span>
            </div>
          </>
        ) : (
          <RefundRequestForm orderReference={order.reference} disabled={!canRequestRefund} />
        )}
      </div>
    </div>
  );
}
