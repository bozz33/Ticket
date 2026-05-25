import type { TicketAvailabilityBadge } from "@/components/ticketing/availability";
import type { ModuleRoute, PublicContent } from "@/lib/types";

export type ContentCardProps = {
  item: PublicContent;
  accountAuthenticated?: boolean;
  accountSessionKey?: string;
  initialFollowing?: boolean;
  initialLiked?: boolean;
  initialLikes?: number;
  labels?: ContentCardLabels;
};

export type ContentCardLabels = {
  date: string;
  free: string;
  followOrganizer: string;
  location: string;
  moduleCta: (module: ModuleRoute, fallback: string) => string;
  moduleTitle: (module: ModuleRoute, fallback: string) => string;
  organizerFollowed: string;
  paid: string;
  priceFrom: string;
  publishedBy: string;
  remainingSeats: (count: number) => string;
  seats: string;
  selection: string;
  soldOut: string;
  trending: string;
};

export type ContentCardModel = {
  coverImage: string;
  detailHref: string;
  editorialBadge: string | null;
  organizerHref: string;
  priceBadge: string;
  publisherImage: string;
  publisherName: string;
  showMedia: boolean;
  ticketAvailabilityBadge: TicketAvailabilityBadge | null;
};
