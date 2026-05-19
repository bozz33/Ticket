import Link from "next/link";

import type { EventTicketTier, ModuleRoute } from "@/lib/types";

import { buildTicketCheckoutHref } from "./helpers";

export function TicketCtaButton({
  module,
  slug,
  organizerSlug,
  ticket,
  compact = false,
}: {
  module: ModuleRoute;
  slug: string;
  organizerSlug: string;
  ticket: EventTicketTier;
  compact?: boolean;
}) {
  const href = buildTicketCheckoutHref(module, slug, ticket, organizerSlug);
  const className = compact ? "button button--full" : "button button--full ticket-card__cta";

  if (!href) {
    return (
      <button className={`${className} button--disabled`} disabled type="button">
        {ticket.isSoldOut ? "Épuisé" : ticket.availabilityLabel}
      </button>
    );
  }

  return (
    <Link className={className} href={href}>
      {ticket.ctaLabel}
      {compact ? ` - ${ticket.title}` : null}
    </Link>
  );
}
