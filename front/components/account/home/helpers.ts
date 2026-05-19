import type { AccountAccessPass, AccountOrder } from "@/lib/types";

export type AccountHomeStat = {
  label: string;
  value: string;
  hint: string;
};

export function formatAccountHomeAmount(amount: number, currency: string) {
  return new Intl.NumberFormat("fr-FR", {
    currency: currency.toUpperCase(),
    minimumFractionDigits: 0,
    style: "currency",
  }).format(amount);
}

export function formatAccountHomeDate(value: string | null | undefined) {
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

export function buildAccountHomeStats(orders: AccountOrder[], passes: AccountAccessPass[]): AccountHomeStat[] {
  const confirmedOrders = orders.filter((order) => order.status === "confirmed");
  const refundedOrders = orders.filter((order) => order.status === "refunded" || order.status === "refund_pending");
  const activePasses = passes.filter((pass) => pass.status === "active");
  const totalSpent = confirmedOrders.reduce((sum, order) => sum + order.total_amount, 0);
  const primaryCurrency = confirmedOrders[0]?.currency_code ?? orders[0]?.currency_code ?? "XOF";
  const refundPendingCount = orders.filter((order) => order.status === "refund_pending").length;

  return [
    {
      label: "Commandes",
      value: String(orders.length),
      hint: `${confirmedOrders.length} confirmée${confirmedOrders.length > 1 ? "s" : ""}`,
    },
    {
      label: "Montant dépensé",
      value: formatAccountHomeAmount(totalSpent, primaryCurrency),
      hint: "Total des commandes confirmées",
    },
    {
      label: "Passes actifs",
      value: String(activePasses.length),
      hint: `${passes.length} pass${passes.length > 1 ? "es" : ""} au total`,
    },
    {
      label: "Remboursements",
      value: String(refundedOrders.length),
      hint: `${refundPendingCount} demande${refundPendingCount > 1 ? "s" : ""} en cours`,
    },
  ];
}
