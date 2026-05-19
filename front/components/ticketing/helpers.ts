import type { EventTicketTier, ModuleRoute } from "@/lib/types";

export function buildTicketCheckoutHref(module: ModuleRoute, slug: string, ticket: EventTicketTier, organizerSlug: string): string | null {
  const checkoutId = ticket.offerId ?? ticket.id;

  if (!ticket.isAvailable || !checkoutId) {
    return null;
  }

  const params = new URLSearchParams({ offer: checkoutId });

  if (organizerSlug) {
    params.set("tenant", organizerSlug);
  }

  return `/checkout/${module}/${slug}?${params.toString()}`;
}
