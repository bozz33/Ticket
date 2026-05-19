import type { PublicContent } from "@/lib/types";

export function buildPaymentReference(
  item: PublicContent,
  selectedOffer: PublicContent["tiers"][number] | null,
  providedReference?: string,
) {
  if (providedReference?.trim()) {
    return providedReference.trim();
  }

  const safeItem = item.id.replace(/[^a-z0-9]/gi, "").toUpperCase().slice(0, 6) || "ORDER";
  const safeOffer =
    (selectedOffer?.id ?? "BASE").replace(/[^a-z0-9]/gi, "").toUpperCase().slice(0, 4) || "BASE";

  return `PAY-${safeItem}-${safeOffer}`;
}

export function buildPaymentQuery(
  selectedOffer: PublicContent["tiers"][number] | null,
  paymentReference: string,
  paidAt: string,
  tenantSlug?: string,
) {
  const params = new URLSearchParams();

  if (selectedOffer?.id) {
    params.set("offer", selectedOffer.id);
  }

  params.set("tx", paymentReference);
  params.set("paidAt", paidAt);

  if (tenantSlug?.trim()) {
    params.set("tenant", tenantSlug.trim());
  }

  return `?${params.toString()}`;
}

export function normalizePaidAt(value?: string) {
  if (!value) {
    return new Date().toISOString();
  }

  const parsed = new Date(value);

  return Number.isNaN(parsed.getTime()) ? new Date().toISOString() : parsed.toISOString();
}
