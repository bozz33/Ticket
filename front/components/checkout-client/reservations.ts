import { releaseCheckoutTicketReservation, reserveCheckoutTicket } from "@/lib/client/checkout";
import type { PublicContent } from "@/lib/types";

export async function reserveSelectedEventTicket({
  selectedOffer,
  quantity,
  tenant,
}: {
  selectedOffer: PublicContent["tiers"][number];
  quantity: number;
  tenant?: string;
}): Promise<{ reservationId?: string; notice?: string; error?: string }> {
  if (selectedOffer.source !== "event_ticket") {
    return {};
  }

  const reservation = await reserveCheckoutTicket({
    ticket: selectedOffer.ticketId ?? selectedOffer.id,
    quantity,
    tenant,
  });

  if (!reservation) {
    return { error: "Impossible de réserver ce ticket." };
  }

  if ("error" in reservation) {
    return { error: reservation.error };
  }

  return {
    reservationId: reservation.id,
    notice: reservation.expires_at
      ? `Ticket réservé jusqu'à ${new Date(reservation.expires_at).toLocaleTimeString()}.`
      : "Ticket réservé temporairement.",
  };
}

export function releaseReservedEventTicket(reservationId: string | undefined, tenant?: string) {
  if (!reservationId) {
    return;
  }

  void releaseCheckoutTicketReservation(reservationId, tenant);
}
