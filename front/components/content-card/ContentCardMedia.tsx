import Link from "next/link";

import { EventLikeButton } from "@/components/EventLikeButton";
import type { PublicContent } from "@/lib/types";

import type { ContentCardModel } from "./types";

type ContentCardMediaProps = {
  accountAuthenticated?: boolean;
  initialLiked?: boolean;
  item: PublicContent;
  model: ContentCardModel;
};

export function ContentCardMedia({ accountAuthenticated, initialLiked, item, model }: ContentCardMediaProps) {
  if (!model.showMedia) {
    return (
      <div className="content-card__textual-head">
        <div className="content-card__textual-row">
          <span className="content-card__module-chip">{item.moduleTitle}</span>
          {item.module === "evenements" ? (
            <EventLikeButton
              eventSlug={item.slug}
              initialAuthenticated={accountAuthenticated}
              initialCount={item.likesCount}
              initialLiked={initialLiked}
              tenantSlug={item.organizerSlug}
              variant="card"
            />
          ) : null}
        </div>
        <div className="content-card__top-badges">
          <span className={`badge ${item.isFree ? "badge--free" : "badge--paid"}`}>{model.priceBadge}</span>
          {model.ticketAvailabilityBadge ? (
            <span className={model.ticketAvailabilityBadge.className}>{model.ticketAvailabilityBadge.label}</span>
          ) : null}
          {model.editorialBadge ? <span className="badge badge--muted">{model.editorialBadge}</span> : null}
          <span className="badge badge--muted">{item.category}</span>
        </div>
      </div>
    );
  }

  return (
    <>
      <Link className="content-card__media" href={model.detailHref}>
        <img alt={item.title} decoding="async" loading="lazy" src={model.coverImage} />
      </Link>
      <div className="content-card__overlay" />
      <div className="content-card__topline">
        <span className="content-card__module-chip">{item.moduleTitle}</span>
        <div className="content-card__top-badges">
          <span className={`badge badge--light ${item.isFree ? "badge--free" : "badge--paid"}`}>
            {model.priceBadge}
          </span>
          {model.ticketAvailabilityBadge ? (
            <span className={`${model.ticketAvailabilityBadge.className} badge--light`}>{model.ticketAvailabilityBadge.label}</span>
          ) : null}
          {model.editorialBadge ? <span className="badge badge--light badge--editorial">{model.editorialBadge}</span> : null}
        </div>
      </div>
      {item.module === "evenements" ? (
        <div className="content-card__floating-actions">
          <EventLikeButton
            eventSlug={item.slug}
            initialAuthenticated={accountAuthenticated}
            initialCount={item.likesCount}
            initialLiked={initialLiked}
            tenantSlug={item.organizerSlug}
            variant="card"
          />
        </div>
      ) : null}
      <div className="content-card__image-meta">
        <span className="badge">{item.category}</span>
        <span className="content-card__image-city">
          {item.city}, {item.country}
        </span>
      </div>
    </>
  );
}
