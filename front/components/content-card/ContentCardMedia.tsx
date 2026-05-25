import Link from "next/link";

import { EventLikeButton } from "@/components/EventLikeButton";
import type { PublicContent } from "@/lib/types";

import { defaultContentCardLabels } from "./labels";
import type { ContentCardLabels, ContentCardModel } from "./types";

type ContentCardMediaProps = {
  accountAuthenticated?: boolean;
  accountSessionKey?: string;
  initialLiked?: boolean;
  initialLikes?: number;
  item: PublicContent;
  labels?: ContentCardLabels;
  model: ContentCardModel;
};

export function ContentCardMedia({
  accountAuthenticated,
  accountSessionKey,
  initialLiked,
  initialLikes,
  item,
  labels = defaultContentCardLabels,
  model,
}: ContentCardMediaProps) {
  const moduleChipClassName = `content-card__module-chip content-card__module-chip--${item.module}`;
  const likeCount = initialLikes ?? item.likesCount;
  const moduleTitle = labels.moduleTitle(item.module, item.moduleTitle);

  if (!model.showMedia) {
    return (
      <div className="content-card__textual-head">
        <div className="content-card__textual-row">
          <span className={moduleChipClassName}>{moduleTitle}</span>
          <EventLikeButton
            accountSessionKey={accountSessionKey}
            eventSlug={item.slug}
            initialAuthenticated={accountAuthenticated}
            initialCount={likeCount}
            initialLiked={initialLiked}
            module={item.module}
            tenantSlug={item.organizerSlug}
            variant="card"
          />
        </div>
        <div className="content-card__top-badges">
          <span className={`badge ${item.isFree ? "badge--free" : "badge--paid"}`}>{model.priceBadge}</span>
          {model.ticketAvailabilityBadge ? (
            <span className={model.ticketAvailabilityBadge.className}>{model.ticketAvailabilityBadge.label}</span>
          ) : null}
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
        <span className={moduleChipClassName}>{moduleTitle}</span>
        <div className="content-card__top-badges">
          <span className={`badge badge--light ${item.isFree ? "badge--free" : "badge--paid"}`}>
            {model.priceBadge}
          </span>
          {model.ticketAvailabilityBadge ? (
            <span className={`${model.ticketAvailabilityBadge.className} badge--light`}>{model.ticketAvailabilityBadge.label}</span>
          ) : null}
        </div>
      </div>
      <div className="content-card__floating-actions">
        <EventLikeButton
          accountSessionKey={accountSessionKey}
          eventSlug={item.slug}
          initialAuthenticated={accountAuthenticated}
          initialCount={likeCount}
          initialLiked={initialLiked}
          module={item.module}
          tenantSlug={item.organizerSlug}
          variant="card"
        />
      </div>
      <div className="content-card__image-meta">
        <span className="badge">{item.category}</span>
        <span className="content-card__image-city">
          {item.city}, {item.country}
        </span>
      </div>
    </>
  );
}
