import type { AccountOrder } from "@/lib/types";

import { ORDER_STATUS_LABELS } from "./helpers";

type OrderHeaderProps = {
  order: AccountOrder;
};

export function OrderHeader({ order }: OrderHeaderProps) {
  return (
    <div className="ac-page-header">
      <h1 className="ac-page-title">{order.offer?.name ?? `Commande #${order.reference}`}</h1>
      <p className="ac-page-sub">
        <span className={`ac-badge ac-badge--${order.status}`}>
          <span className="ac-badge__dot" />
          {ORDER_STATUS_LABELS[order.status]}
        </span>
      </p>
    </div>
  );
}
