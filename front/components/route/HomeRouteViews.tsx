import Link from "next/link";

import { ContentCard } from "@/components/ContentCard";
import { getLikeRenderingContext } from "@/components/route/content-engagement";
import { SectionHeader } from "@/components/route/SectionHeader";
import type {
  FrontPageData,
  OrganizerProfile,
  PlatformConfiguration,
  PublicContent,
} from "@/lib/types";

import { HeroSearch } from "./home/HeroSearch";
import { getFrontSection, sectionStats, sectionText } from "./home/helpers";
import { StatIcon } from "./home/StatIcon";

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

