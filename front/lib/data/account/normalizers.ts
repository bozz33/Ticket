import type { AccountOrder, AccountReceipt } from "@/lib/types";

function toFiniteNumber(value: unknown): number | null {
  if (typeof value === "number" && Number.isFinite(value)) {
    return value;
  }

  if (typeof value === "string" && value.trim() !== "") {
    const parsed = Number(value);

    if (Number.isFinite(parsed)) {
      return parsed;
    }
  }

  return null;
}

function toRecord(value: unknown): Record<string, unknown> | null {
  if (typeof value !== "object" || value === null || Array.isArray(value)) {
    return null;
  }

  return value as Record<string, unknown>;
}

function normalizeLegacyAmount(value: unknown): unknown {
  const amount = toFiniteNumber(value);

  if (amount === null) {
    return value;
  }

  return Math.round(amount / 100);
}

function shouldNormalizeLegacySubunitRecord(record: Record<string, unknown>): boolean {
  const totalAmount = toFiniteNumber(record.total_amount);

  if (totalAmount === null || totalAmount <= 0) {
    return false;
  }

  const meta = toRecord(record.meta);
  const order = toRecord(record.order);
  const orderMeta = toRecord(order?.meta);
  const pricingSnapshot =
    toRecord(record.pricing_snapshot) ||
    toRecord(meta?.pricing_snapshot) ||
    toRecord(order?.pricing_snapshot) ||
    toRecord(orderMeta?.pricing_snapshot);
  const pricingTotal = toFiniteNumber(pricingSnapshot?.total);

  if (pricingTotal !== null && pricingTotal > 0 && totalAmount === pricingTotal * 100) {
    return true;
  }

  const subtotalAmount = toFiniteNumber(record.subtotal_amount);
  const customerFeeAmount = toFiniteNumber(record.customer_fee_amount);

  if (
    subtotalAmount !== null &&
    customerFeeAmount !== null &&
    subtotalAmount + customerFeeAmount > 0 &&
    totalAmount === (subtotalAmount + customerFeeAmount) * 100
  ) {
    return true;
  }

  const unitAmount = toFiniteNumber(record.unit_amount);
  const quantity = toFiniteNumber(record.quantity);

  if (unitAmount !== null && quantity !== null && unitAmount * quantity > 0 && totalAmount === unitAmount * quantity * 100) {
    return true;
  }

  return false;
}

function normalizeLegacySubunitRecord<T extends Record<string, unknown>>(record: T): T {
  if (!shouldNormalizeLegacySubunitRecord(record)) {
    return record;
  }

  return {
    ...record,
    total_amount: normalizeLegacyAmount(record.total_amount),
    refunded_amount: normalizeLegacyAmount(record.refunded_amount),
  } as T;
}

export function normalizeAccountOrderRecord(order: AccountOrder, includeReceipt = true): AccountOrder {
  const normalized = normalizeLegacySubunitRecord({ ...order }) as AccountOrder;

  if (includeReceipt && normalized.receipt) {
    normalized.receipt = normalizeAccountReceiptRecord(normalized.receipt, false);
  }

  return normalized;
}

export function normalizeAccountReceiptRecord(receipt: AccountReceipt, includeOrder = true): AccountReceipt {
  const normalized = normalizeLegacySubunitRecord({ ...receipt }) as AccountReceipt;

  if (includeOrder && normalized.order) {
    normalized.order = normalizeAccountOrderRecord(normalized.order, false);
  }

  return normalized;
}
