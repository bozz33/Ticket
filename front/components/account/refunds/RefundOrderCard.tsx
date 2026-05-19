import Link from "next/link";

import type { AccountOrder } from "@/lib/types";

import {
  formatRefundAmount,
  formatRefundDate,
  getRefundRequestMeta,
  refundStatusLabel,
} from "./helpers";

type RefundOrderCardProps = {
  order: AccountOrder;
};

export function RefundOrderCard({ order }: RefundOrderCardProps) {
  const refundRequest = getRefundRequestMeta(order);

  return (
    <Link className="ac-card" href={`/compte/commandes/${order.reference}`} key={order.id}>
      <div className="ac-card__icon ac-card__icon--dark">
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
          <path d="M3 12a9 9 0 1 0 3-6.708" />
          <path d="M3 3v6h6" />
          <path d="M12 7v5l3 3" />
        </svg>
      </div>
      <div className="ac-card__body">
        <p className="ac-card__title">{order.offer?.name ?? order.reference}</p>
        <p className="ac-card__meta">
          <span>{order.reference}</span>
          <span className="ac-card__meta-sep" />
          <span>{formatRefundDate(refundRequest?.requested_at ?? order.created_at)}</span>
          <span className="ac-card__meta-sep" />
          <span>{refundRequest?.reason ?? refundRequest?.reason_code ?? "Demande acheteur"}</span>
        </p>
      </div>
      <div className="ac-card__right">
        <span className={`ac-badge ac-badge--${order.status}`}>
          <span className="ac-badge__dot" />
          {refundStatusLabel(order.status)}
        </span>
        <span className="ac-card__amount">{formatRefundAmount(order.total_amount, order.currency_code)}</span>
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
  );
}
