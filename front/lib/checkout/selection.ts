import type { OfferTier } from "@/lib/types";

export function getCheckoutSelectionParamName(selection: OfferTier | null): "offer" | "ticket" {
  return selection?.source === "event_ticket" ? "ticket" : "offer";
}

export function getCheckoutInitializationIds(selection: OfferTier | null): { offer?: string; ticket?: string } {
  if (!selection?.id) {
    return {};
  }

  return selection.source === "event_ticket"
    ? { ticket: selection.id }
    : { offer: selection.id };
}
