import type { AccountOrder } from "@/lib/types";

import { type RefundRequestSummary } from "./helpers";
import { OrderAccessPassesPanel } from "./OrderAccessPassesPanel";
import { OrderBackLink } from "./OrderBackLink";
import { OrderBuyerPanel } from "./OrderBuyerPanel";
import { OrderHeader } from "./OrderHeader";
import { OrderReceiptPanel } from "./OrderReceiptPanel";
import { OrderRefundPanel } from "./OrderRefundPanel";
import { OrderSummaryPanel } from "./OrderSummaryPanel";

type OrderDetailViewProps = {
  canRequestRefund: boolean;
  order: AccountOrder;
  refundRequest: RefundRequestSummary | null;
};

export function OrderDetailView({ canRequestRefund, order, refundRequest }: OrderDetailViewProps) {
  return (
    <>
      <OrderBackLink />
      <OrderHeader order={order} />

      <div className="ac-detail">
        <div>
          <OrderSummaryPanel order={order} />
          <OrderAccessPassesPanel passes={order.access_passes} />
          <OrderRefundPanel
            canRequestRefund={canRequestRefund}
            order={order}
            refundRequest={refundRequest}
          />
        </div>

        <div>
          <OrderBuyerPanel order={order} />
          <OrderReceiptPanel receipt={order.receipt} />
        </div>
      </div>
    </>
  );
}
