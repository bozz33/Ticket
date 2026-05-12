import Link from "next/link";

import { OrganizerRegistrationForm } from "@/app/devenir-organisateur/OrganizerRegistrationForm";
import {
  FrontPageData,
  FrontPageSection,
  NavigationLink,
  OrganizerProfile,
  PlatformConfiguration,
  PublicContent,
} from "@/lib/types";

type OrganizerHighlight = {
  organizer: OrganizerProfile;
  items: PublicContent[];
};

function getSectionsByType(page: FrontPageData, type: FrontPageSection["type"]): FrontPageSection[] {
  return page.sections.filter((section) => section.type === type);
}

function getSetting(section: FrontPageSection | undefined, key: string): string {
  const value = section?.settings?.[key];

  return typeof value === "string" ? value : "";
}

function hasLinks(links: NavigationLink[]): boolean {
  return links.length > 0;
}

function renderHero(section: FrontPageSection | undefined, fallbackHeroImage?: string) {
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

function renderFeatureGrid(section: FrontPageSection | undefined, light = false) {
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

function renderMetrics(section: FrontPageSection | undefined) {
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

function renderSplitOverview(section: FrontPageSection | undefined) {
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

function renderOrganizerHighlights(section: FrontPageSection | undefined, organizers: OrganizerHighlight[]) {
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

function renderContactComposite(
  channels: FrontPageSection | undefined,
  form: FrontPageSection | undefined,
) {
  if (!channels && !form) {
    return null;
  }

  const subjectOptions = form?.items ?? [];

  return (
    <section className="section">
      <div className="shell">
        <div className="contact-grid">
          <div>
            {channels ? (
              <div className="contact-card" style={{ marginBottom: "22px" }}>
                {channels.eyebrow ? <p className="eyebrow">{channels.eyebrow}</p> : null}
                {channels.title ? (
                  <h2 style={{ margin: "0 0 20px", fontFamily: "var(--font-display)", fontSize: "1.3rem" }}>
                    {channels.title}
                  </h2>
                ) : null}
                {channels.items.map((item, index) => (
                  <div className="contact-channel" key={`${channels.key}-${index}`}>
                    <div className="contact-channel__icon">
                      <span style={{ fontSize: "0.9rem", fontWeight: 800 }}>
                        {(item.icon || item.label || "i").slice(0, 2).toUpperCase()}
                      </span>
                    </div>
                    <div>
                      {item.label ? <p className="contact-channel__label">{item.label}</p> : null}
                      {item.href ? (
                        <p className="contact-channel__value">
                          <a href={item.href} rel={item.href.startsWith("http") ? "noreferrer" : undefined} target={item.href.startsWith("http") ? "_blank" : undefined}>
                            {item.value || item.href}
                          </a>
                        </p>
                      ) : item.value ? <p className="contact-channel__value">{item.value}</p> : null}
                      {item.body ? <p className="contact-channel__note">{item.body}</p> : null}
                    </div>
                  </div>
                ))}
              </div>
            ) : null}
          </div>

          <div className="contact-card">
            {form?.eyebrow ? <p className="eyebrow">{form.eyebrow}</p> : null}
            {form?.title ? (
              <h2 style={{ margin: "0 0 6px", fontFamily: "var(--font-display)", fontSize: "1.3rem" }}>
                {form.title}
              </h2>
            ) : null}
            {form?.body ? (
              <p style={{ margin: "0 0 24px", color: "var(--text-soft)", fontSize: "0.9rem" }}>{form.body}</p>
            ) : null}

            <form className="contact-form-group">
              <div className="contact-form-row">
                <label className="contact-form-label">
                  Prénom *
                  <input placeholder="Votre prénom" required type="text" />
                </label>
                <label className="contact-form-label">
                  Nom *
                  <input placeholder="Votre nom" required type="text" />
                </label>
              </div>

              <div className="contact-form-row">
                <label className="contact-form-label">
                  Email *
                  <input placeholder="vous@exemple.com" required type="email" />
                </label>
                <label className="contact-form-label">
                  Téléphone
                  <input placeholder="+225 ..." type="tel" />
                </label>
              </div>

              <label className="contact-form-label">
                Sujet *
                <select required>
                  <option value="">-- Sélectionnez un sujet --</option>
                  {subjectOptions.map((item, index) => (
                    <option key={`${form?.key}-subject-${index}`} value={item.label || item.title || `option-${index}`}>
                      {item.title || item.label}
                    </option>
                  ))}
                </select>
              </label>

              <label className="contact-form-label">
                Référence de commande
                <input placeholder="Ex : CMD-2025-00123" type="text" />
              </label>

              <label className="contact-form-label">
                Message *
                <textarea placeholder="Décrivez votre situation en détail..." required rows={5} />
              </label>

              <button className="button button--full" type="button" style={{ minHeight: "52px", fontSize: "0.96rem" }}>
                Envoyer le message
              </button>

              {getSetting(form, "success_note") ? (
                <p style={{ margin: 0, fontSize: "0.78rem", color: "var(--text-soft)", textAlign: "center" }}>
                  {getSetting(form, "success_note")}
                </p>
              ) : null}
            </form>
          </div>
        </div>
      </div>
    </section>
  );
}

function renderFaqPage(page: FrontPageData) {
  const faqSections = getSectionsByType(page, "faq");

  return (
    <section>
      <div className="shell faq-layout">
        <aside>
          <nav aria-label="Categories FAQ" className="faq-categories">
            {faqSections.map((section) => (
              <a className="faq-category__btn" href={`#${section.key}`} key={section.key}>
                <span>{section.title}</span>
              </a>
            ))}
          </nav>
        </aside>

        <div>
          {faqSections.map((section) => (
            <section className="faq-section" id={section.key} key={section.key}>
              {section.title ? <h2>{section.title}</h2> : null}
              {section.body ? <p className="section-copy" style={{ marginBottom: "18px" }}>{section.body}</p> : null}
              <div className="faq-accordion">
                {section.items.map((item, index) => (
                  <details className="faq-accordion__item" key={`${section.key}-${index}`} open={index === 0}>
                    <summary className="faq-accordion__q">
                      <span>{item.title}</span>
                      <span aria-hidden="true" className="faq-accordion__q-icon">+</span>
                    </summary>
                    <div className="faq-accordion__a">
                      <p>{item.body}</p>
                    </div>
                  </details>
                ))}
              </div>
            </section>
          ))}
        </div>
      </div>
    </section>
  );
}

function renderLegalPage(page: FrontPageData) {
  const legalSections = getSectionsByType(page, "legal_article");

  return (
    <section>
      <div className="shell legal-layout">
        <aside>
          <nav aria-label="Sommaire" className="legal-toc">
            <h3>Sommaire</h3>
            <ol className="legal-toc__list">
              {legalSections.map((section) => (
                <li className="legal-toc__item" key={section.key}>
                  <a href={`#${section.key}`}>{section.title}</a>
                </li>
              ))}
            </ol>
          </nav>
        </aside>

        <article className="legal-content">
          {legalSections.map((section, index) => {
            const labelValueItems = section.items.filter((item) => item.label && item.value);
            const narrativeItems = section.items.filter((item) => item.title || item.body);

            return (
              <section className="legal-section" id={section.key} key={section.key}>
                <div className="legal-section__number">{index + 1}</div>
                {section.title ? <h2>{section.title}</h2> : null}
                {section.body ? <p>{section.body}</p> : null}
                {labelValueItems.length > 0 ? (
                  <table className="legal-table">
                    <tbody>
                      {labelValueItems.map((item, itemIndex) => (
                        <tr key={`${section.key}-table-${itemIndex}`}>
                          <td><strong>{item.label}</strong></td>
                          <td>{item.value}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                ) : null}
                {narrativeItems.length > 0 ? (
                  <div className="support-grid" style={{ marginTop: "18px" }}>
                    {narrativeItems.map((item, itemIndex) => (
                      <article className="explore-tile" key={`${section.key}-item-${itemIndex}`}>
                        {item.title ? <h3 style={{ margin: 0 }}>{item.title}</h3> : null}
                        {item.body ? <p style={{ margin: 0 }}>{item.body}</p> : null}
                      </article>
                    ))}
                  </div>
                ) : null}
              </section>
            );
          })}
        </article>
      </div>
    </section>
  );
}

function renderOnboardingForm(section: FrontPageSection | undefined, platform: PlatformConfiguration) {
  if (!section) {
    return null;
  }

  return (
    <section className="section section--light" id="inscription">
      <div className="shell" style={{ maxWidth: "520px" }}>
        <div style={{ marginBottom: "32px" }}>
          {section.eyebrow ? <p className="eyebrow">{section.eyebrow}</p> : null}
          {section.title ? <h2 style={{ fontSize: "1.8rem", fontWeight: 700, margin: "8px 0 12px" }}>{section.title}</h2> : null}
          {section.body ? <p style={{ color: "var(--text-soft)" }}>{section.body}</p> : null}
        </div>
        <OrganizerRegistrationForm brandName={platform.brandName} />
      </div>
    </section>
  );
}

export function ManagedFrontPage({
  page,
  platform,
  organizers = [],
  fallbackHeroImage,
}: {
  page: FrontPageData;
  platform: PlatformConfiguration;
  organizers?: OrganizerHighlight[];
  fallbackHeroImage?: string;
}) {
  const hero = getSectionsByType(page, "hero")[0];

  return (
    <>
      {renderHero(hero, fallbackHeroImage)}

      {page.template === "marketing_page" ? (
        <>
          {renderSplitOverview(getSectionsByType(page, "split_overview")[0])}
          {renderOrganizerHighlights(getSectionsByType(page, "organizer_highlights")[0], organizers)}
        </>
      ) : null}

      {page.template === "contact_page" ? (
        <>
          {renderContactComposite(
            getSectionsByType(page, "contact_channels")[0],
            getSectionsByType(page, "contact_form")[0],
          )}
          {renderFeatureGrid(getSectionsByType(page, "feature_grid")[0], true)}
        </>
      ) : null}

      {page.template === "faq_page" ? (
        <>
          {renderMetrics(getSectionsByType(page, "metrics")[0])}
          {renderFaqPage(page)}
        </>
      ) : null}

      {page.template === "legal_page" ? renderLegalPage(page) : null}

      {page.template === "onboarding_page" ? (
        <>
          {renderFeatureGrid(getSectionsByType(page, "feature_grid")[0])}
          {renderOnboardingForm(getSectionsByType(page, "onboarding_form")[0], platform)}
        </>
      ) : null}

      {page.template === "content_page"
        ? page.sections
            .filter((section) => section.type !== "hero")
            .map((section) => renderFeatureGrid(section))
        : null}
    </>
  );
}

export function fallbackPrimaryLinks(platform: PlatformConfiguration): NavigationLink[] {
  return hasLinks(platform.menus.header_primary)
    ? platform.menus.header_primary
    : [
        { href: "/", label: "Accueil" },
        { href: "/a-propos", label: "A propos" },
        { href: "/evenements", label: "Evenements" },
        { href: "/contact", label: "Contact" },
      ];
}

export function fallbackUtilityLinks(platform: PlatformConfiguration): NavigationLink[] {
  return hasLinks(platform.menus.header_utility)
    ? platform.menus.header_utility
    : [
        { href: "/a-propos", label: "A propos" },
        { href: "/remboursement", label: "Remboursement" },
        { href: "/faq", label: "FAQ" },
        { href: "/mentions-legales", label: "Mentions legales" },
      ];
}
