import type { AccountReceipt, ReceiptStatus } from "@/lib/types";

export const RECEIPT_STATUS_LABELS: Record<ReceiptStatus, string> = {
  cancelled: "Annulé",
  issued: "Émis",
  refunded: "Remboursé",
};

export function formatReceiptAmount(amount: number, currency: string) {
  return new Intl.NumberFormat("fr-FR", {
    currency: currency.toUpperCase(),
    minimumFractionDigits: 0,
    style: "currency",
  }).format(amount);
}

export function formatReceiptDate(iso: string | null) {
  if (!iso) {
    return "—";
  }

  return new Date(iso).toLocaleDateString("fr-FR", {
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    month: "long",
    year: "numeric",
  });
}

export function receiptPaymentReference(receipt: AccountReceipt) {
  return typeof receipt.meta?.transaction_reference === "string"
    ? receipt.meta.transaction_reference
    : receipt.order?.transaction_reference ?? "—";
}

export function receiptGatewayReference(receipt: AccountReceipt) {
  if (typeof receipt.meta?.gateway_transaction_id === "string" || typeof receipt.meta?.gateway_transaction_id === "number") {
    return String(receipt.meta.gateway_transaction_id);
  }

  return typeof receipt.meta?.gateway_reference === "string" ? receipt.meta.gateway_reference : "—";
}
