import type { EventTicketTier } from "@/lib/types";

const STATUS_CLASS_NAMES: Record<EventTicketTier["availabilityStatus"], string> = {
  available: "badge badge--ticket-available",
  low_stock: "badge badge--ticket-low",
  sold_out: "badge badge--ticket-sold-out",
  sales_not_started: "badge badge--ticket-muted",
  sales_ended: "badge badge--ticket-muted",
  inactive: "badge badge--ticket-muted",
};

export function AvailabilityBadge({ ticket }: { ticket: EventTicketTier }) {
  return <span className={STATUS_CLASS_NAMES[ticket.availabilityStatus]}>{ticket.availabilityLabel}</span>;
}
