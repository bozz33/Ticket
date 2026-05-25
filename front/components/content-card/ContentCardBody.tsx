import Link from "next/link";

import type { PublicContent } from "@/lib/types";
import { formatDateRange, formatMoney } from "@/lib/utils";

import { CardIcon } from "./CardIcon";
import { defaultContentCardLabels } from "./labels";
import type { ContentCardLabels, ContentCardModel } from "./types";

type ContentCardBodyProps = {
  item: PublicContent;
  labels?: ContentCardLabels;
  model: ContentCardModel;
};

export function ContentCardBody({ item, labels = defaultContentCardLabels, model }: ContentCardBodyProps) {
  return (
    <div className="content-card__body">
      <div className="content-card__badges content-card__badges--meta">
        {item.highlights.slice(0, 2).map((highlight) => (
          <span className="badge badge--muted" key={highlight}>
            {highlight}
          </span>
        ))}
      </div>

      <div className="content-card__headline">
        <Link className="content-card__title" href={model.detailHref}>
          {item.title}
        </Link>
      </div>

      <div className="content-card__detail-list">
        <div className="content-card__detail-item">
          <span className="content-card__detail-icon">
            <CardIcon name="calendar" />
          </span>
          <strong>
            <span className="content-card__detail-label">{labels.date}</span>
            <span className="content-card__detail-value">{formatDateRange(item)}</span>
          </strong>
        </div>
        <div className="content-card__detail-item">
          <span className="content-card__detail-icon">
            <CardIcon name="location" />
          </span>
          <strong>
            <span className="content-card__detail-label">{labels.location}</span>
            <span className="content-card__detail-value">{item.venueName ?? item.city}, {item.country}</span>
          </strong>
        </div>
        <div className="content-card__detail-item content-card__detail-item--price">
          <span className="content-card__detail-icon">
            <CardIcon name="ticket" />
          </span>
          <strong>
            <span className="content-card__detail-label">{labels.priceFrom}</span>
            <span className="content-card__detail-value">{item.isFree ? labels.free : formatMoney(item.priceFrom, item.currency)}</span>
          </strong>
        </div>
        {item.module === "evenements" && typeof item.remainingSeats === "number" ? (
          <div className="content-card__detail-item">
            <span className="content-card__detail-icon">
              <CardIcon name="ticket" />
            </span>
            <strong>
              <span className="content-card__detail-label">{labels.seats}</span>
              <span className="content-card__detail-value">{item.remainingSeats > 0 ? labels.remainingSeats(item.remainingSeats) : labels.soldOut}</span>
            </strong>
          </div>
        ) : null}
      </div>

      <Link className="button button--full content-card__primary-cta" href={model.detailHref}>
        {labels.moduleCta(item.module, item.moduleCta)}
      </Link>
    </div>
  );
}
