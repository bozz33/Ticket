import Link from "next/link";

import type { PublicContent } from "@/lib/types";
import { formatDateRange, formatMoney } from "@/lib/utils";

import { CardIcon } from "./CardIcon";
import type { ContentCardModel } from "./types";

type ContentCardBodyProps = {
  item: PublicContent;
  model: ContentCardModel;
};

export function ContentCardBody({ item, model }: ContentCardBodyProps) {
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
          <strong>{formatDateRange(item)}</strong>
        </div>
        <div className="content-card__detail-item">
          <span className="content-card__detail-icon">
            <CardIcon name="location" />
          </span>
          <strong>
            {item.venueName ?? item.city}, {item.country}
          </strong>
        </div>
        <div className="content-card__detail-item content-card__detail-item--price">
          <span className="content-card__detail-icon">
            <CardIcon name="ticket" />
          </span>
          <strong>
            <span>À partir de</span>
            {item.isFree ? "Gratuit" : formatMoney(item.priceFrom, item.currency)}
          </strong>
        </div>
      </div>

      <Link className="button button--full content-card__primary-cta" href={model.detailHref}>
        {item.moduleCta}
      </Link>
    </div>
  );
}
