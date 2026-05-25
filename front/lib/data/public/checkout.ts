import type { CheckoutPaymentOptions, CheckoutVerificationResult, EventTicketTier, ModuleRoute, OfferTier, PublicContent } from "@/lib/types";
import { getCheckoutSelectionParamName } from "@/lib/checkout/selection";
import { formatDateRange } from "@/lib/utils";
import { getContentDetail } from "./catalog";
import { getPlatformConfiguration } from "./cms";
import { fetchJson, getTenantPublicPath, type PublicApiEnvelope } from "./shared";

export async function getCheckoutPaymentOptions(
  checkoutItemId: string,
  quantity = 1,
  paymentMethod?: string,
  tenantSlug?: string,
  selectionType: "offer" | "ticket" = "offer",
  customAmount?: number,
): Promise<CheckoutPaymentOptions | null> {
  const paymentMethodQuery = paymentMethod ? `&payment_method=${encodeURIComponent(paymentMethod)}` : "";
  const customAmountQuery = typeof customAmount === "number" && Number.isFinite(customAmount) && customAmount > 0
    ? `&custom_amount=${Math.trunc(customAmount)}`
    : "";
  const path = await getTenantPublicPath(
    `/payment-options?${selectionType}=${encodeURIComponent(checkoutItemId)}&quantity=${quantity}${paymentMethodQuery}${customAmountQuery}`,
    tenantSlug,
  );

  if (!path) {
    return null;
  }

  const payload = await fetchJson<PublicApiEnvelope<CheckoutPaymentOptions>>(path, {
    noStore: true,
    timeoutMs: 5000,
  });

  return payload?.data ?? null;
}

export async function verifyCheckoutPayment(
  reference: string,
  tenantSlug?: string,
): Promise<CheckoutVerificationResult | null> {
  const path = await getTenantPublicPath(
    `/payments/verify/${encodeURIComponent(reference)}`,
    tenantSlug,
  );

  if (!path) {
    return null;
  }

  const payload = await fetchJson<PublicApiEnvelope<CheckoutVerificationResult>>(path, {
    noStore: true,
    timeoutMs: 5000,
  });

  return payload?.data ?? null;
}

export async function getCheckoutData(module: ModuleRoute, slug: string, offerId?: string, tenantSlug?: string) {
  const [platform, item] = await Promise.all([
    getPlatformConfiguration(),
    getContentDetail(module, slug, tenantSlug),
  ]);

  if (!item) {
    return null;
  }

  const selectedOffer = resolveCheckoutSelection(item, offerId);
  const paymentOptions = selectedOffer
    ? await getCheckoutPaymentOptions(
      selectedOffer.id,
      1,
      item.module === "crowdfunding" || selectedOffer.price > 0 ? "card" : "free",
      item.organizerSlug,
      getCheckoutSelectionParamName(selectedOffer),
      item.module === "crowdfunding" ? Math.max(1, selectedOffer.price || item.priceFrom || 1000) : undefined,
    )
    : null;

  return {
    platform,
    item,
    selectedOffer,
    dateLabel: formatDateRange(item),
    paymentOptions,
  };
}

function resolveCheckoutSelection(item: PublicContent, identifier?: string): OfferTier | null {
  const ticket = resolveEventTicketSelection(item, identifier);

  if (ticket) {
    return ticketToCheckoutOffer(ticket);
  }

  return item.tiers.find((tier) => tier.id === identifier) ?? item.tiers[0] ?? null;
}

function resolveEventTicketSelection(item: PublicContent, identifier?: string): EventTicketTier | null {
  const tickets = item.module === "evenements" ? (item.tickets ?? []) : [];

  if (tickets.length === 0) {
    return null;
  }

  if (!identifier) {
    return tickets.find((ticket) => ticket.isAvailable) ?? tickets[0] ?? null;
  }

  return tickets.find((ticket) => ticket.id === identifier || ticket.offerId === identifier) ?? null;
}

function ticketToCheckoutOffer(ticket: EventTicketTier): OfferTier {
  return {
    id: ticket.id,
    title: ticket.title,
    subtitle: ticket.subtitle ?? undefined,
    price: ticket.price,
    currency: ticket.currency ?? "XOF",
    remaining: ticket.remaining ?? undefined,
    quantityLabel: ticket.quantityLabel ?? undefined,
    ctaLabel: ticket.ctaLabel,
    perks: ticket.perks,
    source: "event_ticket",
    ticketId: ticket.id,
    availabilityStatus: ticket.availabilityStatus,
    availabilityLabel: ticket.availabilityLabel,
    isAvailable: ticket.isAvailable,
    isSoldOut: ticket.isSoldOut,
  };
}

