import type { CheckoutPaymentOptions, CheckoutVerificationResult, ModuleRoute } from "@/lib/types";
import { formatDateRange } from "@/lib/utils";
import { getContentDetail } from "./catalog";
import { getPlatformConfiguration } from "./cms";
import { fetchJson, getTenantPublicPath, type PublicApiEnvelope } from "./shared";

export async function getCheckoutPaymentOptions(
  offerId: string,
  quantity = 1,
  paymentMethod?: string,
  tenantSlug?: string,
): Promise<CheckoutPaymentOptions | null> {
  const paymentMethodQuery = paymentMethod ? `&payment_method=${encodeURIComponent(paymentMethod)}` : "";
  const path = await getTenantPublicPath(
    `/payment-options?offer=${encodeURIComponent(offerId)}&quantity=${quantity}${paymentMethodQuery}`,
    tenantSlug,
  );

  if (!path) {
    return null;
  }

  const payload = await fetchJson<PublicApiEnvelope<CheckoutPaymentOptions>>(path, { revalidate: 45 });

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

  const payload = await fetchJson<PublicApiEnvelope<CheckoutVerificationResult>>(path, { noStore: true });

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

  const selectedOffer = item.tiers.find((tier) => tier.id === offerId) ?? item.tiers[0] ?? null;
  const paymentOptions = selectedOffer
    ? await getCheckoutPaymentOptions(
      selectedOffer.id,
      1,
      selectedOffer.price > 0 ? "card" : "free",
      item.organizerSlug,
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

