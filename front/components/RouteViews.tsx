import Link from "next/link";
import { notFound } from "next/navigation";
import type { ReactNode } from "react";

import { CatalogFilters } from "@/components/CatalogFilters";
import { CheckoutClient } from "@/components/CheckoutClient";
import { ContentCard } from "@/components/ContentCard";
import { EventLikeButton } from "@/components/EventLikeButton";
import { OrganizerFollowCard } from "@/components/OrganizerFollowCard";
import { PrintButton } from "@/components/PrintButton";
import { QrCode } from "@/components/QrCode";
import { getAuthToken } from "@/lib/auth";
import { getEventLikeSummaries } from "@/lib/data/public";
import {
  AccountUser,
  FrontPageData,
  FrontPageSection,
  OrganizerCatalogStats,
  OrganizerProfile,
  PlatformConfiguration,
  PublicContent,
  FaqEntry,
  SearchFilters,
  TimelineEntry,
} from "@/lib/types";
import {
  buildPublicUrl,
  buildSearchQuery,
  formatDateLabel,
  formatDateRange,
  formatMoney,
  getStaticPageHeroImage,
} from "@/lib/utils";

const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "") ?? (process.env.NODE_ENV === "development" ? "http://127.0.0.1:8000" : "");

/* ================================================================
   HELPERS — Composants internes réutilisables
   ================================================================ */

type EventLikeSummaryMap = Record<string, { liked: boolean; likes: number }>;

async function getLikeRenderingContext(items: PublicContent[]): Promise<{
  accountAuthenticated: boolean;
  likeSummaries: EventLikeSummaryMap;
}> {
  const hasLikeableEvents = items.some((item) => item.module === "evenements" && Boolean(item.organizerSlug) && Boolean(item.slug));

  if (!hasLikeableEvents) {
    return {
      accountAuthenticated: false,
      likeSummaries: {},
    };
  }

  const token = await getAuthToken();

  if (!token) {
    return {
      accountAuthenticated: false,
      likeSummaries: {},
    };
  }

  return {
    accountAuthenticated: true,
    likeSummaries: await getEventLikeSummaries(items, token),
  };
}

async function getOrganizerFollowRenderingContext(slug: string): Promise<{
  accountAuthenticated: boolean;
  following: boolean;
}> {
  const token = await getAuthToken();

  if (!token || !apiBaseUrl) {
    return {
      accountAuthenticated: false,
      following: false,
    };
  }

  try {
    const response = await fetch(`${apiBaseUrl}/api/v1/tenants/${encodeURIComponent(slug)}/organization-profile/follow`, {
      cache: "no-store",
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
    });

    if (!response.ok) {
      return {
        accountAuthenticated: false,
        following: false,
      };
    }

    const payload = (await response.json().catch(() => null)) as { data?: { following?: boolean } } | null;

    return {
      accountAuthenticated: true,
      following: Boolean(payload?.data?.following),
    };
  } catch {
    return {
      accountAuthenticated: false,
      following: false,
    };
  }
}

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

