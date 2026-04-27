import Link from "next/link";
import { notFound } from "next/navigation";
import type { ReactNode } from "react";

import { CatalogFilters } from "@/components/CatalogFilters";
import { ContentCard } from "@/components/ContentCard";
import { HeroSearch } from "@/components/HeroSearch";
import { PrintButton } from "@/components/PrintButton";
import { QuantityStepper } from "@/components/QuantityStepper";
import { findOrganizerProfile } from "@/lib/data/catalog";
import { PlatformConfiguration, PublicContent, SearchFilters } from "@/lib/types";
import {
  buildPublicUrl,
  buildSearchQuery,
  formatDateLabel,
  formatDateRange,
  formatMoney,
  getModuleMeta,
} from "@/lib/utils";

/* ================================================================
   HELPERS — Composants internes réutilisables
   ================================================================ */

function SectionHeader({
  eyebrow,
  title,
  description,
  action,
}: {
  eyebrow: string;
  title: string;
  description?: string;
  action?: ReactNode;
}) {
  return (
    <div className="section-header">
      <div>
        <p className="eyebrow">{eyebrow}</p>
        <h2>{title}</h2>
        {description ? <p className="section-copy">{description}</p> : null}
      </div>
      {action}
    </div>
  );
}

function StatIcon({ index }: { index: number }) {
  const icon = index % 3;

  if (icon === 1) {
    return (
      <svg aria-hidden="true" viewBox="0 0 24 24">
        <path d="M8.5 11.5a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z" />
        <path d="M15.5 12.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7Z" />
        <path d="M2.8 20.5c.8-3.7 2.8-5.5 5.7-5.5s4.9 1.8 5.7 5.5" />
        <path d="M13.2 20.5c.4-2.2 1.7-3.6 3.8-3.9 1.8-.2 3.2.5 4.2 2.1" />
      </svg>
    );
  }

  if (icon === 2) {
    return (
      <svg aria-hidden="true" viewBox="0 0 24 24">
        <path d="M5.5 5.5h5v5h-5z" />
        <path d="M13.5 5.5h5v5h-5z" />
        <path d="M5.5 13.5h5v5h-5z" />
        <path d="M13.5 13.5h5v5h-5z" />
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

/* ── Pagination ──────────────────────────────────────────────── */
function Pagination({
  basePath,
  currentPage,
  filters,
  totalPages,
}: {
  basePath: string;
  currentPage: number;
  filters: SearchFilters;
  totalPages: number;
}) {
  if (totalPages <= 1) return null;

  const windowStart = Math.max(1, currentPage - 1);
  const windowEnd = Math.min(totalPages, windowStart + 3);
  const firstPage = Math.max(1, windowEnd - 3);
  const pageNumbers = Array.from(
    { length: windowEnd - firstPage + 1 },
    (_, index) => firstPage + index,
  );

  const buildHref = (page: number) =>
    `${basePath}${buildSearchQuery({
      ...filters,
      page,
    })}`;

  return (
    <nav aria-label="Navigation pages" className="pagination">
      {currentPage > 1 ? (
        <Link className="pagination__btn" href={buildHref(currentPage - 1)}>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M15 18l-6-6 6-6" />
          </svg>
          Precedent
        </Link>
      ) : (
        <span className="pagination__btn is-disabled">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M15 18l-6-6 6-6" />
          </svg>
          Precedent
        </span>
      )}

      {firstPage > 1 ? (
        <>
          <Link className="pagination__btn" href={buildHref(1)}>
            1
          </Link>
          <span className="pagination__ellipsis">…</span>
        </>
      ) : null}

      {pageNumbers.map((page) => (
        <Link
          className={`pagination__btn${page === currentPage ? " active" : ""}`}
          href={buildHref(page)}
          key={page}
        >
          {page}
        </Link>
      ))}

      {windowEnd < totalPages ? (
        <>
          <span className="pagination__ellipsis">…</span>
          <Link className="pagination__btn" href={buildHref(totalPages)}>
            {totalPages}
          </Link>
        </>
      ) : null}

      {currentPage < totalPages ? (
        <Link className="pagination__btn" href={buildHref(currentPage + 1)}>
          Suivant
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M9 18l6-6-6-6" />
          </svg>
        </Link>
      ) : (
        <span className="pagination__btn is-disabled">
          Suivant
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M9 18l6-6-6-6" />
          </svg>
        </span>
      )}
    </nav>
  );
}

/* ── Icône cadenas pour booking summary ─────────────────────── */
function LockIcon() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <rect height="11" rx="2" width="14" x="5" y="11" />
      <path d="M8 11V7a4 4 0 0 1 8 0v4" />
    </svg>
  );
}

const DEFAULT_SERVICE_FEE = 1000;

function getCheckoutAmounts(
  item: PublicContent,
  selectedOffer: PublicContent["tiers"][number] | null,
) {
  const subtotal = selectedOffer?.price ?? item.priceFrom;
  const serviceFee = item.isFree ? 0 : DEFAULT_SERVICE_FEE;

  return {
    subtotal,
    serviceFee,
    total: item.isFree ? 0 : subtotal + serviceFee,
  };
}

function buildPaymentReference(
  item: PublicContent,
  selectedOffer: PublicContent["tiers"][number] | null,
  providedReference?: string,
) {
  if (providedReference?.trim()) {
    return providedReference.trim();
  }

  const safeItem = item.id.replace(/[^a-z0-9]/gi, "").toUpperCase().slice(0, 6) || "ORDER";
  const safeOffer =
    (selectedOffer?.id ?? "BASE").replace(/[^a-z0-9]/gi, "").toUpperCase().slice(0, 4) || "BASE";

  return `PAY-${safeItem}-${safeOffer}`;
}

function normalizePaidAt(value?: string) {
  if (!value) {
    return new Date().toISOString();
  }

  const parsed = new Date(value);

  return Number.isNaN(parsed.getTime()) ? new Date().toISOString() : parsed.toISOString();
}

function buildPaymentQuery(
  selectedOffer: PublicContent["tiers"][number] | null,
  paymentReference: string,
  paidAt: string,
) {
  const params = new URLSearchParams();

  if (selectedOffer?.id) {
    params.set("offer", selectedOffer.id);
  }

  params.set("tx", paymentReference);
  params.set("paidAt", paidAt);

  return `?${params.toString()}`;
}

/* ================================================================
   HOME VIEW
   ================================================================ */
export function HomeView({
  platform,
  featured,
  popular,
  organizers,
  categories,
  stats,
}: {
  platform: PlatformConfiguration;
  featured: PublicContent[];
  popular: PublicContent[];
  organizers: Array<{
    organizer: {
      slug: string;
      name: string;
      tagline: string;
      city: string;
      country: string;
      bannerUrl: string;
      logoUrl: string;
      verified: boolean;
    };
    items: PublicContent[];
  }>;
  categories: string[];
  stats: Array<{ label: string; value: string }>;
}) {
  return (
    <>
      <section className="hero">
        <div className="hero__image">
          <img
            alt="Scene premium et public pendant un evenement"
            src="https://images.unsplash.com/photo-1505236858219-8359eb29e329?auto=format&fit=crop&w=1800&q=80"
          />
        </div>
        <div className="shell hero__content">
          <div className="hero__copy">
            <p className="eyebrow">Marketplace publique · {platform.brandName}</p>
            <h1>Des experiences a reserver, soutenir ou rejoindre.</h1>
            <p className="hero__lede">
              Un catalogue premium pour billets, formations, stands, candidatures et campagnes,
              avec des parcours d'achat clairs et une mise en avant forte des organisateurs.
            </p>
            <div className="hero__actions">
              <Link className="button" href="/evenements">
                Explorer le catalogue
              </Link>
              <Link className="button button--ghost-light" href="/devenir-organisateur">
                Publier sur la plateforme
              </Link>
            </div>
          </div>

          <HeroSearch categories={categories} />
        </div>
      </section>

      <section className="stats-strip stats-strip--floating">
        <div className="shell stats-strip__grid">
          {stats.map((stat, index) => (
            <article className="stat-tile" key={stat.label}>
              <span className="stat-tile__icon">
                <StatIcon index={index} />
              </span>
              <span className="stat-tile__copy">
                <strong>{stat.value}</strong>
                <span>{stat.label}</span>
              </span>
            </article>
          ))}
        </div>
      </section>

      <section className="section">
        <div className="shell">
          <SectionHeader
            action={
              <Link className="button button--ghost" href="/recherche">
                Tout voir
              </Link>
            }
            description="Une vitrine riche et visuelle, avec des cartes denses et des CTA directs."
            eyebrow="Selection editee"
            title="A la une"
          />
          <div className="card-grid card-grid--three">
            {featured.map((item) => (
              <ContentCard item={item} key={item.id} />
            ))}
          </div>
        </div>
      </section>

      <section className="section section--light">
        <div className="shell">
          <SectionHeader
            description="Les contenus les plus consultes et les plus proches de la conversion."
            eyebrow="Tendances"
            title="Populaires cette semaine"
          />
          <div className="card-grid card-grid--three">
            {popular.map((item) => (
              <ContentCard item={item} key={item.id} />
            ))}
          </div>
        </div>
      </section>

      <section className="section">
        <div className="shell">
          <SectionHeader
            description="Chaque tenant peut etre valorise comme une vraie page publique d'organisateur."
            eyebrow="Organisateurs"
            title="Profils publics mis en avant"
          />

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
                      <Link href={`/${item.module}/${item.slug}`} key={item.id}>
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
    </>
  );
}

/* ================================================================
   MODULE LISTING VIEW — REFONTE BOLETO
   - Filter bar horizontal (style Boleto) en remplacement de la sidebar
   - Grille 3 colonnes plein largeur
   - Pagination
   ================================================================ */
export function ModuleListingView({
  module,
  title,
  description,
  heroImageUrl,
  items,
  filters,
  currentPage,
  totalItems,
  totalPages,
  categories,
  cities,
}: {
  module: PublicContent["module"];
  title: string;
  description: string;
  heroImageUrl: string;
  items: PublicContent[];
  filters: SearchFilters;
  currentPage: number;
  totalItems: number;
  totalPages: number;
  categories: string[];
  cities: string[];
}) {
  const meta = getModuleMeta(module);
  const highlightedCities = cities.slice(0, 3).join(" · ");

  return (
    <>
      {/* Hero section */}
      <section className="page-hero">
        <img alt={title} className="page-hero__image" src={heroImageUrl} />
        <div className="shell page-hero__content">
          <p className="eyebrow">Catalogue public</p>
          <h1>{title}</h1>
          <p>{description}</p>
          <div className="page-hero__pills">
            <span>{totalItems} contenus</span>
            <span>{categories.length} categories</span>
            <span>{highlightedCities || "Plusieurs villes"}</span>
          </div>
        </div>
      </section>

      {/* ── Barre de filtre horizontale Boleto ───────────────── */}
      <CatalogFilters
        action={`/${module}`}
        categories={categories}
        cities={cities}
        filters={filters}
      />

      {/* ── Grille de contenus plein largeur ─────────────────── */}
      <section className="listing-section">
        <div className="shell">
          <div className="listing-top-bar">
            <p className="listing-count">
              <strong>{totalItems}</strong> resultats
            </p>
            <Link
              className="button button--ghost button--small"
              href={`/recherche${buildSearchQuery({ ...filters, page: 1 })}`}
            >
              Recherche globale
            </Link>
          </div>

          {items.length > 0 ? (
            <>
              <div className="card-grid card-grid--three">
                {items.map((item) => (
                  <ContentCard item={item} key={item.id} />
                ))}
              </div>
              <Pagination
                basePath={`/${module}`}
                currentPage={currentPage}
                filters={filters}
                totalPages={totalPages}
              />
            </>
          ) : (
            <div className="empty-state">
              <h3>Aucun {meta.singular} ne correspond a ces filtres.</h3>
              <p>Elargissez la recherche ou revenez au catalogue complet.</p>
              <Link className="button" href={`/${module}`}>
                Reinitialiser les filtres
              </Link>
            </div>
          )}
        </div>
      </section>
    </>
  );
}

/* ================================================================
   DETAIL — SHARE LINKS
   ================================================================ */
function ShareLinks({ item }: { item: PublicContent }) {
  const publicUrl = encodeURIComponent(buildPublicUrl(`/${item.module}/${item.slug}`));
  const encodedTitle = encodeURIComponent(item.title);

  return (
    <div className="share-links">
      <span>Partager</span>
      <a href={`https://wa.me/?text=${encodedTitle}%20${publicUrl}`} rel="noreferrer" target="_blank">
        WhatsApp
      </a>
      <a href={`https://www.facebook.com/sharer/sharer.php?u=${publicUrl}`} rel="noreferrer" target="_blank">
        Facebook
      </a>
      <a href={`https://www.linkedin.com/sharing/share-offsite/?url=${publicUrl}`} rel="noreferrer" target="_blank">
        LinkedIn
      </a>
      <a href={`https://t.me/share/url?url=${publicUrl}&text=${encodedTitle}`} rel="noreferrer" target="_blank">
        Telegram
      </a>
    </div>
  );
}

/* ── Panneau sticky résumé (page détail) ────────────────────── */
function StickySummary({ item }: { item: PublicContent }) {
  const organizer = findOrganizerProfile(item.organizerSlug);
  const organizerImage = organizer?.logoUrl ?? item.organizers[0]?.imageUrl ?? item.coverImageUrl;
  const organizerName = organizer?.name ?? item.organizers[0]?.name ?? "Equipe organisatrice";

  return (
    <aside className="sticky-panel">
      <Link className="publisher-pill publisher-pill--card" href={`/organisateurs/${item.organizerSlug}`}>
        <img alt={organizerName} src={organizerImage} />
        <span>
          <small>Publie par</small>
          <strong>{organizerName}</strong>
        </span>
      </Link>
      <div className="sticky-panel__price">
        <span>A partir de</span>
        <strong>{item.isFree ? "Gratuit" : formatMoney(item.priceFrom, item.currency)}</strong>
      </div>
      <ul className="facts-list">
        <li>
          <strong>Date</strong>
          <span>{formatDateRange(item)}</span>
        </li>
        <li>
          <strong>Lieu</strong>
          <span>
            {item.venueName ?? item.city}, {item.country}
          </span>
        </li>
        <li>
          <strong>Organisateur</strong>
          <span>{organizerName}</span>
        </li>
      </ul>
      <div className="sticky-panel__cta-list">
        {item.tiers.map((tier) => (
          <Link
            className="button button--full"
            href={`/checkout/${item.module}/${item.slug}?offer=${tier.id}`}
            key={tier.id}
          >
            {tier.ctaLabel} - {tier.title}
          </Link>
        ))}
      </div>
      <div className="detail-trust-box">
        <strong>Checkout securise</strong>
        <p>Confirmation, verification paiement et recapitulatif centralises pour chaque commande.</p>
      </div>
      <ShareLinks item={item} />
    </aside>
  );
}

/* ── Blocs de détail (description, offres, speakers, FAQ...) ── */
function DetailBlocks({ item }: { item: PublicContent }) {
  return (
    <>
      <section className="detail-block">
        <SectionHeader
          description="Une lecture directe du contenu, puis des blocs adaptes selon le module."
          eyebrow={item.eyebrow}
          title="Presentation"
        />
        <div className="prose">
          <p>{item.description}</p>
          <ul className="bullet-list">
            {item.program.map((entry) => (
              <li key={entry}>{entry}</li>
            ))}
          </ul>
        </div>
      </section>

      <section className="detail-block">
        <SectionHeader eyebrow="Offres" title="Tickets, options ou paliers" />
        <div className="offer-grid">
          {item.tiers.map((tier) => (
            <article className="offer-card" key={tier.id}>
              <div>
                <h3>{tier.title}</h3>
                {tier.subtitle ? <p>{tier.subtitle}</p> : null}
              </div>
              <strong>{tier.price === 0 ? "Gratuit" : formatMoney(tier.price, tier.currency)}</strong>
              <ul className="bullet-list">
                {tier.perks.map((perk) => (
                  <li key={perk}>{perk}</li>
                ))}
              </ul>
              <Link
                className="button button--full"
                href={`/checkout/${item.module}/${item.slug}?offer=${tier.id}`}
              >
                {tier.ctaLabel}
              </Link>
            </article>
          ))}
        </div>
      </section>

      {item.speakers.length > 0 ? (
        <section className="detail-block detail-block--speakers">
          <SectionHeader eyebrow="Equipe" title="Intervenants et profils clefs" />
          <div className="people-grid">
            {item.speakers.map((speaker) => (
              <article className="person-card" key={speaker.name}>
                <img alt={speaker.name} src={speaker.imageUrl} />
                <h3>{speaker.name}</h3>
                <p>{speaker.role}</p>
              </article>
            ))}
          </div>
        </section>
      ) : null}

      <section className="detail-block">
        <SectionHeader eyebrow="Calendrier" title="Moments importants" />
        <div className="timeline">
          {item.timeline.map((entry) => (
            <article className="timeline__item" key={`${entry.label}-${entry.dateLabel}`}>
              <p>{entry.dateLabel}</p>
              <h3>{entry.label}</h3>
              <span>{entry.description}</span>
            </article>
          ))}
        </div>
      </section>

      {item.conditions.length > 0 || item.requiredDocuments.length > 0 ? (
        <section className="detail-block">
          <div className="two-column-text">
            <div>
              <SectionHeader eyebrow="Conditions" title="Regles et eligibilite" />
              <ul className="bullet-list">
                {item.conditions.map((condition) => (
                  <li key={condition}>{condition}</li>
                ))}
              </ul>
            </div>
            <div>
              <SectionHeader eyebrow="Pieces" title="Documents a prevoir" />
              {item.requiredDocuments.length > 0 ? (
                <ul className="bullet-list">
                  {item.requiredDocuments.map((document) => (
                    <li key={document}>{document}</li>
                  ))}
                </ul>
              ) : (
                <p className="section-copy">Aucun document obligatoire supplementaire.</p>
              )}
            </div>
          </div>
        </section>
      ) : null}

      {item.faq.length > 0 ? (
        <section className="detail-block">
          <SectionHeader eyebrow="FAQ" title="Questions frequentes" />
          <div className="faq-list">
            {item.faq.map((entry) => (
              <article className="faq-item" key={entry.question}>
                <h3>{entry.question}</h3>
                <p>{entry.answer}</p>
              </article>
            ))}
          </div>
        </section>
      ) : null}
    </>
  );
}

/* ================================================================
   MODULE DETAIL VIEW — Suppression section stats sombre
   ================================================================ */
export function ModuleDetailView({
  item,
  related,
}: {
  item: PublicContent | null;
  related: PublicContent[];
}) {
  if (!item) {
    notFound();
  }

  const meta = getModuleMeta(item.module);
  const organizer = findOrganizerProfile(item.organizerSlug);
  const organizerImage = organizer?.logoUrl ?? item.organizers[0]?.imageUrl ?? item.coverImageUrl;
  const organizerName = organizer?.name ?? item.organizers[0]?.name ?? "Organisateur";

  return (
    <>
      {/* Hero détail */}
      <section className="detail-hero">
        <img alt={item.title} className="detail-hero__image" src={item.coverImageUrl} />
        <div className="shell detail-hero__content">
          <div className="detail-hero__copy">
            <div className="content-card__badges">
              <span className="badge">{item.category}</span>
              {item.badges.map((badge) => (
                <span className="badge badge--muted" key={badge}>
                  {badge}
                </span>
              ))}
            </div>
            <p className="eyebrow">{item.eyebrow}</p>
            <h1>{item.title}</h1>
            <p className="hero__lede">{item.summary}</p>
            <div className="detail-hero__facts">
              <span>{formatDateRange(item)}</span>
              <span>
                {item.city}, {item.country}
              </span>
            </div>
            <Link
              className="publisher-pill publisher-pill--dark"
              href={`/organisateurs/${item.organizerSlug}`}
            >
              <img alt={organizerName} src={organizerImage} />
              <span>
                <small>Organisateur</small>
                <strong>{organizerName}</strong>
              </span>
            </Link>
          </div>
        </div>
      </section>

      {/*
        ⚠️  Section stats sombre supprimée (demande utilisateur — image 5)
        L'ancien bloc .stats-strip--detail a été retiré ici.
        Il est aussi masqué via CSS : .stats-strip--detail { display: none }
      */}

      {/* Galerie (si disponible) */}
      {item.gallery.length > 0 ? (
        <section className="section section--light section--tight">
          <div className="shell gallery-strip">
            {item.gallery.slice(0, 3).map((image) => (
              <article className="gallery-strip__item" key={image}>
                <img alt={item.title} src={image} />
              </article>
            ))}
          </div>
        </section>
      ) : null}

      {/* Corps de la page (blocs + panneau sticky) */}
      <section className="section">
        <div className="shell detail-layout">
          <div>
            <DetailBlocks item={item} />
          </div>
          <StickySummary item={item} />
        </div>
      </section>

      {/* Contenus similaires */}
      <section className="section section--light">
        <div className="shell">
          <SectionHeader
            action={
              <Link className="button button--ghost" href={`/${item.module}`}>
                Retour au catalogue
              </Link>
            }
            eyebrow="A decouvrir aussi"
            title={`Autres ${meta.title.toLowerCase()}`}
          />
          <div className="card-grid card-grid--three">
            {related.map((candidate) => (
              <ContentCard item={candidate} key={candidate.id} />
            ))}
          </div>
        </div>
      </section>
    </>
  );
}

/* ================================================================
   ORGANIZER VIEW
   ================================================================ */
export function OrganizerView({
  organizer,
  items,
}: {
  organizer: {
    slug: string;
    name: string;
    legalName: string;
    tagline: string;
    description: string;
    city: string;
    country: string;
    verified: boolean;
    followers: number;
    logoUrl: string;
    bannerUrl: string;
    websiteUrl?: string;
    supportEmail: string;
    supportPhone: string;
    socialLinks: Array<{ label: string; url: string }>;
  } | null;
  items: PublicContent[];
}) {
  if (!organizer) {
    notFound();
  }

  return (
    <>
      <section className="organizer-hero">
        <img alt={organizer.name} className="organizer-hero__image" src={organizer.bannerUrl} />
        <div className="shell organizer-hero__content">
          <div className="organizer-hero__identity">
            <img alt={organizer.name} src={organizer.logoUrl} />
            <div>
              <p className="eyebrow">Organisateur public</p>
              <h1>{organizer.name}</h1>
              <p>{organizer.tagline}</p>
              <div className="detail-hero__facts">
                <span>
                  {organizer.city}, {organizer.country}
                </span>
                <span>{organizer.followers} followers</span>
                <span>{organizer.verified ? "Profil verifie" : "Profil public"}</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="section">
        <div className="shell organizer-layout">
          <div>
            <SectionHeader eyebrow="A propos" title={organizer.legalName} />
            <p className="section-copy">{organizer.description}</p>
            <div className="facts-list facts-list--plain">
              <div>
                <strong>Contact</strong>
                <span>{organizer.supportEmail}</span>
              </div>
              <div>
                <strong>Telephone</strong>
                <span>{organizer.supportPhone}</span>
              </div>
              <div>
                <strong>Site</strong>
                <span>{organizer.websiteUrl ?? "A definir"}</span>
              </div>
            </div>
          </div>

          <aside className="sticky-panel">
            <div className="sticky-panel__price">
              <span>Raccourcis</span>
              <strong>Profil public</strong>
            </div>
            <div className="sticky-panel__cta-list">
              {organizer.socialLinks.map((social) => (
                <a
                  className="button button--full button--ghost"
                  href={social.url}
                  key={social.label}
                >
                  {social.label}
                </a>
              ))}
            </div>
          </aside>
        </div>
      </section>

      <section className="section section--light">
        <div className="shell">
          <SectionHeader eyebrow="Catalogue" title="Contenus publies" />
          <div className="card-grid card-grid--three">
            {items.map((item) => (
              <ContentCard item={item} key={item.id} />
            ))}
          </div>
        </div>
      </section>
    </>
  );
}

/* ================================================================
   SEARCH RESULTS VIEW — REFONTE BOLETO
   - Filter bar avec onglets de module
   - Grille plein largeur 3 colonnes
   - Pagination
   ================================================================ */
export function SearchResultsView({
  items,
  filters,
  currentPage,
  totalItems,
  totalPages,
  categories,
  cities,
}: {
  items: PublicContent[];
  filters: SearchFilters;
  currentPage: number;
  totalItems: number;
  totalPages: number;
  categories: string[];
  cities: string[];
}) {
  return (
    <>
      {/* Hero compact */}
      <section className="page-hero page-hero--compact">
        <div className="shell page-hero__content">
          <p className="eyebrow">Recherche globale</p>
          <h1>Tout le catalogue public</h1>
          <p>Une seule recherche pour tous les modules et tous les organisateurs.</p>
        </div>
      </section>

      {/* ── Barre de filtre horizontale avec onglets module ──── */}
        <CatalogFilters
          action="/recherche"
          categories={categories}
          cities={cities}
          filters={filters}
          includeModule
          layout="wide"
        />

      {/* ── Grille de contenus plein largeur ─────────────────── */}
      <section className="listing-section">
        <div className="shell">
          <div className="listing-top-bar">
            <p className="listing-count">
              <strong>{totalItems}</strong> resultats
            </p>
          </div>

          {items.length > 0 ? (
            <>
              <div className="card-grid card-grid--three">
                {items.map((item) => (
                  <ContentCard item={item} key={item.id} />
                ))}
              </div>
              <Pagination
                basePath="/recherche"
                currentPage={currentPage}
                filters={filters}
                totalPages={totalPages}
              />
            </>
          ) : (
            <div className="empty-state">
              <h3>Aucun contenu ne correspond a ces filtres.</h3>
              <p>Modifiez vos criteres ou explorez les catalogues par module.</p>
              <Link className="button" href="/recherche">
                Reinitialiser
              </Link>
            </div>
          )}
        </div>
      </section>
    </>
  );
}

/* ================================================================
   CHECKOUT VIEW — Booking Summary dark style Boleto
   Remplace l'ancien sticky-panel blanc par un panneau sombre
   avec détail ligne par ligne (image 8 de référence)
   ================================================================ */
export function CheckoutView({
  item,
  selectedOffer,
  platform,
  dateLabel,
}: {
  item: PublicContent | null;
  selectedOffer: PublicContent["tiers"][number] | null;
  platform: PlatformConfiguration;
  dateLabel: string;
}) {
  if (!item) {
    notFound();
  }

  const total = selectedOffer?.price ?? item.priceFrom;
  const organizer = findOrganizerProfile(item.organizerSlug);
  const organizerName = organizer?.name ?? item.organizers[0]?.name ?? "Organisateur";
  const organizerImage = organizer?.logoUrl ?? item.organizers[0]?.imageUrl ?? item.coverImageUrl;
  const { subtotal, serviceFee, total: totalPayable } = getCheckoutAmounts(item, selectedOffer);
  const paymentReference = buildPaymentReference(item, selectedOffer);
  const paidAt = normalizePaidAt();
  const paymentQuery = buildPaymentQuery(selectedOffer, paymentReference, paidAt);
  const successHref = `/checkout/${item.module}/${item.slug}/succes${paymentQuery}`;

  return (
    <>
      <section className="page-hero page-hero--compact">
        <div className="shell page-hero__content">
          <p className="eyebrow">Checkout</p>
          <h1>{item.title}</h1>
          <p>Parcours court, rassurant et lisible jusqu'au paiement.</p>
          <div className="page-hero__pills">
            <span>{selectedOffer?.title ?? "Offre principale"}</span>
            <span>{dateLabel}</span>
          </div>
        </div>
      </section>

      <section className="section">
        <div className="shell checkout-layout">

          {/* ── Formulaire checkout ────────────────────────────── */}
          <div className="checkout-form">
            <div className="checkout-head">
              <div>
                <span className="badge">{item.category}</span>
                <h2>{selectedOffer?.title ?? "Offre principale"}</h2>
                <p className="section-copy">
                  Acheteur, quantite, paiement et confirmation reunis dans une seule surface.
                </p>
              </div>
              <Link className="publisher-pill" href={`/organisateurs/${item.organizerSlug}`}>
                <img alt={organizerName} src={organizerImage} />
                <span>
                  <small>Organisateur</small>
                  <strong>{organizerName}</strong>
                </span>
              </Link>
            </div>

            <div className="checkout-steps">
              <span className="is-active">1. Offre</span>
              <span className="is-active">2. Acheteur</span>
              <span>3. Paiement</span>
              <span>4. Confirmation</span>
            </div>

            <div className="checkout-section">
              <h2>Informations acheteur</h2>
              <div className="form-grid">
                <label>
                  Nom complet
                  <input placeholder="Votre nom complet" />
                </label>
                <label>
                  Email
                  <input placeholder="vous@exemple.com" type="email" />
                </label>
                <label>
                  Telephone
                  <input placeholder="+225 ..." />
                </label>
                <label>
                  Quantite
                  <QuantityStepper />
                </label>
              </div>
            </div>

            <div className="checkout-section">
              <h2>Paiement</h2>
              <div className="payment-methods">
                {platform.paymentMethods.map((method) => (
                  <span className="payment-chip" key={method}>
                    {method}
                  </span>
                ))}
              </div>
              <p className="section-copy">
                La confirmation finale est validee par verification serveur et webhook de paiement.
              </p>
            </div>

          </div>

          {/* ── Booking Summary — Dark, style Boleto ─────────── */}
          <aside className="booking-summary">

            {/* Header */}
            <div className="booking-summary__header">
              <p className="booking-summary__eyebrow">Resume</p>
              <h2 className="booking-summary__title">
                {selectedOffer?.title ?? "Offre principale"}
              </h2>
              <p className="booking-summary__subtitle">{item.category}</p>
            </div>

            {/* Organisateur */}
            <div className="booking-summary__publisher">
              <img alt={organizerName} src={organizerImage} />
              <div className="booking-summary__publisher-copy">
                <small>Publie par</small>
                <strong>{organizerName}</strong>
              </div>
            </div>

            {/* Lignes de détail */}
            <div className="booking-summary__details">
              <div className="booking-summary__detail-row">
                <span className="booking-summary__detail-label">Date</span>
                <span className="booking-summary__detail-value">{dateLabel}</span>
              </div>
              <div className="booking-summary__detail-row">
                <span className="booking-summary__detail-label">Lieu</span>
                <span className="booking-summary__detail-value">
                  {item.venueName ?? item.city}, {item.country}
                </span>
              </div>
              <div className="booking-summary__detail-row">
                <span className="booking-summary__detail-label">Format</span>
                <span className="booking-summary__detail-value">
                  {item.format ?? "Presentiel"}
                </span>
              </div>
              {selectedOffer?.perks && selectedOffer.perks.length > 0 && (
                <div className="booking-summary__detail-row">
                  <span className="booking-summary__detail-label">Inclus</span>
                  <span className="booking-summary__detail-value">
                    {selectedOffer.perks.slice(0, 2).join(", ")}
                    {selectedOffer.perks.length > 2 && "…"}
                  </span>
                </div>
              )}
            </div>

            {/* Prix */}
            <div className="booking-summary__price-block">
              <div className="booking-summary__price-row">
                <span className="booking-summary__price-label">Sous-total</span>
                <span className="booking-summary__price-value">
                  {item.isFree ? "Gratuit" : formatMoney(subtotal, item.currency)}
                </span>
              </div>
              <div className="booking-summary__price-row">
                <span className="booking-summary__price-label">Frais de service</span>
                <span className="booking-summary__price-value">
                  {item.isFree ? "—" : formatMoney(serviceFee, item.currency)}
                </span>
              </div>
            </div>

            {/* Total */}
            <div className="booking-summary__total-block">
              <span className="booking-summary__total-label">Total a payer</span>
              <span className="booking-summary__total-value">
                {item.isFree ? "Gratuit" : formatMoney(totalPayable, item.currency)}
              </span>
            </div>

            {/* CTA */}
            <div className="booking-summary__cta">
              <Link className="button button--full" href={successHref}>
                Confirmer le paiement
              </Link>
            </div>

            {/* Réassurance */}
            <div className="booking-summary__trust">
              <LockIcon />
              <span>Paiement securise — verification serveur</span>
            </div>
          </aside>

        </div>
      </section>
    </>
  );
}

/* ================================================================
   PAYMENT SUCCESS VIEW
   ================================================================ */
export function PaymentSuccessView({
  item,
  selectedOffer,
  platform,
  paymentReference,
  paidAt,
}: {
  item: PublicContent | null;
  selectedOffer: PublicContent["tiers"][number] | null;
  platform: PlatformConfiguration;
  paymentReference?: string;
  paidAt?: string;
}) {
  if (!item) {
    notFound();
  }

  const organizer = findOrganizerProfile(item.organizerSlug);
  const organizerName = organizer?.name ?? item.organizers[0]?.name ?? "Organisateur";
  const organizerImage = organizer?.logoUrl ?? item.organizers[0]?.imageUrl ?? item.coverImageUrl;
  const { subtotal, serviceFee, total } = getCheckoutAmounts(item, selectedOffer);
  const resolvedReference = buildPaymentReference(item, selectedOffer, paymentReference);
  const resolvedPaidAt = normalizePaidAt(paidAt);
  const receiptHref = `/checkout/${item.module}/${item.slug}/recu${buildPaymentQuery(
    selectedOffer,
    resolvedReference,
    resolvedPaidAt,
  )}`;

  return (
    <>
      <section className="page-hero page-hero--compact">
        <div className="shell page-hero__content">
          <p className="eyebrow">Paiement confirme</p>
          <h1>Reservation enregistree</h1>
          <p>Le paiement a ete valide et un recapitulatif est deja disponible.</p>
        </div>
      </section>

      <section className="section">
        <div className="shell success-layout">
          <article className="success-card">
            <span className="success-card__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24">
                <path d="M20 7 9.5 17.5 4 12" />
              </svg>
            </span>
            <p className="eyebrow">Succes</p>
            <h2>Votre commande est confirmee</h2>
            <p className="section-copy">
              {platform.brandName} a centralise la confirmation, la reference de paiement et le
              recapitulatif de commande.
            </p>

            <div className="success-card__meta">
              <article>
                <span>Reference</span>
                <strong>{resolvedReference}</strong>
              </article>
              <article>
                <span>Offre</span>
                <strong>{selectedOffer?.title ?? item.title}</strong>
              </article>
              <article>
                <span>Total</span>
                <strong>{item.isFree ? "Gratuit" : formatMoney(total, item.currency)}</strong>
              </article>
              <article>
                <span>Paye le</span>
                <strong>{formatDateLabel(resolvedPaidAt)}</strong>
              </article>
            </div>

            <div className="success-card__actions">
              <Link className="button" href={receiptHref}>
                Voir le recu
              </Link>
              <Link className="button button--ghost" href={`/${item.module}/${item.slug}`}>
                Retour au contenu
              </Link>
            </div>
          </article>

          <aside className="success-side">
            <div className="success-side__panel">
              <Link className="publisher-pill publisher-pill--card" href={`/organisateurs/${item.organizerSlug}`}>
                <img alt={organizerName} src={organizerImage} />
                <span>
                  <small>Publie par</small>
                  <strong>{organizerName}</strong>
                </span>
              </Link>

              <div className="success-side__facts">
                <div>
                  <span>Date</span>
                  <strong>{formatDateRange(item)}</strong>
                </div>
                <div>
                  <span>Lieu</span>
                  <strong>
                    {item.venueName ?? item.city}, {item.country}
                  </strong>
                </div>
                <div>
                  <span>Sous-total</span>
                  <strong>{item.isFree ? "Gratuit" : formatMoney(subtotal, item.currency)}</strong>
                </div>
                <div>
                  <span>Frais</span>
                  <strong>{item.isFree ? "—" : formatMoney(serviceFee, item.currency)}</strong>
                </div>
              </div>

              <div className="success-side__footer">
                <LockIcon />
                <span>Confirmation envoyee et reference disponible pour le suivi.</span>
              </div>
            </div>
          </aside>
        </div>
      </section>
    </>
  );
}

/* ================================================================
   RECEIPT VIEW
   ================================================================ */
export function ReceiptView({
  item,
  selectedOffer,
  platform,
  paymentReference,
  paidAt,
}: {
  item: PublicContent | null;
  selectedOffer: PublicContent["tiers"][number] | null;
  platform: PlatformConfiguration;
  paymentReference?: string;
  paidAt?: string;
}) {
  if (!item) {
    notFound();
  }

  const organizer = findOrganizerProfile(item.organizerSlug);
  const organizerName = organizer?.name ?? item.organizers[0]?.name ?? "Organisateur";
  const organizerImage = organizer?.logoUrl ?? item.organizers[0]?.imageUrl ?? item.coverImageUrl;
  const { subtotal, serviceFee, total } = getCheckoutAmounts(item, selectedOffer);
  const resolvedReference = buildPaymentReference(item, selectedOffer, paymentReference);
  const resolvedPaidAt = normalizePaidAt(paidAt);
  const successHref = `/checkout/${item.module}/${item.slug}/succes${buildPaymentQuery(
    selectedOffer,
    resolvedReference,
    resolvedPaidAt,
  )}`;

  return (
    <>
      <section className="page-hero page-hero--compact">
        <div className="shell page-hero__content">
          <p className="eyebrow">Recu</p>
          <h1>Recu de paiement</h1>
          <p>Un recapitulatif clair, imprimable et partageable pour la transaction.</p>
        </div>
      </section>

      <section className="section">
        <div className="shell receipt-layout">
          <article className="receipt-card">
            <header className="receipt-card__header">
              <div>
                <p className="eyebrow">Recu officiel</p>
                <h2>{platform.brandName}</h2>
                <p className="section-copy">Reference {resolvedReference}</p>
              </div>
              <span className="receipt-card__status">Paye</span>
            </header>

            <div className="receipt-card__meta">
              <div>
                <span>Contenu</span>
                <strong>{item.title}</strong>
              </div>
              <div>
                <span>Offre</span>
                <strong>{selectedOffer?.title ?? "Offre principale"}</strong>
              </div>
              <div>
                <span>Date de paiement</span>
                <strong>{formatDateLabel(resolvedPaidAt)}</strong>
              </div>
              <div>
                <span>Lieu</span>
                <strong>
                  {item.venueName ?? item.city}, {item.country}
                </strong>
              </div>
            </div>

            <div className="receipt-card__publisher">
              <img alt={organizerName} src={organizerImage} />
              <div>
                <small>Organisateur</small>
                <strong>{organizerName}</strong>
              </div>
            </div>

            <div className="receipt-lines">
              <div className="receipt-line">
                <span>{selectedOffer?.title ?? item.title}</span>
                <strong>{item.isFree ? "Gratuit" : formatMoney(subtotal, item.currency)}</strong>
              </div>
              <div className="receipt-line receipt-line--muted">
                <span>Quantite</span>
                <strong>1</strong>
              </div>
              <div className="receipt-line receipt-line--muted">
                <span>Frais de service</span>
                <strong>{item.isFree ? "—" : formatMoney(serviceFee, item.currency)}</strong>
              </div>
            </div>

            <div className="receipt-card__total">
              <span>Total regle</span>
              <strong>{item.isFree ? "Gratuit" : formatMoney(total, item.currency)}</strong>
            </div>

            <footer className="receipt-card__footer">
              <div>
                <span>Support</span>
                <strong>{platform.supportEmail}</strong>
              </div>
              <div>
                <span>Telephone</span>
                <strong>{platform.supportPhone}</strong>
              </div>
            </footer>
          </article>

          <aside className="receipt-side">
            <div className="receipt-side__panel">
              <PrintButton className="button button--full">Imprimer le recu</PrintButton>
              <Link className="button button--ghost button--full" href={successHref}>
                Retour au succes
              </Link>
              <Link className="button button--ghost button--full" href={`/${item.module}/${item.slug}`}>
                Retour au contenu
              </Link>
              <p className="receipt-side__note">
                Ce recu reprend la reference, le montant et l'organisateur visible publiquement.
              </p>
            </div>
          </aside>
        </div>
      </section>
    </>
  );
}
