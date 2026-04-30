import Link from "next/link";

import { PublicContent } from "@/lib/types";
import { formatDateRange, formatMoney, getModuleMeta } from "@/lib/utils";

function CardIcon({ name }: { name: "calendar" | "location" | "ticket" }) {
  if (name === "calendar") {
    return (
      <svg aria-hidden="true" viewBox="0 0 24 24">
        <path d="M7 3v4" />
        <path d="M17 3v4" />
        <path d="M4.5 9.5h15" />
        <path d="M6.5 5.5h11A2.5 2.5 0 0 1 20 8v10a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 18V8a2.5 2.5 0 0 1 2.5-2.5Z" />
      </svg>
    );
  }

  if (name === "location") {
    return (
      <svg aria-hidden="true" viewBox="0 0 24 24">
        <path d="M19 10.4c0 5.1-7 10.1-7 10.1s-7-5-7-10.1A7 7 0 0 1 19 10.4Z" />
        <path d="M12 13a2.6 2.6 0 1 0 0-5.2A2.6 2.6 0 0 0 12 13Z" />
      </svg>
    );
  }

  return (
    <svg aria-hidden="true" viewBox="0 0 24 24">
      <path d="M4.5 7.5A2.5 2.5 0 0 1 7 5h10a2.5 2.5 0 0 1 2.5 2.5v2.2a2.3 2.3 0 0 0 0 4.6v2.2A2.5 2.5 0 0 1 17 19H7a2.5 2.5 0 0 1-2.5-2.5v-2.2a2.3 2.3 0 0 0 0-4.6V7.5Z" />
      <path d="M13.5 7.5v9" />
    </svg>
  );
}

export function ContentCard({ item }: { item: PublicContent }) {
  const meta = getModuleMeta(item.module);
  const detailHref = `/${item.module}/${item.slug}`;
  const organizerHref = `/organisateurs/${item.organizerSlug}`;
  const publisherImage = item.organizers[0]?.imageUrl ?? item.coverImageUrl;
  const publisherName = item.organizers[0]?.name ?? "Organisateur";
  const featuredBadge = item.badges[0] ?? (item.popular ? "Tendance" : item.featured ? "Selection" : null);

  return (
    <article className="content-card">
      <div className="content-card__stage">
        <Link className="content-card__media" href={detailHref}>
          <img alt={item.title} src={item.coverImageUrl} />
        </Link>
        <div className="content-card__overlay" />
        <div className="content-card__topline">
          <span className="content-card__module-chip">{meta.title}</span>
          {featuredBadge ? <span className="badge badge--light">{featuredBadge}</span> : null}
        </div>
        <div className="content-card__image-meta">
          <span className="badge">{item.category}</span>
          <span className="content-card__image-city">
            {item.city}, {item.country}
          </span>
        </div>
      </div>

      <div className="content-card__body">
        <div className="content-card__badges content-card__badges--meta">
          {item.highlights.slice(0, 2).map((highlight) => (
            <span className="badge badge--muted" key={highlight}>
              {highlight}
            </span>
          ))}
        </div>

        <Link className="content-card__title" href={detailHref}>
          {item.title}
        </Link>

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
              <span>A partir de</span>
              {item.isFree ? "Gratuit" : formatMoney(item.priceFrom, item.currency)}
            </strong>
          </div>
        </div>

        <Link className="button button--full content-card__primary-cta" href={detailHref}>
          {meta.cta}
        </Link>
      </div>

      <div className="content-card__publisher">
        <Link className="content-card__publisher-main" href={organizerHref}>
          <img alt={publisherName} src={publisherImage} />
          <span className="content-card__publisher-copy">
            <small>Publie par</small>
            <strong>{publisherName}</strong>
          </span>
        </Link>
        <Link className="content-card__publisher-action" href={organizerHref}>
          Suivre
        </Link>
      </div>
    </article>
  );
}
