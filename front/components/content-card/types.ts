import type { PublicContent } from "@/lib/types";

export type ContentCardProps = {
  item: PublicContent;
  accountAuthenticated?: boolean;
  initialLiked?: boolean;
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
};
