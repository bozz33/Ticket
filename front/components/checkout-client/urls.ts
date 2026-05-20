import { getCheckoutSelectionParamName } from "@/lib/checkout/selection";
import type { PublicContent } from "@/lib/types";

export function buildCheckoutCallbackUrl(item: PublicContent, selectedOffer: PublicContent["tiers"][number]) {
  const callbackUrl = new URL(`/checkout/${item.module}/${item.slug}/succes`, window.location.origin);
  callbackUrl.searchParams.set(getCheckoutSelectionParamName(selectedOffer), selectedOffer.id);

  if (item.organizerSlug) {
    callbackUrl.searchParams.set("tenant", item.organizerSlug);
  }

  return callbackUrl;
}

export function buildCheckoutSuccessUrl(item: PublicContent, selectedOffer: PublicContent["tiers"][number] | null, reference: string) {
  const successUrl = new URL(`/checkout/${item.module}/${item.slug}/succes`, window.location.origin);

  if (selectedOffer?.id) {
    successUrl.searchParams.set(getCheckoutSelectionParamName(selectedOffer), selectedOffer.id);
  }

  if (item.organizerSlug) {
    successUrl.searchParams.set("tenant", item.organizerSlug);
  }

  successUrl.searchParams.set("tx", reference);

  return successUrl.toString();
}
