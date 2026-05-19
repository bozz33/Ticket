import type { PublicContent } from "@/lib/types";

import { TicketTierCard } from "./TicketTierCard";

export function TicketTierList({ item }: { item: PublicContent }) {
  const tickets = item.tickets ?? [];

  if (tickets.length === 0) {
    return null;
  }

  return (
    <div className="offer-grid ticket-grid">
      {tickets.map((ticket) => (
        <TicketTierCard
          key={ticket.id}
          module={item.module}
          organizerSlug={item.organizerSlug}
          slug={item.slug}
          ticket={ticket}
        />
      ))}
    </div>
  );
}
