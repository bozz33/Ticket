import type {
  CheckoutInitializationResult,
  CheckoutPaymentOptions,
  ModuleRoute,
  TicketReservationResult,
} from "@/lib/types";

type CheckoutInitializationInput = {
  offer?: string;
  ticket?: string;
  quantity: number;
  payment_method?: string;
  content_module: ModuleRoute;
  content_slug: string;
  callback_url: string;
  tenant?: string;
  ticket_reservation?: string;
};

type ApiEnvelope<T> = {
  data?: T;
  message?: string;
  error?: string;
  code?: string;
};

export async function getCheckoutPaymentOptions(
  checkoutItemId: string,
  quantity = 1,
  paymentMethod?: string,
  tenantSlug?: string,
  selectionType: "offer" | "ticket" = "offer",
): Promise<CheckoutPaymentOptions | null> {
  const params = new URLSearchParams({
    [selectionType]: checkoutItemId,
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


export async function reserveCheckoutTicket(input: {
  ticket: string;
  quantity: number;
  tenant?: string;
}): Promise<TicketReservationResult | { error: string } | null> {
  const params = input.tenant ? `?tenant=${encodeURIComponent(input.tenant)}` : "";

  try {
    const response = await fetch(`/api/checkout/ticket-reservations${params}`, {
      method: "POST",
      cache: "no-store",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ ticket: input.ticket, quantity: input.quantity }),
    });
    const payload = (await response.json()) as ApiEnvelope<TicketReservationResult>;

    if (!response.ok || !payload.data) {
      return { error: payload.message ?? payload.error ?? "Impossible de réserver ce ticket." };
    }

    return payload.data;
  } catch {
    return null;
  }
}

export async function releaseCheckoutTicketReservation(reservationId: string, tenant?: string) {
  const params = tenant ? `?tenant=${encodeURIComponent(tenant)}` : "";

  try {
    const response = await fetch(`/api/checkout/ticket-reservations/${encodeURIComponent(reservationId)}${params}`, {
      method: "DELETE",
      cache: "no-store",
      headers: {
        Accept: "application/json",
      },
    });

    return response.ok;
  } catch {
    return false;
  }
}
