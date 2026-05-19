import type { OrderStatus } from "@/lib/types";

export type RefundRequestSummary = {
  requested_at?: string;
  reason_code?: string;
  reason?: string | null;
};

export const ORDER_STATUS_LABELS: Record<OrderStatus, string> = {
  pending: "En attente",
  confirmed: "Confirmée",
  cancelled: "Annulée",
  refund_pending: "Remboursement en cours",
  refunded: "Remboursée",
};

export const PASS_TYPE_LABELS: Record<string, string> = {
  event_ticket: "Billet",
  training_enrollment: "Inscription formation",
  stand_reservation: "Réservation stand",
  purchase_pass: "Pass achat",
};

export function formatAccountAmount(amount: number, currency: string) {
  return new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency: currency.toUpperCase(),
    minimumFractionDigits: 0,
  }).format(amount);
}

export function formatAccountDate(iso: string | null) {
  if (!iso) return "—";
  return new Date(iso).toLocaleDateString("fr-FR", {
    day: "numeric",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}