function getModuleDefaultDetailContent(item: PublicContent): {
  description: string;
  sectionDescription: string;
  program: string[];
  timeline: TimelineEntry[];
  conditions: string[];
  requiredDocuments: string[];
  faq: FaqEntry[];
  offerTitle: string;
} {
  const dateLabel = formatDateRange(item);

  switch (item.module) {
    case "formations":
      return {
        description:
          "Cette page présente une formation avec un cadre stable: objectifs, déroulé, conditions d'accès et informations pratiques restent homogènes tant qu'aucune personnalisation spécifique n'est faite côté super-admin.",
        sectionDescription: "Une base éditoriale permanente pour les formations, avec un rendu sobre et durable.",
        program: [
          "Présentation du programme et des objectifs pédagogiques",
          "Informations pratiques sur le format, la durée et le niveau attendu",
          "Accès à la confirmation, au reçu et au pass après validation",
        ],
        timeline: [
          { label: "Ouverture des inscriptions", dateLabel: "Immédiat", description: "Les réservations restent disponibles tant que l'offre est active." },
          { label: "Session planifiée", dateLabel, description: "La session suit les dates publiées sur la fiche formation." },
          { label: "Accès participant", dateLabel: "Après confirmation", description: "Chaque inscrit retrouve son reçu et son pass dans son panel acheteur." },
        ],
        conditions: [
          "Une seule commande valide ouvre l'accès à l'inscription correspondante.",
          "Les informations du profil acheteur servent de référence pour la commande et le reçu.",
        ],
        requiredDocuments: [
          "Justificatif complémentaire uniquement si demandé par l'organisateur.",
          "Coordonnées acheteur à jour pour les confirmations et rappels.",
        ],
        faq: [
          { question: "L'inscription est-elle nominative ?", answer: "Oui. Les confirmations, reçus et passes restent rattachés au compte acheteur connecté." },
          { question: "Le reçu est-il disponible après paiement ?", answer: "Oui. Il est consultable, imprimable et téléchargeable depuis le panel acheteur." },
        ],
        offerTitle: "Formules, inscriptions ou packs",
      };
    case "stands":
      return {
        description:
          "Cette page présente un stand avec une trame permanente: visibilité, conditions d'occupation, règles de réservation et informations de contact restent stables jusqu'à modification depuis le panel super-admin.",
        sectionDescription: "Une base éditoriale permanente pour les stands, pensée pour la clarté commerciale.",
        program: [
          "Présentation du positionnement et des bénéfices de visibilité",
          "Conditions d'occupation et préparation logistique",
          "Confirmation centralisée avec reçu et pass associés",
        ],
        timeline: [
          { label: "Réservation ouverte", dateLabel: "Immédiat", description: "Les emplacements restent réservables tant que l'offre est active." },
          { label: "Mise en place", dateLabel, description: "Les informations pratiques suivent la date de présence annoncée sur la fiche." },
          { label: "Accès exposant", dateLabel: "Après confirmation", description: "Le panel acheteur centralise la commande, le reçu et le pass d'accès." },
        ],
        conditions: [
          "Chaque réservation est liée à un compte acheteur identifié.",
          "Les conditions d'installation et d'utilisation peuvent être précisées par l'organisateur.",
        ],
        requiredDocuments: [
          "Éléments d'identification ou de marque seulement si demandés par l'organisateur.",
          "Coordonnées valides pour la logistique et les confirmations.",
        ],
        faq: [
          { question: "Le stand est-il confirmé immédiatement ?", answer: "Oui, dès que la commande est validée côté plateforme." },
          { question: "Un pass est-il généré ?", answer: "Oui. Un pass d'accès est créé pour la réservation confirmée." },
        ],
        offerTitle: "Réservations et emplacements",
      };
    case "appels-a-projets":
      return {
        description:
          "Cette page garde un socle permanent pour les appels à projets: objectifs, cadre de soumission, documents attendus et logique de traitement restent lisibles et homogènes, sauf personnalisation par le super-admin.",
        sectionDescription: "Une base éditoriale permanente pour cadrer les candidatures et éviter les pages trop fragiles.",
        program: [
          "Lecture du cadre de candidature et des conditions générales",
          "Préparation des pièces demandées et des informations du porteur",
          "Soumission depuis le formulaire public relié au tenant organisateur",
        ],
        timeline: [
          { label: "Candidatures ouvertes", dateLabel: item.applicationOpensAt ? formatDateLabel(item.applicationOpensAt) : "Ouvert", description: "Le formulaire public reste disponible pendant la période de soumission." },
          { label: "Clôture", dateLabel: item.deadlineAt ? formatDateLabel(item.deadlineAt) : "À confirmer", description: "Aucune nouvelle soumission n'est acceptée après la date limite." },
          { label: "Instruction", dateLabel: "Après soumission", description: "Les candidatures restent visibles dans le panel organisateur du tenant concerné." },
        ],
        conditions: [
          "Le dossier doit être soumis depuis le formulaire public prévu pour l'appel.",
          "Les informations d'identité et de contact doivent être cohérentes et exploitables.",
        ],
        requiredDocuments: [
          "Pièces listées dans le formulaire public si elles sont requises.",
          "Justificatifs additionnels seulement si l'organisateur les demande.",
        ],
        faq: [
          { question: "Faut-il être connecté pour candidater ?", answer: "Le formulaire public peut être ouvert au public, mais la plateforme peut imposer un compte acheteur prêt pour les actions sensibles." },
          { question: "La soumission remonte-t-elle chez l'organisateur ?", answer: "Oui. Les candidatures sont visibles dans le panel organisateur du tenant qui a publié l'appel." },
        ],
        offerTitle: "Candidatures et options",
      };
    case "crowdfunding":
      return {
        description:
          "Cette page présente une campagne avec des informations durables par défaut: objectif, logique de contribution, suivi de campagne et conditions de soutien restent stables tant qu'aucune mise à jour n'est faite côté super-admin.",
        sectionDescription: "Une base éditoriale permanente pour les campagnes et leurs parcours de contribution.",
        program: [
          "Présentation du projet et de son objectif de campagne",
          "Lecture des niveaux de contribution ou options de soutien",
          "Suivi du reçu et de la confirmation dans le panel acheteur",
        ],
        timeline: [
          { label: "Campagne en cours", dateLabel: "Ouvert", description: "Les contributions restent disponibles tant que la campagne est active." },
          { label: "Objectif et progression", dateLabel: "Suivi en temps réel", description: "La progression publique reste visible sur la fiche campagne." },
          { label: "Confirmation", dateLabel: "Après validation", description: "Chaque contribution confirmée génère un reçu et un suivi centralisé." },
        ],
        conditions: [
          "Chaque soutien confirmé reste attaché au compte acheteur utilisé.",
          "Les modalités précises peuvent être détaillées par l'organisateur si nécessaire.",
        ],
        requiredDocuments: [
          "Aucun document supplémentaire par défaut pour soutenir une campagne.",
        ],
        faq: [
          { question: "Le soutien génère-t-il un reçu ?", answer: "Oui, un reçu est généré pour chaque contribution confirmée." },
          { question: "Un pass est-il systématique ?", answer: "Non. Selon le type de campagne, la plateforme peut générer un pass d'achat générique ou seulement un reçu." },
        ],
        offerTitle: "Paliers et contributions",
      };
    default:
      return {
        description: item.description,
        sectionDescription: "Une lecture directe du contenu, puis des blocs adaptes selon le module.",
        program: item.program,
        timeline: item.timeline,
        conditions: item.conditions,
        requiredDocuments: item.requiredDocuments,
        faq: item.faq,
        offerTitle: "Tickets, options ou paliers",
      };
  }
}

function getFrontSection(
  page: FrontPageData | null | undefined,
  type: FrontPageSection["type"],
  key?: string,
): FrontPageSection | undefined {
  return page?.sections.find((section) => section.type === type && (!key || section.key === key));
}

function getFrontSections(
  page: FrontPageData | null | undefined,
  type: FrontPageSection["type"],
): FrontPageSection[] {
  return page?.sections.filter((section) => section.type === type) ?? [];
}

