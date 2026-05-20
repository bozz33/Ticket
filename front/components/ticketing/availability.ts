import type { PublicContent } from "@/lib/types";

export type TicketAvailabilityBadge = {
  label: string;
  className: string;
};

export function buildEventTicketAvailabilityBadge(item: PublicContent): TicketAvailabilityBadge | null {
  if (item.module !== "evenements" || !item.tickets?.length) {
    return null;
  }

  const tickets = item.tickets;

  if (tickets.every((ticket) => ticket.isSoldOut || ticket.availabilityStatus === "inactive" || ticket.availabilityStatus === "sales_ended")) {
    return {
      label: "Épuisé",
      className: "badge badge--ticket-sold-out",
    };
  }

  const lowStockTicket = tickets.find((ticket) => ticket.availabilityStatus === "low_stock");

  if (lowStockTicket) {
    return {
      label: lowStockTicket.availabilityLabel,
      className: "badge badge--ticket-low",
    };
  }

  const nextTicket = tickets.find((ticket) => ticket.availabilityStatus === "sales_not_started");

  if (nextTicket && !tickets.some((ticket) => ticket.isAvailable)) {
    return {
      label: nextTicket.availabilityLabel,
      className: "badge badge--ticket-muted",
    };
  }

  const remaining = item.remainingSeats;

  if (typeof remaining === "number" && remaining > 0 && remaining <= 5) {
    return {
      label: remaining === 1 ? "Dernière place" : "Dernières places",
      className: "badge badge--ticket-low",
    };
  }

  return {
    label: "Disponible",
    className: "badge badge--ticket-available",
  };
}
