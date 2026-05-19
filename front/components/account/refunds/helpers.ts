import type { AccountOrder } from "@/lib/types";

export type RefundRequestMeta = {
  requested_at?: string;
  reason?: string | null;
  reason_code?: string | null;
};

export function filterRefundOrders(orders: AccountOrder[]) {
  return orders.filter(
    (order) => order.status === "refund_pending" || order.status === "refunded" || Boolean(order.meta?.refund_request),
  );
}

export function getRefundRequestMeta(order: AccountOrder): RefundRequestMeta | null {
  return (order.meta?.refund_request ?? null) as RefundRequestMeta | null;
}

export function formatRefundAmount(amount: number, currency: string) {
  return new Intl.NumberFormat("fr-FR", {
    currency: currency.toUpperCase(),
    minimumFractionDigits: 0,
    style: "currency",
  }).format(amount);
}

export function formatRefundDate(value: string | null | undefined) {
  if (!value) {
    return "—";
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return "—";
  }

  return new Intl.DateTimeFormat("fr-FR", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  }).format(date);
}

export function refundStatusLabel(status: AccountOrder["status"]) {
  if (status === "refund_pending") {
    return "Remboursement en cours";
  }

  if (status === "refunded") {
    return "Remboursée";
  }

  return status;
}