function sectionText(value: string | null | undefined, fallback: string): string {
  return value && value.trim().length > 0 ? value : fallback;
}

function sectionStats(
  section: FrontPageSection | undefined,
  fallback: Array<{ label: string; value: string }>,
): Array<{ label: string; value: string }> {
  if (!section || section.items.length === 0) {
    return fallback;
  }

  return section.items
    .map((item, index) => {
      const value = sectionText(item.value, "");

      return {
        label: sectionText(item.label ?? item.title, fallback[index]?.label ?? ""),
        value: value === "Dynamique" ? (fallback[index]?.value ?? "") : value,
      };
    })
    .filter((item) => item.label && item.value);
}

function CatalogCmsSections({ sections }: { sections: FrontPageSection[] }) {
  if (sections.length === 0) {
    return null;
  }

  return (
    <>
      {sections.map((section, sectionIndex) => (
        <section className={`section${sectionIndex % 2 === 1 ? " section--light" : ""}`} key={section.key}>
          <div className="shell">
            <SectionHeader
              description={section.body ?? undefined}
              eyebrow={sectionText(section.eyebrow, "Catalogue")}
              title={sectionText(section.title, "A decouvrir")}
            />
            {section.items.length > 0 ? (
              <div className="tile-grid">
                {section.items.map((item, itemIndex) => (
                  <article className="explore-tile" key={`${section.key}-${itemIndex}`}>
                    {item.image_url ? <img alt={item.title ?? item.label ?? "Illustration"} decoding="async" loading="lazy" src={item.image_url} /> : null}
                    {item.value ? <span className="badge">{item.value}</span> : null}
                    {item.title ? <h2>{item.title}</h2> : null}
                    {item.body ? <p>{item.body}</p> : null}
                    {item.href ? (
                      <Link className="button button--ghost" href={item.href}>
                        {item.label || "Explorer"}
                      </Link>
                    ) : null}
                  </article>
                ))}
              </div>
            ) : null}
          </div>
        </section>
      ))}
    </>
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
        <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
        <path d="M4 20c1-3.8 3.8-6 8-6s7 2.2 8 6" />
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

function HeroSearch({ categories }: { categories: string[] }) {
  const quickLinks = [
    { href: "/formations", label: "Formations" },
    { href: "/stands", label: "Stands" },
    { href: "/appels-a-projets", label: "Appels a projets" },
    { href: "/crowdfunding", label: "Crowdfunding" },
  ];

  return (
    <aside className="hero-search">
      <div className="hero-search__head">
        <p>Marketplace public</p>
        <h2>Trouvez rapidement un contenu</h2>
      </div>

      <div className="hero-search__tabs">
        {quickLinks.map((link) => (
          <Link href={link.href} key={link.href}>
            {link.label}
          </Link>
        ))}
      </div>

      <form action="/recherche" className="hero-search__fields" method="get">
        <div className="hero-search__field-stack">
          <label htmlFor="hero-search-q">
            <span className="hero-search__field-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="7" />
                <path d="m20 20-3.5-3.5" />
              </svg>
            </span>
            Rechercher
          </label>
          <input id="hero-search-q" name="q" placeholder="Evenement, formation, stand..." type="search" />
        </div>

        <div className="hero-search__field-stack">
          <label htmlFor="hero-search-category">
            <span className="hero-search__field-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24">
                <path d="M4 7h16" />
                <path d="M7 12h10" />
                <path d="M10 17h4" />
              </svg>
            </span>
            Categorie
          </label>
          <select defaultValue="" id="hero-search-category" name="category">
            <option value="">Toutes les categories</option>
            {categories.map((category) => (
              <option key={category} value={category}>
                {category}
              </option>
            ))}
          </select>
        </div>

        <button className="button" type="submit">
          Explorer
        </button>
      </form>
    </aside>
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

function normalizePaidAt(value?: string) {
  if (!value) {
    return new Date().toISOString();
  }

  const parsed = new Date(value);

  return Number.isNaN(parsed.getTime()) ? new Date().toISOString() : parsed.toISOString();
}

export async function HomeView({
  page,
  platform,
  featured,
  popular,
  organizers,
  categories,
  stats,
}: {
  page?: FrontPageData | null;
  platform: PlatformConfiguration;
  featured: PublicContent[];
  popular: PublicContent[];
  organizers: Array<{ organizer: OrganizerProfile; items: PublicContent[] }>;
  categories: string[];
  stats: Array<{ label: string; value: string }>;
}) {
  const hero = getFrontSection(page, "hero", "home_hero") ?? getFrontSection(page, "hero");
  const metrics = getFrontSection(page, "metrics", "home_metrics") ?? getFrontSection(page, "metrics");
  const featuredSection = getFrontSection(page, "feature_grid", "home_featured");
  const popularSection = getFrontSection(page, "feature_grid", "home_popular");
  const organizersSection = getFrontSection(page, "organizer_highlights", "home_organizers")
    ?? getFrontSection(page, "organizer_highlights");
  const displayedStats = sectionStats(metrics, stats);
  const { accountAuthenticated, likeSummaries } = await getLikeRenderingContext([...featured, ...popular]);

  return (
    <>
      <section className="hero">
        <div className="hero__image">
          <img
            alt={sectionText(hero?.title, "Scene premium et public pendant un evenement")}
            src={sectionText(hero?.image_url, "https://images.unsplash.com/photo-1505236858219-8359eb29e329?auto=format&fit=crop&w=1800&q=80")}
          />
        </div>
        <div className="shell hero__content">
          <div className="hero__copy">
            <p className="eyebrow">{sectionText(hero?.eyebrow, `Marketplace publique · ${platform.brandName}`)}</p>
            <h1>{sectionText(hero?.title, "Des experiences a reserver, soutenir ou rejoindre.")}</h1>
            <p className="hero__lede">
              {sectionText(
                hero?.body,
                "Un catalogue premium pour billets, formations, stands, candidatures et campagnes, avec des parcours d'achat clairs et une mise en avant forte des organisateurs.",
              )}
            </p>
            <div className="hero__actions">
              <Link className="button" href={hero?.primary_cta?.url || "/evenements"}>
                {hero?.primary_cta?.label || "Explorer le catalogue"}
              </Link>
              <Link className="button button--ghost-light" href={hero?.secondary_cta?.url || "/devenir-organisateur"}>
                {hero?.secondary_cta?.label || "Publier sur la plateforme"}
              </Link>
            </div>
          </div>

          <HeroSearch categories={categories} />
        </div>
      </section>

      <section className="stats-strip stats-strip--floating">
        <div className="shell stats-strip__grid">
          {displayedStats.map((stat, index) => (
            <article className="stat-tile" key={stat.label}>
              <span aria-hidden="true" className="stat-tile__icon">
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
            description={featuredSection?.body ?? "Une vitrine riche et visuelle, avec des cartes denses et des CTA directs."}
            eyebrow={sectionText(featuredSection?.eyebrow, "Selection editee")}
            title={sectionText(featuredSection?.title, "A la une")}
          />
          <div className="card-grid card-grid--three">
            {featured.map((item) => (
              <ContentCard
                accountAuthenticated={accountAuthenticated}
                initialLiked={likeSummaries[item.slug]?.liked}
                item={item}
                key={item.id}
              />
            ))}
          </div>
        </div>
      </section>

      <section className="section section--light">
        <div className="shell">
          <SectionHeader
            description={popularSection?.body ?? "Les contenus les plus consultes et les plus proches de la conversion."}
            eyebrow={sectionText(popularSection?.eyebrow, "Tendances")}
            title={sectionText(popularSection?.title, "Populaires cette semaine")}
          />
          <div className="card-grid card-grid--three">
            {popular.map((item) => (
              <ContentCard
                accountAuthenticated={accountAuthenticated}
                initialLiked={likeSummaries[item.slug]?.liked}
                item={item}
                key={item.id}
              />
            ))}
          </div>
        </div>
      </section>

      <section className="section">
        <div className="shell">
          <SectionHeader
            description={organizersSection?.body ?? "Chaque tenant peut etre valorise comme une vraie page publique d'organisateur."}
            eyebrow={sectionText(organizersSection?.eyebrow, "Organisateurs")}
            title={sectionText(organizersSection?.title, "Profils publics mis en avant")}
          />
          <div className="organizer-grid">
            {organizers.map(({ organizer, items }) => (
              <article className="organizer-card" key={organizer.slug}>
                <div className="organizer-card__banner">
                  <img alt={organizer.name} decoding="async" loading="lazy" src={organizer.bannerUrl} />
                </div>
                <div className="organizer-card__body">
                  <div className="organizer-card__identity">
                    <img alt={organizer.name} decoding="async" loading="lazy" src={organizer.logoUrl} />
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
    </>
  );
}

/* ================================================================
   MODULE LISTING VIEW — REFONTE BOLETO
   - Filter bar horizontal (style Boleto) en remplacement de la sidebar
   - Grille 3 colonnes plein largeur
   - Pagination
   ================================================================ */
export async function ModuleListingView({
  page,
  module,
  title,
  singular,
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
  page?: FrontPageData | null;
  module: PublicContent["module"];
  title: string;
  singular: string;
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
  const hero = getFrontSection(page, "hero");
  const moduleHrefMode = module === "evenements" ? "query" : "route";
  const { accountAuthenticated, likeSummaries } = await getLikeRenderingContext(items);

  return (
    <>
      <section className="page-hero">
        <img alt={sectionText(hero?.title, title)} className="page-hero__image" src={sectionText(hero?.image_url, heroImageUrl)} />
        <div className="shell page-hero__content">
          <p className="eyebrow">{sectionText(hero?.eyebrow, "Catalogue public")}</p>
          <h1>{sectionText(hero?.title, title)}</h1>
          <p>{sectionText(hero?.body, description)}</p>
        </div>
      </section>

      <CatalogFilters
        action={`/${module}`}
        categories={categories}
        cities={cities}
        filters={filters}
        includeModule
        moduleHrefMode={moduleHrefMode}
      />

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
                  <ContentCard
                    accountAuthenticated={accountAuthenticated}
                    initialLiked={likeSummaries[item.slug]?.liked}
                    item={item}
                    key={item.id}
                  />
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
              <h3>Aucun {singular} ne correspond a ces filtres.</h3>
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

function StickySummary({ item }: { item: PublicContent }) {
  const organizerImage = item.organizers[0]?.imageUrl ?? item.coverImageUrl;
  const organizerName = item.organizers[0]?.name ?? "Equipe organisatrice";
  const applicationHref = item.module === "appels-a-projets" && item.applicationForm
    ? `/appels-a-projets/${item.slug}/postuler`
    : null;

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
        {applicationHref ? (
          <Link className="button button--full" href={applicationHref}>
            Soumettre ma candidature
          </Link>
        ) : null}
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
      {item.module === "evenements" ? (
        <div className="detail-like-panel">
          <span>J'aime</span>
          <EventLikeButton
            eventSlug={item.slug}
            initialCount={item.likesCount}
            tenantSlug={item.organizerSlug}
            variant="detail"
          />
        </div>
      ) : null}
      <div className="detail-trust-box">
        <strong>{applicationHref ? "Candidature securisee" : "Checkout securise"}</strong>
        <p>{applicationHref ? "Formulaire public valide cote serveur, villes et pays locaux, pieces jointes controlees." : "Confirmation, verification paiement et recapitulatif centralises pour chaque commande."}</p>
      </div>
      <ShareLinks item={item} />
    </aside>
  );
}

function DetailBlocks({ item }: { item: PublicContent }) {
  const defaults = getModuleDefaultDetailContent(item);
  const description = item.module === "evenements" ? item.description : defaults.description;
  const program = item.module === "evenements" ? item.program : defaults.program;
  const timeline = item.module === "evenements" ? item.timeline : defaults.timeline;
  const conditions = item.module === "evenements" ? item.conditions : defaults.conditions;
  const requiredDocuments = item.module === "evenements" ? item.requiredDocuments : defaults.requiredDocuments;
  const faq = item.module === "evenements" ? item.faq : defaults.faq;

  return (
    <>
      <section className="detail-block">
        <SectionHeader
          eyebrow="Presentation"
          title={item.summary || item.title}
          description={defaults.sectionDescription}
        />
        <div className="prose">
          <p>{description}</p>
          <ul className="bullet-list">
            {program.map((entry) => (
              <li key={entry}>{entry}</li>
            ))}
          </ul>
        </div>
      </section>

      {item.module === "appels-a-projets" && item.applicationForm ? (
        <section className="detail-block">
          <SectionHeader eyebrow="Candidature" title="Deposer votre dossier" />
          <div className="prose">
            <p>{item.applicationForm.description ?? "Le formulaire public de candidature inclut les informations personnelles, la localisation, le numero avec indicatif et les pieces jointes requises."}</p>
            <Link className="button" href={`/appels-a-projets/${item.slug}/postuler`}>
              {item.applicationForm.submit_label ?? "Soumettre ma candidature"}
            </Link>
          </div>
        </section>
      ) : null}

      {item.tiers.length > 0 ? (
        <section className="detail-block">
          <SectionHeader eyebrow="Offres" title={defaults.offerTitle} />
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
      ) : null}

      {item.speakers.length > 0 ? (
        <section className="detail-block detail-block--speakers">
          <SectionHeader eyebrow="Equipe" title="Intervenants et profils clefs" />
          <div className="people-grid">
            {item.speakers.map((speaker) => (
              <article className="person-card" key={speaker.name}>
                <img alt={speaker.name} decoding="async" loading="lazy" src={speaker.imageUrl} />
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
          {timeline.map((entry) => (
            <article className="timeline__item" key={`${entry.label}-${entry.dateLabel}`}>
              <p>{entry.dateLabel}</p>
              <h3>{entry.label}</h3>
              <span>{entry.description}</span>
            </article>
          ))}
        </div>
      </section>

      {conditions.length > 0 || requiredDocuments.length > 0 ? (
        <section className="detail-block">
          <div className="two-column-text">
            <div>
              <SectionHeader eyebrow="Conditions" title="Regles et eligibilite" />
              <ul className="bullet-list">
                {conditions.map((condition) => (
                  <li key={condition}>{condition}</li>
                ))}
              </ul>
            </div>
            <div>
              <SectionHeader eyebrow="Pieces" title="Documents a prevoir" />
              {requiredDocuments.length > 0 ? (
                <ul className="bullet-list">
                  {requiredDocuments.map((document) => (
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

      {faq.length > 0 ? (
        <section className="detail-block">
          <SectionHeader eyebrow="FAQ" title="Questions frequentes" />
          <div className="faq-list">
            {faq.map((entry) => (
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

export async function ModuleDetailView({
  item,
  related,
}: {
  item: PublicContent | null;
  related: PublicContent[];
}) {
  if (!item) {
    notFound();
  }

  const organizerImage = item.organizers[0]?.imageUrl ?? item.coverImageUrl;
  const organizerName = item.organizers[0]?.name ?? "Organisateur";
  const { accountAuthenticated, likeSummaries } = await getLikeRenderingContext([item, ...related]);

  return (
    <>
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

      {item.gallery.length > 0 ? (
        <section className="section section--light section--tight">
          <div className="shell gallery-strip">
            {item.gallery.slice(0, 3).map((image) => (
              <article className="gallery-strip__item" key={image}>
                <img alt={item.title} decoding="async" loading="lazy" src={image} />
              </article>
            ))}
          </div>
        </section>
      ) : null}

      <section className="section">
        <div className="shell detail-layout">
          <div>
            <DetailBlocks item={item} />
          </div>
          <StickySummary item={item} />
        </div>
      </section>

      <section className="section section--light">
        <div className="shell">
          <SectionHeader
            action={
              <Link className="button button--ghost" href={`/${item.module}`}>
                Retour au catalogue
              </Link>
            }
            eyebrow="A decouvrir aussi"
            title={`Autres ${item.moduleTitle.toLowerCase()}`}
          />
          <div className="card-grid card-grid--three">
            {related.map((candidate) => (
              <ContentCard
                accountAuthenticated={accountAuthenticated}
                initialLiked={likeSummaries[candidate.slug]?.liked}
                item={candidate}
                key={candidate.id}
              />
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
export async function OrganizerView({
  organizer,
  items,
  filters,
  currentPage,
  totalItems,
  totalPages,
  categories,
  cities,
  stats,
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
  filters: SearchFilters;
  currentPage: number;
  totalItems: number;
  totalPages: number;
  categories: string[];
  cities: string[];
  stats: OrganizerCatalogStats;
}) {
  if (!organizer) {
    notFound();
  }

  const eventCount = stats.byModule.evenements ?? 0;
  const paidCount = stats.paid;
  const freeCount = stats.free;
  const { accountAuthenticated, likeSummaries } = await getLikeRenderingContext(items);
  const followContext = await getOrganizerFollowRenderingContext(organizer.slug);

  return (
    <>
      <section className="organizer-hero">
        <img alt={organizer.name} className="organizer-hero__image" src={organizer.bannerUrl} />
        <div className="shell organizer-hero__content organizer-profile__hero">
          <div className="organizer-profile__hero-copy">
            <div className="organizer-hero__identity">
              <img alt={organizer.name} decoding="async" loading="lazy" src={organizer.logoUrl} />
              <div>
                <p className="eyebrow">Organisateur public</p>
                <h1>{organizer.name}</h1>
                <p>{organizer.tagline}</p>
                <div className="detail-hero__facts">
                  <span>
                    {organizer.city}, {organizer.country}
                  </span>
                  <span>{organizer.followers} abonnés</span>
                  <span>{organizer.verified ? "Profil verifie" : "Profil public"}</span>
                </div>
              </div>
            </div>
            <p className="organizer-profile__lede">{organizer.description}</p>
            <div className="organizer-profile__hero-actions">
              {organizer.websiteUrl ? (
                <a className="button" href={organizer.websiteUrl} rel="noreferrer" target="_blank">
                  Visiter le site
                </a>
              ) : null}
              <a className="button button--ghost-light" href={`mailto:${organizer.supportEmail}`}>
                Contacter l'organisation
              </a>
            </div>
          </div>
          <aside className="organizer-profile__hero-card">
            <OrganizerFollowCard
              initialAuthenticated={followContext.accountAuthenticated}
              initialFollowing={followContext.following}
              initialFollowers={organizer.followers}
              organizerName={organizer.name}
              slug={organizer.slug}
            />
            <div className="organizer-profile__hero-stats organizer-profile__hero-stats--compact">
              <div>
                <strong>{eventCount}</strong>
                <span>événements publiés</span>
              </div>
              <div>
                <strong>{paidCount}</strong>
                <span>publications payantes</span>
              </div>
              <div>
                <strong>{freeCount}</strong>
                <span>publications gratuites</span>
              </div>
              <div>
                <strong>{organizer.socialLinks.length}</strong>
                <span>liens publics</span>
              </div>
            </div>
          </aside>
        </div>
      </section>

      <section className="section">
        <div className="shell organizer-profile__body">
          <div className="organizer-profile__main">
            <section className="detail-block">
              <SectionHeader
                eyebrow="A propos"
                title={organizer.legalName}
                description="Une page publique claire pour présenter l'entreprise, rassurer les acheteurs et rendre les informations immédiatement lisibles."
              />
              <div className="prose">
                <p>{organizer.description}</p>
              </div>
            </section>

            <section className="detail-block">
              <SectionHeader eyebrow="Informations" title="Coordonnées et présence publique" />
              <div className="organizer-profile__facts">
                <div>
                  <span>Ville</span>
                  <strong>{organizer.city}, {organizer.country}</strong>
                </div>
                <div>
                  <span>E-mail</span>
                  <strong>{organizer.supportEmail}</strong>
                </div>
                <div>
                  <span>Téléphone</span>
                  <strong>{organizer.supportPhone}</strong>
                </div>
                <div>
                  <span>Site web</span>
                  <strong>{organizer.websiteUrl ?? "A définir"}</strong>
                </div>
              </div>
            </section>
          </div>

          <aside className="organizer-profile__aside">
            <section className="detail-block organizer-profile__media">
              <p className="eyebrow">Présentation</p>
              <img alt={organizer.name} decoding="async" loading="lazy" src={organizer.bannerUrl} />
              <div className="organizer-profile__media-copy">
                <strong>{organizer.name}</strong>
                <span>{organizer.tagline}</span>
              </div>
            </section>

            {organizer.socialLinks.length > 0 ? (
              <section className="detail-block">
                <SectionHeader eyebrow="Réseaux" title="Suivre l'organisation" />
                <div className="sticky-panel__cta-list">
                  {organizer.socialLinks.map((social) => (
                    <a
                      className="button button--full button--ghost"
                      href={social.url}
                      key={social.label}
                      rel="noreferrer"
                      target="_blank"
                    >
                      {social.label}
                    </a>
                  ))}
                </div>
              </section>
            ) : null}
          </aside>
        </div>
      </section>

      <section className="section section--light">
        <div className="shell">
          <SectionHeader
            eyebrow="Catalogue"
            title="Événements de l'organisation"
            description="Cette vue affiche directement tous les événements publics publiés par cette organisation." 
          />
          <div className="listing-top-bar organizer-profile__catalog-meta">
            <p className="listing-count organizer-profile__catalog-count">
              <strong>{totalItems}</strong> événements publics
            </p>
            <p className="organizer-profile__catalog-note">Tous les événements affichés ici proviennent de {organizer.name}.</p>
          </div>
          {items.length > 0 ? (
            <>
              <div className="card-grid card-grid--three">
                {items.map((item) => (
                  <ContentCard
                    accountAuthenticated={accountAuthenticated}
                    initialLiked={likeSummaries[item.slug]?.liked}
                    item={item}
                    key={item.id}
                  />
                ))}
              </div>
              <Pagination
                basePath={`/organisateurs/${organizer.slug}`}
                currentPage={currentPage}
                filters={filters}
                totalPages={totalPages}
              />
            </>
          ) : (
            <div className="empty-state">
              <h3>Aucun événement public n'est disponible pour cette organisation.</h3>
              <p>Revenez plus tard pour découvrir les prochains événements publiés.</p>
            </div>
          )}
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
export async function SearchResultsView({
  page,
  items,
  filters,
  currentPage,
  totalItems,
  totalPages,
  categories,
  cities,
}: {
  page?: FrontPageData | null;
  items: PublicContent[];
  filters: SearchFilters;
  currentPage: number;
  totalItems: number;
  totalPages: number;
  categories: string[];
  cities: string[];
}) {
  const hero = getFrontSection(page, "hero");
  const { accountAuthenticated, likeSummaries } = await getLikeRenderingContext(items);

  return (
    <>
      {/* Hero compact */}
      <section className="page-hero page-hero--compact">
        {hero?.image_url ? <img alt={sectionText(hero.title, "Recherche")} className="page-hero__image" src={hero.image_url} /> : null}
        <div className="shell page-hero__content">
          <p className="eyebrow">{sectionText(hero?.eyebrow, "Recherche globale")}</p>
          <h1>{sectionText(hero?.title, "Tout le catalogue public")}</h1>
          <p>{sectionText(hero?.body, "Une seule recherche pour tous les modules et tous les organisateurs.")}</p>
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
                  <ContentCard
                    accountAuthenticated={accountAuthenticated}
                    initialLiked={likeSummaries[item.slug]?.liked}
                    item={item}
                    key={item.id}
                  />
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
  paymentOptions,
  accountUser,
  loginUrl,
}: {
  item: PublicContent | null;
  selectedOffer: PublicContent["tiers"][number] | null;
  platform: PlatformConfiguration;
  dateLabel: string;
  paymentOptions: import("@/lib/types").CheckoutPaymentOptions | null;
  accountUser: AccountUser | null;
  loginUrl: string;
}) {
  if (!item) {
    notFound();
  }

  return (
    <CheckoutClient
      accountUser={accountUser}
      dateLabel={dateLabel}
      initialPaymentOptions={paymentOptions}
      item={item}
      loginUrl={loginUrl}
      platform={platform}
      selectedOffer={selectedOffer}
    />
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
  isConfirmed,
  verification,
}: {
  item: PublicContent | null;
  selectedOffer: PublicContent["tiers"][number] | null;
  platform: PlatformConfiguration;
  paymentReference?: string;
  paidAt?: string;
  isConfirmed?: boolean;
  verification?: import("@/lib/types").CheckoutVerificationResult | null;
}) {
  if (!item) {
    notFound();
  }

  const organizerName = item.organizers[0]?.name ?? "Organisateur";
  const organizerImage = item.organizers[0]?.imageUrl ?? item.coverImageUrl;
  const subtotal = verification?.amounts.net ?? (selectedOffer?.price ?? item.priceFrom);
  const serviceFee = verification?.amounts.fees ?? 0;
  const total = verification?.amounts.gross ?? subtotal + serviceFee;
  const currency = verification?.amounts.currency ?? item.currency;
  const resolvedReference = buildPaymentReference(item, selectedOffer, paymentReference);
  const resolvedPaidAt = normalizePaidAt(paidAt);
  const receiptHref = `/checkout/${item.module}/${item.slug}/recu${buildPaymentQuery(
    selectedOffer,
    resolvedReference,
    resolvedPaidAt,
  )}`;
  const resolvedConfirmed = isConfirmed ?? verification?.is_successful ?? false;

  return (
    <>
      <section className="page-hero page-hero--compact">
        <div className="shell page-hero__content">
          <p className="eyebrow">{resolvedConfirmed ? "Paiement confirme" : "Paiement en attente"}</p>
          <h1>{resolvedConfirmed ? "Reservation enregistree" : "Verification du paiement en cours"}</h1>
          <p>
            {resolvedConfirmed
              ? "Le paiement a ete valide et un recapitulatif est deja disponible."
              : "Le paiement n'est pas encore confirme. Verifiez le statut avant de considérer la commande comme finalisée."}
          </p>
        </div>
      </section>

      <section className="section">
        <div className="shell success-layout">
          <article className="success-card">
            <span className="success-card__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24">
                {resolvedConfirmed ? <path d="M20 7 9.5 17.5 4 12" /> : <path d="M12 8v4m0 4h.01" />}
              </svg>
            </span>
            <p className="eyebrow">{resolvedConfirmed ? "Succes" : "Statut a verifier"}</p>
            <h2>{resolvedConfirmed ? "Votre commande est confirmee" : "Votre paiement n'est pas encore confirme"}</h2>
            <p className="section-copy">
              {resolvedConfirmed
                ? `${platform.brandName} a centralise la confirmation, la reference de paiement et le recapitulatif de commande.`
                : `${platform.brandName} attend encore une confirmation definitive du paiement ou une re-verification du serveur.`}
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
                <strong>{total === 0 ? "Gratuit" : formatMoney(total, currency)}</strong>
              </article>
              <article>
                <span>Paye le</span>
                <strong>{formatDateLabel(resolvedPaidAt)}</strong>
              </article>
            </div>

            <div className="success-card__actions">
              {resolvedConfirmed ? (
                <Link className="button" href={receiptHref}>
                  Voir le recu
                </Link>
              ) : (
                <Link className="button" href={`/${item.module}/${item.slug}?tenant=${encodeURIComponent(item.organizerSlug)}`}>
                  Revenir au checkout
                </Link>
              )}
              <Link className="button button--ghost" href={`/${item.module}/${item.slug}?tenant=${encodeURIComponent(item.organizerSlug)}`}>
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
                  <strong>{subtotal === 0 ? "Gratuit" : formatMoney(subtotal, currency)}</strong>
                </div>
                <div>
                  <span>Frais</span>
                  <strong>{serviceFee === 0 ? "—" : formatMoney(serviceFee, currency)}</strong>
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
  isConfirmed,
  verification,
}: {
  item: PublicContent | null;
  selectedOffer: PublicContent["tiers"][number] | null;
  platform: PlatformConfiguration;
  paymentReference?: string;
  paidAt?: string;
  isConfirmed?: boolean;
  verification?: import("@/lib/types").CheckoutVerificationResult | null;
}) {
  if (!item) {
    notFound();
  }

  const organizerName = item.organizers[0]?.name ?? "Organisateur";
  const organizerImage = item.organizers[0]?.imageUrl ?? item.coverImageUrl;
  const subtotal = verification?.amounts.net ?? (selectedOffer?.price ?? item.priceFrom);
  const serviceFee = verification?.amounts.fees ?? 0;
  const total = verification?.amounts.gross ?? subtotal + serviceFee;
  const currency = verification?.amounts.currency ?? item.currency;
  const resolvedReference = buildPaymentReference(item, selectedOffer, paymentReference);
  const resolvedPaidAt = normalizePaidAt(paidAt);
  const receiptReference = verification?.receipt?.reference ?? resolvedReference;
  const gatewayReference = verification?.receipt?.gateway_reference ?? verification?.gateway_reference ?? resolvedReference;
  const gatewayTransactionId = verification?.receipt?.gateway_transaction_id ?? verification?.gateway_transaction_id ?? "—";
  const orderReference = verification?.order?.reference ?? "—";
  const paymentMethod = verification?.gateway?.name ?? (total === 0 ? "Gratuit" : "Gateway");
  const receiptTrackingUrl = buildPublicUrl(`/checkout/${item.module}/${item.slug}/recu${buildPaymentQuery(
    selectedOffer,
    resolvedReference,
    resolvedPaidAt,
  )}`);
  const successHref = `/checkout/${item.module}/${item.slug}/succes${buildPaymentQuery(
    selectedOffer,
    resolvedReference,
    resolvedPaidAt,
  )}`;
  const resolvedConfirmed = isConfirmed ?? verification?.is_successful ?? false;

  return (
    <>
      <section className="page-hero page-hero--compact">
        <div className="shell page-hero__content">
          <p className="eyebrow">Recu</p>
          <h1>{resolvedConfirmed ? "Recu de paiement" : "Recu indisponible"}</h1>
          <p>
            {resolvedConfirmed
              ? "Un recapitulatif clair, imprimable et partageable pour la transaction."
              : "Le paiement n'est pas encore confirmé, le reçu officiel n'est donc pas disponible pour le moment."}
          </p>
        </div>
      </section>

      <section className="section">
        <div className="shell receipt-layout">
          <article className="receipt-card">
            <header className="receipt-card__header">
              <div>
                <p className="eyebrow">Reçu de paiement</p>
                <h2>{platform.brandName}</h2>
                <p className="section-copy">N° référence {receiptReference}</p>
              </div>
              <span className="receipt-card__status">{resolvedConfirmed ? "Paye" : "En attente"}</span>
            </header>

            <div className="receipt-transaction">
              <div>
                <span>Montant</span>
                <strong>{total === 0 ? "Gratuit" : formatMoney(total, currency)}</strong>
              </div>
              <div>
                <span>ID transaction</span>
                <strong>{gatewayReference}</strong>
              </div>
              <div>
                <span>ID gateway</span>
                <strong>{gatewayTransactionId}</strong>
              </div>
              <div>
                <span>Moyen de paiement</span>
                <strong>{paymentMethod}</strong>
              </div>
              <div>
                <span>Statut</span>
                <strong>{resolvedConfirmed ? "Payé" : "En attente"}</strong>
              </div>
              <div>
                <span>Commande</span>
                <strong>{orderReference}</strong>
              </div>
              <div>
                <span>Généré le</span>
                <strong>{formatDateLabel(resolvedPaidAt)}</strong>
              </div>
            </div>

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
                <strong>{subtotal === 0 ? "Gratuit" : formatMoney(subtotal, currency)}</strong>
              </div>
              <div className="receipt-line receipt-line--muted">
                <span>Quantite</span>
                <strong>{verification?.quantity ?? 1}</strong>
              </div>
              <div className="receipt-line receipt-line--muted">
                <span>Frais de service</span>
                <strong>{serviceFee === 0 ? "—" : formatMoney(serviceFee, currency)}</strong>
              </div>
            </div>

            <div className="receipt-card__total">
              <span>{resolvedConfirmed ? "Total regle" : "Total a verifier"}</span>
              <strong>{total === 0 ? "Gratuit" : formatMoney(total, currency)}</strong>
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
              <div className="receipt-qr">
                <QrCode value={receiptTrackingUrl} />
                <span>Scanner pour suivre l'état de votre réservation</span>
              </div>
              {resolvedConfirmed ? <PrintButton className="button button--full">Imprimer le recu</PrintButton> : null}
              <Link className="button button--ghost button--full" href={successHref}>
                Retour au succes
              </Link>
              <Link className="button button--ghost button--full" href={`/${item.module}/${item.slug}?tenant=${encodeURIComponent(item.organizerSlug)}`}>
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
