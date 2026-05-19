import type { AccountOrder } from "@/lib/types";

import { filterRefundOrders } from "./helpers";
import { RefundEmptyState } from "./RefundEmptyState";
import { RefundOrderCard } from "./RefundOrderCard";

type RefundsViewProps = {
  orders: AccountOrder[];
};

export function RefundsView({ orders }: RefundsViewProps) {
  const refundOrders = filterRefundOrders(orders);

  return (
    <>
      <div className="ac-page-header">
        <h1 className="ac-page-title">Remboursements</h1>
        <p className="ac-page-sub">Suivez ici vos demandes en cours et les remboursements déjà traités.</p>
      </div>

      {refundOrders.length === 0 ? (
        <RefundEmptyState />
      ) : (
        <div className="ac-list">
          {refundOrders.map((order) => (
            <RefundOrderCard key={order.id} order={order} />
          ))}
        </div>
      )}
    </>
  );
}
