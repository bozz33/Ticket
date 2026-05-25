import Link from "next/link";

import type { FrontPageSection } from "@/lib/types";

import { getSetting } from "./helpers";
import type { OrganizerHighlight } from "./types";

export function renderHero(section: FrontPageSection | undefined, fallbackHeroImage?: string) {
  if (!section) {
    return null;
  }

  const variant = getSetting(section, "variant") || "page";
  const metaLabel = getSetting(section, "updated_at_label");
  const imageUrl = section.image_url || fallbackHeroImage || "";

  if (variant === "inner") {
    return (
      <section
        className="inner-hero"
        style={{
          backgroundImage: `linear-gradient(135deg, rgba(13,20,32,0.8), rgba(23,34,56,0.86)), url("${imageUrl}")`,
        }}
      >
        <div className="shell">
          <p className="inner-hero__eyebrow">{section.eyebrow}</p>
          <h1>{section.title}</h1>
          {section.body ? <p>{section.body}</p> : null}
          {metaLabel ? <p className="inner-hero__meta">{metaLabel}</p> : null}
        </div>
      </section>
    );
  }

  return (
    <section className="page-hero">
      {imageUrl ? (
        <img alt={section.title ?? "Illustration"} className="page-hero__image" src={imageUrl} />
      ) : null}
      <div className="shell page-hero__content">
        {section.eyebrow ? <p className="eyebrow">{section.eyebrow}</p> : null}
        {section.title ? <h1>{section.title}</h1> : null}
        {section.body ? <p>{section.body}</p> : null}
        {metaLabel ? <p className="inner-hero__meta">{metaLabel}</p> : null}
      </div>
    </section>
  );
}

export function renderFeatureGrid(section: FrontPageSection | undefined, light = false) {
  if (!section) {
    return null;
  }

  return (
    <section className={`section${light ? " section--light" : ""}`}>
      <div className="shell">
        {section.title || section.eyebrow || section.body ? (
          <div className="section-header">
            <div>
              {section.eyebrow ? <p className="eyebrow">{section.eyebrow}</p> : null}
              {section.title ? <h2>{section.title}</h2> : null}
              {section.body ? <p className="section-copy">{section.body}</p> : null}
            </div>
          </div>
        ) : null}
        <div className="support-grid">
          {section.items.map((item, index) => (
            <article className="explore-tile" key={`${section.key}-${index}`}>
              {item.image_url ? (
                <img
                  alt={item.title || item.label || section.title || "Illustration"}
                  decoding="async"
                  loading="lazy"
                  src={item.image_url}
                  style={{ borderRadius: "18px", height: "150px", objectFit: "cover", width: "100%" }}
                />
              ) : null}
              {item.title ? <h2 style={{ margin: 0, fontSize: "1.1rem" }}>{item.title}</h2> : null}
              {item.body ? <p style={{ margin: 0 }}>{item.body}</p> : null}
              {item.href ? (
                <Link className="button button--ghost" href={item.href}>
                  {item.label || "En savoir plus"}
                </Link>
              ) : item.value ? <p style={{ margin: 0, fontWeight: 700 }}>{item.value}</p> : null}
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}

export function renderMetrics(section: FrontPageSection | undefined) {
  if (!section) {
    return null;
  }

  return (
    <section className="section section--light">
      <div className="shell tile-grid">
        {section.items.map((item, index) => (
          <article className="explore-tile" key={`${section.key}-${index}`}>
            <span className="badge">{section.eyebrow || "KPI"}</span>
            <h2>{item.value}</h2>
            <p>{item.label || item.title}</p>
          </article>
        ))}
      </div>
    </section>
  );
}

export function renderSplitOverview(section: FrontPageSection | undefined) {
  if (!section) {
    return null;
  }

  const bullets = section.items.filter((item) => item.title && !item.label && !item.value);
  const facts = section.items.filter((item) => item.label && item.value);

  return (
    <section className="section">
      <div className="shell about-grid">
        <article className="detail-block">
          {section.eyebrow ? <p className="eyebrow">{section.eyebrow}</p> : null}
          {section.title ? <h2>{section.title}</h2> : null}
          {section.body ? <p className="section-copy">{section.body}</p> : null}
          {bullets.length > 0 ? (
            <ul className="bullet-list">
              {bullets.map((item, index) => (
                <li key={`${section.key}-bullet-${index}`}>{item.title}</li>
              ))}
            </ul>
          ) : null}
        </article>

        <aside className="sticky-panel">
          <div className="sticky-panel__price">
            <span>En bref</span>
            <strong>{section.title}</strong>
          </div>
          {facts.length > 0 ? (
            <ul className="facts-list">
              {facts.map((item, index) => (
                <li key={`${section.key}-fact-${index}`}>
                  <strong>{item.label}</strong>
                  <span>{item.value}</span>
                </li>
              ))}
            </ul>
          ) : null}
          <div className="sticky-panel__cta-list">
            {section.primary_cta?.url && section.primary_cta.label ? (
              <Link className="button button--full" href={section.primary_cta.url}>
                {section.primary_cta.label}
              </Link>
            ) : null}
            {section.secondary_cta?.url && section.secondary_cta.label ? (
              <Link className="button button--full button--ghost" href={section.secondary_cta.url}>
                {section.secondary_cta.label}
              </Link>
            ) : null}
          </div>
        </aside>
      </div>
    </section>
  );
}

export function renderOrganizerHighlights(section: FrontPageSection | undefined, organizers: OrganizerHighlight[]) {
  if (!section) {
    return null;
  }

  return (
    <section className="section section--light">
      <div className="shell">
        <div className="section-header">
          <div>
            {section.eyebrow ? <p className="eyebrow">{section.eyebrow}</p> : null}
            {section.title ? <h2>{section.title}</h2> : null}
            {section.body ? <p className="section-copy">{section.body}</p> : null}
          </div>
        </div>

        <div className="organizer-grid">
          {organizers.map(({ organizer, items }) => (
            <article className="organizer-card" key={organizer.slug}>
              <div className="organizer-card__banner">
                <img alt={organizer.name} src={organizer.bannerUrl} />
              </div>
              <div className="organizer-card__body">
                <div className="organizer-card__identity">
                  <img alt={organizer.name} src={organizer.logoUrl} />
                  <div>
                    <Link href={`/organisateurs/${organizer.slug}`}>{organizer.name}</Link>
                    <p>
                      {organizer.city}, {organizer.country}
                    </p>
                  </div>
                </div>
                <p className="organizer-card__copy">{organizer.tagline}</p>
                <div className="organizer-card__mini-list">
                  {items.map((item) => (
                    <Link href={`/${item.module}/${item.slug}?tenant=${encodeURIComponent(item.organizerSlug)}`} key={item.id}>
                      {item.title}
                    </Link>
                  ))}
                </div>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
