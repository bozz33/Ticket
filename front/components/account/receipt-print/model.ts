import type { AccountReceipt } from "@/lib/types";
import { buildPublicUrl } from "@/lib/utils";

export type ReceiptPrintModel = {
  buyerPhone: string;
  gatewayTransactionId: string;
  offerName: string;
  orderReference: string;
  paymentMethod: string;
  paymentReference: string;
  providerReference: string;
  quantity: number;
  serviceDescription: string;
  unitAmount: number;
  verificationUrl: string;
};

function metaString(receipt: AccountReceipt, key: string) {
  const value = receipt.meta?.[key];

  return typeof value === "string" && value.length > 0 ? value : null;
}

export function buildReceiptPrintModel(receipt: AccountReceipt, tenantSlug: string): ReceiptPrintModel {
  const orderReference = receipt.order?.reference ?? "—";
  const paymentReference = metaString(receipt, "transaction_reference") ?? receipt.order?.transaction_reference ?? "—";
  const gatewayTransactionId =
    typeof receipt.meta?.gateway_transaction_id === "string" || typeof receipt.meta?.gateway_transaction_id === "number"
      ? String(receipt.meta.gateway_transaction_id)
      : "—";
  const providerReference = metaString(receipt, "gateway_reference") ?? paymentReference ?? "—";
  const paymentMethod =
    metaString(receipt, "payment_method_label") ?? metaString(receipt, "payment_method") ?? "Paiement électronique";
  const verificationUrl = buildPublicUrl(`/verifier/recu/${tenantSlug}/${encodeURIComponent(receipt.reference)}`);
  const offerName = receipt.order?.offer?.name ?? metaString(receipt, "offer_name") ?? "Achat Ticket";
  const quantity = receipt.order?.quantity ?? 1;
  const unitAmount =
    typeof receipt.order?.unit_amount === "number" && quantity > 0
      ? receipt.order.unit_amount
      : Math.round(receipt.total_amount / Math.max(quantity, 1));
  const buyerPhone = receipt.order?.buyer_phone ?? receipt.buyer_phone ?? "—";
  const serviceDescription =
    metaString(receipt, "service_description") ??
    `${offerName} - confirmation officielle de votre achat sur Ticket Public Marketplace.`;

  return {
    buyerPhone,
    gatewayTransactionId,
    offerName,
    orderReference,
    paymentMethod,
    paymentReference,
    providerReference,
    quantity,
    serviceDescription,
    unitAmount,
    verificationUrl,
  };
}
