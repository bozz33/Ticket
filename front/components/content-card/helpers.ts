import { buildEventTicketAvailabilityBadge } from "@/components/ticketing";
import type { PublicContent } from "@/lib/types";

import { CARD_IMAGE_FALLBACK } from "./constants";
import type { ContentCardModel } from "./types";

export function buildContentCardModel(item: PublicContent): ContentCardModel {
  const detailHref = item.organizerSlug
    ? `/${item.module}/${item.slug}?tenant=${encodeURIComponent(item.organizerSlug)}`
    : `/${item.module}/${item.slug}`;
  const organizerHref = `/organisateurs/${item.organizerSlug}`;
  const coverImage = item.coverImageUrl?.trim() || CARD_IMAGE_FALLBACK;
  const publisherImage = item.organizers[0]?.imageUrl?.trim() || coverImage;
  const publisherName = item.organizers[0]?.name ?? "Organisateur";
  const priceBadge = item.isFree ? "Gratuit" : "Payant";
  const editorialBadge =
    item.badges.find((badge) => badge !== priceBadge) ?? (item.popular ? "Tendance" : item.featured ? "Sélection" : null);
  const ticketAvailabilityBadge = buildEventTicketAvailabilityBadge(item);

  return {
    coverImage,
    detailHref,
    editorialBadge,
    organizerHref,
    priceBadge,
    publisherImage,
    publisherName,
    showMedia: true,
    ticketAvailabilityBadge,
  };
}
