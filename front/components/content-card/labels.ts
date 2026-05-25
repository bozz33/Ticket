import type { ContentCardLabels } from "./types";

const MODULE_TITLE_KEYS: Record<string, string> = {
  "appels-a-projets": "content_card.module.appels_a_projets",
  crowdfunding: "content_card.module.crowdfunding",
  evenements: "content_card.module.evenements",
  formations: "content_card.module.formations",
  stands: "content_card.module.stands",
};

const MODULE_CTA_KEYS: Record<string, string> = {
  "appels-a-projets": "content_card.cta.appels_a_projets",
  crowdfunding: "content_card.cta.crowdfunding",
  evenements: "content_card.cta.evenements",
  formations: "content_card.cta.formations",
  stands: "content_card.cta.stands",
};

export const defaultContentCardLabels: ContentCardLabels = {
  date: "Date",
  free: "Gratuit",
  followOrganizer: "Suivre l'orga",
  location: "Lieu",
  moduleCta: (_module, fallback) => fallback,
  moduleTitle: (_module, fallback) => fallback,
  organizerFollowed: "Orga suivie",
  paid: "Payant",
  priceFrom: "A partir de",
  publishedBy: "Publie par",
  remainingSeats: (count: number) => `${count} restantes`,
  seats: "Places",
  selection: "Selection",
  soldOut: "Epuise",
  trending: "Tendance",
};

export function buildContentCardLabels(
  t: (key: string, fallback: string) => string,
): ContentCardLabels {
  return {
    date: t("content_card.date", defaultContentCardLabels.date),
    free: t("content_card.free", defaultContentCardLabels.free),
    followOrganizer: t("content_card.follow_organizer", defaultContentCardLabels.followOrganizer),
    location: t("content_card.location", defaultContentCardLabels.location),
    moduleCta: (module, fallback) => t(MODULE_CTA_KEYS[module] ?? "", fallback),
    moduleTitle: (module, fallback) => t(MODULE_TITLE_KEYS[module] ?? "", fallback),
    organizerFollowed: t("content_card.organizer_followed", defaultContentCardLabels.organizerFollowed),
    paid: t("content_card.paid", defaultContentCardLabels.paid),
    priceFrom: t("content_card.price_from", defaultContentCardLabels.priceFrom),
    publishedBy: t("content_card.published_by", defaultContentCardLabels.publishedBy),
    remainingSeats: (count: number) => t("content_card.remaining_seats", "{count} restantes").replace("{count}", String(count)),
    seats: t("content_card.seats", defaultContentCardLabels.seats),
    selection: t("content_card.selection", defaultContentCardLabels.selection),
    soldOut: t("content_card.sold_out", defaultContentCardLabels.soldOut),
    trending: t("content_card.trending", defaultContentCardLabels.trending),
  };
}
