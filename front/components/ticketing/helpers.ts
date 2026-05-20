import type { EventTicketTier, ModuleRoute } from "@/lib/types";

export function buildTicketCheckoutHref(module: ModuleRoute, slug: string, ticket: EventTicketTier, organizerSlug: string): string | null {
  if (!ticket.isAvailable || !ticket.id) {
    return null;
  }

  const params = new URLSearchParams({ ticket: ticket.id });

  if (organizerSlug) {
    params.set("tenant", organizerSlug);
  }

  return `/checkout/${module}/${slug}?${params.toString()}`;
}
