import type { EventTicketTier, ModuleRoute } from "@/lib/types";
import { formatMoney } from "@/lib/utils";

import { AvailabilityBadge } from "./AvailabilityBadge";
import { TicketCtaButton } from "./TicketCtaButton";

export function TicketTierCard({
  module,
  slug,
  organizerSlug,
  ticket,
}: {
  module: ModuleRoute;
  slug: string;
  organizerSlug: string;
  ticket: EventTicketTier;
}) {
  return (
    <article className={`offer-card ticket-card${ticket.isAvailable ? "" : " ticket-card--unavailable"}`}>
      <div className="ticket-card__header">
        <div>
          <h3>{ticket.title}</h3>
          {ticket.subtitle ? <p>{ticket.subtitle}</p> : null}
        </div>
        <AvailabilityBadge ticket={ticket} />
      </div>
      <strong>{ticket.price === 0 ? "Gratuit" : formatMoney(ticket.price, ticket.currency ?? "XOF")}</strong>
      {ticket.quantityLabel ? <span className="ticket-card__quantity">{ticket.quantityLabel}</span> : null}
      {ticket.perks.length > 0 ? (
        <ul className="bullet-list">
          {ticket.perks.map((perk) => (
            <li key={perk}>{perk}</li>
          ))}
        </ul>
      ) : null}
      <TicketCtaButton module={module} organizerSlug={organizerSlug} slug={slug} ticket={ticket} />
    </article>
  );
}
