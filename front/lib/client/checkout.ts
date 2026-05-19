import type {
  CheckoutInitializationResult,
  CheckoutPaymentOptions,
  ModuleRoute,
} from "@/lib/types";

type CheckoutInitializationInput = {
  offer: string;
  quantity: number;
  payment_method?: string;
  content_module: ModuleRoute;
  content_slug: string;
  callback_url: string;
  tenant?: string;
};

type ApiEnvelope<T> = {
  data?: T;
  message?: string;
  error?: string;
  code?: string;
};

export async function getCheckoutPaymentOptions(
  offerId: string,
  quantity = 1,
  paymentMethod?: string,
  tenantSlug?: string,
): Promise<CheckoutPaymentOptions | null> {
  const params = new URLSearchParams({
    offer: offerId,
    quantity: String(quantity),
  });

  if (paymentMethod) {
    params.set("payment_method", paymentMethod);
  }

  if (tenantSlug) {
    params.set("tenant", tenantSlug);
  }

  try {
    const response = await fetch(`/api/checkout/payment-options?${params.toString()}`, {
      cache: "no-store",
      headers: {
        Accept: "application/json",
      },
    });

    if (!response.ok) {
      return null;
    }

    const payload = (await response.json()) as ApiEnvelope<CheckoutPaymentOptions>;

    return payload.data ?? null;
  } catch {
    return null;
  }
}

export async function initializeCheckoutPayment(
  input: CheckoutInitializationInput,
): Promise<CheckoutInitializationResult | { error: string } | null> {
  try {
    const response = await fetch("/api/checkout/initialize", {
      method: "POST",
      cache: "no-store",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
      body: JSON.stringify(input),
    });
    const payload = (await response.json()) as ApiEnvelope<CheckoutInitializationResult>;

    if (!response.ok || !payload.data) {
      return { error: payload.message ?? payload.error ?? "Impossible d'initialiser le paiement." };
    }

    return payload.data;
  } catch {
    return null;
  }
}
